<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Zend\Filter\Null;

class Type extends ServiceBase
{
    /**
     * @param $apartelId
     * @param bool $allStatus
     * @return array
     */
    public function getApartelTypesWithRates($apartelId, $allStatus = false)
    {
        /**
         * @var \DDD\Dao\Apartel\Type $typeDao
         */
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $typesRates = $typeDao->getApartelTypesWithRates($apartelId, $allStatus);

        $output = [];
        if ($typesRates) {
            foreach ($typesRates as $row) {
                $output[$row['type_id']] = [
                    'type_id' => $row['type_id'],
                    'type_name' => $row['type_name'],
                    'type_cubilis_id' => $row['type_cubilis_id'],
                    'rate_list' => (isset($output[$row['type_id']]['rate_list']) ? $output[$row['type_id']]['rate_list'] : []),
                ];

                $output[$row['type_id']]['rate_list'][$row['rate_id']] = [
                    'rate_id' => $row['rate_id'],
                    'rate_name' => $row['rate_name'],
                    'rate_cubilis_id' => $row['rate_cubilis_id'],
                    'type' => $row['type'],
                ];
            }
        }
        return $output;
    }

    /**
     * @param $params
     * @param $apartelId
     * @return bool
     */
    public function linkTypeRate($params, $apartelId)
    {

        if(empty($params)) {
            return ['status' => 'error', 'msg' => TextConstants::BAD_REQUEST];
        }

        /**
         * @var \DDD\Dao\Apartel\Type $typeDao
         * @var \DDD\Dao\Apartel\Rate $rateDao
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
         */
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

        // set null all old cubilis type id
        $typeDao->update(['cubilis_id' => Null], ['apartel_id' => $apartelId]);

        // set null all old cubilis rate id
        $rateDao->update(['cubilis_id' => Null], ['apartel_id' => $apartelId]);
        $ginosiTypes = $ginosiRate = [];
        foreach ($params['cubilis_type_id'] as $cubilisType) {
            $ginosiTypeId = isset($params['ginosi_type_id'][$cubilisType]) && $params['ginosi_type_id'][$cubilisType]
                            ? $params['ginosi_type_id'][$cubilisType] : 0;

            if ($ginosiTypeId) {
                // check duplicate type
                if (in_array($ginosiTypeId, $ginosiTypes)) {
                    return ['status' => 'error', 'msg' => TextConstants::ERROR_DUPLICATE_APARTEL_TYPE];
                }
                $ginosiTypes[] = $ginosiTypeId;

                // set cubilis type id
                $typeDao->update(['cubilis_id' => $cubilisType], ['id' => $ginosiTypeId]);

                // set cubilis rate id
                if (isset($params['ginosi_rate_id'][$cubilisType])) {
                    $ginosiRate = [];
                    foreach ($params['ginosi_rate_id'][$cubilisType] as $key => $rate) {
                        // check duplicate rate
                        if ($rate && in_array($rate, $ginosiRate)) {
                            // set null all old cubilis rate id
                            $rateDao->update(['cubilis_id' => Null], ['apartel_type_id' => $ginosiTypeId]);
                            return ['status' => 'error', 'msg' => TextConstants::ERROR_DUPLICATE_APARTEL_RATE];
                        }

                        if ($rate && isset($params['cubilis_rate_id'][$cubilisType][$key])) {
                            $ginosiRate[] = $rate;
                            $rateDao->update(['cubilis_id' => $params['cubilis_rate_id'][$cubilisType][$key]], ['id' => $rate]);
                        }
                    }
                }
            }
        }

        // repair availability and set to queue
        $roomTypeList = $typeDao->getAllSyncRoomTypes($apartelId);
        $dates = $inventoryDao->getMinMaxDate();
        $dateMin = $dates['min_date'];
        $dateMax = $dates['max_date'];
        foreach ($roomTypeList as $roomType) {
            $roomTypeId = $roomType['id'];
            $inventoryDao->setApartelAvailabilityByRoomType($roomTypeId);
            $syncService->push($roomTypeId, $dateMin, $dateMax, [], $syncService::ENTITY_TYPE_APARTEL);
        }

        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE];
    }

    /**
     * @param $apartelId
     * @param $typeId
     * @return array
     */
    public function getTypeDetails($apartelId, $typeId)
    {
        /**
         * @var \DDD\Dao\Apartel\General $generalDao
         * @var \DDD\Dao\Apartel\Type $typeDao
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroupItems $groupApartmentDao
         * @var \DDD\Dao\Apartel\RelTypeApartment $relTypeApartmentDao
         */
        $result = [
            'all_apartment_list' => [],
            'apartment_list' => [],
            'type_name' => '',
            'form_type_id' => $typeId,
        ];
        $generalDao = $this->getServiceLocator()->get('dao_apartel_general');
        $groupApartmentDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $relTypeApartmentDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');

        // get general data
        $general = $generalDao->fetchOne(['id' => $apartelId], ['apartment_group_id']);
        if (!$general) {
            return $result;
        }

        // get all apartment list for apartel
        $apartmentList = $groupApartmentDao->getSaleApartmentGroupItems($general->getApartmentGroupId());

        // get used apartment list (with one sql is a difficult)
        $usedApartments = $relTypeApartmentDao->getApartelUsedApartment($apartelId, $typeId);
        $usedApartmentList = [];
        foreach ($usedApartments as $apartment) {
            $usedApartmentList[] = $apartment['apartment_id'];
        }

        foreach ($apartmentList as $apartment) {
            if (!in_array($apartment->getApartmentId(), $usedApartmentList)) {
                $result['all_apartment_list'][$apartment->getApartmentId()]  = $apartment->getApartmentName();
            }
        }

        // if edit mode
        if ($typeId) {
            // get type data
            $typeData = $typeDao->getRoomTypeData($typeId);
            if ($typeData) {
                $result['type_name'] = $typeData['name'];
            }

            // get type apartment list
            $typeUsedApartment = $relTypeApartmentDao->getApartmentListByTypeId($typeId);
            foreach ($typeUsedApartment as $apartment) {
                $result['apartment_list'][] = $apartment['apartment_id'];
            }
        }

        return $result;
    }

    /**
     * @param $data
     * @param $apartelId
     * @return int
     */
    public function saveType($data, $apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\Type $typeDao
         * @var \DDD\Dao\Apartel\RelTypeApartment $relTypeApartmentDao
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         * @var \Library\ChannelManager\ChannelManager $channelManager
         */
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $relTypeApartmentDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $typeName = $data['type_name'];
        $typeId = $data['form_type_id'];
        $setAvailabilityQueue = false;
        $newApartmentList = $data['apartment_list'];
        // update mode
        if ($typeId) {
            // update name
            $typeDao->update(['name' => $typeName], ['id' => $typeId]);

            // get old apartmentList
            $oldApartmentIds = [];
            $oldApartmentList = $relTypeApartmentDao->getApartmentListByTypeId($typeId);
            foreach ($oldApartmentList as $oldApartment) {
                $oldApartmentIds[] = $oldApartment['apartment_id'];
            }

            sort($oldApartmentIds);
            sort($newApartmentList);
            if ($oldApartmentIds != $newApartmentList) {
                $setAvailabilityQueue = true;
            }

            // delete old apartment list
            $relTypeApartmentDao->delete(['apartel_type_id' => $typeId]);
        } else {
            // insert new type
            $typeId = $typeDao->save(['name' => $typeName, 'apartel_id' => $apartelId]);
        }

        // insert apartment list
        foreach ($newApartmentList as $apartment) {
            $relTypeApartmentDao->save(['apartel_type_id' => $typeId, 'apartment_id' => $apartment]);
        }

        // change availability when change apartment count
        if ($typeId) {
            $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

            if ($newApartmentList && count($newApartmentList)) {
                $apartmentList = implode(',', $newApartmentList);
            } else {
                $apartmentList = false;
            }

            /** @var \DDD\Dao\Apartel\Rate $ratesDao */
            $ratesDao = $this->getServiceLocator()->get('dao_apartel_rate');
            $checkHasStandardRate = $ratesDao->getRoomTypeParentRate($typeId);

            if ($checkHasStandardRate && $setAvailabilityQueue) {
                $inventoryDao->setApartelAvailabilityByApartmentList($typeId, $apartmentList);

                $isSyncWithCubilis = $typeDao->getApartelTypeSyncWithCubilis($typeId);
                if ($isSyncWithCubilis) {
                    /** @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService */
                    $dates = $inventoryDao->getMinMaxDate();
                    $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
                    $syncService->push($typeId, $dates['min_date'], $dates['max_date'], [], $syncService::ENTITY_TYPE_APARTEL);
                }
            }
        }

        return $typeId;
    }

    /**
     * @param $typeId
     * @return array
     */
    public function deleteType($typeId)
    {
        /**
         * @var \DDD\Dao\Apartel\Type $typeDao
         */
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $typeData = $typeDao->fetchOne(['id' => $typeId], ['cubilis_id']);
        if (!$typeData) {
            return ['status' => 'error', 'msg' => TextConstants::BAD_REQUEST];
        }

        // check link to cubilis
        if ($typeData->getCubilisId()) {
            return ['status' => 'error', 'msg' => TextConstants::HAS_CUBILIS_LINK];
        }

        $typeDao->delete(['id' => $typeId]);
        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_DELETE];
    }

    /**
     * @param $typeId
     * @param $dates
     * @param int $capacity
     * @param $apartmentListUsed
     * @param bool $building
     * @return array
     */
    public function getBestApartmentForType($typeId, $dates, $capacity = 1, $apartmentListUsed, $building = false)
    {
        /**
         * @var \DDD\Dao\Apartel\RelTypeApartment $relTypeApartmentDao
         * @var \DDD\Dao\Apartment\Inventory $inventoryApartmentDao
         */
        $relTypeApartmentDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $inventoryApartmentDao = $this->getServiceLocator()->get('dao_apartment_inventory');
        $apartmentList = $relTypeApartmentDao->getAvailabilityApartmentList($typeId, $dates, $capacity, $apartmentListUsed, $building);

        // not available apartment
        if (!$apartmentList->count()) {
            $firsApartment = $relTypeApartmentDao->getApartmentByTypeId($typeId);
            return ['status' => 'not-available', 'apartment_id' => $firsApartment['apartment_id']];
        }

        $apartmentListCorrect = $notAvailableApartments = [];
        foreach ($apartmentList as $apartment) {
            if (!$apartment['availability']) {
                $notAvailableApartments[] = $apartment['apartment_id'];
            }
            $apartmentListCorrect[] = $apartment;
        }

        foreach ($apartmentListCorrect as $key => $apartment) {
            if (in_array($apartment['apartment_id'], $notAvailableApartments)) {
                unset($apartmentListCorrect[$key]);
            }
        }

        if (empty($apartmentListCorrect)) {
            $firsApartment = $relTypeApartmentDao->getApartmentByTypeId($typeId);
            return ['status' => 'not-available', 'apartment_id' => $firsApartment['apartment_id']];
        }

        // get apartment list width on day before and one day after (left right) available day
        $apartments = $beforeNotAvailable = $afterNotAvailable = [];
        foreach ($apartmentListCorrect as $apartment) {
            $apartmentBefore = $inventoryApartmentDao->getAvailabilityByRateDate($apartment['rate_id'],
                date('Y-m-d', strtotime('-1 day', strtotime($dates['date_from']))));
            $apartmentAfter = $inventoryApartmentDao->getAvailabilityByRateDate($apartment['rate_id'], $dates['date_to']);
            $apartments[$apartment['apartment_id']] = [
                'before' => $apartmentBefore['availability'],
                'after' => $apartmentAfter['availability']
            ];
        }

        // get best apartment
        foreach ($apartments as $id => $apartment) {
            // 1. if before and after not available
            if(!$apartment['before'] && !$apartment['after']) {
                return ['status' => 'available', 'apartment_id' => $id];
            }

            // not available before
            if (!$apartment['before']) {
                $beforeNotAvailable[] = $id;
            }

            // not available after
            if (!$apartment['after']) {
                $afterNotAvailable[] = $id;
            }
        }

        // 2. if before not available
        if (!empty($beforeNotAvailable)) {
            return ['status' => 'available', 'apartment_id' => current($beforeNotAvailable)];
        }

        // 3. if after not available
        if (!empty($afterNotAvailable)) {
            return ['status' => 'success', 'apartment_id' => current($afterNotAvailable)];
        }

        // 4. just available apartment
        return ['status' => 'available', 'apartment_id' => current(array_keys($apartments))];
    }



}
