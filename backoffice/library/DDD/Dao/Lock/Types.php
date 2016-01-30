<?php
namespace DDD\Dao\Lock;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class Types
 * @package DDD\Dao\Lock
 * @author Hrayr Papikyan
 */
class Types extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCK_TYPES;
    
    public function __construct($sm, $domain = 'DDD\Domain\Lock\Types')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllLockTypesForSelect()
    {
        return $this->fetchAll();
    }
}
