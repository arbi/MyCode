<?php
namespace DDD\Dao\ApartmentGroup;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class BuildingSections extends TableGatewayManager
{
    protected $table = DbTables::TBL_BUILDING_SECTIONS;
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $buildingId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getSectionData($buildingId) {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($buildingId) {
            $select->columns([
                'id',
                'name',
                'lock_id',
                'apartment_entry_textline_id',
                'count_apartment' => new Expression('(SELECT COUNT(a.id) FROM ga_apartments AS a where a.building_section_id = ' . $this->getTable() . '.id)'),
                'lots_id' => new Expression("(SELECT GROUP_CONCAT(building_lots.lot_id SEPARATOR ',')
                                                FROM " . DbTables::TBL_BUILDING_LOTS . " AS building_lots
                                              WHERE building_lots.building_section_id = ga_building_sections.id)"),
                'lots_name' => new Expression("(SELECT GROUP_CONCAT(parking_lots.name SEPARATOR ', ')
                                                    FROM " . DbTables::TBL_BUILDING_LOTS . " AS building_lots
                                                LEFT JOIN " . DbTables::TBL_PARKING_LOTS . " AS parking_lots ON building_lots.lot_id = parking_lots.id
                                                WHERE building_lots.building_section_id = ga_building_sections.id)"),
            ]);
            $select->join(
                ['locks' => DbTables::TBL_LOCKS],
                $this->getTable() . '.lock_id = locks.id',
                [
                    'lock_name' => 'name',
                ],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable().'.building_id', $buildingId);
        });

        return $result;
    }

    /**
     * @param $buildingId
     * @return \ArrayObject
     */
    public function getSectionForBuilding($buildingId)
    {
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use ($buildingId) {
            $select->columns([
                'id',
                'name'
            ]);
            $select->where->equalTo($this->getTable().'.building_id', $buildingId);
        });

        return $result;
    }

    /**
     * @param $lockId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllBuildingsSectionWithLock($lockId)
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use ($lockId) {
            $select->columns([
                'building_id',
                'building_section_name' => new Expression("CONCAT(apartment_group.name, ' ', ". $this->getTable() .".name)"),
            ]);
            $select->join(
                ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.building_id = apartment_group.id',
                [],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.lock_id' => $lockId]);
        });
    }
}
