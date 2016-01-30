<?php

namespace DDD\Dao\Finance\Espm;

use Library\Finance\Base\Account;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use DDD\Service\Finance\Budget as BudgetService;
use Zend\Db\Sql\Where;

class Espm extends TableGatewayManager
{
    protected $table = DbTables::TBL_ESPM;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Finance\Espm\Espm');
    }

    /**
     * @param $params
     * @return array
     */
    public function getAllEspms($params)
    {
        $this->setEntity(new \ArrayObject());
        $where = new Where();

        if (isset($params['transaction_account']) && $params['transaction_account']) {
            $where->equalTo($this->getTable() . '.transaction_account_id', $params['transaction_account']);
        }

        if (isset($params['account']) && $params['account']) {
            $where->equalTo($this->getTable() . '.external_account_id', $params['account']);
        }

        if (isset($params['type']) && $params['type']) {
            $where->equalTo($this->getTable() . '.type', $params['type']);
        }

        if (isset($params['status']) && $params['status']) {
            $where->equalTo($this->getTable() . '.status', $params['status']);
        }

        if (isset($params['reason']) && $params['reason']) {
            $where->like($this->getTable() . '.reason',  '%' . $params['reason'] . '%');
        }

        if (isset($params['amount']) && $params['amount']) {
            $where->equalTo($this->getTable() . '.amount',  $params['amount']);
        }

        if (isset($params['is_archived']) && $params['is_archived'] != 2) {
            $where->equalTo($this->getTable() . '.is_archived',  $params['is_archived']);
        }

        $offset  = $params['iDisplayStart'];
        $limit   = $params['iDisplayLength'];
        $sortCol = $params['iSortCol_0'];
        $sortDir = $params['sSortDir_0'];

        $this->setEntity(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $where) {
            $sortColumns = [
                'transaction_account_id',
                'external_account_id',
                'type',
                'status',
                'amount',
                'creator_id',
            ];
            $select->columns([
                'id',
                'transaction_account_id',
                'external_account_id',
                'type',
                'status',
                'amount',
                'creator_id',
                'supplier_name' => new Expression("ifnull(
                            ifnull(b_p.partner_name, s.name),
                            concat(b_u.firstname, ' ', b_u.lastname)
                    )")
            ]);

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['creator' => new Expression('CONCAT(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            )->join(
                ['external_account' => DbTables::TBL_EXTERNAL_ACCOUNT],
                $this->getTable() . '.external_account_id = external_account.id',
                [
                    'external_account_name' => 'name'
                ],
                Select::JOIN_LEFT
            )->join(
                ['t_a' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.transaction_account_id = t_a.id',
                [
                    'transaction_type' => 'type'
                ],
                Select::JOIN_LEFT
            )->join(
                ['b_u' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('t_a.holder_id = b_u.id AND t_a.type=' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            )->join(
                ['s' => DbTables::TBL_SUPPLIERS],
                new Expression('t_a.holder_id = s.id AND t_a.type=' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            )->join(
                ['b_p' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('t_a.holder_id = b_p.gid AND t_a.type=' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            )->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                [
                    'currency_code' => 'code'
                ],
                Select::JOIN_LEFT
            );

            $select->where($where);

            $select
                ->group($this->getTable() . '.id')
                ->order($sortColumns[$sortCol].' '.$sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement   = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount = $statement->execute();
        $row         = $resultCount->current();
        $total       = $row['total'];

        return  [
            'result' => $result,
            'total'  => $total,
        ];
    }

    /**
     * @param $espmId
     * @return array|\ArrayObject|null
     */
   public function getEspmData($espmId)
   {
       $this->setEntity(new \ArrayObject());
       return $this->fetchOne(function (Select $select) use ($espmId) {
           $select->columns([
               'id',
               'amount',
               'currency_id',
               'transaction_account_id',
               'external_account_id',
               'status',
               'type',
               'reason',
               'created_date',
               'date',
               'is_archived',
               'transaction_account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
               'transaction_unique_account_id' => new Expression('
                    ifnull(
                        ifnull(partner.gid, supplier.id), people.id
                    )
                ')
           ]);
           $select->join(
               ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
               $this->getTable() . '.transaction_account_id = accounts.id',
               [
                   'transaction_account_type' => 'type',
               ],
               Select::JOIN_LEFT
           )->join(
               ['people' => DbTables::TBL_BACKOFFICE_USERS],
               new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
               [],
               Select::JOIN_LEFT
           )->join(
               ['supplier' => DbTables::TBL_SUPPLIERS],
               new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
               [],
               Select::JOIN_LEFT
           )->join(
               ['partner' => DbTables::TBL_BOOKING_PARTNERS],
               new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
               [],
               Select::JOIN_LEFT
           )->join(
               ['currency' => DbTables::TBL_CURRENCY],
               $this->getTable() . '.currency_id = currency.id',
               [
                   'currency_code' => 'code'
               ],
               Select::JOIN_LEFT
           )->join(
               ['user' => DbTables::TBL_BACKOFFICE_USERS],
               $this->getTable() . '.creator_id = user.id',
               [
                   'creator' => new Expression("CONCAT(user.firstname, ' ', user.lastname)")
               ],
               Select::JOIN_LEFT
           );
           $select->where->equalTo($this->getTable() . '.id', $espmId);
       });
   }

    /**
     * @return array|\ArrayObject|null
     */
    public function getEspmStatus($espmId)
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($espmId) {
            $select->columns([
                'status'
            ]);

            $select->where->equalTo($this->getTable() . '.id', $espmId);
        });
    }

}
