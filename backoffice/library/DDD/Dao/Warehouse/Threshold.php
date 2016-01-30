<?php
namespace DDD\Dao\Warehouse;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class Threshold extends TableGatewayManager
{
    protected $table = DbTables::TBL_WM_THRESHOLD;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Warehouse\Storage\Threshold');
    }

    /**
     * @param $storageId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllThresholdForStorage($storageId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($storageId) {
            $select->columns([
                'id',
                'threshold'
            ]);
            $select->join(
                ['category' => DbTables::TBL_ASSET_CATEGORIES],
                $this->getTable() . '.asset_category_id = category.id',
                [
                    'name'
                ],
                $select::JOIN_LEFT
            );
            $select->where->equalTo($this->getTable() . '.storage_id', $storageId);
        });

        return $result;
    }
   
}
