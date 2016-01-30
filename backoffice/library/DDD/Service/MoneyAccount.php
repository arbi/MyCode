<?php

namespace DDD\Service;

use DDD\Dao\User\UserManager;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;
use Library\Finance\Base\TransactionBase;
use Library\Finance\Finance;
use Library\Utility\Debug;

use Zend\Filter\Null;
use Zend\Form\Form;
use Zend\Http\Request;

class MoneyAccount extends ServiceBase
{
    const TYPE_PERSON       = 1;
    const TYPE_BANK         = 2;
    const TYPE_CREDIT_CARD  = 3;
    const TYPE_SAVINGS      = 4;
    const TYPE_DEBIT_CARD   = 5;

    const OPERATION_VIEW_TRANSACTION   = 1;
    const OPERATION_ADD_TRANSACTION    = 2;
    const OPERATION_MANAGE_ACCOUNT     = 3;
    const OPERATION_MANAGE_TRANSACTION = 4;

    const SEARCHABLE_YES = 1;
    const SEARCHABLE_NO = 0;

    const PERMISSION_NONE = 0;
    const PERMISSION_VIEW_TRANSACTIONS = 1;
    const PERMISSION_ADD_TRANSACTIONS = 2;
    const PERMISSION_MANAGE_TRANSACTIONS = 4;
    const PERMISSION_MANAGE_ACCOUNT = 8;
    const PERMISSION_ALL = 32767;

    protected $moneyAccountDao;
    protected $bankMoneyUsersDao;

	/**
	 * @param int|null $status
	 * @return \DDD\Domain\MoneyAccount\MoneyAccount[]|\ArrayObject
	 */
	public function getAllMoneyAccounts($status = null)
    {
		$moneyAccountDao = $this->getMoneyAccountDao();

		return $moneyAccountDao->getAllMoneyAccounts($status);
	}

    /**
     * @return array
     */
    public function getMoneyAccountList()
    {
        $moneyAccounts = $this->getAllMoneyAccounts();
        $moneyAccountList = [];

        if ($moneyAccounts->count()) {
            foreach ($moneyAccounts as $moneyAccount) {
                array_push($moneyAccountList, [
                    'id' => $moneyAccount->getId(),
                    'name' => $moneyAccount->getName(),
                    'currency' => $moneyAccount->getCurrencyName(),
                    'type' => $moneyAccount->getType(),
                ]);
            }
        }

        return $moneyAccountList;
    }

    public function moneyAccountList($start, $limit, $sortCol, $sortDir, $search, $all, $userId = 0)
    {
        $moneyAccountDao = $this->getMoneyAccountDao();

        return $moneyAccountDao->moneyAccountList($start, $limit, $sortCol, $sortDir, $search, $all, $userId);
    }

    public function moneyAccountCount($search, $all)
    {
        $moneyAccountDao = $this->getMoneyAccountDao();

        return $moneyAccountDao->moneyAccountCount($search, $all);
    }

	/**
	 * Get active Money Accounts and selected one if parameter $also present even though it is not active
	 *
	 * @param bool|int $also Keep in view selected record even though it is not active
	 * @return \DDD\Domain\MoneyAccount\MoneyAccount[]|\ArrayObject
	 */
	public function getActiveMoneyAccounts($also = false) {
		$moneyAccountDao = $this->getMoneyAccountDao();

		return $moneyAccountDao->getActiveMoneyAccounts($also);
	}

    /**
     * @return array[]|array
     */
    public function getActiveMoneyAccountList() {
        /** @var BackofficeAuthenticationService $authenticationService */
        $authService = $this->getServiceLocator()->get('library_backoffice_auth');

        if ($authService->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER)) {
            $userId = 0;
        } else {
            $userId = $authService->getIdentity()->id;
        }

        $moneyAccountDao = $this->getMoneyAccountDao();
        $moneyAccounts = $moneyAccountDao->getActiveMoneyAccountsWithCurrencyAndBank($userId);
        $moneyAccountList = [];

        if ($moneyAccounts->count()) {
            foreach ($moneyAccounts as $moneyAccount) {
                array_push($moneyAccountList, [
                    'id' => $moneyAccount['id'],
                    'name' => $moneyAccount['name'],
                    'bank_name' => $moneyAccount['bank_name'],
                    'currency' => $moneyAccount['currency_name'],
                    'access' => !empty($moneyAccount['money_account_user_id']) || $authService->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER) ? 'true' : 'false',
                ]);
            }
        }

        return $moneyAccountList;
    }

    /**
     * @param int $userId
     * @param int $posessionType
     * @return array
     */
    public function getUserMoneyAccountListByPosession($userId, $posessionType)
    {
        /** @var BackofficeAuthenticationService $authService */
        $authService = $this->getServiceLocator()->get('library_backoffice_auth');
        $moneyAccountDao = $this->getMoneyAccountDao();

        if ($authService->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER)) {
            $userId = 0;
        }

        $moneyAccounts = $moneyAccountDao->getUserMoneyAccountsByPosession($userId, $posessionType);
        $moneyAccountList = [];

        if ($moneyAccounts->count()) {
            foreach ($moneyAccounts as $moneyAccount) {
                array_push($moneyAccountList, [
                    'id' => $moneyAccount->getId(),
                    'name' => $moneyAccount->getName(),
                    'bank_name' => $moneyAccount->getBankName(),
                    'currency' => $moneyAccount->getCurrencyName(),
                ]);
            }
        }

        return $moneyAccountList;
    }

	/**
	 * @return int[]|array
	 */
	public function getInactiveMoneyAccountSimpleList() {
		$moneyAccountDao = $this->getMoneyAccountDao();
		$moneyAccounts = $moneyAccountDao->fetchAll(['active' => 0], ['id']);
		$moneyAccountList = [];

		if ($moneyAccounts->count()) {
			foreach ($moneyAccounts as $moneyAccount) {
				array_push($moneyAccountList, $moneyAccount->getId());
			}
		}

		return $moneyAccountList;
	}

    /**
     * @param $moneyAccountId
     * @return array
     * @throws \Exception
     */
    public function getMoneyAccountData($moneyAccountId)
    {
        $moneyAccountDao   = $this->getMoneyAccountDao();
        $bankAccountDomain = $moneyAccountDao->getMoneyAccountById($moneyAccountId);

		if (!$bankAccountDomain) {
			throw new \Exception('No money account found.');
		}

        return $bankAccountDomain;;
	}

    /**
     * @param int $moneyAccountId
     * @return bool|\ArrayObject
     */
    public function getMoneyAccountName($moneyAccountId)
    {
        /**
         * @var \DDD\Domain\MoneyAccount\MoneyAccount|bool $result
         */
        $moneyAccountDao = $this->getMoneyAccountDao();
        $result = $moneyAccountDao->fetchOne(['id' => $moneyAccountId], ['name']);

        if ($result) {
            return $result->getName();
        }

        return '';
    }

	/**
	 * @param \ArrayObject $postData
	 * @param null|int $moneyAccountId
	 * @param int $creatorId
	 *
	 * @return bool
	 */
	public function saveMoneyAccount($postData, &$moneyAccountId, $creatorId) {
		try {
			// Remove submit button data
			if (isset($postData['save'])) {
				unset($postData['save']);
			}

			// Remove view_transactions data
			if (isset($postData['view_transactions'])) {
				$viewTransactions = $postData['view_transactions'];
				unset($postData['view_transactions']);
			}

			// Remove add_transactions data
			if (isset($postData['add_transactions'])) {
				$addTransactions = $postData['add_transactions'];
				unset($postData['add_transactions']);
			}

			// Remove manage_transactions data
			if (isset($postData['manage_transactions'])) {
				$manageTransactions = $postData['manage_transactions'];
				unset($postData['manage_transactions']);
			}

			// Remove manage_transactions data
			if (isset($postData['manage_account'])) {
				$accountManagers = $postData['manage_account'];
				unset($postData['manage_account']);
			}

            $finance = new Finance($this->getServiceLocator());
            $moneyAccount = $finance->getMoneyAccount($moneyAccountId);
            $postData = iterator_to_array($postData);

            if ($moneyAccountId) {
                $moneyAccount->save($postData);
            } else {
                // Bank account should be added as active
                $postData['active'] = 1;
                $moneyAccountId = $moneyAccount->create($postData);
            }

			// Delete all relations on edit
			if ($moneyAccountId) {
				$this->getMoneyAccountUsersDao()->delete([
					'money_account_id' => $moneyAccountId,
				]);
			}

			// Save Money Account <-> User relations for transaction viewers
			if (isset($viewTransactions) && is_array($viewTransactions)) {
				foreach ($viewTransactions as $userId) {
					// Create new stack of relations
					$this->getMoneyAccountUsersDao()->save([
                        'money_account_id' => $moneyAccountId,
                        'user_id'          => $userId,
                        'operation_type'   => self::OPERATION_VIEW_TRANSACTION,
					]);
				}
			}

			// Save Money Account <-> User relations for transaction ceators
			if (isset($addTransactions) && is_array($addTransactions)) {
				foreach ($addTransactions as $userId) {
					$this->getMoneyAccountUsersDao()->save([
                        'money_account_id' => $moneyAccountId,
                        'user_id'          => $userId,
                        'operation_type'   => self::OPERATION_ADD_TRANSACTION,
					]);
				}
			}

			// Save Money Account <-> User relations for transaction managers
			$isset = false;

			if (isset($manageTransactions) && is_array($manageTransactions)) {
				foreach ($manageTransactions as $userId) {
					if ($userId == $creatorId) {
						$isset = true;
					}

					$this->getMoneyAccountUsersDao()->save([
                        'money_account_id' => $moneyAccountId,
                        'user_id'          => $userId,
                        'operation_type'   => self::OPERATION_MANAGE_TRANSACTION,
					]);
				}
			}

			// Save Money Account <-> User relations for account managers
			$isset = false;

			if (isset($accountManagers) && is_array($accountManagers)) {
				foreach ($accountManagers as $userId) {
					if ($userId == $creatorId) {
						$isset = true;
					}

					$this->getMoneyAccountUsersDao()->save([
                        'money_account_id' => $moneyAccountId,
                        'user_id'          => $userId,
                        'operation_type'   => self::OPERATION_MANAGE_ACCOUNT,
					]);
				}
			}

            // Save creator as account manager
			if (!$isset && !$moneyAccountId) {
				$this->getMoneyAccountUsersDao()->save([
                    'money_account_id' => $moneyAccountId,
                    'user_id'          => $creatorId,
                    'operation_type'   => self::OPERATION_MANAGE_ACCOUNT,
				]);
			}
		} catch (\Exception $ex) {
			return false;
		}

		return true;
	}

    /**
     * @param $userId
     * @param $bankAccountId
     * @return array|\ArrayObject|null
     */
    public function checkManagerExistence($userId, $bankAccountId)
    {
		$moneyAccountDao = $this->getMoneyAccountUsersDao();

		return $moneyAccountDao->fetchOne([
			'money_account_id' => $bankAccountId,
			'user_id' => $userId,
			'operation_type' => self::OPERATION_MANAGE_TRANSACTION,
		]);
	}

    /**
     * @param Form $form
     * @param int $moneyAccountId
     * @return Form
     */
    public function fillData($form, $moneyAccountId)
    {
        /** @var UserManager $userDao */
        $userDao = new UserManager($this->getServiceLocator(), '\ArrayObject');

		if ($moneyAccountId) {
			$users = $this->getMoneyAccountUsersInOperationTypes($moneyAccountId);

            $usersForView = $form->get('view_transactions')->getOption('value_options');
            if (isset($users[self::OPERATION_VIEW_TRANSACTION]) && count($users[self::OPERATION_VIEW_TRANSACTION])) {
                foreach ($users[self::OPERATION_VIEW_TRANSACTION] as $relUserId) {
                    if (!isset($usersForView[$relUserId])) {
                        $userDomain = $userDao->getUserById($relUserId, true);
                        $usersForView[$relUserId] = $userDomain['firstname'] . ' ' . $userDomain['lastname'];

                        $form->get('view_transactions')->setOptions(['value_options' => $usersForView]);
                    }
                }
            }

            $usersForAdd = $form->get('add_transactions')->getOption('value_options');
            if (isset($users[self::OPERATION_ADD_TRANSACTION]) && count($users[self::OPERATION_ADD_TRANSACTION])) {
                foreach ($users[self::OPERATION_ADD_TRANSACTION] as $relUserId) {
                    if (!isset($usersForAdd[$relUserId])) {
                        $userDomain = $userDao->getUserById($relUserId, true);
                        $usersForAdd[$relUserId] = $userDomain['firstname'] . ' ' . $userDomain['lastname'];

                        $form->get('add_transactions')->setOptions(['value_options' => $usersForAdd]);
                    }
                }
            }

            $transactionManagers = $form->get('manage_transactions')->getOption('value_options');
            if (isset($users[self::OPERATION_MANAGE_TRANSACTION]) && count($users[self::OPERATION_MANAGE_TRANSACTION])) {
                foreach ($users[self::OPERATION_MANAGE_TRANSACTION] as $relUserId) {
                    if (!isset($transactionManagers[$relUserId])) {
                        $userDomain = $userDao->getUserById($relUserId, true);
                        $transactionManagers[$relUserId] = $userDomain['firstname'] . ' ' . $userDomain['lastname'];

                        $form->get('manage_transactions')->setOptions(['value_options' => $transactionManagers]);
                    }
                }
            }

            $accountManagers = $form->get('manage_transactions')->getOption('value_options');
            if (isset($users[self::OPERATION_MANAGE_ACCOUNT]) && count($users[self::OPERATION_MANAGE_ACCOUNT])) {
                foreach ($users[self::OPERATION_MANAGE_ACCOUNT] as $relUserId) {
                    if (!isset($accountManagers[$relUserId])) {
                        $userDomain = $userDao->getUserById($relUserId, true);
                        $accountManagers[$relUserId] = $userDomain['firstname'] . ' ' . $userDomain['lastname'];

                        $form->get('manage_transactions')->setOptions(['value_options' => $accountManagers]);
                    }
                }
            }

			$form->setData([
				'view_transactions' => isset($users[self::OPERATION_VIEW_TRANSACTION]) ? $users[self::OPERATION_VIEW_TRANSACTION] : [],
				'add_transactions' => isset($users[self::OPERATION_ADD_TRANSACTION]) ? $users[self::OPERATION_ADD_TRANSACTION] : [],
				'manage_transactions' => isset($users[self::OPERATION_MANAGE_TRANSACTION]) ? $users[self::OPERATION_MANAGE_TRANSACTION] : [],
				'manage_account' => isset($users[self::OPERATION_MANAGE_ACCOUNT]) ? $users[self::OPERATION_MANAGE_ACCOUNT] : [],
			]);
		}

		return $form;
	}

	/**
	 * @param int $moneyAccountId
	 * @param int $status
	 * @return bool
	 */
	public function activateMoneyAccount($moneyAccountId, $status)
    {
		try {
			$this->getMoneyAccountDao()->save([
				'active' => $status,
			], ['id' => $moneyAccountId]);
		} catch (\Exception $ex) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public static function getMoneyAccountTypes()
    {
		return [
			self::TYPE_PERSON => 'Person',
			self::TYPE_BANK => 'Current',
			self::TYPE_CREDIT_CARD => 'Credit Card',
			self::TYPE_SAVINGS => 'Savings',
			self::TYPE_DEBIT_CARD => 'Debit Card',
		];
	}

	/**
	 * @param string $like
	 * @return bool|int
	 */
	public static function getMoneyAccountLike($like)
	{
		$bankAccounts = self::getMoneyAccountTypes();

		foreach ($bankAccounts as $key => $value) {
			if (strpos(strtolower($value), strtolower($like)) !== false) {
				return $key;
			}
		}
		return false;
	}


	/**
	 * @param int $moneyAccountId
	 * @return string|bool
	 */
	public static function getMoneyAccountTypeById($moneyAccountId) {
		$bankAccounts = self::getMoneyAccountTypes();

		return (
			isset($bankAccounts[$moneyAccountId])
				? $bankAccounts[$moneyAccountId]
				: false
		);
	}

	/**
	 * @param int $moneyAccountId
	 * @return array
	 */
	protected function getMoneyAccountUsersInOperationTypes($moneyAccountId)
    {
		/**
		 * @var \ArrayObject $data
		 */
		$bankMoneyUsersDao = $this->getMoneyAccountUsersDao();
		$output = [];
		$data = $bankMoneyUsersDao->fetchAll([
			'money_account_id' => $moneyAccountId
		]);

		if ($data->count()) {
			foreach ($data as $relationItem) {
				if (!isset($output[$relationItem['operation_type']])) {
					$output[$relationItem['operation_type']] = [];
				}

				array_push($output[$relationItem['operation_type']], $relationItem['user_id']);
			}
		}

		return $output;
	}

	/**
	 * @return \DDD\Dao\MoneyAccount\MoneyAccount
	 */
	protected function getMoneyAccountDao() {
        if (!$this->moneyAccountDao) {
            $this->moneyAccountDao = $this->getServiceLocator()->get('dao_money_account_money_account');
        }

        return $this->moneyAccountDao;
    }

	/**
	 * @return \DDD\Dao\MoneyAccount\MoneyAccount
	 */
	protected function getMoneyAccountUsersDao() {
		if (!$this->bankMoneyUsersDao) {
			$this->bankMoneyUsersDao = $this->getServiceLocator()->get('dao_money_account_money_account_users');
		}

		return $this->bankMoneyUsersDao;
	}

    public function combineTransactions($transactionIds)
    {
        /**
         * @var \DDD\Dao\Finance\Transaction\Transactions $moneyTransactionsDao
         * @var \DDD\Dao\Finance\Transaction\ExpenseTransactions $expenseTransactionsDao
         * @var \DDD\Service\Finance\Transaction\BankTransaction $transactionService
         */
        try {
            $moneyTransactionsDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');
            $expenseTransactionsDao = $this->getServiceLocator()->get('dao_finance_transaction_expense_transactions');
            $transactionService = $this->getServiceLocator()->get('service_finance_transaction_bank_transaction');

            $moneyTransactionsDao->beginTransaction();
            $moneyAccountTransactions = $moneyTransactionsDao->getMoneyAccountTransactionsByIds($transactionIds);
            $transactionIdsCopy = $transactionIds;
            array_walk($transactionIdsCopy, function(&$value, $key) {$value = '#' . $value;});
            $combinedMoneyAccountTransaction =
                [
                'amount' => 0,
                'is_verified' => 1,
                ];
            $descriptionPartNotExpenseIds = '';
            $descriptionPartNotExpenseIdsArray = [];
            $descriptionPartExpenseIdsArray = [];
            $descriptionPartExpenseIds = 'Expense Transactions: ';
            $datesArray = [];

            foreach ($moneyAccountTransactions as $moneyAccountTransaction) {
                $desc = $moneyAccountTransaction['description'];

                if (preg_match('/^Expense Transaction #(\d+)$/i', $desc, $matches) ||
                    preg_match('/^Expense transaction\. Ticket id #(\d+)\.$/i', $desc, $matches)) {
                    array_push($descriptionPartExpenseIdsArray, '#' . $matches[1]);
                } else {
                    array_push($descriptionPartNotExpenseIdsArray, $desc);
                }

                $combinedMoneyAccountTransaction['amount'] += $moneyAccountTransaction['amount'];
                $combinedMoneyAccountTransaction['account_id'] = $moneyAccountTransaction['account_id'];
                $combinedMoneyAccountTransaction['currency_id'] = $moneyAccountTransaction['currency_id'];
                $combinedMoneyAccountTransaction['status'] = $moneyAccountTransaction['status'];
                $combinedMoneyAccountTransaction['type'] = $moneyAccountTransaction['type'];
                array_push($datesArray, $moneyAccountTransaction['date']);
            }
            if (!count($descriptionPartExpenseIdsArray)) {
                $descriptionPartExpenseIds = '';
            } else {
                $descriptionPartExpenseIds .= implode(', ', $descriptionPartExpenseIdsArray);
            }
            if (count($descriptionPartNotExpenseIdsArray)) {
                $descriptionPartNotExpenseIds = implode(', ', $descriptionPartNotExpenseIdsArray);
                if (count($descriptionPartExpenseIdsArray)) {
                    $descriptionPartNotExpenseIds .= ', ';
                }
            }
            $combinedMoneyAccountTransaction['description'] = $descriptionPartNotExpenseIds . $descriptionPartExpenseIds;
            $combinedMoneyAccountTransaction['date'] = min($datesArray);
            //deleting chosen ones
            $moneyTransactionsDao->deleteByIds($transactionIds);

            //saving the combined
            $combinedMoneyTransactionId = $moneyTransactionsDao->save($combinedMoneyAccountTransaction);

            //updating expense transactions
            $expenseTransactionsDao->updateMoneyTransactions($transactionIds, $combinedMoneyTransactionId);

            // set as verified transaction
            $transactionService->changeVerifyStatus($combinedMoneyTransactionId, TransactionBase::IS_VERIFIED);

            $moneyTransactionsDao->commitTransaction();
        } catch (\Exception $ex) {
            $moneyTransactionsDao->rollbackTransaction();
            return false;
        }
        return true;
    }

    /**
     * One hell of an amazing method!!!
     * @param $moneyAccountId int
     * @return int
     */
    public function composePermissionsOnMoneyAccount($moneyAccountId)
    {
        /** @var BackofficeAuthenticationService $authenticationService */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        $permissionLevel = self::PERMISSION_NONE;

        // If Global Money Account Manager - give all the permissions.
        if ($authenticationService->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER)) {
            $permissionLevel = self::PERMISSION_ALL;
        // If has creator role and is in process of money account creation.
        } else if (!$moneyAccountId) {
            if ($authenticationService->hasRole(Roles::ROLE_MONEY_ACCOUNT_CREATOR)) {
                $permissionLevel = self::PERMISSION_ALL;
            } else {
                $permissionLevel = self::PERMISSION_NONE;
            }
        } else {
            $loggedInUserId = $authenticationService->getIdentity()->id;
            $moneyAccountUsers = $this->getMoneyAccountUsersInOperationTypes($moneyAccountId);

            if (in_array($loggedInUserId, $moneyAccountUsers[self::OPERATION_MANAGE_ACCOUNT])) {
                $permissionLevel = self::PERMISSION_ALL;
            } else {
                if (isset($moneyAccountUsers[self::OPERATION_MANAGE_TRANSACTION]) && in_array($loggedInUserId, $moneyAccountUsers[self::OPERATION_MANAGE_TRANSACTION])) {
                    $permissionLevel |= self::PERMISSION_MANAGE_TRANSACTIONS;
                    $permissionLevel |= self::PERMISSION_VIEW_TRANSACTIONS;
                }
                if (isset($moneyAccountUsers[self::OPERATION_VIEW_TRANSACTION]) && in_array($loggedInUserId, $moneyAccountUsers[self::OPERATION_VIEW_TRANSACTION])) {
                    $permissionLevel |= self::PERMISSION_VIEW_TRANSACTIONS;
                }
                if (isset($moneyAccountUsers[self::OPERATION_ADD_TRANSACTION]) && in_array($loggedInUserId, $moneyAccountUsers[self::OPERATION_ADD_TRANSACTION])) {
                    $permissionLevel |= self::PERMISSION_ADD_TRANSACTIONS;
                }
            }
        }

        return $permissionLevel;
    }
}
