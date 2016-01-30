<?php

namespace DDD\Service\Queue;

use DDD\Service\ServiceBase;
use Library\Constants\DbTables;
use Library\Utility\Helper;
use Zend\Db\Sql\Where;

/**
 * Class InventorySynchronizationQueue
 * @package DDD\Service\Queue
 */
class InventorySynchronizationQueue extends ServiceBase
{
    const MAXIMUM_ATTEMPTS = 10;
    const LOG_ATTEMPTS = 6;
    const TIMEOUT_MAX_ATTEMPTS = 4;
    const MAX_PROCESSING_COUNT = 50;
    const MULTI_INSERT_MAX_COUNT = 500;
    const MAX_DAY = 9;

    const CUBILIS_MAX_ATTEMPTS_FAILED = 'Max attempts have been made for inventory sync with Cubilis';

    const ENTITY_TYPE_APARTMENT = 1;
    const ENTITY_TYPE_APARTEL   = 2;

    /**
     * @return array
     */
    public static function attemptTimeMap ()
    {
        return [
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 5,
                4 => 10,
                5 => 30,
                6 => 60,
                7 => 300,
                8 => 720,
                9 => 1440,
                10 => 1440,
            ];
    }
    /**
     * @return int
     */
    public function getLowAttemptEntity()
    {
        /**
         * @var \DDD\Dao\Queue\InventorySynchronizationQueue $dao
         */
        $dao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');

        $lowEntity = $dao->getLowAttemptEntity();

        return $lowEntity;
    }

    /**
     * @param $entityId
     * @param $entityType
     * @param $attempts
     * @param $startDate
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetch($entityId, $entityType, $attempts, $startDate)
    {
        /**
         * @var \DDD\Dao\Queue\InventorySynchronizationQueue $dao
         */
        $dao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');

        $endDate = date('Y-m-d', strtotime($startDate . ' +' . self::MAX_DAY . ' days'));

        $collection = $dao->getQueueItemsByEntityId($entityId, $entityType, $attempts, $startDate, $endDate);

        return $collection;
    }

    /**
     * @param $ids
     * @param $limitAttempt
     * @return bool
     */
    public function incrementAttempts($ids, $limitAttempt = false)
    {
        /**
         * @var \DDD\Dao\Queue\InventorySynchronizationQueue $syncDao
         */
        $syncDao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');

        $syncDao->incrementAttempts($ids, $limitAttempt);
        return true;
    }

    /**
     * @param $ids
     * @return bool
     */
    public function bulkDelete($ids)
    {
        /**
         * @var \DDD\Dao\Queue\InventorySynchronizationQueue $syncDao
         */
        $syncDao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');
        $syncDao->bulkDelete($ids);
        return true;
    }

    /**
     * @param int $apartmentId
     * @return int
     */
    public function setMaxAttempts($apartmentId)
    {
        /**
         * @var \DDD\Dao\Queue\InventorySynchronizationQueue $syncDao
         */
        $syncDao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');

        $result = $syncDao->update(
            [
                'attempts' => self::MAXIMUM_ATTEMPTS
            ],
            [
                'entity_id' => $apartmentId
            ]
        );

        return $result;
    }

    /**
     *
     * @return int Count of deleted rows or zero if there is nothing to remove
     */
    public function checkAndCleanPointlessRecords()
    {
        /* @var $syncDao \DDD\Dao\Queue\InventorySynchronizationQueue */
        $syncDao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');

        $pointlessRowsCount = $syncDao->removeDateExpiredRows();
        $pointlessRowsCount += $syncDao->removeDisconnectedEntity();

        return $pointlessRowsCount;
    }

    /**
     * @param $entityId
     * @param $startDate
     * @param $endDate
     * @param array $weekdays
     * @return bool
     */
    public function push($entityId, $startDate, $endDate, $weekdays = [], $entityType = self::ENTITY_TYPE_APARTMENT)
    {
        try {
            /**
             * @var \DDD\Dao\Queue\InventorySynchronizationQueue $dao
             */
            $dao = $this->getServiceLocator()->get('dao_queue_inventory_synchronization_queue');

            $dates = $this->constructDateCollectionFromRange($startDate, $endDate);
            $queueItem = [];
            foreach ($dates as $date) {
                if (is_array($weekdays) && !empty($weekdays) && !in_array(Helper::siftWeekDay($date->format('w')), $weekdays)) {
                    continue;
                }

                $formattedDate = $date->format('Y-m-d');
                if ($formattedDate < date('Y-m-d')) {
                    continue;
                }

                $queueItem[] = [
                    'addition_date' => date('Y-m-d H:i:s'),
                    'entity_id' => $entityId,
                    'date' => $formattedDate,
                    'entity_type' => $entityType,
                ];
            }

            /*
             * Push data into queue table with multi-insert queries of
             * max. self::MULTI_INSERT_MAX_COUNT records per query.
             */
            $chunkedData = array_chunk($queueItem, self::MULTI_INSERT_MAX_COUNT);

            foreach ($chunkedData as $chunk) {
                $dao->multiInsert($chunk, true);
            }

            return true;
        } catch (\Exception $e) {

            return false;
        }

    }

    /**
     * Construct date collection from date range
     *
     * @param $startDate
     * @param $endDate
     * @return DatePeriod
     */
    private function constructDateCollectionFromRange($startDate, $endDate)
    {
        $begin = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end = $end->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $dateCollection = new \DatePeriod($begin, $interval, $end);

        return $dateCollection;
    }
}