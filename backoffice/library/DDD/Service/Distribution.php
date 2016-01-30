<?php

namespace DDD\Service;

use DDD\Dao\Apartment\Main;
use DDD\Dao\ApartmentGroup\ApartmentGroup;

class Distribution extends ServiceBase
{
    const TYPE_APARTMENT = 1;
    const TYPE_APARTEL = 2;
    const TYPE_APARTEL_FISCAL = 3;

    public static $types = [
        self::TYPE_APARTMENT => 'Apartment',
        self::TYPE_APARTEL => 'Apartel',
        self::TYPE_APARTEL_FISCAL => 'Fiscal',
    ];

    /**
     * @param string $query
     * @return array
     */
    public function getApartmentsAndApartels($query)
    {
        /**
         * @var Main $apartmentDao
         * @var ApartmentGroup $apartelDao
         * @var \DDD\Dao\Apartel\Fiscal $fiscalDao
         */
        $apartmentDao = $this->getServiceLocator()->get('dao_apartment_main');
        $apartelDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $fiscalDao = $this->getServiceLocator()->get('dao_apartel_fiscal');
        $distributionList = [];

        $apartments = $apartmentDao->getSellingApartments($query);
        $apartels = $apartelDao->getApartelListByQ($query);
        $fiscals = $fiscalDao->getFiscalListByQ($query);

        if ($apartments->count()) {
            foreach ($apartments as $apartment) {
                array_push($distributionList, [
                    'id' => self::TYPE_APARTMENT . '_' . $apartment['id'],
                    'identity_id' => $apartment['id'],
                    'name' => $apartment['name'],
                    'type_id' => self::TYPE_APARTMENT,
                    'type' => self::$types[self::TYPE_APARTMENT],
                ]);
            }
        }

        if ($apartels->count()) {
            foreach ($apartels as $apartel) {
                array_push($distributionList, [
                    'id' => self::TYPE_APARTEL . '_' . $apartel->getId(),
                    'identity_id' => $apartel->getId(),
                    'name' => $apartel->getName(),
                    'type_id' => self::TYPE_APARTEL,
                    'type' => self::$types[self::TYPE_APARTEL],
                ]);
            }
        }

        if ($fiscals->count()) {
            foreach ($fiscals as $fiscal) {
                array_push($distributionList, [
                    'id' => self::TYPE_APARTEL_FISCAL . '_' . $fiscal['id'],
                    'identity_id' => $fiscal['id'],
                    'name' => $fiscal['name'],
                    'type_id' => self::TYPE_APARTEL_FISCAL,
                    'type' => self::$types[self::TYPE_APARTEL_FISCAL],
                ]);
            }
        }

        return $distributionList;
    }
}
