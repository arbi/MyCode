<?php

namespace DDD\Dao\User;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class    ExternalAccount
 * @package DDD\Dao\User
 * @author  Harut Grigoryan
 */
class ExternalAccount extends TableGatewayManager
{
	/**
	 * @var string
	 */
	protected $table = DbTables::TBL_EXTERNAL_ACCOUNT;

	/**
	 * Constructor
	 * @access public
	 *
	 * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
	 * @param string $domain
	 */
	public function __construct($sm, $domain = 'DDD\Domain\User\ExternalAccount')
    {
        parent::__construct($sm, $domain);
    }

	/**
	 * Get by params
	 *
	 * @param array $params
	 * @return array|\ArrayObject|null
	 */
	public function getExternalAccountsByParams($params = [])
	{
		$result = $this->fetchAll(function (Select $select) use ($params) {
			$where = new Where();
			$where->equalTo('transaction_account_id', $params['transactionAccountID']);

			if (isset($params['status']) && $params['status'] > 0) {
				$where->equalTo('status', $params['status']);
			}

			if (!empty($params['sort'])) {
				foreach ($params['sort'] as $col => $dir) {
					$select->order($col.' '.$dir);
				}
			}

			if (!empty($params['search'])) {
				$where->like('name', '%' . $params['search'] . '%');
			}

			$select->where($where)
				   ->order('id DESC');
		});

		return $result;
	}

	/**
	 * Get by Primary Key
	 *
	 * @param $id
	 * @return \DDD\Domain\User\ExternalAccount
	 */
	public function getById($id)
	{
		$result = $this->fetchAll(function (Select $select) use ($id) {
			$where = new Where();
			$where->equalTo('id', $id);

			$select->where($where);
		});

		return $result->current();
	}

	/**
	 * Get By Transaction Account ID
	 *
	 * @param  $transactionAccountId
	 * @return \Zend\Db\ResultSet\ResultSet
	 */
	public function getByTransactionAccountId($transactionAccountId)
	{
		$result = $this->fetchAll(function (Select $select) use ($transactionAccountId) {
			$where = new Where();
			$where->equalTo('transaction_account_id', $transactionAccountId);

			$select->where($where)
				   ->order('id DESC');
		});

		return $result;
	}

	/**
	 * Get Active Accounts By Transaction Account ID
	 *
	 * @param  $transactionAccountId
	 * @return \Zend\Db\ResultSet\ResultSet
	 */
	public function getActiveAccountsByTransactionAccountId($transactionAccountId)
	{
		$result = $this->fetchAll(function (Select $select) use ($transactionAccountId) {
			$where = new Where();
			$where->equalTo('transaction_account_id', $transactionAccountId);
			$where->equalTo('status', \DDD\Service\User\ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ACTIVE);

			$select->where($where)
				->order('id DESC');
		});

		return $result;
	}

	/**
	 * @param $supplierId
	 * @return \Zend\Db\ResultSet\ResultSet
	 */
	public function getAccountsBySupplierId($supplierId)
	{
		$this->setEntity(new \ArrayObject());
		$result = $this->fetchAll(function (Select $select) use ($supplierId) {
			$select->columns([
				'id',
				'name'
			]);

			$select->where->equalTo('transaction_account_id', $supplierId);
			$select->order('name ASC');
		});

		return $result;
	}

	/**
	 * Check default account
	 *
	 * @param  $transactionAccountId
	 * @return \DDD\Domain\User\ExternalAccount
	 */
	public function checkDefault($transactionAccountId)
	{
		$result = $this->fetchAll(function (Select $select) use ($transactionAccountId) {
			$select->where->equalTo('transaction_account_id', $transactionAccountId);
			$select->where->equalTo('is_default', \DDD\Service\User\ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT);
		});

		return $result->current();
	}
}
