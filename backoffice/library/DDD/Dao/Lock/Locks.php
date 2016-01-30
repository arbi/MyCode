<?php
namespace DDD\Dao\Lock;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

use DDD\Service\Lock\Usages\Base as UsageBase;
use DDD\Service\Lock\General as LockService;

/**
 * Class Locks
 * @package DDD\Dao\Lock
 * @author Hrayr Papikyan
 */
class Locks extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCKS;

    public function __construct($sm, $domain = 'DDD\Domain\Lock\Locks')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $data
     * @return int
     */
    public function saveNewLock($data)
    {
        unset($data['additional_settings']);
        $insertedId = $this->save($data);
        return $insertedId;
    }

    /**
     * @param $data
     * @return int
     */
    public function editLock($data)
    {
        $id = $data['id'];
        unset($data['additional_settings']);
        unset($data['id']);

        $data['name'] = trim($data['name']);

        $insertedId = $this->save($data,['id'=>$id]);
        return $insertedId;
    }

    /**
     * @param $lockId
     */
    public function deleteLock($lockId)
    {
        $this->delete(['id' => $lockId]);
    }

    /**
     * @param int $lockId
     * @return \DDD\Domain\Lock\Locks
     */
    public function getLockById($lockId)
    {
        return $this->fetchOne(function(Select $select) use ($lockId){
            $select->columns(array(
                'id',
                'type_id',
                'name',
                'description',
                'is_physical'
            ));
            $select->join(
                ['lock_types' => DbTables::TBL_LOCK_TYPES],
                $this->table . '.type_id = lock_types.id',
                [
                    'explanation' => 'explanation',
                ],
                Select::JOIN_INNER
            );
            $select->where([$this->table . '.id' => $lockId]);
        });
    }

    /**
     * @param $where
     * @return \DDD\Domain\Lock\Locks
     */
   public function getLocksSearchResults($where)
   {
       return $this->fetchAll(function (Select $select) use($where) {
           $select->columns(
               [
                   'id',
                   'name',
                   'description',

               ]
           );

           $select->join(
               ['lock_types' => DbTables::TBL_LOCK_TYPES],
               $this->table . '.type_id = lock_types.id',
               [
                   'type_name' => 'name',
                   'usage_apartment' => 'usage_apartment',
                   'usage_building'  => 'usage_building',
                   'usage_parking'   => 'usage_parking'
               ],
               Select::JOIN_INNER
           );
           $select->where($where);

           $select->order($this->table . '.name');
       });
   }

    public function getForSelectByUsage($usageField)
    {
        $this->getResultSetPrototype()->setArrayObjectPrototype(new \DDD\Domain\Lock\ForSelect());
        return $this->fetchAll(function (Select $select) use($usageField) {
            $select->columns(
                [
                    'id',
                    'name',
                    'is_physical'
                ]
            );

            $select->join(
                ['lock_types' => DbTables::TBL_LOCK_TYPES],
                $this->table . '.type_id = lock_types.id',
                ['type_name' => 'name'],
                Select::JOIN_INNER
            );

            $select->where(['lock_types.' . $usageField => 1]);

            $select->order(['name' => 'asc']);
        });
    }

    public function getPhysicalUsage($usageDao, $usageId, $lockId, $selectedUsage, $notPhysical = false)
    {
        $preDomain = $usageDao->getResultSetPrototype()->getArrayObjectPrototype();
        $usageDao->getResultSetPrototype()->setArrayObjectPrototype(new \ArrayObject());

        $result = $usageDao->fetchOne(function (Select $select) use($usageDao, $usageId, $lockId, $selectedUsage, $notPhysical) {
            $select->columns(
                ['count' => new Expression('COUNT(*)')]
            );

            if (!$notPhysical && $usageDao instanceof $selectedUsage) {
                if ($selectedUsage === '\DDD\Dao\ApartmentGroup\BuildingSections') {
                    $select->where->notEqualTo('building_id', $usageId);
                } else {
                    $select->where->notEqualTo('id', $usageId);
                }
            }

            $select->where->equalTo('lock_id', $lockId);
        });
        $usageDao->getResultSetPrototype()->setArrayObjectPrototype($preDomain);
        return $result;
    }

    public function getLockByUsage($usageId, $usage)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        switch ($usage) {
            case LockService::USAGE_APARTMENT_TYPE:
                break;
            case LockService::USAGE_BUILDING_TYPE:
                break;
            case LockService::USAGE_PARKING_TYPE:
                break;
        }

        $result =  $this->fetchAll(function (Select $select) use($usageId, $usage) {

            if ($usage == LockService::USAGE_APARTMENT_TYPE) {

                $select->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->table . '.id = apartments.lock_id',
                    ['apartment_lock_id' => 'lock_id'],
                    Select::JOIN_LEFT
                );
                $select->where->equalTo('apartments.id' , $usageId);


            } elseif ($usage == LockService::USAGE_BUILDING_TYPE) {
                //TODO :
            } elseif ($usage == LockService::USAGE_PARKING_TYPE) {
                //TODO:
            }

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartments.city_id = city.id',
                ['timezone'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['lock_type_settings' => DbTables::TBL_LOCK_TYPE_SETTINGS],
                'lock_type_settings.lock_type_id = ' . $this->getTable() . '.type_id',
                ['setting_item_id'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['lock_type_setting_items' => DbTables::TBL_LOCK_TYPE_SETTING_ITEMS],
                'lock_type_settings.setting_item_id = lock_type_setting_items.id',
                ['name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_settings' => DbTables::TBL_LOCK_SETTINGS],
                new Expression('lock_settings.setting_item_id = lock_type_setting_items.id AND lock_settings.lock_id = ' . $this->getTable() . '.id'),
                ['value'],
                Select::JOIN_LEFT
            );

            $select->order('lock_type_settings.setting_item_id ASC');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    /**
     * @param $apartmentId
     * @param $usage
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getLockByReservationApartmentId($apartmentId, $usage)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        switch ($usage) {
            case LockService::USAGE_APARTMENT_TYPE:
                $selectedDb = 'apartments';
                break;
            case LockService::USAGE_BUILDING_TYPE:
                $selectedDb = 'building_section';
                break;
            case LockService::USAGE_PARKING_TYPE:
                $selectedDb = 'parking';
                break;
        }

        $result =  $this->fetchAll(function (Select $select) use($apartmentId, $usage, $selectedDb) {
            $select->columns(['id']);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->table . '.id = apartments.lock_id',
                ['apartment_lock_id' => 'lock_id'],
                Select::JOIN_LEFT
            );

            if ($usage == LockService::USAGE_BUILDING_TYPE) {
                $select->join(
                    ['building_section' => DbTables::TBL_BUILDING_SECTIONS],
                    'building_section.id = apartments.building_section_id',
                    ['building_lock_id' => 'lock_id'],
                    Select::JOIN_LEFT
                );
            } elseif ($usage == LockService::USAGE_PARKING_TYPE) {

                $select->join(
                    ['a_details' => DbTables::TBL_APARTMENTS_DETAILS],
                    'a_details.apartment_id= apartments.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['pb' => DbTables::TBL_BUILDING_LOTS],
                    'apartments.building_section_id = pb.building_section_id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['parking' => DbTables::TBL_PARKING_LOTS],
                    'parking.id = pb.lot_id',
                    ['parking_lock_id' => 'lock_id'],
                    Select::JOIN_LEFT
                );
            }

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartments.city_id = city.id',
                ['timezone'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['locks' => DbTables::TBL_LOCKS],
                'locks.id = ' . $selectedDb . '.lock_id',
                ['type_id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_type_settings' => DbTables::TBL_LOCK_TYPE_SETTINGS],
                'lock_type_settings.lock_type_id = locks.type_id',
                ['setting_item_id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_type_setting_items' => DbTables::TBL_LOCK_TYPE_SETTING_ITEMS],
                'lock_type_settings.setting_item_id = lock_type_setting_items.id',
                ['name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_settings' => DbTables::TBL_LOCK_SETTINGS],
                new Expression('lock_settings.setting_item_id = lock_type_setting_items.id AND lock_settings.lock_id = locks.id'),
                ['value'],
                Select::JOIN_LEFT
            );

            $select->where->equalTo('apartments.id' , $apartmentId);
            $select->order('lock_type_settings.setting_item_id ASC');

        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }
}
