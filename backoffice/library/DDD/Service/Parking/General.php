<?php

namespace DDD\Service\Parking;

use DDD\Service\ServiceBase;
use Library\Upload\Files;
use FileManager\Constant\DirectoryStructure;

class General extends ServiceBase
{
    /**
     * Returns true if a different parking lot with same name exists
     *
     * @param string $name
     * @param int $id
     * @return boolean
     */
    public function checkParkingLotExistence($name, $id)
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
        $check = $parkingGeneralDao->checkParkingLotExistence($name, $id);

        return $check;
    }

    /**
     * @param array $data
     * @param int $id
     * @return int
     */
    public function saveParkingLot($data, $id)
    {
        /* @var $parkingGeneralDao \DDD\Dao\Parking\General */
        $parkingGeneralDao      = $this->getServiceLocator()->get('dao_parking_general');
        $oldData                = $parkingGeneralDao->getParkingById($id);

        $saveData = [
            'name'           => $data['name'],
            'is_virtual'     => (int)$data['is_virtual'],
            'lock_id'        => (int)$data['lock_id'],
        ];

        if (isset($data['country_id'])) {
            $saveData['country_id'] = (int)$data['country_id'];
        }

        if (isset($data['province_id'])) {
            $saveData['province_id'] = (int)$data['province_id'];
        }

        if (isset($data['city_id'])) {
            $saveData['city_id'] = (int)$data['city_id'];
        }

        if (isset($data['address'])) {
            $saveData['address'] = $data['address'];
        }

        if (isset($data['direction_textline_id'])) {
            $saveData['direction_textline_id'] =  (int)$data['direction_textline_id'];
        }

        if (!empty($data['parking_permit'])) {
            $saveData['parking_permit'] = $data['parking_permit'];
        }

        $where = $id ? ['id' => $id] : false;

        $response = $parkingGeneralDao->save($saveData, $where);

        if (!$id) {
            $id = $response;
        }

        if (!empty($data['parking_permit'])) {$uploadFolder = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . DirectoryStructure::FS_IMAGES_PARKING_ATTACHMENTS
            . $id . '/';

            $destination = $uploadFolder . $data['parking_permit'];

            Files::moveFile(DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_TEMP_PATH
                . $data['parking_permit'], $destination);

            if ($oldData && $oldData->getParkingPermit()) {
                @unlink($uploadFolder . $oldData->getParkingPermit());
            }
        }

        return $id;
    }

    /**
     * @param int $parkingLotId
     * @return \DDD\Domain\Parking\General
     */
    public function getParkingById($parkingLotId)
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
        return $parkingGeneralDao->getParkingById($parkingLotId);
    }

    /**
     * @param int $parkingLotId
     * @param int $status
     * @return int
     */
    public function changeStatus($parkingLotId, $status)
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
        return $parkingGeneralDao->save(['active' => $status], ['id' => $parkingLotId]);
    }

    /**
     * @param int $apartmentId
     * @return array
     */
    public function getParkingLotsForSelect($apartmentId)
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
        $result = $parkingGeneralDao->getParkingLotsForSelect($apartmentId);
        $return = [];

        if ($result) {
            foreach ($result as $row) {
                $return[$row['id']] = $row['name'];
            }
        }

        return $return;
    }

    /**
     * @param int $parkingLotId
     * @return \ArrayObject
     */
    public function getUsages($parkingLotId)
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
        return $parkingGeneralDao->getUsages($parkingLotId);
    }

    /**
     * @param int $parkingLotId
     * @return bool
     */
    public function removeParkingLotUsages($parkingLotId)
    {

        /**
         * @var \DDD\Dao\Apartment\Spots $apartmentsSpotDao
         */
        $apartmentsSpotDao = $this->getServiceLocator()->get('dao_apartment_spots');
        $apartmentsSpotDao->deleteApartmentSpotsByLot($parkingLotId);

        return true;
    }

    /**
     * @param $lockId
     * @return mixed
     */
    public function getAllParkingsWithLock($lockId)
    {
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
        return $parkingGeneralDao->getAllParkingsWithLock($lockId);
    }
}
