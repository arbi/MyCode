<?php
namespace Console\Controller;

use DDD\Service\Queue\InventorySynchronizationQueue;
use Library\ChannelManager\CivilResponder;
use Library\ChannelManager\Provider\Cubilis\Cubilis;
use Library\Constants\EmailAliases;
use Library\Constants\TextConstants;
use Library\Controller\ConsoleBase;

/**
 * Class InventorySynchronizationController
 * @package Console\Controller
 *
 * @author Tigran Petrosyan
 */
class InventorySynchronizationController extends ConsoleBase
{
    private $queueStart = false;
    private $queueRestart = false;

    const INVENTORY_QUEUE_EXECUTION_COMMAND = 'ginosole inventory-synchronization execute-inventory';

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');

        if ($this->getRequest()->getParam('start')) {
            $this->queueStart = true;
        }

        if ($this->getRequest()->getParam('restart')) {
            $this->queueRestart = true;
        }

        switch ($action) {
            case 'execute-inventory':
                $this->executeApartmentInventoryQueue();
                break;
            default :
                echo '- type true parameter ( inventory-synchronization execute-inventory )' . $action . PHP_EOL;
                return false;
        }
    }

    public function executeApartmentInventoryQueue()
    {

        $this->outputMessage('Synchronization start.');

        /**
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncQueueService
         * @var $syncQueueService \DDD\Service\Queue\InventorySynchronizationQueue
         * @var \Library\ChannelManager\ChannelManager $channelManagerService
         */

        // start|restart param required
        if (!$this->queueStart && !$this->queueRestart) {
            $this->outputMessage('type correct command please.');

            return FALSE;
        }

        if ($this->checkProcessExists(self::INVENTORY_QUEUE_EXECUTION_COMMAND)) {
            $this->outputMessage('Same process exist. This process is stopped!');

            return FALSE;
        }

        /*
         * Initially we don't want a delay between two consecutive runs.
         * And we ant to restart process once it's over.
         */
        $seconds = 0;
        $message = $status = '';
        $restartProcess = true;

        try {
            $syncQueueService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');

            // Cleanup Pointless Records
            $this->outputMessage('Check and clean pointless records...');
            $countOfPointlessRecords = $syncQueueService->checkAndCleanPointlessRecords();
            $this->outputMessage('Removed ' . $countOfPointlessRecords . ' pointless records');

            $this->outputMessage('Launch queue...');

            $entity = $syncQueueService->getLowAttemptEntity();
            if ($entity) {
                $entityId = $entity['entity_id'];
                $entityType = $entity['entity_type'];
                $attempts = $entity['attempts'];
                $minuteForSend = $entity['minute_for_send'];
                $date = $entity['date'];
                if (!$attempts || $syncQueueService::attemptTimeMap()[$attempts] <= $minuteForSend) {

                    $queueItems = $syncQueueService->fetch($entityId, $entityType, $attempts, $date);
                    $processedIds = $itemForRemove = [];
                    if ($queueItems->count()) {
                        $ratesCollection = [];

                        foreach ($queueItems as $item) {
                            $ratesCollection[] = [
                                'rate_id' => $item->getCubilisRateId(),
                                'room_id' => $item->getCubilisRoomId(),
                                'avail' => $item->getAvailability(),
                                'price' => $item->getPrice(),
                                'date' => $item->getDate(),
                            ];

                            array_push($processedIds, $item->getId());

                            // get max attempt items
                            if ($attempts >= $syncQueueService::MAXIMUM_ATTEMPTS) {
                                array_push($itemForRemove, $item->getId());
                            }
                        }

                        // check entity type
                        $channelManagerService = $this->getServiceLocator()->get('channel_manager');
                        if ($entityType == $syncQueueService::ENTITY_TYPE_APARTEL) {
                            $channelManagerService->setProductType($channelManagerService::PRODUCT_APARTEL);

                            /** @var \DDD\Dao\Apartel\Type $apartelTypeDao */
                            $apartelTypeDao = $this->getServiceLocator()->get('dao_apartel_type');
                            $apartel = $apartelTypeDao->getApartel($entityId);
                            $syncProductId = $apartel['apartel_id'];
                        } else {
                            $syncProductId = $entityId;
                        }

                        // sync to cubilis
                        $response = $channelManagerService->syncCollection($syncProductId, $ratesCollection);
                        unset($ratesCollection);

                        // handle response
                        if ($response->getStatus() == CivilResponder::STATUS_SUCCESS) {
                            $message = TextConstants::SUCCESS_CUBILIS_UPDATE;
                            $status = CivilResponder::STATUS_SUCCESS;
                            // Sync was successful. Time to clear those records from queue
                            $syncQueueService->bulkDelete($processedIds);
                        } else {

                            // remove when attempt reached max limit
                            if (!empty($itemForRemove)) {
                                $syncQueueService->bulkDelete($itemForRemove);
                            }

                            if ($attempts < $syncQueueService::MAXIMUM_ATTEMPTS) {
                                // response handling
                                if (strpos($response->getMessage(), 'timed out') !== false) {
                                    //cURL operation timed out. Do nothing. We don't want to increase attempts in this case.
                                    $syncQueueService->incrementAttempts($processedIds, $syncQueueService::TIMEOUT_MAX_ATTEMPTS);
                                    $message = $response->getMessage();
                                } else if ($response->getCode() == Cubilis::NO_CONNECTION_CODE) {
                                    // Response status was different than 200. No need to increase attempts count
                                    $syncQueueService->incrementAttempts($processedIds, $syncQueueService::TIMEOUT_MAX_ATTEMPTS);
                                    $message = 'Unable to connect to cubilis.';
                                    $seconds = 30;
                                } else if (strpos($response->getMessage(), 'Authentication error') !== false) {
                                    /*
                                     * We've got authentication error.
                                     * Additional execution of queue items related to this apartment
                                     * makes no sense. Hence setting max attempt.
                                     */
                                    $syncQueueService->setMaxAttempts($entityId);
                                    $attempts = $syncQueueService::MAXIMUM_ATTEMPTS;
                                } else {
                                    /*
                                     * Not a specific error we want to handle.
                                     * Just increasing attempts count to give it another chance
                                     */
                                    $syncQueueService->incrementAttempts($processedIds);
                                    $message = $response->getMessage();
                                }
                            }

                            $status = CivilResponder::STATUS_ERROR;

                            // stop auto restart
                            $restartProcess = false;

                            // get for log items
                            if ($attempts >= $syncQueueService::LOG_ATTEMPTS) {
                                $this->gr2warn('Synchronization queue cannot send items', [
                                    'entity_id' => $entityId,
                                    'entity_type' => $entityType == $syncQueueService::ENTITY_TYPE_APARTEL ? 'Apartel' : 'Apartment',
                                    'attempt' => $attempts,
                                    'response_code' => $response->getCode(),
                                    'response_status' => $response->getStatus(),
                                    'provider' => $response->getProvider(),
                                    'full_message' => $response->getMessage()
                                ]);
                            }
                        }
                    } else {
                        $message = 'Synchronization queue is empty.';
                        $status = CivilResponder::STATUS_WARNING;
                        $restartProcess = false;
                    }
                } else {
                    $message = 'Synchronization queue is empty or now has not available record for send.';
                    $status = CivilResponder::STATUS_WARNING;
                    $restartProcess = false;
                }
            } else {
                $message = 'Synchronization queue is empty.';
                $status = CivilResponder::STATUS_WARNING;
                $restartProcess = false;
            }
            $this->markupMessage($message, $status);
			$this->outputMessage($message);

            // Launch script again
            if ($restartProcess) {
                $this->outputMessage('Restart queue after ' . $seconds . ' seconds...');

                sleep($seconds);
                $this->restartQueue();
            }

            $this->outputMessage('Process done successfully');

            return true;
		} catch (\Exception $e) {
            $this->gr2logException($e, 'Inventory Synchronization Queue Failed!');

            $message = $e->getMessage();
            $this->markupMessage($message, CivilResponder::STATUS_ERROR);
            echo $message;

            return false;
        }
    }

    private function markupMessage(&$msg, $type)
    {
        switch ($type) {
            case CivilResponder::STATUS_SUCCESS:
                $msg = "\033[1;32m" . $msg . "\033[0m";
                break;
            case CivilResponder::STATUS_ERROR:
                $msg = "\033[1;31m" . $msg . "\033[0m";
                break;
            case CivilResponder::STATUS_WARNING:
                $msg = "\033[1;33m" . $msg . "\033[0m";
                break;
        }
    }

    private function checkProcessExists($processName)
    {
        $command = "ps ax | grep '{$processName}'";

        $result = explode("\n", shell_exec($command));

        // check Start command count
        $startCommand = $processName.' start';
        $startCount = 0;

        foreach ($result as $key => $row) {
            if (empty($row)) {
                unset($result[$key]);
                continue;
            }

            if (strstr($row, $startCommand)) {
                $startCount++;

                if ($this->queueRestart) {
                    unset($result[$key]);
                }
            }

            if ($startCount > 2) {
                return TRUE;
            }
        }

        if (count($result) < 5) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    private function restartQueue()
    {
        shell_exec(self::INVENTORY_QUEUE_EXECUTION_COMMAND.' --restart > /dev/null & ');
        exit;
    }
}
