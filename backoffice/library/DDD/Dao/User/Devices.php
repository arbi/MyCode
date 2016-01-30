<?php

namespace DDD\Dao\User;

use Library\Constants\Constants;
use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;

class Devices extends TableGatewayManager
{
    protected $table   = DbTables::TBL_USER_DEVICES;

    public function __construct($sm, $domain = 'DDD\Domain\User\Devices')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $userId
     *
     * @return \DDD\Domain\User\Devices[]
     */
    public function getDevicesByUserId($userId)
    {
        $result = $this->fetchAll(function (Select $select) use ($userId) {
            $select->where
                ->equalTo($this->getTable() . '.user_id', $userId);
        });

        return $result;
    }

    /**
     * @param string $hash
     *
     * @return \DDD\Domain\User\Devices[]
     */
    public function getUserIdByHash($hash)
    {
        $result = $this->fetchOne(function (Select $select) use ($hash) {
            $select->columns([
                'id',
                'user_id'
            ]);
            $select->where
                ->equalTo($this->getTable() . '.hash', $hash);
        });

        return $result;
    }

    /**
     * @param $id
     * @return array
     */
    public function getById($id)
    {
        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->where
                ->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }

    /**
     * @param $userId
     * @param $hash
     * @return array
     */
    public function getByUserIdAndHash($userId, $hash)
    {
        $result = $this->fetchOne(function (Select $select) use ($userId, $hash) {
            $select->where
                ->equalTo($this->getTable() . '.user_id', $userId)
                ->equalTo($this->getTable() . '.hash', $hash);
        });

        return $result;
    }

    /**
     * @param $userId
     * @param $hash
     * @return int
     */
    public function saveDeviceHash($userId, $hash)
    {
        $result = $this->save([
            'user_id'    => $userId,
            'hash'       => $hash,
            'date_added' => date(Constants::DATABASE_DATE_TIME_FORMAT),
        ]);

        return $result;
    }

    /**
     * @param $id
     * @return int
     */
    public function unlinkDeviceById($id)
    {
        $result = $this->delete([
            'id' => $id,
        ]);

        return $result;
    }
}