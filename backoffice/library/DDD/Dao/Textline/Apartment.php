<?php

namespace DDD\Dao\Textline;

use DDD\Service\Translation;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

use DDD\Domain\Textline\Apartment as ApartmentTextline;

/**
 * Class Apartment
 * @package DDD\Dao\Textline
 */
class Apartment extends TableGatewayManager
{

    protected $table = DbTables::TBL_PRODUCT_TEXTLINES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Textline\Apartment');
    }

    /**
     *
     * @param int $apartmentId
     * @return ApartmentTextline
     */
    public function getApartmentDirectEntryTextline($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ApartmentTextline);

        /** @var ApartmentTextline $result */
        $result = $this->fetchOne(function (Select $select) use ($apartmentId){
            $select->columns([
                'id',
                'en_text' => 'en'
            ]);

            $select
                ->where
                    ->equalTo('entity_id', $apartmentId)
                    ->equalTo('entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION);
        });
        return $result;
    }

    /**
     *
     * @param int $apartmentId
     * @return ApartmentTextline
     */
    public function getBuildingDirectEntryTextline($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ApartmentTextline);

        /** @var ApartmentTextline $result */
        $result = $this->fetchOne(function (Select $select) use ($apartmentId){
            $select->columns([
                'id',
                'en_text' => 'en'
            ]);

            $select
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression('apartments.building_section_id = ' . $this->getTable() . '.entity_id AND apartments.id = ' . $apartmentId),
                    [],
                    Select::JOIN_INNER
                )
                ->where
                ->equalTo('entity_type', Translation::PRODUCT_TEXTLINE_TYPE_BUILDING_SECTION_APARTMENT_ENTRY);
        });
        return $result;
    }

    /**
     * @param bool $textlineId
     * @param bool $entityId
     * @param bool $entityType
     * @return array|\ArrayObject|null
     */
    public function getProductTextline($textlineId = false, $entityId = false, $entityType = false)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($textlineId, $entityId, $entityType) {
            $select->columns([
                'id',
                'entity_id',
                'entity_type',
                'en'
            ]);

            if ($textlineId) {
                $select->where
                    ->equalTo('id', $textlineId);
            } else {
                $select->where
                    ->equalTo('entity_id', $entityId)
                    ->and
                    ->equalTo('entity_type', $entityType);
            }
        });

        return $result;
    }

    /**
     * @param $textlineId
     * @return array|\ArrayObject|null
     */
    public function getProductTextlineById($textlineId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($textlineId) {
            $select->columns([
                'en'
            ]);
            $select->where->equalTo('id', $textlineId);
        });
    }

    /**
     * Get apartment using information
     *
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentUsageByApartmentId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id',
                'entity_id',
                'entity_type',
                'en'
            ]);

            $select->where
                ->equalTo('entity_id', $apartmentId)
                ->and
                ->equalTo('entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_USAGE);
        });
    }

    /**
     * Get apartment group using information
     *
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentBuildingUsageByApartmentId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id'          => 'id',
                'building_id' => 'entity_id',
                'en'          => 'en',
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                new Expression($this->getTable() . '.entity_id = apartments.building_id'),
                [],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('apartments.id', $apartmentId)
                ->and
                ->equalTo($this->getTable() . '.entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE);
        });
    }

    /**
     * Get apartment group facility information
     *
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentBuildingFacilityByApartmentId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id'          => 'id',
                'building_id' => 'entity_id',
                'en'          => 'en',
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                new Expression($this->getTable() . '.entity_id = apartments.building_id'),
                [],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('apartments.id', $apartmentId)
                ->and
                ->equalTo($this->getTable() . '.entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES);
        });
    }

    /**
     * Get apartment group facility information
     *
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentBuildingPolicyByApartmentId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id'          => 'id',
                'building_id' => 'entity_id',
                'en'          => 'en',
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                new Expression($this->getTable() . '.entity_id = apartments.building_id'),
                [],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('apartments.id', $apartmentId)
                ->and
                ->equalTo($this->getTable() . '.entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES);
        });
    }
}