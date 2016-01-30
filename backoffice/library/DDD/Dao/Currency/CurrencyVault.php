<?php

namespace DDD\Dao\Currency;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class CurrencyVault extends TableGatewayManager
{
    protected $table = DbTables::TBL_CURRENCY_VAULT;

    public function __construct($sm, $domain = 'DDD\Domain\Currency\Currency')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * Do not use this method because of useless
     * @deprecated
     * @param $from
     * @param $dateTime
     * @return array|\ArrayObject|null
     */
    public function getCurrencyValueClosestToTheMoment($from, $dateTime)
    {
        return $this->fetchOne(function (Select $select) use ($from, $dateTime) {
            $select->columns(['value'])
                ->order(new Expression('ABS(TIMESTAMPDIFF(SECOND,date,"' . $dateTime . '")) ASC', []))
                ->limit(1);
            $select->where
                ->equalTo('currency_id', $from);

        });
    }

    /**
     * @param string $date
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getExchangeRatesByDate($date)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use ($date) {
            $select->columns(['date', 'value']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['name', 'code', 'symbol'],
                Select::JOIN_LEFT
            );
            $select->where([
                'currency.visible' => 1,
                $this->getTable() . '.date' => $date
            ]);
        });
    }

    /**
     * @param string $date
     * @return bool
     */
    public function isExchangeRatesExistsByDate($date)
    {
        return $this->fetchOne(function(Select $select) use ($date) {
            $select->columns(['id']);
            $select->where->expression("date = ?", [$date]);
        });
    }

    /**
     * @param int $currencyId
     * @param string $range
     * @param int $start
     * @param int $length
     * @param array $order
     * @return \ArrayObject
     */
    public function getCurrencyValuesInRange($currencyId, $range, $start, $length, $order)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($currencyId, $range, $start, $length, $order) {
            $where = new Where();
            $dates = explode(' - ', $range);

            $dateFrom = date('Y-m-d', strtotime($dates[0]));
            $dateTo   = date('Y-m-d', strtotime($dates[1]));

            $where
                ->equalTo($this->getTable() . '.currency_id', $currencyId)
                ->lessThanOrEqualTo($this->getTable() . '.date', $dateTo)
                ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom);
            $columns = ['date', 'value'];
            $orderColumns = ['date', 'value'];

            $orderList = [];
            foreach ($order as $entity) {
                $orderList[] = $orderColumns[$entity['column']] . ' ' . $entity['dir'];
            }

            $select
                ->columns($columns)
                ->where($where)
                ->group('date')
                ->order($orderList)
                ->offset((int)$start)
                ->limit((int)$length);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }
}
