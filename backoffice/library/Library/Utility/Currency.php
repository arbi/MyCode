<?php

namespace Library\Utility;

use Library\DbManager\TableGatewayManager;

class Currency
{
    /**
     * @var \DDD\Dao\Currency\Currency $currencyDao
     */
    private $currencyDao;

    /**
     * @param $dao \DDD\Dao\Currency\Currency
     */
    public function __construct($dao)
    {
        $this->currencyDao = $dao;
    }

    protected function getRate($from, $to)
    {
        $this->currencyDao->setEntity(new \ArrayObject());
        $currencyList = $this->currencyDao->getList();

        $supportedFrom = false;
        $supportedTo = false;

        $toValue = 1;
        $fromValue = 1;

        if (is_int($from) && is_int($to)) {
            $field = 'id';
        } else if (is_string($from) && is_string($to)) {
            $field = 'code';
        } else {
            throw new \Exception('Invalid argument passed to getRate()');
        }

        foreach ($currencyList as $currency) {
            if (is_int($from) && is_int($to)) {
                $likeFromTo = (int)$currency[$field];
            } else {
                $likeFromTo = $currency[$field];
            }

            if ($likeFromTo === $from) {
                $supportedFrom = true;
                $fromValue = $currency['value'];
            }

            if ($likeFromTo === $to) {
                $supportedTo = true;
                $toValue = $currency['value'];
            }

            if ($supportedFrom && $supportedTo) {
                break;
            }
        }

        if (!$supportedFrom) {
            throw new \Exception('Unable to exchange from $from');
        }

        if (!$supportedTo) {
            throw new \Exception('Unable to exchange to $to');
        }

        return $toValue / $fromValue;
    }

    /**
     * @param float $price
     * @param string|int $from
     * @param string|int $to
     *
     * @return float
     */
    public function convert($price, $from, $to)
    {
        $rate = $this->getRate($from, $to);

        return number_format($price * $rate, 2, '.', '');
    }
}
