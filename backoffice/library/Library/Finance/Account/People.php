<?php

namespace Library\Finance\Account;

use DDD\Dao\User\UserManager;
use Library\Finance\Base\Account;

class People extends Account
{
    /**
     * @var UserManager|null $dao
     */
    protected $dao;

    /**
     * @return int
     */
    public function getType()
    {
        return Account::TYPE_PEOPLE;
    }

    /**
     * @return bool|int
     */
    public function getCurrency()
    {
        return false;
    }

    /**
     * @param int $accountId
     * @throws \InvalidArgumentException
     */
    public function getAccountById($accountId)
    {
        // do nothing
    }

    /**
     * @return void
     */
    public function prepare()
    {
        // do nothing
    }
}
