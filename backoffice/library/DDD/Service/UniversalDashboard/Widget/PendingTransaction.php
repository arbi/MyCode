<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;
use Zend\Validator\Db\RecordExists;
use Library\Constants\DbTables;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;


class PendingTransaction extends ServiceBase {

    /**
     * @param bool|string $type
     * @return \ArrayObject|\DDD\Domain\Booking\ChargeTransaction[]
     */
    public function getPendingTransactions($type = false)
    {
		/**
         * @var $transactionDao \DDD\Dao\Booking\ChargeTransaction
         */
		$transactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
		$notVerifiedTransactions = $transactionDao->getPendingTransactions($type);
		return $notVerifiedTransactions;
	}

    /**
     * @param bool|string $type
     * @return mixed
     */
    public function getPendingTransactionsCount($type = false)
    {
		/**
         * @var $transactionDao \DDD\Dao\Booking\ChargeTransaction
         */
		$transactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
		$count = $transactionDao->getPendingTransactionsCount($type);
		return $count;
	}
}
