<?php

namespace DDD\Service\Apartment;

use DDD\Dao\Apartment\Room as ProductRoom;
use DDD\Service\ServiceBase;
use Library\Constants\TextConstants;

/**
 * Service class providing methods to work with apartment rates
 *
 * @package core
 * @subpackage core/service
 *
 *
 * @author Tigran Petrosyan
 */
class Rate extends ServiceBase
{
    const APARTMENT_RATE_REFUNDABLE     = 1;
    const APARTMENT_RATE_NON_REFUNDABLE = 2;

    const PENALTY_TYPE_PERCENT      = 1;
    const PENALTY_TYPE_FIXED_AMOUNT = 2;
    const PENALTY_TYPE_NIGHTS       = 3;

    const TYPE1 = 1;
    const TYPE2 = 2;
    const TYPE3 = 3;
    const TYPE4 = 4;
    const TYPE5 = 5;

    const STATUS_ACTIVE = 1;

    public static function getRateTypes()
    {
        return [
            self::TYPE1 => 'Parent',
            self::TYPE2 => 'Non Refundable',
            self::TYPE3 => 'Easy',
            self::TYPE4 => 'Flexible',
            self::TYPE5 => 'Undefined',
        ];
    }
    /**
     * @param int $apartmentId
     * @return \DDD\Domain\Apartment\Rate\Select[]|\ArrayObject|null
     */
    public function getApartmentRates($apartmentId)
    {
    	$apartmentRateDao = $this->getApartmentRateDao();
    	$rates = $apartmentRateDao->getApartmentRates($apartmentId);

    	return $rates;
    }

    /**
     * @access public
     * @param int $rateID
     * @var $apartmentRateDao \DDD\Dao\Apartment\Rate
     * @return \ArrayObject
     */
    public function getRateDetails($rateID)
    {
    	$apartmentRateDao = $this->getApartmentRateDao();

    	$details = $apartmentRateDao->getRateDetails($rateID);

    	return $details;
    }

    public function saveRate($details, $apartmentId)
    {
        /**
         * @var \DDD\Dao\Apartment\Rate $rateDao
         * @var \DDD\Service\Apartment\Inventory $inventoryService
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartment_rate');
        $inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');
    	$apartmentRateDao = $this->getApartmentRateDao();
	    $rateList = $this->getApartmentRates($apartmentId);

    	$rateID = $details['id'];
    	unset($details['id']);
    	unset($details['save_button']);

	    $editMode = $rateID;

    	$data = [];

        if (!$rateList->count() || (!isset($details['type']) && isset($details['is_parent']) && $details['is_parent'])) {
            $isParent = true;
            $data['type'] = self::TYPE1;
        } else {
            $isParent = false;
            $data['type'] = $details['type'];
        }

        //parent rate
        if ($isParent) {

           $weekPrice = $details['weekday_price'];
           $weekEndPrice = $details['weekend_price'];

        } else { // not parent rate
            $weekPricePercent = $details['weekday_price'];
            $weekendPricePercent = $details['weekend_price'];
            $weekPlusMinus = $details['week_day_plus_minus'];
            $weekendPlusMinus = $details['weekend_plus_minus'];
            $parentRatePrices = $rateDao->getApartmentParentRatePrices($apartmentId);
            $weekPriceParent = $parentRatePrices['week_price'];
            $data['week_percent'] = $weekPricePercent * $weekPlusMinus;
            $weekendPriceParent = $parentRatePrices['weekend_price'];
            $data['weekend_percent'] = $weekendPricePercent * $weekendPlusMinus;


            if ($weekPlusMinus > 0) {
                $weekPrice = $weekPriceParent + $weekPriceParent * $weekPricePercent / 100;
            } else {
                $weekPrice = $weekPriceParent - $weekPriceParent * $weekPricePercent / 100;
            }

            if ($weekendPlusMinus > 0) {
                $weekEndPrice = $weekendPriceParent + $weekendPriceParent * $weekendPricePercent / 100;
            } else {
                $weekEndPrice = $weekendPriceParent - $weekendPriceParent * $weekendPricePercent / 100;
            }
        }

        $data['active']                  = $details['active'];
        $data['capacity']                = $details['capacity'];
        $data['week_price']              = round($weekPrice, 2);
        $data['weekend_price']           = round($weekEndPrice, 2);
        $data['min_stay']                = $details['min_stay'];
        $data['max_stay']                = $details['max_stay'];
        $data['release_period_start']    = $details['release_window_start'];
        $data['release_period_end']      = $details['release_window_end'];
        $data['is_refundable']           = $details['is_refundable'];
        $data['penalty_type']            = $details['penalty_type'];
        $data['refundable_before_hours'] = $details['refundable_before_hours'];

        $data['penalty_percent']         = $details['penalty_percent'];
        $data['penalty_nights']          = $details['penalty_nights'];
        $data['penalty_fixed_amount']    = $details['penalty_fixed_amount'];
        $data['name']                    = $details['rate_name'];

        if (!(int)$details['active']) {
            $data['cubilis_id'] = null;
        }

        if ($details['is_refundable'] == self::APARTMENT_RATE_NON_REFUNDABLE) {
            $data['penalty_type']         = 1;
            $data['penalty_percent']      = 100;
            $data['penalty_nights']       = 0;
            $data['penalty_fixed_amount'] = 1;
    	}

	    if ($editMode) {

		    $apartmentRateDao->save($data, ['id' => $rateID]);

            // if change parent rate price, should change child rate price
            if ($isParent) {
                $rateDao->updateChildRatePrice($apartmentId, $data['week_price'], $data['weekend_price']);
            }
	    } else {
            $roomDao    = new ProductRoom($this->getServiceLocator());
            $roomDomain = $roomDao->getById($apartmentId);

            $data['apartment_id'] = $roomDomain->getApartmentId();
            $data['room_id']      = $roomDomain->getId();

            $rateID = $apartmentRateDao->save($data);
	    }

        // insert data
        if (!$editMode) {
            if (!$inventoryService->updateAvailability(true, $apartmentId)) {
                return ['status' => 'error',  'msg' => 'Rate was successfully updated, but availability table could not be filled.'];
            }
        }

    	return ['status' => 'success', 'msg' => $editMode ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_CREATED, 'rate_id' => $rateID];
    }

    /**
     * Remove rate
     * @access public
     * @param int $rateID
     * @return boolean
     */
    public function deleteRate($rateID)
    {
    	$apartmentRateDao = $this->getApartmentRateDao();
    	$apartmentInventoryDao = $this->getApartmentInventoryDao();

    	$removeAvailabilityResult = $apartmentInventoryDao->delete(["rate_id"=>$rateID]);
    	$removeRateResult = $apartmentRateDao->delete(["id"=>$rateID]);

    	return $removeAvailabilityResult && $removeRateResult;
    }

    /**
     * Activate given rate
     * @access public
     *
     * @param int $rateID
     * @var $apartmentRateDao \DDD\Dao\Apartment\Rate
     * @return boolean
     */
    public function activateRate($rateID)
    {
    	$apartmentRateDao = $this->getApartmentRateDao();

    	$status = 1;
    	$result = $apartmentRateDao->changeRateStatus($rateID, $status);

    	return $result;
    }

    /**
     * Deactivate given rate
     * @access public
     *
     * @param int $rateID
     * @var $apartmentRateDao \DDD\Dao\Apartment\Rate
     * @return boolean
     */
    public function deactivateRate($rateID)
    {
    	$apartmentRateDao = $this->getApartmentRateDao();

    	$status = 0;
    	$result = $apartmentRateDao->changeRateStatus($rateID, $status);

    	return $result;
    }

	/**
	 * @param int $rateID
	 * @param string $year
	 * @param string $month
	 * @return Select[]|\ArrayObject|null
	 */
    public function getRateAvailabilityForMonth($rateID, $year, $month)
    {
    	$firstDay = date('Y-m-d', strtotime('first day of ' . $year . '-' . $month));
    	$lastDay = date('Y-m-d', strtotime('last day of ' . $year . '-' . $month));

    	$apartmentInventoryDao = $this->getApartmentInventoryDao();
    	$monthInventory = $apartmentInventoryDao->getRateInventoryForRange($rateID, $firstDay, $lastDay);

    	return $monthInventory;
    }

	public function isRateConnectedToCubilis($rateId)
    {
        /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
        $productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');
		$productRateDomain = $productRateDao->getCubilisIdByRateId($rateId);

		if ($productRateDomain && !is_null($productRateDomain->getCubilisId())) {
			return true;
		}

		return false;
	}

    /**
     * @param int $rateId
     * @param string $rateName
     * @return bool
     */
    public function checkDuplicateRateName($apartmentId, $rateId, $rateName)
    {
        $apartmentRateDao = $this->getApartmentRateDao();
        $result           = $apartmentRateDao->checkDuplicateRateName($apartmentId, $rateId, $rateName);
        return (bool)$result->count();
    }

    /**
     * @access public
     * @param string $domain
     * @return \DDD\Dao\Apartment\Inventory
     */
    public function getApartmentInventoryDao($domain = '\DDD\Domain\Apartment\Inventory\RateAvailability')
    {
		return new \DDD\Dao\Apartment\Inventory($this->getServiceLocator(), $domain);
	}

	/**
	 * @access public
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Rate
	 */
	public function getApartmentRateDao($domain = '\DDD\Domain\Apartment\Rate\Select')
    {
		return new \DDD\Dao\Apartment\Rate($this->getServiceLocator(), $domain);
	}
}
