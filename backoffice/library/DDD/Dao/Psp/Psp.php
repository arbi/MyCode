<?php

namespace DDD\Dao\Psp;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class Psp extends TableGatewayManager
{
    protected $table = DbTables::TBL_PSP;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Finance\Psp\ManagePspTableRow');
    }

    /**
     * @param        $offset
     * @param        $limit
     * @param        $sortCol
     * @param        $sortDir
     * @param        $like
     * @param string $all
     *
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\Finance\Psp\ManagePspTableRow[]
     */
    function getPspList($offset, $limit, $sortCol, $sortDir, $like, $all = '1')
    {
        if ($all === '1') {
            $whereAll = 'AND ' . $this->getTable() . '.active = 1';
        } elseif ($all === '2') {
            $whereAll = 'AND ' . $this->getTable() . '.active = 0';
        } else {
            $whereAll = ' ';
        }

        $sortColumns = [
            'active',
            'short_name',
            'name',
            'money_account_name',
            'batch',
        ];

        return $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like, $whereAll, $sortColumns) {
            $select->columns([
                 'id',
                 'active',
                 'short_name',
                 'name',
                 'batch',
            ]);

            $select->join(
                ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable().'.money_account_id = money_account.id',
                ['money_account_name' => 'name']
            );

            $select->where("(money_account.name like '%".$like."%'
                OR " . $this->getTable() . ".name like '%".$like."%'
                OR short_name like '%".$like."%')
                $whereAll");

            $select
                ->order($sortColumns[$sortCol] . ' ' . $sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
        });
    }

    public function getPspCount($like, $all = '1')
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
            $select->join(array('money_account' => DbTables::TBL_MONEY_ACCOUNT) ,
                $this->getTable().'.money_account_id = money_account.id', array('money_account_name'=>'name'));
            $select->where("(money_account.name like '%".$like."%'
                OR " . $this->getTable() . ".name like '%".$like."%'
                OR short_name like '%".$like."%')
                $whereAll");

            $select->columns(['id']);
        });

        return $result->count();
    }

    /**
    * @param int $pspId
    * @return \ArrayObject
    */
   public function getPspData($pspId)
   {
       $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

       return $this->fetchOne(function (Select $select) use ($pspId) {
           $select->where(['id' => $pspId]);
       });
   }


    public function getPspListForTransaction()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {
            $select
                ->join(
                    ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                    $this->getTable().'.money_account_id = money_account.id',
                    ['money_account_name' => 'name']
                )
                ->join(
                    ['currency' => DbTables::TBL_CURRENCY],
                    'money_account.currency_id = currency.id',
                    ['code']
                );

            $select->where([$this->getTable().'.active' => 1]);
            $select->order([$this->getTable().'.short_name ASC']);
        });

        return $result;
    }

    /**
    * @param int $moneyAccountId
    * @return \ArrayObject
    */
   public function getPspListByMoneyAccountID($moneyAccountId)
   {
       $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

       return $this->fetchAll(function (Select $select) use ($moneyAccountId) {
           $select->where([
               'money_account_id' => $moneyAccountId,
               'active' => 1,
           ]);
           $select->order([$this->getTable().'.short_name ASC']);
       });
   }

   /**
    * @param int $pspId
    * @return \ArrayObject
    */
   public function getPspInActiveBank($pspId)
   {
       $this->setEntity(new \ArrayObject());

       return $this->fetchOne(function (Select $select) use ($pspId) {
            $select
                ->join(
                    ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                    $this->getTable().'.money_account_id = money_account.id',
                    ['money_account_name' => 'name']
                )
                ->join(
                    ['currency' => DbTables::TBL_CURRENCY],
                    'money_account.currency_id = currency.id',
                    ['code']
                );
            $select->where([$this->getTable().'.id' => $pspId, 'money_account.active' => 0]);
       });
   }

    /**
     * @param bool|int $all
     * @param bool|int $selected
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getPsps($all = true, $selected = false)
    {
        return $this->fetchAll(function (Select $select) use ($all, $selected) {
            $where = new Where();
            if (!$all) {
                $where->equalTo('active', 1);
                if ($selected) {
                    $where
                        ->or
                        ->equalTo('id', $selected);
                }
            }
            $select
                ->columns(['id', 'short_name'])
                ->where($where)
                ->order('short_name');
        });
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\Finance\Psp\ManagePspTableRow[]
     */
    public function getBatchPSPs()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns(['id', 'short_name']);
            $select->where([
                'active' => 1,
                'batch' => 1,
            ]);
        });
    }

    /**
     * @param $pspId
     * @return int
     */
    public function getMoneyAccountIdByPspId($pspId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($pspId) {
            $select->columns(['money_account_id']);
            $select->where(['id' => $pspId]);
        });

        return $result['money_account_id'];
    }

    /**
     * @param $pspId
     * @return int
     */
    public function getPspInfo($pspId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($pspId) {
            $select->columns(['name', 'money_account_id']);
            $select->join(
                ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable().'.money_account_id = money_account.id',
                ['currency_id']
            )->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'money_account.currency_id = currency.id',
                ['currency' => 'code']
            );
            $select->where->equalTo($this->getTable() . '.id', $pspId);
        });

        return $result;
    }

}
