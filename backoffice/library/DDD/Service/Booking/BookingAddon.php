<?php

namespace DDD\Service\Booking;

use DDD\Domain\Booking\Addons;
use DDD\Service\ServiceBase;
use Library\Utility\Debug;

class BookingAddon extends ServiceBase
{
    const ADDON_TYPE_ACC           = 1;
    const ADDON_TYPE_TAX_TOT       = 2;
    const ADDON_TYPE_TAX_VAT       = 3;
    const ADDON_TYPE_SALES_TAX     = 4;
    const ADDON_TYPE_CITY_TAX      = 5;
    const ADDON_TYPE_PARKING       = 6;
    const ADDON_TYPE_CLEANING_FEE  = 8;
    const ADDON_TYPE_MINIBAR       = 9;
    const ADDON_TYPE_DAMAGE        = 7;
    const ADDON_TYPE_DISCOUNT      = 10;
    const ADDON_TYPE_NIGHT         = 11;
    const ADDON_TYPE_PENALTY       = 12;
    const ADDON_TYPE_COMPENSATION  = 13;
    const ADDON_TYPE_PARKING_NIGHT = 14;
    const ADDON_TYPE_EXTRA_PERSON  = 15;

    public static function getDeprecatedAddonTypes()
    {
        return
            [
                self::ADDON_TYPE_MINIBAR
            ];
    }

	/**
	 * Get addons list
	 * @return Addons[]|\ArrayObject
	 */
	public function getAddons()
    {
        /**
         * @var \DDD\Dao\Booking\Addons $reservationAddonDao
         * @var \DDD\Domain\Booking\Addons[]|\ArrayObject $addons
         */
    	$reservationAddonDao = $this->getServiceLocator()->get('dao_booking_addons');
    	$addons = $reservationAddonDao->getAllAddons();

    	return $addons;
    }

    /**
     * Get addons list
     * @return array
     */
    public function getAddonsArray()
    {
    	$addons = $this->getAddons();
    	$addonsArray = [];

        if ($addons->count()) {
            foreach ($addons as $addon) {
                $addonsArray[$addon->getId()] = [
                    'id' 			=> $addon->getId(),
                    'name' 			=> $addon->getName(),
                    'location_join' => $addon->getLocation_join(),
                    'value' 		=> $addon->getValue(),
                    'currency_id' 	=> $addon->getCurrencyID(),
                    'cname' 		=> $addon->getCurrencyCode(),
                    'currency_rate' => $addon->getCurrencyRate(),
                    'std' 			=> $addon->getStd(),
                    'default_commission' => $addon->getDefaultCommission(),
                ];
            }
        }

    	return $addonsArray;
    }

    public function getAddonsInArray()
    {
        $addons = $this->getAddons();
        $addonList = [];

        if ($addons->count()) {
            foreach ($addons as $addon) {
                $addonList[$addon->getId()] = $addon->getName();
            }
        }

        return $addonList;
    }
}
