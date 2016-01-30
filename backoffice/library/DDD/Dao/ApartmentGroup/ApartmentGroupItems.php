<?php

namespace DDD\Dao\ApartmentGroup;

use Library\Constants\Objects;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use DDD\Service\Accommodations as AccommodationService;

class ApartmentGroupItems extends TableGatewayManager
{

    protected $table = DbTables::TBL_APARTMENT_GROUP_ITEMS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\ApartmentGroup\ApartmentGroupItems');
    }

    /**
     *
     * @param int $user_id
     * @return \DDD\Domain\ApartmentGroup\ApartmentGroupItems
     */
    public function getAccommodationByUser($user_id)
    {
        $result = $this->fetchAll(function (Select $select) use($user_id) {
            $select->columns([
                        'apartment_id'
                    ])
                    ->join(
                        ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                        $this->getTable() . '.apartment_group_id = apartment_group.id',
                        []
                    )
                    ->where('apartment_group.user_id = ' . $user_id);
        });

        return $result;
    }

    /**
     * @param $apartmentGroupId
     * @return \DDD\Domain\ApartmentGroup\ApartmentGroupItems[]
     */
    public function getApartmentGroupItems($apartmentGroupId, $allData = false, $active = false)
    {
        $result = $this->fetchAll(function (Select $select) use($apartmentGroupId, $allData, $active) {

            $columns = ['apartment_id'];

            if ($allData) {
                $columns = array_merge($columns, [
                    'id',
                    'apartment_group_id'
                ]);
            }

            $select
                ->columns($columns)
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartments.id',
                    ['apartment_name' => 'name']
                )->where([$this->getTable() . '.apartment_group_id' => $apartmentGroupId]);

            if ($active) {
                $select->where->notEqualTo('apartments.status', AccommodationService::APARTMENT_STATUS_DISABLED);
            }
        });

        return $result;
    }

    /**
     *
     * @param int $apartelId
     * @return \ArrayObject|\ArrayObject[]
     */
    public function apartelApartmentDistributionListList($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use($apartelId) {
            $select->columns([
                        'apartmentId' => 'apartment_id'
                    ])
                    ->join(
                        ['apartment' => DbTables::TBL_APARTMENTS],
                        $this->getTable() . '.apartment_id = apartment.id',
                        ['apartment_name' => 'name']
                    )
                    ->join(
                        ['ota_distribution' => DbTables::TBL_APARTMENT_OTA_DISTRIBUTION],
                        'ota_distribution.apartment_id = apartment.id',
                        [
                            'url',
                            'partner_id',
                            'ota_status' => 'status'
                        ],
                        'LEFT'
                    )
                    ->join(
                        ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                        'partner.gid = ota_distribution.partner_id', [],
                        'LEFT'
                    );

            $select->where([$this->getTable() . '.apartment_group_id' => $apartelId]);

            $select->order($this->getTable() . '.apartment_id');
        });

        return $result;
    }

    /**
     * @param $apartmentGroupId
     * @return \DDD\Domain\ApartmentGroup\ApartmentGroupItems []
     */
    public function getSaleApartmentGroupItems($apartmentGroupId)
    {
        $result = $this->fetchAll(function (Select $select) use($apartmentGroupId) {
            $select
                ->columns([
                    'apartment_id'
                ])
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartments.id',
                    ['apartment_name' => 'name']
                )
                ->where->equalTo($this->getTable() . '.apartment_group_id', $apartmentGroupId)
                        ->in('apartments.status', [Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE, Objects::PRODUCT_STATUS_LIVEANDSELLIG]);
        });

        return $result;
    }

    public function getCityByBuidling($buildingId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($buildingId) {

            $columns = ['name'];
            $select->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartments.id',
                    ['city_id']
                )->where([$this->getTable() . '.apartment_group_id' => $buildingId]);

                $select->where->notEqualTo('apartments.status', AccommodationService::APARTMENT_STATUS_DISABLED);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }
}
