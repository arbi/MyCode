<?php
namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Continents extends TableGatewayManager
{
    protected $table = DbTables::TBL_CONTINENTS;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Geolocation\Continents');
    }
    
    public function getContinentsByText($txt)
    {
        $result = $this->fetchAll(function (Select $select) use($txt) {
            $select->join(
                array('utils' => DbTables::TBL_UN_TEXTLINES),
                $this->getTable().'.textline_id = utils.id',
                array('en'));
            $select->where
                    ->like('utils.en', $txt.'%');
            $select->columns(array('id', 'detail_id'))
                    ->order('utils.en');
        });
        return $result;
    }

    /**
     * @param $id
     * @return \DDD\Domain\Geolocation\Continents
     */
    public function getContinentById($id)
    {
        $result = $this->fetchOne(function (Select $select) use($id) {
            $select->join(
                array('utils' => DbTables::TBL_UN_TEXTLINES),
                $this->getTable().'.textline_id = utils.id',
                array('en'));
            $select->where
                   ->equalTo($this->getTable().'.id', $id);
            $select->columns(array('id', 'detail_id'));
        });
        return $result;
    }
}