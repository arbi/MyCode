<?php
namespace DDD\Dao\Lock;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class SettingItems
 * @package DDD\Dao\Lock
 * @author Hrayr papikyan
 */
class SettingItems extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCK_TYPE_SETTING_ITEMS;
    
    public function __construct($sm, $domain = 'DDD\Domain\Lock\SettingItems')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $lockTypeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getLockTypeSettings($lockTypeId)
    {
        $result = $this->fetchAll(function(Select $select) use($lockTypeId){
            $select->columns(array(
                'id',
                'name',
                'is_required',
                'input_type'
            ));

            $select->join(
                array('type_settings' => DbTables::TBL_LOCK_TYPE_SETTINGS),
                $this->getTable().'.id = type_settings.setting_item_id',
                [],
                SELECT::JOIN_INNER
            );

            $select->where
                ->equalTo('type_settings.lock_type_id', $lockTypeId);
        });

        return $result;
    }

}
