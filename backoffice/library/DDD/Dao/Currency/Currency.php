<?php

namespace DDD\Dao\Currency;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Currency extends TableGatewayManager
{
    protected $table = DbTables::TBL_CURRENCY;

    public function __construct($sm, $domain = 'DDD\Domain\Currency\Currency') {
        parent::__construct($sm, $domain);
    }

	/**
	 * @return \DDD\Domain\Currency\Currency[]|\ArrayObject
	 */
	public function getForSelect() {
        $prototype = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Currency\Currency);
		$result = $this->fetchAll(function (Select $select) {
			$select->columns([
                'id',
                'name',
                'code',
				'value'
			])->order('name');
		});

        $this->setEntity($prototype);
		return $result;
	}

	public function getList($key = false, $val = false)
    {
        if ($key AND $val
            AND in_array($key, array('id', 'code'))) {
            $result = $this->fetchOne(function (Select $select) use ($key, $val) {
                $select->columns(array(
                    'id',
                    'name',
                    'code',
                    'symbol',
                    'value',
                    'auto_update',
                    'gate',
                    'last_updated'))
                        ->order('name');
                $select->where
                    ->equalTo($key, $val);
            });
        } else {
            $result = $this->fetchAll(function (Select $select) use ($key, $val) {
                if ($key AND $val) {
                    $select->where
                            ->equalTo($key, $val);
                }

                $select->columns(array(
                    'id',
                    'name',
                    'code',
                    'symbol',
                    'value',
                    'auto_update',
                    'gate',
                    'last_updated'))
                        ->order('name');
            });
        }

		return $result;
	}

    /**
     * @return \DDD\Domain\Currency\Currency[]|\ArrayObject
     */
    public function getCurrencyListForSite()
    {
		return $this->fetchAll(function (Select $select) {
			$select->columns([
                'id',
                'code',
				'symbol',
                'name',
				'value',
			]);
            $select->where->equalTo('visible', 1);
            $select->order('ordering');
		});
	}

    /**
     * Returns currency exchange rates by dates. If dates not provided - returns for today.
     *
     * @param array $dateList
     * @param bool $onlyVisible
     *
     * @return \DDD\Domain\Currency\Currency[]|\ArrayObject
     */
    public function getCurrenciesByDates($dateList = [], $onlyVisible = true)
    {
        return $this->fetchAll(function (Select $select) use ($dateList, $onlyVisible) {
            $select->columns(['id', 'code', 'symbol']);
            $select->join(
                ['vault' => DbTables::TBL_CURRENCY_VAULT],
                $this->getTable() . '.id = vault.currency_id',
                ['date', 'value'],
                Select::JOIN_RIGHT
            );

            if (count($dateList)) {
                $select->where->expression('vault.date in ("' . implode('","', $dateList) . '")', []);
            } else {
                $select->where->equalTo('vault.date', date('Y-m-d'));
            }

            if ($onlyVisible) {
                $select->where->equalTo($this->getTable() . '.visible', 1);
            }

            $select->order($this->getTable() . '.ordering');
        });
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAllCurrencies()
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->order('ordering asc');
        })->buffer();
    }

    /**
     * @param $code
     * @return array|\ArrayObject|null
     */
    public function getCurrencyData($code)
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($code) {
            $select->columns([
                'symbol'
            ]);
            $select->where->equalTo('code', $code);
        });
    }
}
