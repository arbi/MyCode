<?php
namespace DDD\Dao\MoneyAccount;

use Library\Authentication\BackofficeAuthenticationService;
use DDD\Service\MoneyAccount AS MoneyAcService;
use Library\Constants\Roles;
use Library\Utility\Debug;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class MoneyAccount extends TableGatewayManager
{
    protected $table = DbTables::TBL_MONEY_ACCOUNT;

    public function __construct($sm, $domain = 'DDD\Domain\MoneyAccount\MoneyAccount')
    {
        parent::__construct($sm, $domain);
    }

    public function getMoneyAccountOptions($moneyAccountId = null)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	$columns = [
    		'money_account_id' => 'id',
    		'money_account_name' => 'name'
    	];

    	$result = $this->fetchAll(function (Select $select) use($columns, $moneyAccountId) {
    		$select->columns($columns);

    		$select->join(
    			['currency' => DbTables::TBL_CURRENCY],
    			$this->table . '.currency_id = currency.id',
    			['cname' => 'code']
            );

    		$select->where->equalTo($this->table . '.active', 1)->or->equalTo($this->table .'.id', $moneyAccountId);

    		$select->order(
    			[$this->table . '.name ASC']
    		);
    	});
    	return $result;
    }

	/**
	 * @param int|null $status
	 * @return \DDD\Domain\MoneyAccount\MoneyAccount[]|\ArrayObject
	 */
	public function getAllMoneyAccounts($status) {
		return $this->fetchAll(function (Select $select) use ($status) {

			$select->join(
				['currencies' => DbTables::TBL_CURRENCY],
				$this->getTable() . '.currency_id = currencies.id',
				['currency_name' => 'code'],
				Select::JOIN_LEFT
			);

            $select->where([$this->getTable() . '.is_searchable' => 1]);

			if (!is_null($status)) {
				$select->where([$this->getTable() . '.active' => $status]);
			}

			$select->order([$this->getTable() . '.name', $this->getTable() . '.currency_id']);
		});
	}

    public function moneyAccountList($offset, $limit, $sortCol, $sortDir, $like, $all = '1', $userId = 0)
    {
        if ($all === '1') {
            $whereAll = 'AND ' . $this->getTable() . '.active = 1';
        } elseif ($all === '2') {
            $whereAll = 'AND ' . $this->getTable() . '.active = 0';
        } else {
            $whereAll = ' ';
        }

        $columns = ['active', 'name', 'type', 'balance', 'code'];

        $result = $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like, $whereAll, $columns, $userId) {
            $select->join(
                ['currencies' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currencies.id',
                ['currency_name' => 'code'],
                Select::JOIN_LEFT
            );
            if ($userId) {
                $select
                    ->join(
                        ['mat' => DbTables::TBL_MONEY_ACCOUNT_USERS],
                        new Expression($this->getTable() . '.id = mat.money_account_id AND mat.user_id = ' . (int)$userId . ' AND (mat.operation_type = ' . MoneyAcService::OPERATION_VIEW_TRANSACTION . ' OR mat.operation_type = ' . MoneyAcService::OPERATION_MANAGE_TRANSACTION . ')'),
                        ['has_transactions_view' => 'id'],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['mam' => DbTables::TBL_MONEY_ACCOUNT_USERS],
                        new Expression($this->getTable() . '.id = mam.money_account_id AND mam.user_id = ' . (int)$userId . ' AND mam.operation_type = ' . MoneyAcService::OPERATION_MANAGE_ACCOUNT),
                        ['is_manager' => 'id'],
                        Select::JOIN_LEFT
                    );
            }
            $typeQueryAtring = " ";
            if ($like) {
                $type = MoneyAcService::getMoneyAccountLike($like);
                if ($type !== false) {
                    $typeQueryAtring = " OR " . $this->getTable() . ".`type` = " . $type . " ";
                }
            }

            $select->where("(" . $this->getTable() . ".name like '%" . $like . "%'
                OR currencies.code like '%" . $like . "%' " . $typeQueryAtring . ")
                $whereAll");

            $select->order($columns[$sortCol] . ' ' . $sortDir)
                   ->offset((int)$offset)
                   ->limit((int)$limit);
        });

        return $result;
    }

    public function moneyAccountCount($like, $all = '1')
    {
        /**
         * @var \ArrayObject $result
         */
        switch ($all) {
            case '1':
                $whereAll = 'AND ' . $this->getTable() . '.active = 1';
                break;
            case '2':
                $whereAll = 'AND ' . $this->getTable() . '.active = 0';
                break;
            default:
                $whereAll = ' ';
        }

        $result = $this->fetchAll(function (Select $select) use ($like, $whereAll) {

            $select->join(
                ['currencies' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currencies.id',
                ['currency_name' => 'code'],
                Select::JOIN_LEFT
            );
            $typeQueryAtring = " ";
            if ($like) {
                $type = MoneyAcService::getMoneyAccountLike($like);
                if ($type !== false) {
                    $typeQueryAtring = " OR " . $this->getTable() . ".`type` = " . $type . " ";
                }
            }
            $select->where("(" . $this->getTable() . ".name like '%".$like."%'
                OR currencies.code like '%".$like."%' " . $typeQueryAtring .")
                $whereAll");

            $select->columns(['id']);
        });

        return $result->count();
    }

	/**
	 * @param int $userId
	 * @return \ArrayObject
	 */
	public function getActiveMoneyAccountsWithCurrencyAndBank($userId = 0) {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
		$result =  $this->fetchAll(function (Select $select) use ($userId) {
            $select->columns(['id', 'name', 'currency_id']);
            $select->join(
                ['currencies' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currencies.id',
                ['currency_name' => 'code'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['banks' => DbTables::TBL_BANK],
                $this->getTable() . '.bank_id = banks.id',
                ['bank_name' => 'name'],
                Select::JOIN_LEFT
            );

            if ($userId) {
                $select->join(
                    ['mau' => DbTables::TBL_MONEY_ACCOUNT_USERS],
                    new Expression($this->getTable() . '.id = mau.money_account_id AND mau.user_id = ' . $userId . ' AND (mau.operation_type = ' . MoneyAcService::OPERATION_ADD_TRANSACTION . ' OR mau.operation_type = ' . MoneyAcService::OPERATION_MANAGE_ACCOUNT . ')'),
                    ['money_account_user_id' => 'id'],
                    Select::JOIN_LEFT
                );
            }

			$select->where([$this->getTable() . '.active' => 1]);
		})->buffer();
        $this->setEntity($entity);
        return $result;
	}

	/**
	 * @param int $userId
	 * @param int $posessionType
	 * @return ResultSet|\DDD\Domain\MoneyAccount\MoneyAccount[]
	 */
	public function getUserMoneyAccountsByPosession($userId, $posessionType) {
		return $this->fetchAll(function (Select $select) use ($userId, $posessionType) {
            $select->columns(['id', 'name', 'currency_id']);
            $select->join(
                ['currencies' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currencies.id',
                ['currency_name' => 'code'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['banks' => DbTables::TBL_BANK],
                $this->getTable() . '.bank_id = banks.id',
                ['bank_name' => 'name'],
                Select::JOIN_LEFT
            );

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.active', 1);

            if ($userId) {
                $select->join(
                    ['ma_users' => DbTables::TBL_MONEY_ACCOUNT_USERS],
                    $this->getTable() . '.id = ma_users.money_account_id',
                    [],
                    Select::JOIN_LEFT
                );
                $where
                    ->equalTo('ma_users.user_id', $userId)
                    ->NEST
                        ->equalTo('ma_users.operation_type', $posessionType)
                        ->OR
                        ->equalTo('ma_users.operation_type', MoneyAcService::OPERATION_MANAGE_ACCOUNT)
                    ->UNNEST;
            }


			$select->where($where);
		})->buffer();
	}

    /**
     * @param bool|int $also
     * @return \DDD\Domain\MoneyAccount\MoneyAccount[]|\ArrayObject
     */
    public function getActiveMoneyAccounts($also) {
        return $this->fetchAll(function (Select $select) use ($also) {
            $where = new Where();
            $where->equalTo('active', 1);

            $also === false ?: $where->or->equalTo('id', $also);

            $select->where($where);
            $select->order(['name', 'currency_id']);
        });
    }

	/**
	 * @param int $moneyAccountId
	 * @return \ArrayObject
	 */
	public function getMoneyAccountById($moneyAccountId) {
        $this->setEntity(new \ArrayObject());

		return $this->fetchOne(function (Select $select) use ($moneyAccountId) {
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.responsible_person_id = users.id',
                ['responsible_person_name' => new Expression('CONCAT(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users2' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.card_holder_id = users2.id',
                ['card_holder_name' => new Expression('CONCAT(users2.firstname, " ", users2.lastname)')],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $moneyAccountId]);
		});
	}

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getActiveBankList()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'name',
            ]);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['money_code' => 'code']
            );

            $select->where([$this->getTable().'.active' => 1, $this->getTable().'.type' => \DDD\Service\MoneyAccount::TYPE_BANK]);
            $select->order([$this->getTable().'.name ASC']);
        });
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getActivePersonList()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'name',
                'type',
            ]);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['money_code' => 'code']
            );

            $select->where([$this->getTable().'.active' => 1, $this->getTable().'.type' => \DDD\Service\MoneyAccount::TYPE_PERSON]);
            $select->order([$this->getTable().'.name ASC']);
        });
    }
}
