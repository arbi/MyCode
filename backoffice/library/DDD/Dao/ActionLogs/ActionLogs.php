<?php

namespace DDD\Dao\ActionLogs;

use Library\ActionLogger\Logger;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class ActionLogs extends TableGatewayManager
{
    protected $table = DbTables::TBL_ACTION_LOGS;

    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $moduleId
     * @param int $identityId
     * @param int $actionId
     *
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getByTicket($moduleId, $identityId, $actionId = null)
    {
        return $this->fetchAll(function (Select $select) use ($moduleId, $identityId, $actionId) {

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.module_id', $moduleId)
                ->equalTo($this->getTable() . '.identity_id', $identityId);

            if (!is_null($actionId)) {
                $where->equalTo($this->getTable() . '.action_id', $actionId);
            }

            $select
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = users.id',
                    ['user_name' => new Expression('CONCAT(firstname, " ", lastname)'), 'is_system' => 'system'],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order([$this->getTable() . '.timestamp DESC', $this->getTable() . '.id DESC']);
        });
    }

    /**
     * @param int $apartmentId
     *
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getByApartmentId($apartmentId)
    {
        return $this->fetchAll(function (Select $select) use ($apartmentId) {

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.identity_id' => $apartmentId,
            ]);

            $select->where->in(
                    $this->getTable() . '.module_id',
                    [
                        Logger::MODULE_APARTMENT_GENERAL,
                        Logger::MODULE_APARTMENT_DETAILS,
                        Logger::MODULE_APARTMENT_LOCATION,
                        Logger::MODULE_APARTMENT_MEDIA,
                        Logger::MODULE_APARTMENT_DOCUMENTS,
                        Logger::MODULE_APARTMENT_RATES,
                        Logger::MODULE_APARTMENT_CALENDAR,
                        Logger::MODULE_APARTMENT_INVENTORY,
                        Logger::MODULE_APARTMENT_CONNECTION,
                        Logger::MODULE_APARTMENT_REVIEW
                    ]
            );

            $select->order([$this->getTable() . '.timestamp ASC']);
        });
    }

    /**
     * @param int $groupId
     *
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getByApartmentGroupId($groupId)
    {
        return $this->fetchAll(function (Select $select) use ($groupId) {

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.identity_id' => $groupId,
            ]);

            $select->where->in(
                    $this->getTable() . '.module_id',
                    [
                        Logger::MODULE_APARTMENT_GROUPS
                    ]
            );

            $select->order([$this->getTable() . '.timestamp ASC']);
        });
    }

    /**
     * @param int $partnerId
     *
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getByPartnerId($partnerId)
    {
        return $this->fetchAll(function (Select $select) use ($partnerId) {

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.identity_id' => $partnerId,
            ]);

            $select->where->in(
                    $this->getTable() . '.module_id',
                    [
                        Logger::MODULE_PARTNERS
                    ]
            );

            $select->order([$this->getTable() . '.timestamp ASC']);
        });
    }

    /**
     * @param int $locationId
     *
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getByLocationId($locationId)
    {
        return $this->fetchAll(function (Select $select) use ($locationId) {

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.identity_id' => $locationId,
            ]);

            $select->where->in(
                    $this->getTable() . '.module_id',
                    [
                        Logger::MODULE_LOCATIONS
                    ]
            );

            $select->order([$this->getTable() . '.timestamp ASC']);
        });
    }

    /*
     * temorary function for refine some log value
     */
    public function refineLogValue()
    {
        return $this->fetchAll(function (Select $select) {
            $select->where
                ->equalTo($this->getTable() . '.action_id', 22)
                ->equalTo($this->getTable() . '.module_id', 1)
                ->expression($this->getTable() . ".value REGEXP '^-[0-9]+$|[0-9]+$'", []);
        });
    }

}
