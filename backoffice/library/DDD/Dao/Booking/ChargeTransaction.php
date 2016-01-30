<?php

namespace DDD\Dao\Booking;

use DDD\Service\Booking\Charge as ChargeService;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\ResultSet\ResultSet;
use DDD\Domain\Booking\SumTransaction;
use DDD\Service\Booking\BankTransaction;
use DDD\Domain\Booking\TransactionSummary;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class ChargeTransaction extends TableGatewayManager
{
    protected $table = DbTables::TBL_CHARGE_TRANSACTION;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Booking\ChargeTransaction') {
        parent::__construct($sm, $domain);
    }

    /**
     * Return all transactions of given reservation
     * @param int $reservationId
     * @return ResultSet
     */
    public function getReservationTransactions($reservationId)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChargeTransaction());

        return $this->fetchAll(function (Select $select) use($reservationId) {
            $select->join(
                            ['bank' => DbTables::TBL_MONEY_ACCOUNT] ,
                            $this->getTable().'.money_account_id = bank.id',
                            ['bank'=> 'name'],
                            'left'
                          )
                   ->join(
                            ['users' => DbTables::TBL_BACKOFFICE_USERS] ,
                            $this->getTable().'.user_id = users.id',
                            ['user'=> new Expression("CONCAT(users.firstname, ' ', users.lastname)")],
                            'left'
                         )
                   ->join(
                            ['users1' => DbTables::TBL_BACKOFFICE_USERS] ,
                            $this->getTable().'.cache_user = users1.id',
                            ['cacheuser'=> new Expression("CONCAT(users1.firstname, ' ', users1.lastname)")],
                            'left'
                        )
                   ->join(
                            ['psp' => DbTables::TBL_PSP] ,
                            $this->getTable().'.psp_id = psp.id',
                            ['psp_name'=> 'name'],
                            'left'
                        );
            $select->where->equalTo($this->getTable() . '.reservation_id', $reservationId);
			$select->order('date');
		});
    }

    public function getTransactionsWhereBankAmount0AndAccCurencyNotEqualsBankCurrency()
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'money_account_currency',
                'acc_amount',
            ]);
            $select->join(
                ['booking' => DbTables::TBL_BOOKINGS] ,
                $this->getTable().'.reservation_id = booking.id',
                ['apartment_currency_code'],
                'left'
            );
            $select->where
                ->equalTo($this->getTable() . '.bank_amount', 0)
                ->notEqualTo($this->getTable() . '.bank_rate', 0)
                ->equalTo($this->getTable() . '.type', 9)
                ->notEqualTo($this->getTable() . '.acc_amount', 0)
                ->notEqualTo($this->getTable() . '.money_account_id', 0)
                ->notEqualTo($this->getTable() . '.money_account_currency', 'bookig.acc_currency');

			$select->order('date');
		});
    }

    /**
     * THIS ONE MUST BE DELETED! DO NOT USE!
     * @param int $reservationId
     * @return SumTransaction
     */
    public function transactionSum($reservationId)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\SumTransaction());

        return $this->fetchOne(function (Select $select) use($reservationId) {
    		$select->columns(array(
    				'sum_acc'=>new Expression("SUM(acc_amount)"),
    				'sum_customer'=>new Expression("SUM(customer_amount)"),
    		));
    		$select->where
    		->equalTo('reservation_id', $reservationId)
    		->notEqualTo('type', BankTransaction::BANK_TRANSACTION_TYPE_VALIDATION)
    		->in('status', [BankTransaction::BANK_TRANSACTION_STATUS_APPROVED, BankTransaction::BANK_TRANSACTION_STATUS_PENDING]);
    	});
    }

    /**
     * Calculate summary of approved transactions both in apartment and customer currencies
     * @param int $reservationId
     * @param number $moneyDirection
     * @param array $excludeTransactionTypes
     * @return TransactionSummary
     */
    public function calculateTransactionsSummary($reservationId, $moneyDirection, $excludeTransactionTypes)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\TransactionSummary());

        return $this->fetchOne(function (Select $select) use($reservationId, $moneyDirection, $excludeTransactionTypes) {
    		$select->columns(array(
    			'summary_apartment_currency'=> new Expression("SUM(acc_amount)"),
    			'summary_customer_currency'	=> new Expression("SUM(customer_amount)"),
    		));
    		$select->where
    				->equalTo('reservation_id', $reservationId)
    				->equalTo('money_direction', $moneyDirection)
    				->notEqualTo('type', BankTransaction::BANK_TRANSACTION_TYPE_VALIDATION)
                    ->in('status', [BankTransaction::BANK_TRANSACTION_STATUS_APPROVED, BankTransaction::BANK_TRANSACTION_STATUS_PENDING]);
            if (count($excludeTransactionTypes)) {
                $select->where->notIn('type', $excludeTransactionTypes);
            }
    	});
    }

    /**
     * @param $type
     * @return \DDD\Domain\Booking\ChargeTransaction[]|\ArrayObject
     */
    public function getPendingTransactions($type)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		return $this->fetchAll(function (Select $select) use ($type) {
			$select->columns([
				'id',
                'date',
                'type',
                'user_id',
                'acc_amount',
                'money_account_currency',
                'type',
                'status',
			]);

			$select->join([
				'users' => DbTables::TBL_BACKOFFICE_USERS
			], $this->getTable() . '.cache_user = users.id', [
				'user' => new Expression('CONCAT(firstname, " ", lastname)')
			], Select::JOIN_LEFT);

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.money_account_currency = c.code',
                ['symbol' => 'symbol'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = reservation.id',
                [
                    'reservation_id' => 'id',
                    'res_number',
                    'acc_name',
                    'guest' => new Expression('CONCAT(guest_first_name, " ", guest_last_name)'),
                ],
                Select::JOIN_INNER
            );

			$select->where->equalTo($this->getTable() . '.status', BankTransaction::BANK_TRANSACTION_STATUS_PENDING);
            if ($type == 'cash') {
                $select->where->equalTo($this->getTable() . '.reviewed', 0)
                              ->in($this->getTable() . '.type', [BankTransaction::BANK_TRANSACTION_TYPE_CASH, BankTransaction::BANK_TRANSACTION_TYPE_CASH_REFUND]);
            } elseif ($type == 'frontier') {
                $select->where->equalTo($this->getTable() . '.reviewed', BankTransaction::FRONTIER_TRANSACTION_REVIEWED);
            } else {
                $select->where->equalTo($this->getTable() . '.reviewed', 0)
                              ->notIn($this->getTable() . '.type', [BankTransaction::BANK_TRANSACTION_TYPE_CASH, BankTransaction::BANK_TRANSACTION_TYPE_CASH_REFUND]);
            }
		});
	}

    /**
     * @param $type bool
     * @return int
     */
    public function getPendingTransactionsCount($type)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		$result = $this->fetchOne(function (Select $select) use ($type) {
			$select->columns(['count' => new Expression('COUNT(*)')]);

            $select->where->equalTo($this->getTable() . '.status', BankTransaction::BANK_TRANSACTION_STATUS_PENDING);
            if ($type == 'cash') {
                $select->where->equalTo($this->getTable() . '.reviewed', 0)
                    ->in($this->getTable() . '.type', [BankTransaction::BANK_TRANSACTION_TYPE_CASH, BankTransaction::BANK_TRANSACTION_TYPE_CASH_REFUND]);
            } elseif ($type == 'frontier') {
                $select->where->equalTo($this->getTable() . '.reviewed', BankTransaction::FRONTIER_TRANSACTION_REVIEWED);
            } else {
                $select->where->equalTo($this->getTable() . '.reviewed', 0)
                    ->notIn($this->getTable() . '.type', [BankTransaction::BANK_TRANSACTION_TYPE_CASH, BankTransaction::BANK_TRANSACTION_TYPE_CASH_REFUND]);
            }
        });
        return $result['count'];
	}

	/**
	 * @return ArrayObject
	 */
	public function getTransactionsForReviewed()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		return $this->fetchAll(function (Select $select) {
			$select->columns([
				'id',
                'date',
                'user_id',
                'apartment_id',
                'bank_amount',
                'money_account_currency'
			]);
			$select->join([
				'users' => DbTables::TBL_BACKOFFICE_USERS
			], $this->getTable() . '.user_id = users.id', [
				'transactioner' => new Expression('CONCAT(firstname, " ", lastname)')
			], Select::JOIN_LEFT);

			$select->join([
				'booking' => DbTables::TBL_BOOKINGS
			], $this->getTable() . '.reservation_id = booking.id', [
				'guest' => new Expression('CONCAT(guest_first_name, " ", guest_last_name)'),
                'res_number'
			], Select::JOIN_LEFT);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                'booking.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );
            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.money_account_currency = c.code',
                ['symbol' => 'symbol']
            );

			$select->where
                   ->equalTo($this->getTable() . '.reviewed', 1);
		});
	}

	/**
	 * @return int
	 */
	public function getTransactionsForReviewedCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		$result = $this->fetchOne(function (Select $select) {
			$select->columns(['count' => new Expression('COUNT(*)')]);
            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.money_account_currency = c.code',
                []
            );

			$select->where->equalTo($this->getTable() . '.reviewed', 1);
		});

        return $result['count'];
	}

    public function getReservationTransactionsAllCurrency($reservationId) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use($reservationId) {
            $select->columns([
                'amount' => 'bank_amount',
                'acc_amount' => 'acc_amount',
                'amount_currency' => 'money_account_currency',
                'type' => 'type',
                'date' => 'date',
                'psp_id' => 'psp_id',
            ]);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.money_account_currency = currency.code',
                ['symbol' => 'symbol']
            );
            $select->where
                ->equalTo('reservation_id', $reservationId)
                ->notIn('type', [BankTransaction::BANK_TRANSACTION_TYPE_VALIDATION, BankTransaction::BANK_TRANSACTION_TYPE_PAY])
                ->in('status', [BankTransaction::BANK_TRANSACTION_STATUS_APPROVED, BankTransaction::BANK_TRANSACTION_STATUS_PENDING]);
        });
        return $result;
    }

    /**
     * @param Where $statement
     * @param bool $isGroupSelected
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getTransactionSummary($statement, $isGroupSelected)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) use ($statement, $isGroupSelected) {
            $rTable = DbTables::TBL_BOOKINGS;
            $aTable = DbTables::TBL_APARTMENTS;
            $cTable = DbTables::TBL_CURRENCY;
            $agiTable = DbTables::TBL_APARTMENT_GROUP_ITEMS;
            $pspTable = DbTables::TBL_PSP;

            $select->columns([
                'amount' => new Expression("SUM({$this->getTable()}.acc_amount)"),
                'count' => new Expression("COUNT({$this->getTable()}.acc_amount)"),
                'type',
            ]);
            $select->join(
                DbTables::TBL_BOOKINGS,
                "{$this->getTable()}.reservation_id = {$rTable}.id",
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_PSP,
                "{$this->getTable()}.psp_id = {$pspTable}.id",
                ['psp_name' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_APARTMENTS,
                "{$rTable}.apartment_id_origin = {$aTable}.id",
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_CURRENCY,
                "{$cTable}.id = {$aTable}.currency_id",
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );

            if ($isGroupSelected) {
                $select->join(
                    DbTables::TBL_APARTMENT_GROUP_ITEMS,
                    "{$agiTable}.apartment_id = {$aTable}.id",
                    [],
                    Select::JOIN_INNER
                );
            }

            $select->where($statement);
            $select->group([
                $this->getTable() . '.type',
                $cTable . '.code',
            ]);
        });
    }

    /**
     * @param Where $statement
     * @param bool $isGroupSelected
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getTransactionDownloadable($statement, $isGroupSelected)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) use ($statement, $isGroupSelected) {
            $rTable = DbTables::TBL_BOOKINGS;
            $aTable = DbTables::TBL_APARTMENTS;
            $cTable = DbTables::TBL_CURRENCY;
            $agiTable = DbTables::TBL_APARTMENT_GROUP_ITEMS;
            $pspTable = DbTables::TBL_PSP;

            $select->join(
                DbTables::TBL_BOOKINGS,
                "{$this->getTable()}.reservation_id = {$rTable}.id",
                ['res_number'],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_PSP,
                "{$this->getTable()}.psp_id = {$pspTable}.id",
                ['psp_name' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_APARTMENTS,
                "{$rTable}.apartment_id_origin = {$aTable}.id",
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_CURRENCY,
                "{$cTable}.id = {$aTable}.currency_id",
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );

            if ($isGroupSelected) {
                $select->join(
                    DbTables::TBL_APARTMENT_GROUP_ITEMS,
                    "{$agiTable}.apartment_id = {$aTable}.id",
                    [],
                    Select::JOIN_RIGHT
                );
            }

            $select->where($statement);
        });
    }

    /**
     * @todo: convert to zend's compatible format
     * @param int $moneyTransactionId
     * @return \Zend\Db\Adapter\Driver\ResultInterface|array[]
     */
    public function getTransactionsByMoneyTransactionId($moneyTransactionId)
    {
        $driver = $this->getAdapter()->getDriver();
        $stmt = $driver->createStatement("
            select
                ga_reservation_transactions.id,
                ga_reservation_transactions.reservation_id,
                ga_reservations.res_number,
                ga_money_accounts.name as money_account,
                ga_reservation_transactions.bank_amount as amount,
                ga_reservation_transactions.money_account_currency as currency,
                ga_reservation_transactions.user_id,
                ga_reservation_transactions.money_account_id as money_account_entity_id,
                concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname) as user,
                ga_reservation_transactions.comment as description
            from ga_reservation_transactions
                left join ga_reservations on ga_reservations.id = ga_reservation_transactions.reservation_id
                left join ga_money_accounts on ga_money_accounts.id = ga_reservation_transactions.money_account_id
                left join ga_bo_users on ga_bo_users.id = ga_reservation_transactions.user_id
            where ga_reservation_transactions.money_transaction_id = ?;
        ");

        return $stmt->execute([$moneyTransactionId]);
    }

    /**
     * @param int $pspId
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return ResultSet
     */
    public function getCollectionReadyVirtualReservations($pspId, $dateFrom, $dateTo)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use ($pspId, $dateFrom, $dateTo) {
            $select->columns([
                'id',
                'bank_amount',
                'type',
                'money_account_id',
                'transaction_date' => 'date',
            ]);
            $select->join(
                ['money_transaction' => DbTables::TBL_TRANSACTIONS],
                $this->getTable() . '.money_transaction_id = money_transaction.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.money_account_currency = currency.code',
                ['symbol'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = reservation.id',
                [
                    'res_number',
                    'departure_date' => 'date_to',
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['psp' => DbTables::TBL_PSP],
                $this->getTable() . '.money_account_id = psp.money_account_id',
                [],
                Select::JOIN_RIGHT
            );
            $select->where([
                'psp.id' => $pspId,
                $this->getTable() . '.money_direction' => ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT,
            ]);
            $select->where
                ->between($this->getTable() . '.date', $dateFrom, $dateTo)
                ->notEqualTo($this->getTable() . '.bank_amount', 0)
                ->in($this->getTable() . '.status', [BankTransaction::BANK_TRANSACTION_STATUS_APPROVED])
                ->in($this->getTable() . '.type', [
                    BankTransaction::BANK_TRANSACTION_TYPE_COLLECT,
                    BankTransaction::BANK_TRANSACTION_TYPE_REFUND,
                    BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE,
                    BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD,
                    BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER,
                ])
                ->isNull('money_transaction.id');
            $select->order('transaction_date desc');
        });
    }
}
