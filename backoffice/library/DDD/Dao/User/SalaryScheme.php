<?php

namespace DDD\Dao\User;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class    SalaryScheme
 * @package DDD\Dao\User
 * @author  Harut Grigoryan
 */
class SalaryScheme extends TableGatewayManager
{
	/**
	 * @var string
	 */
	protected $table = DbTables::TBL_SALARY_SCHEMES;

	/**
	 * Constructor
	 * @access public
	 *
	 * @param ServiceLocatorInterface $sm
	 * @param string $domain
	 */
	public function __construct($sm, $domain = 'DDD\Domain\User\SalaryScheme')
    {
        parent::__construct($sm, $domain);
    }

	/**
	 * @param $id
	 * @return \DDD\Domain\User\SalaryScheme
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
	 * @param  array $params
	 * @return array|\ArrayObject|null
	 */
	public function getSalarySchemesByParams($params = [])
	{
		$result = $this->fetchAll(function (Select $select) use ($params) {
			$where = new Where();

			if (isset($params['status']) && $params['status'] > 0) {
				$where->equalTo($this->getTable() . '.status', $params['status']);
			} else {
				$where->notEqualTo($this->getTable() . '.status', \DDD\Service\User\SalaryScheme::SALARY_SCHEME_STATUS_ARCHIVED);
			}

			if (!empty($params['sort'])) {
				foreach ($params['sort'] as $col => $dir) {
					$select->order($col.' '.$dir);
				}
			}

			$where->equalTo('transactionAccount.id', $params['transactionAccountId']);

			$select
				->join(
					['externalAccount' => DbTables::TBL_EXTERNAL_ACCOUNT],
					$this->getTable() . '.external_account_id = externalAccount.id',
					[]
				)
				->join(
					['transactionAccount' => DbTables::TBL_TRANSACTION_ACCOUNTS],
					'externalAccount.transaction_account_id = transactionAccount.id',
					[]
				)
				->where($where);
		});

		return $result;
	}
}
