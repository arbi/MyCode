<?php

namespace DDD\Service\Apartment;

use DDD\Dao\Apartment\Details as ApartmentDetailsDAO;
use DDD\Dao\Apartment\Main as DaoMain;
use DDD\Service\ServiceBase;
use Library\Utility\Currency;

/**
 * Service class providing methods to work with apartment
 * @author Tigran Petrosyan
 * @package core
 * @subpackage core/service
 */
class Main extends ServiceBase
{
	/**
	 * Get apartment building
	 * @param int $apartmentId
	 * @return string
	 */
	public function getApartmentBuilding($apartmentId)
    {
		$apartmentMainDao = new DaoMain($this->getServiceLocator(), 'ArrayObject');

		$result = $apartmentMainDao->getApartmentBuilding($apartmentId);
        return $result;
	}

    /**
	 * Get apartment apartels
	 * @param int $apartmentId
	 * @return ArrayObject
	 */
	public function getApartmentApartels($apartmentId)
    {
		$apartmentMainDao = new DaoMain($this->getServiceLocator(), 'ArrayObject');

		$result = $apartmentMainDao->getApartmentApartels($apartmentId);
        return $result;
	}

    /**
     * Get apartment status
     * @param int $apartmentId
     * @return int
     */
    public function getApartmentDates($apartmentId)
    {
        $apartmentMainDao =  new DaoMain($this->getServiceLocator(), 'ArrayObject');

        $result = $apartmentMainDao->getApartmentDates($apartmentId);

        return $result;
    }

    /**
     * @param $apartmentId
     * @param $apartmentCurrencyIsoCode
     * @param $guestCurrencyIsoCode
     * @param $checkCurrency
     * @return float|int
     */
    public function getApartmentCleaningFeeInGuestCurrency($apartmentId, $apartmentCurrencyIsoCode, $guestCurrencyIsoCode, $checkCurrency = false)
    {
        /**
         * @var ApartmentDetailsDAO $apartmentDetailsDao
         */
        $apartmentDetailsDao = $this->getServiceLocator()->get('dao_apartment_details');

        $cleaningFee = $apartmentDetailsDao->getCleaningFee($apartmentId);

        if ($cleaningFee) {

            if (!$checkCurrency || $apartmentCurrencyIsoCode == $guestCurrencyIsoCode) {
                return $cleaningFee;
            }

            /**
             * @var \DDD\Dao\Currency\Currency $currencyDao
             */
            $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');
            $currencyConverter = new Currency($currencyDao);

            $cleaningFee = $currencyConverter->convert($cleaningFee, $apartmentCurrencyIsoCode, $guestCurrencyIsoCode);
        } else {
            $cleaningFee = 0;
        }

        return $cleaningFee;
    }
}
