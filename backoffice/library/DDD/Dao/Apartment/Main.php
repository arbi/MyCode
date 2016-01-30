<?php

namespace DDD\Dao\Apartment;

use Library\Constants\Objects;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class Main extends TableGatewayManager {
    /**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENTS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentBuilding($apartmentId)
    {
        return $this->fetchOne(function (Select $select) use($apartmentId) {
            $columns = ['building_id'];
            $select->join(
                ['ag' => DbTables::TBL_APARTMENT_GROUPS],
                new Expression($this->getTable() . '.building_id = ag.id and ag.usage_building = 1 and '.$this->getTable() . '.id = '.$apartmentId),
                ['name']
            );
            $select->columns($columns);
        });
    }

    public function getApartmentApartels($apartmentId)
    {
        return $this->fetchAll(function (Select $select) use($apartmentId) {
            $columns = [];

            $where = new Where();
            $where->equalTo($this->table . '.id', $apartmentId);

            $select->join(
                ['agi' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                $this->getTable() . '.id = agi.apartment_id',
                [],
                Select::JOIN_LEFT
            )->join(
                ['ag' => DbTables::TBL_APARTMENT_GROUPS],
                new Expression('agi.apartment_group_id = ag.id and ag.usage_apartel = 1 AND active = 1'),
                ['id', 'apartel_name' => 'name'],
                Select::JOIN_LEFT
            )->where($where);
            $select->columns($columns);
        });
    }

    public function getApartmentDates($apartmentId)
    {
        return $this->fetchOne(function (Select $select) use($apartmentId) {
            $columns = ['create_date', 'disable_date', 'edit_date'];
            $where = new Where();
            $where->equalTo($this->table . '.id', $apartmentId);
            $select
                ->where($where)
                ->columns($columns);
        });
    }

    /**
     * @param string $query
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getSellingApartments($query)
    {
        return $this->fetchAll(function (Select $select) use ($query) {
            $select->where
                ->in('status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE, Objects::PRODUCT_STATUS_SUSPENDED])
                ->and
                ->like('name', "%{$query}%");

            $select
                ->columns(['id', 'name'])
                ->order('name');
        });
    }
}
