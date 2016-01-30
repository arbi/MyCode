<?php

namespace DDD\Service\UniversalDashboard;

use DDD\Dao\Booking\ChargeTransaction;
use DDD\Dao\Finance\Expense\ExpenseItem;
use DDD\Dao\Finance\Expense\Expenses;
use DDD\Dao\User\Vacationdays;
use Library\ActionLogger\Logger;
use DDD\Service\ServiceBase;
use DDD\Service\User\Vacation as VacationService;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

/**
 * Class Main
 * @package DDD\Service\UniversalDashboard
 */
class Main extends ServiceBase
{
    protected $serviceLocator;
    protected $_vacationdays = null;
    protected $_user = null;
    protected $_expense = null;
    protected $_expense_item = null;

    /**
     * @param int $userId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getPendingPOItems($userId)
    {
		return $this->getExpenseItemDao()->getManagersPendingItems($userId);
	}

    /**
     * @param int $userId
     * @return int
     */
    public function getPendingPOItemsCount($userId)
    {
		return $this->getExpenseItemDao()->getManagersPendingItemsCount($userId);
	}

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAwaitingApprovalExpenses()
    {
		return $this->getExpenseDao()->getNotApprovedExpenses();
	}

    /**
     * @return int
     */
    public function getAwaitingApprovalExpenseCount()
    {
		return $this->getExpenseDao()->getNotApprovedExpenseCount();
	}

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAwaitingTransfer()
    {
		return $this->getExpenseItemDao()->getItemsAwaitingTransfer();
	}

    /**
     * @return int
     */
    public function getAwaitingTransferCount()
    {
		return $this->getExpenseItemDao()->getItemsAwaitingTransferCount();
	}

    /**
     * @param int $userId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getNotApprovedItems($userId)
    {
        return $this->getExpenseItemDao()->getNotApprovedItems($userId);
    }

    /**
     * @param int|null $userId
     * @return int
     */
    public function getNotApprovedItemsCount($userId)
    {
        return $this->getExpenseItemDao()->getNotApprovedItemsCount($userId);
    }

    /**
     * @param $loggedInUserID
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getMyActualPO($loggedInUserID)
    {
        return $this->getExpenseDao()->getMyActualPO($loggedInUserID);
    }

    /**
     * @param $loggedInUserID
     * @return int
     */
    public function getMyActualPOCount($loggedInUserID)
    {
        return $this->getExpenseDao()->getMyActualPOCount($loggedInUserID);
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getReadyToBeSettledPO()
    {
        return $this->getExpenseDao()->getReadyToBeSettledPO();
    }

    /**
     * @return int
     */
    public function getReadyToBeSettledPOCount()
    {
        return $this->getExpenseDao()->getReadyToBeSettledPOCount();
    }

	/**
	 * Get reservations for "Last Minute Reservations" widget
	 * @var \DDD\Dao\Booking\Booking $bookingDao
	 * @return \DDD\Domain\Booking\CustomerService[]|\ArrayObject
	 *
	 * @todo create new service for "Last Minute Reservations" widget and move this method to there
	 */
    public function getLastMinuteReservation()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\CustomerService());

    	return $bookingDao->getReservationForToday();
    }

	/**
	 * Get reservations for "Last Minute Reservations" widget
	 * @var \DDD\Dao\Booking\Booking $bookingDao
	 * @return int
	 */
    public function getLastMinuteReservationCount()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\CustomerService());

    	return $bookingDao->getReservationForTodayCount();
    }

    public function resolveLastMinuteReservation($id)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var Logger $logger
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger     = $this->getServiceLocator()->get('ActionLogger');

        $bookingDao->setEntity(new \DDD\Domain\Booking\CustomerService());

    	$bookingDao->save(['lmr_resolved' => '1'], ['id' => $id]);
        $logger->save(Logger::MODULE_BOOKING, $id, Logger::ACTION_LAST_MINUTE_RESOLVED);

    	return true;
    }

    /**
     * Get reservations count for "Chargeback Pending Count" widget
     */
    public function getChargebackPendingCount()
    {
        /**
         * @var ChargeTransaction $transactionDao
         */
        $transactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
    	$count = $transactionDao->getChargebackPendingCount();
        return $count;
    }

    /**
     * Get reservations for "Chargeback Pending" widget
     */
    public function getChargebackPending()
    {
        /**
         * @var ChargeTransaction $transactionDao
         */
        $transactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
        return $transactionDao->getChargebackPending();
    }

    public function markSettled($id)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var Logger $logger
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger     = $this->getServiceLocator()->get('ActionLogger');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

    	$bookingDao->save(['payment_settled' => 1, 'settled_date' => date("Y-m-d H:m:s")], ['id' => $id]);
        $logger->save(Logger::MODULE_BOOKING, $id, Logger::ACTION_RESERVATION_SETTLED);
    }

    public function markPaid($id)
    {
        /**
         * @var Logger $logger
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger     = $this->getServiceLocator()->get('ActionLogger');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

    	$bookingDao->save(['partner_settled' => 1], ['id' => $id]);
        $logger->save(Logger::MODULE_BOOKING, $id, Logger::ACTION_PAID_TO_AFFILIATE);
    }

	public function getUserDao()
    {
		if ($this->_user === null) {
			$this->_user = $this->getServiceLocator()->get('dao_user_user_manager');
		}
		return $this->_user;
	}

    /**
     * @param string $domain
     * @return Expenses|null
     */
    public function getExpenseDao($domain = '\ArrayObject')
    {
		if (is_null($this->_expense)) {
			$this->_expense = new Expenses($this->getServiceLocator(), $domain);
		}

		return $this->_expense;
	}

    /**
     * @param string $domain
     * @return ExpenseItem|null
     */
    public function getExpenseItemDao($domain = '\ArrayObject')
    {
		if (is_null($this->_expense_item)) {
			$this->_expense_item = new ExpenseItem($this->getServiceLocator(), $domain);
		}

		return $this->_expense_item;
	}
}
