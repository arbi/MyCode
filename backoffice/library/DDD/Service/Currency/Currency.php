<?php
namespace DDD\Service\Currency;

use DDD\Service\ServiceBase;
use Library\Utility\Debug;

/**
 * Currency service
 *
 * @package core
 * @subpackage core_service
 * @author Tigran Petrosyan
 */
class Currency extends ServiceBase
{
    // Default Currency is EURO
    const DEFAULT_CURRENCY = 51;

    private $currencyValues = false;

	/**
	 * @return \DDD\Domain\Currency\Currency[]|\ArrayObject
	 */
	public function getCurrenciesToPopulateSelect() {
		/** @var \DDD\Dao\Currency\Currency $currencyDao */
		$currencyDao = $this->getDao('dao_currency_currency');

		$currencies = $currencyDao->getForSelect();

		return $currencies;
	}

    /**
     * @return array
     */
    public function getSimpleCurrencyList()
    {
        $currencies = $this->getCurrenciesToPopulateSelect();
        $currencyList = [];

        if ($currencies->count()) {
            foreach ($currencies as $currency) {
                $currencyList[$currency->getId()] = $currency->getCode();
            }
        }

        return $currencyList;
    }

    /**
     * @param array $dates
     * @return array|array[]
     */
    public function getActiveCurrencyList($dates)
    {
        $currencyDomain = $this->getActiveCurrencies($dates);
        $currencyList = [];

        if ($currencyDomain->count()) {
            foreach ($currencyDomain as $currency) {
                array_push($currencyList, [
                    'id' => $currency->getId(),
                    'code' => $currency->getCode(),
                    'value' => $currency->getValue(),
                    'symbol' => $currency->getSymbol(),
                ]);
            }
        }

        return $currencyList;
    }

    /**
     * @return \ArrayObject|\DDD\Domain\Currency\Currency[]
     */
    public function getActiveCurrencies()
    {
        $currencyDao = $this->getDao('dao_currency_currency');
        return $currencyDao->getCurrencyListForSite();
    }

    /**
     * @param array $dateList
     * @param bool $onlyVisible
     *
     * @return \ArrayObject|\DDD\Domain\Currency\Currency[]
     */
    public function getCurrenciesByDates($dateList, $onlyVisible = true)
    {
        /**
         * @var CurrencyVault $currencyVaultService
         */
        $currencyDao = $this->getDao('dao_currency_currency');
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');

        $currencyVaultService->updateTodaysExchangeRate();

        if (!count($dateList)) {
            array_push($dateList, date('Y-m-d'));
        }

        $currencies = $currencyDao->getCurrenciesByDates($dateList, $onlyVisible);
        $currencyList = [];

        if ($currencies->count()) {
            foreach ($currencies as $currency) {
                if (!isset($currencyList[$currency->getDate()])) {
                    $currencyList[$currency->getDate()] = [];
                }

                $currencyList[$currency->getDate()][$currency->getCode()] = [
                    'id' => $currency->getId(),
                    'code' => $currency->getCode(),
                    'value' => $currency->getValue(),
                    'symbol' => $currency->getSymbol(),
                ];
            }
        }

        return $currencyList;
    }

    public function getCurrencyList($key = FALSE, $val = FALSE)
    {
	    $currencyDao = $this->getDao('dao_currency_currency');
        return $currencyDao->getList($key, $val);
    }

    public function getCurrency($currencyId)
    {
	    $currencyDao = $this->getDao('dao_currency_currency');
        return $currencyDao->fetchOne(['id' => $currencyId]);
    }

    /**
	 * @param \ArrayObject $postData
	 * @param bool|int $currencyId
	 * @return bool|int
	 */
	public function saveCurrecny($postData, $currencyId = false) {
		try {
			// Remove submit button data
			if (isset($postData['submit'])) {
				unset($postData['submit']);
			}
            // Remove id hidden field
			if (isset($postData['id'])) {
				unset($postData['id']);
			}

            $postData['last_updated'] = date('Y-m-d H:i:s');

			$where = $currencyId ? ['id' => $currencyId] : false;
            $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');
			$lastInsertId = $currencyDao->save($postData, $where);
            return $lastInsertId;
		} catch (\Exception $ex) {
			return false;
		}
	}

    public function updateCurrencyValue($valuesArray, $currency_code)
    {
        $currencyDao = $this->getDao('dao_currency_currency');
        return $currencyDao->save($valuesArray, $currency_code);
    }

    public function convertCurrency($value, $from, $to)
    {
        if (is_numeric($from)) {
            $rateFrom = self::getCurrencyList('id', $from);
        } else {
            $rateFrom = self::getCurrencyList('code', $from);
        }

        if (is_numeric($to)) {
            $rateTo = self::getCurrencyList('id', $to);
        } else {
            $rateTo = self::getCurrencyList('code', $to);
        }

        if ($rateFrom && $rateTo) {
            $rateFrom  = $rateFrom->getValue();
            $rateTo    = $rateTo->getValue();
            $priceEuro = $value / $rateFrom;
            $priceTo   = $priceEuro * $rateTo;
        } else {
            $priceTo = 0;
        }

        return number_format($priceTo, 2, ',', '');
    }

    /**
     * @param $currencyId
     * @return string
     */
    public function getCurrencyIsoCode($currencyId)
    {
        /**
         * @var \DDD\Dao\Currency\Currency $currencyDao
         */
        $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');

        $result = $currencyDao->fetchOne(
            ['id' => $currencyId],
            ['code']
        );

        return $result->getCode();
    }

    /**
     * @param $currencyIsoCode
     * @return string
     */
    public function getCurrencyIDByIsoCode($currencyIsoCode)
    {
        /**
         * @var \DDD\Dao\Currency\Currency $currencyDao
         */
        $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');

        $result = $currencyDao->fetchOne(
            ['code' => $currencyIsoCode],
            ['id']
        );

        return $result->getId();
    }

    /**
     * Calculate Currency Conversion
     *
     * @param string $codeFrom Currency Code.
     * @param string $codeTo Currency Code.
     * @return array
     * @throws \Exception
     */
    public function getCurrencyConversionRate($codeFrom, $codeTo)
    {
        $currencyValues = $this->getCurrencyValues();

        if (!empty($currencyValues[$codeFrom]) && !empty($currencyValues[$codeTo])) {
            return ($currencyValues[$codeFrom] / $currencyValues[$codeTo]);
        } else {
            return 1;
        }
    }

    /**
     * @return array
     */
    public function getCurrencyValues()
    {
        if ($this->currencyValues) {
            return $this->currencyValues;
        } else {
            /** @var \DDD\Dao\Currency\Currency $currencyDao */
            $currencyDao        = $this->getServiceLocator()->get('dao_currency_currency');
            $currencyDomainList = $currencyDao->getForSelect();

            if ($currencyDomainList->count()) {
                $currencyValues = [];

                foreach ($currencyDomainList as $currencyDomain) {
                    $currencyValues[$currencyDomain->getCode()] = $currencyDomain->getValue();
                }

                $this->setCurrencyValues($currencyValues);

                return $currencyValues;
            } else {
                $this->gr2warn("Currencies not found in database");

                return false;
            }
        }
    }

    /**
     * @param array $currencyValues
     */
    public function setCurrencyValues($currencyValues)
    {
        $this->currencyValues = $currencyValues;
    }
}
