<?php

namespace DDD\Service\Apartel;

use DDD\Service\Apartment\Rate;
use DDD\Service\ServiceBase;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Validator\ClassicValidator;
use Zend\Filter\Null;

class Inventory extends ServiceBase
{
    const PRICE_CHANGE_LIMIT     = 70;
    const AVAILABILITY_LIMIT     = 4;
    const MIN_AVAILABILITY_LIMIT = 65;
    const MAX_AVAILABILITY_LIMIT = 115;

    /**
     * @param bool $yearly
     * @param null $apartelId
     * @return bool
     */
    public function updateAvailability($yearly = false, $apartelId = null)
    {
        try {
            /**
             * @var \DDD\Dao\Apartel\Rate $apartelRateDao
             */
            $apartelRateDao = $this->getServiceLocator()->get('dao_apartel_rate');
            $rateDomainList = $apartelRateDao->getAllRatesByApartelId($apartelId);

            if ($rateDomainList->count()) {
                $firstDay = date('Y-m-d', strtotime('first day of this month'));
                $nextYear = date('Y-m-d', strtotime($firstDay . " +12 months"));

                if ($yearly) {
                    $dateFrom = $firstDay;
                    $dateTo = date('Y-m-d', strtotime($firstDay . " +13 months"));
                } else {
                    $dateFrom = date("Y-m-d", strtotime("-1 month", strtotime(date($nextYear))));
                    $dateTo = date("Y-m-d", strtotime("+1 month", strtotime(date($nextYear))));
                }

                foreach ($rateDomainList as $rateDomain) {
                    $this->insertBundle($rateDomain, $dateFrom, $dateTo, $apartelId);

                    // Delete past availabilities monthly, only when script called by cron
                    if (!$yearly) {
                        $this->deletePastAvailabilities($rateDomain->getId());
                    }
                }
            } else {
                throw new \Exception('No rates found to update.');
            }
        } catch (\Exception $ex) {
            if (!$yearly) {
                $this->gr2logException($ex, 'Monthly availability update for Apartel is failed');
            }

            return false;
        }

        return true;
    }

    /**
     * @param \DDD\Domain\Apartel\Rate\Rate $rateDomain
     * @param $dateFrom
     * @param $dateTo
     * @param $isFromRateManagement
     */
    private function insertBundle($rateDomain, $dateFrom, $dateTo, $isFromRateManagement)
    {
        /**
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         * @var \DDD\Dao\Apartel\RelTypeApartment $typeRelApartmentDao
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
         * @var \DDD\Dao\Apartel\Rate $rateDao
         * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
         */
        $inventoryDao        = $this->getServiceLocator()->get('dao_apartel_inventory');
        $typeRelApartmentDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $syncService         = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $rateDao             = $this->getServiceLocator()->get('dao_apartel_rate');
        $apartelDetailsDao   = $this->getServiceLocator()->get('dao_apartel_details');

//aaa
        $dateSeeker = new \DateTime($dateFrom);
        $roomTypeId = $rateDomain->getApartelTypeId();
        $rateId     = $rateDomain->getId();
        $apartelId  = $rateDomain->getApartelId();
        $isParent   = $rateDomain->getType() == Rate::TYPE1 ? true : false;

        $apartelInfo = $apartelDetailsDao->fetchOne(['apartel_id' => $apartelId], ['default_availability']);

        // for new rate create
        $availabilities = [];
        if (!$isParent && $isFromRateManagement) {
            $availabilityList = $rateDao->getAllAvailabilityByTypeId($rateDomain->getApartelTypeId());

            foreach ($availabilityList as $row) {
                $availabilities[$row['date']] = $row['availability'];
            }
        }

        // max availability
        $availability = $typeRelApartmentDao->getAvailabilityForApartelType($roomTypeId);

        // get inventory data
        $inventoryData      = $inventoryDao->fetchAll(['rate_id' => $rateId]);
        $inventoryRateDates = [];
        foreach ($inventoryData as $row) {
            $inventoryRateDates[] = $row->getDate();
        }

        while ($dateSeeker->format('Y-m-d') < $dateTo) {
            $date        = $dateSeeker->format('Y-m-d');
            $weekDayName = $dateSeeker->format('D');
            $dateSeeker->modify('+1 day');

            // if exist record with the rate and date break
            if (in_array($date, $inventoryRateDates)) {
                continue;
            }

            // for new rate create
            if (!$isParent && $isFromRateManagement && array_key_exists($date, $availabilities)) {
                $availability = $availabilities[$date];
            }

            if ($apartelInfo && $apartelInfo->getDefaultAvailability()) {
                if ($availability > self::AVAILABILITY_LIMIT) {
                    $availability = rand(self::MIN_AVAILABILITY_LIMIT, self::MAX_AVAILABILITY_LIMIT);
                }
            }

            $inventoryDao->save([
                'apartel_id'      => $apartelId,
                'apartel_type_id' => $roomTypeId,
                'rate_id'         => $rateId,
                'date'            => $date,
                'availability'    => $availability,
                'price'           => $this->getAppropriatePrice($rateDomain, $weekDayName),
            ]);

            // send to queue
            if (!$isFromRateManagement && !is_null($rateDomain->getCubilisId())) {
                $syncService->push($roomTypeId, $date, $date, [], $syncService::ENTITY_TYPE_APARTEL);
            }
        }

        // update all availability if add new standard rate
        if ($isParent && $isFromRateManagement) {
            $inventoryDao->setApartelAvailabilityByRoomType($roomTypeId, false, false);
        }
    }

    /**
     * @param int $rateId
     * @return void
     */
    private function deletePastAvailabilities($rateId)
    {
        /**
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         */
        $dateTo = date('Y-m-d', strtotime('first day of this month -1 month'));
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');
        $inventoryDao->deleteAvailabilities($rateId, $dateTo);
    }

    /**
     * @param $rateDomain
     * @param $weekDayName
     * @return mixed
     */
    private function getAppropriatePrice($rateDomain, $weekDayName)
    {
        if (in_array($weekDayName, ['Fri', 'Sat'])) {
            return $rateDomain->getWeekendPrice();
        }

        return $rateDomain->getWeekPrice();
    }


    /**
     * @param $roomTypeId
     * @param $dateRange
     * @param $weekDays
     * @param $price
     * @param $priceType
     * @param $setLockPrice
     * @param int $forceLockPrice
     * @param int $forceUpdatePrice
     * @return $this|bool|Inventory
     */
    public function updateInventoryRangeByPrice($roomTypeId, $dateRange, $weekDays, $price, $priceType, $setLockPrice, $forceLockPrice = 0, $forceUpdatePrice = 0)
    {
        /**
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

        // Define Variables
        $dateRange = Helper::refactorDateRange($dateRange);
        $weekDays = Helper::reformatWeekdays($weekDays);

        // check price changes
        if (!$forceUpdatePrice) {
            $priceAVGOld = $inventoryDao->getPriceAVGRange($roomTypeId, $dateRange['date_from'], $dateRange['date_to'], $weekDays);
            $priceAVGNew = $inventoryDao->getPriceAVGRangeByPriceType($roomTypeId, $dateRange['date_from'], $dateRange['date_to'], $weekDays, $price, $priceType);

            if ($priceAVGNew  < $priceAVGOld - $priceAVGOld * self::PRICE_CHANGE_LIMIT/100) {
                return ['status' => 'limit_exceed', 'msg' => TextConstants::PRICE_EXCEED_LIMIT];
            }
        }

        // update price
        return $this->updatePriceByRange($roomTypeId, $price, $dateRange['date_from'], $dateRange['date_to'], $weekDays, $priceType, $setLockPrice, $forceLockPrice);
    }

    /**
     * @param $roomTypeId
     * @param $price
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $priceType
     * @param $setLockPrice
     * @param $forceLockPrice
     * @return $this|bool|Inventory
     */
    public function updatePriceByRange($roomTypeId, $price, $dateFrom, $dateTo, $weekDays, $priceType, $setLockPrice = 0, $forceLockPrice = 0)
    {
        // update price
        $this->updatePriceByRoomType($roomTypeId, $price, $dateFrom, $dateTo, $weekDays, $priceType, $setLockPrice, $forceLockPrice);

        // set queue
        return $this->setToQueue($roomTypeId, $dateFrom, $dateTo, $weekDays, null);
    }

    /**
     * @param $roomTypeId
     * @param $amount
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $priceType
     * @param int $setLockPrice
     * @param $forceLockPrice
     * @throws \Exception
     */
    public function updatePriceByRoomType($roomTypeId, $amount, $dateFrom, $dateTo, $weekDays, $priceType, $setLockPrice = 0, $forceLockPrice)
    {
        /**
         * @var \DDD\Dao\Apartel\Rate $rateDao
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

        // get parent rate
        $parentRateId = $rateDao->getRoomTypeParentRate($roomTypeId);

        // update parent rate price by range
        $inventoryDao->updateParentRatePriceByRang($amount, $priceType, $parentRateId['id'], $dateFrom, $dateTo, $weekDays, $forceLockPrice);

        // get rates without parent rate
        $rates = $rateDao->getRoomTypeRatesWithoutParent($roomTypeId);
        $rates = iterator_to_array($rates);

        // get parent inventory date by range
        $parentInventoryData = $inventoryDao->getRateInventoryData($roomTypeId, $dateFrom, $dateTo, $weekDays, $forceLockPrice);

        // for check rate date already updated or not
        $checkRateIdDateCombination = [];

        foreach ($parentInventoryData as $parentRate) {
            // get date name
            $percentField = Helper::getDateWeekType($parentRate['date']);
            $parentRatePrice = $parentRate['price'];

            // update all rate without parent
            foreach ($rates as $rate) {
                if (isset($checkRateIdDateCombination[$parentRate['date']]) && in_array($rate['id'], $checkRateIdDateCombination[$parentRate['date']])) {
                    continue;
                }

                $checkRateIdDateCombination[$parentRate['date']][] = $rate['id'];
                $childPrice = round($parentRatePrice + $parentRatePrice * $rate[$percentField] / 100, 2);
                $inventoryDao->update([
                    'price' => $childPrice
                ], [
                    'rate_id' => $rate['id'],
                    'date' => $parentRate['date'],
                ]);
            }
        }

        // set lock price bit
        $inventoryDao->updateLockPriceBit($roomTypeId, $dateFrom, $dateTo, $weekDays, $setLockPrice, $forceLockPrice);
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $isAvailability
     * @return $this|bool
     */
    public function setToQueue($roomTypeId, $dateFrom, $dateTo, $weekDays, $isAvailability)
    {
        /**
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $queueService
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         */
        $queueService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $userId = $auth->getIdentity()->id;

        // send data to Graylog
        $logArray = [
            'product_type'               => 'Apartel',
            'module'                     => 'Apartel',
            'controller'                 => 'Inventory',
            'room_type_id'               => $roomTypeId,
            'date_from'                  => $dateFrom,
            'date_to'                    => $dateTo,
            'action_mode'                => 'days range',
            'user_id'                    => $userId
        ];

        if (!is_null($isAvailability)) {
            $logArray['action_type'] = 'availability';
            $logArray['availability_update_action'] = ($isAvailability) ? 'open' : 'close';
            $this->gr2info('Availability Update', $logArray);
        } else {
            $logArray['action_type'] = 'price';
            $parentInventoryData = $inventoryDao->getRateInventoryData($roomTypeId, $dateFrom, $dateTo, $weekDays, null);
            foreach ($parentInventoryData as $parentRate) {
                $logArray['price_value'] = $parentRate['price'];
                $logArray['date_from'] = $parentRate['date'];
                $logArray['date_to'] = $parentRate['date'];
                $this->gr2info('Availability Update', $logArray);
            }
        }

        // set to queue
        $weekDayArray = [];
        if ($weekDays) {
            $weekDayArray = explode(',', $weekDays);
        }
        $queueService->push($roomTypeId, $dateFrom, $dateTo, $weekDayArray, $queueService::ENTITY_TYPE_APARTEL);

        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE];
    }
}
