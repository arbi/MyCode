<?php

namespace Library\Finance\Base;

interface IAccount
{
    /**
     * @return int
     */
    public function getType();

    /**
     * @return Account
     */
    public function getAccount();

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId);

    /**
     * @return int
     */
    public function getAccountId();

    /**
     * @return int
     */
    public function getTransactionAccountId();
}
