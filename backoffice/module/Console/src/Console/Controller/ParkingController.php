<?php
namespace Console\Controller;

use Library\Controller\ConsoleBase;
use \DDD\Service\Parking\Spot\Inventory;

/**
 * Class ParkingController
 * @package Console\Controller
 */
class ParkingController extends ConsoleBase
{
    private $parkingLotId = false;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', false);

        if ($this->getRequest()->getParam('id')) {
            $this->parkingLotId = $this->getRequest()->getParam('id');
        }

        switch ($action) {
            case 'extend-inventory': $this->extendInventoryAction();
                break;
            default :
                echo '- type  parameter (parking extend-inventory)'.PHP_EOL;
                return false;
        }
    }

    public function extendInventoryAction()
    {

        try {
            /**
             * @var \DDD\Service\Parking\Spot\Inventory $inventoryService
             */
            $inventoryService = $this->getServiceLocator()->get('service_parking_spot_inventory');

            $this->outputMessage('Please wait until availability update will finish.');

            $spots = $inventoryService->getEndDates();

            if ($spots) {
                foreach ($spots as $spot) {
                    $dateStart = $spot->getDate();
                    $dateStart = date('Y-m-d', strtotime($dateStart . " +1 days"));
                    $dateEnd = date('Y-m-d', strtotime("today +12 months"));
                    $dateEnd = date('Y-m-d', strtotime($dateEnd . " +" . (2 * Inventory::FILL_MARGIN) . " days"));

                    $inventoryService->fillInventory($dateStart, $dateEnd, $spot->getSpotId());

                    $this->outputMessage('Availability updated successfully.');
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Parking Availability Update Failed');

            $this->outputMessage($e->getMessage());

            return false;
        }
    }
}
