<?php
namespace DDD\Dao\Lock;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class LockSettings
 * @package DDD\Dao\Lock
 * @author Hrayr Papikyan
 */
class LockSettings extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCK_SETTINGS;
    
    public function __construct($sm, $domain = 'DDD\Domain\Lock\LockSettings')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $lastInsertedId
     * @param array $settings
     */
    public function saveNewLockSettings($lastInsertedId,$settings)
    {
        foreach ($settings as $key=>$value) {
            $keyArray = explode('_',$key);
            $this->save(
                [
                    'lock_id'           => $lastInsertedId,
                    'setting_item_id'   => (int)$keyArray[1],
                    'value'             => $value
                ]
            );
        }
    }

    /**
     * @param array $settings
     */
    public function editLockSettings($settings)
    {
        foreach ($settings as $key=>$value) {
            $keyArray = explode('_',$key);
            $this->save(
                ['value'=>$value],
                ['id'   =>$keyArray['1']]
            );
        }
    }

    /**
     * @param int $lockId
     */
    public function deleteLockSettings($lockId)
    {
        $this->delete(['lock_id' => $lockId]);
    }

    /**
     * @param int $lockId
     * @return \DDD\Domain\Lock\LockSettings[]
     */
    public function getLockSettingsByLockId($lockId)
    {

        $result = $this->fetchAll(function(Select $select) use($lockId){
            $select->columns(array(
                'id',
                'setting_item_id',
                'value'
            ));

            $select->join(
                array('type_setting_items' => DbTables::TBL_LOCK_TYPE_SETTING_ITEMS),
                $this->getTable().'.setting_item_id = type_setting_items.id',
                [
                    'setting_name'     => 'name',
                    'setting_type'     => 'input_type',
                    'setting_required' => 'is_required'
                ],
                Select::JOIN_INNER
            );

            $select->where
                ->equalTo($this->getTable().'.lock_id', $lockId);
        });

        return $result;
    }
}
