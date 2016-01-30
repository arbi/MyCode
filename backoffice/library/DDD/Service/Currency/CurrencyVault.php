<?php

namespace DDD\Service\Currency;

use DDD\Service\ServiceBase;

class CurrencyVault extends ServiceBase
{
    /**
     * @param float|string $value Amount
     * @param int|string $from Currency id or code
     * @param int|string $to Currency id or code
     * @param bool|string $dateTime Date/Datetime string or FALSE
     * @return float
     */
    public function convertCurrency($value, $from, $to, $dateTime = false)
    {
        $currencyVaultDao = $this->getServiceLocator()->get('dao_currency_currency_vault');
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');

        if ($dateTime !== false) {
            //even if we have only date we
            $dateTime = date("Y-m-d H:i:s", strtotime($dateTime));
        } else {
            $dateTime = date("Y-m-d H:i:s");
        }

        if (!is_numeric($from)) {
            $rateFrom = $currencyService->getCurrencyList('code', $from);
            $from = $rateFrom->getId();
        }

        if (!is_numeric($to)) {
            $rateTo = $currencyService->getCurrencyList('code', $to);
            $to = $rateTo->getId();
        }

        $resultFrom = $currencyVaultDao->getCurrencyValueClosestToTheMoment($from, $dateTime);
        $resultTo = $currencyVaultDao->getCurrencyValueClosestToTheMoment($to, $dateTime);

        $resultFromValue = $resultFrom->getValue();
        $resultToValue = $resultTo->getValue();

        $priceEuro = $value / $resultFromValue;
        $priceTo = $priceEuro * $resultToValue;

        return $priceTo;
    }

    /**
     * @param null $date
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getExchangeRatesByDate($date = null)
    {
        /**
         * @var \DDD\Dao\Currency\CurrencyVault $currencyVaultDao
         */
        $currencyVaultDao = $this->getServiceLocator()->get('dao_currency_currency_vault');

        if (is_null($date)) {
            $date = date('Y-m-d');

            $this->updateTodaysExchangeRate();
        }

        return $currencyVaultDao->getExchangeRatesByDate($date);
    }

    /**
     * @return void
     */
    public function updateTodaysExchangeRate()
    {
        /**
         * @var \DDD\Dao\Currency\CurrencyVault $currencyVaultDao
         */
        $currencyVaultDao = $this->getServiceLocator()->get('dao_currency_currency_vault');
        $date = date('Y-m-d');

        if (!$currencyVaultDao->isExchangeRatesExistsByDate($date)) {
            shell_exec('ginosole currency update-currency-vault');
        }
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
        /**
         * @var \DDD\Dao\Currency\CurrencyVault $currencyVaultDao
         */
        $currencyVaultDao = $this->getServiceLocator()->get('dao_currency_currency_vault');

        return $currencyVaultDao->getCurrencyValuesInRange($currencyId, $range, $start, $length, $order);
    }
}
