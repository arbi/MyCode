<?php

namespace Library\Finance\Account;

use Library\Finance\Base\Account;

class Partner extends Account
{
    /**
     * @return int
     */
    public function getType()
    {
        return Account::TYPE_PARTNER;
    }

    /**
     * @param int $accountId
     * @throws \InvalidArgumentException
     */
    public function getAccountById($accountId)
    {
        // fill there
    }

    /**
     * @return void
     */
    public function prepare()
    {
        // do nothing
    }
}
