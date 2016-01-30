<?php

namespace DDD\Service\Parking;

use DDD\Service\ServiceBase;
use DDD\Service\Parking\Spot\Inventory;

class Spot extends ServiceBase
{
    /**
     * @param int $lotId
     * @return \DDD\Domain\Parking\Spot[]
     */
    public function getParkingSpots($lotId)
    {
        /**
         * @var \DDD\Dao\Parking\Spot $parkingSpotsDao
         */
        $parkingSpotsDao = $this->getServiceLocator()->get('dao_parking_spot');
        return $parkingSpotsDao->fetchAll(['lot_id' => $lotId]);
    }

    /**
     * @param array $data
     * @param int $spotId
     * @return int
     */
    public function saveSpot($data, $spotId)
    {
        /**
         * @var \DDD\Dao\Parking\Spot $parkingSpotsDao
         * @var \DDD\Service\Parking\Spot\Inventory $inventoryService
         */
        $parkingSpotsDao  = $this->getServiceLocator()->get('dao_parking_spot');
        $inventoryService = $this->getServiceLocator()->get('service_parking_spot_inventory');
        $saveData = [];
        if (isset($data['unit'])) {
            $saveData['unit'] = $data['unit'];
        }
        if (isset($data['price'])) {
            $saveData['price'] = (double)$data['price'];
        }
        if (isset($data['lot_id'])) {
            $saveData['lot_id'] = (int)$data['lot_id'];
        }
        if (isset($data['permit_id'])) {
            $saveData['permit_id'] = $data['permit_id'];
        }
        $where = ($spotId ? ['id' => $spotId] : false);

        $response = $parkingSpotsDao->save($saveData, $where);

        if (!$spotId) {
            $spotId = $response;

            $dateFrom = date('Y-m-d');
            $dateTo = date('Y-m-d', strtotime($dateFrom . " +12 months"));
            $dateTo = date('Y-m-d', strtotime($dateTo . " +" . Inventory::FILL_MARGIN . " days"));

            $inventoryService->fillInventory($dateFrom, $dateTo, $spotId);
        }

        return $spotId;
    }

    /**
     * @param int $spotId
     * @return int
     */
    public function deleteSpot($spotId)
    {
        /**
         * @var \DDD\Dao\Parking\Spot $parkingSpotsDao
         */
        $parkingSpotsDao = $this->getServiceLocator()->get('dao_parking_spot');

        return $parkingSpotsDao->delete(['id' => $spotId]);
    }

    /**
     * @param int $spotId
     * @return \ArrayObject
     */
    public function getUsages($spotId)
    {
        /**
         * @var \DDD\Dao\Parking\Spot $parkingSpotsDao
         */
        $parkingSpotsDao = $this->getServiceLocator()->get('dao_parking_spot');
        return $parkingSpotsDao->getUsages($spotId);
    }

    /**
     * @param array $params
     * @return boolean
     */
    public function isUnitUniqueInLot($params)
    {
        $parkingSpotsDao = $this->getServiceLocator()->get('dao_parking_spot');
        $result = $parkingSpotsDao->isUnitUniqueInLot($params);
        if ($result === false) {
            return true;
        }
        return false;
    }

    public function getSpotsByBuilding($buildingId)
    {
        $parkingSpotsDao = $this->getServiceLocator()->get('dao_parking_spot');
        return $parkingSpotsDao->getSpotsByBuilding($buildingId);
    }
}
