<?php
namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Provinces extends TableGatewayManager
{
    protected $table = DbTables::TBL_PROVINCES;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Geolocation\Provinces');
    }

    public function getAllProvinceByCountryID($id){
        $result = $this->fetchAll(function (Select $select) use($id) {
            $select->join(
                array('gd' => DbTables::TBL_LOCATION_DETAILS),
                $this->getTable().'.detail_id = gd.id',
                array('name'));
            $select->where
                   ->equalTo($this->getTable().'.country_id', $id);
            $select->columns(array('id', 'detail_id'))
                    ->order('name');
        });
        return $result;
    }

    public function getProvinceById($id)
    {
        $result = $this->fetchOne(function (Select $select) use($id) {
            $select->columns(
                [
                    'id',
                    'detail_id'
                ]
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = details.id',
                [
                    'name',
                    'slug'
                ]
            );

            $select->where->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }

}