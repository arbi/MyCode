<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use DDD\Service\Apartment\Rate as ApartmentRateService;
use Library\Constants\TextConstants;

class Rate extends ServiceBase
{

    /**
     * @param $typeId
     * @param $rateId
     * @param $apartelId
     * @return array
     */
    public function getDetailsForForm($typeId, $rateId, $apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\RelTypeApartment $relTypeApartmentDao
         * @var \DDD\Dao\Apartel\Rate $rateDao
         */
        $relTypeApartmentDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $apartmentDetails = $relTypeApartmentDao->getApartmentDetails($typeId);

        // check this rate
        $thisRateIsParent = false;
        if ($rateId) {
            $thisRateIsParent = $rateDao->fetchOne(['id' => $rateId, 'type' => ApartmentRateService::TYPE1]);
        }

        // rate count
        $rateCount = $rateDao->fetchOne(['apartel_type_id' => $typeId], ['id']);

        // parent price
        $parentPrices = $rateDao->getApartelParentRatePrices($typeId);

        $data = [
            'parentPrices' => $parentPrices,
            'type_id' => $typeId,
            'currency' => $apartmentDetails['code'],
            'apartment_max_pax' => $apartmentDetails['capacity'],
            'is_parent' => ($rateId && $thisRateIsParent) || !$rateCount ? true : false,
        ];
        return $data;
    }

    /**
     * @param $rateId
     * @return array
     */
    public function getRateDetails($rateId)
    {
        /**
         * @var \DDD\Dao\Apartel\Rate $rateDao
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $rateData = $rateDao->getRateDetails($rateId);

        $viewData = [];
        // if not parent rate
        if ($rateData['type'] != ApartmentRateService::TYPE1) {
            $viewData['week_price'] = $rateData['week_price'];
            $viewData['weekend_price'] = $rateData['weekend_price'];

            $weekPercent = $rateData['week_percent'];
            $weekendPercent = $rateData['weekend_percent'];

            $viewData['is_week_minus'] = $weekPercent > 0 ? false : true;
            $viewData['is_weekend_minus'] = $weekendPercent > 0 ? false : true;

            $rateData['week_price'] = abs($weekPercent);
            $rateData['weekend_price'] = abs($weekendPercent);
        }

        return [
            'formValue' => $rateData,
            'viewPriceData' => $viewData,
        ];
    }

    /**
     * @param $details
     * @param $apartelId
     * @return array
     */
    public function saveRate($details, $apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\Rate $rateDao
         * @var \DDD\Service\Apartel\Inventory $inventoryService
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $typeId = $details['type_id'];
        $rateId = $details['rate_id'];
        $rateCount = $rateDao->fetchOne(['apartel_type_id' => $typeId], ['id']);
        $isEdit = $rateId;
        $data = [];

        $status = 'success';
        // detect rate type
        if (!isset($details['type']) || !$rateCount) {
            $data['type'] = ApartmentRateService::TYPE1;
            $isParent = true;
        } else {
            $data['type'] = $details['type'];
            $isParent = false;
        }

        //parent rate
        if ($isParent) {

            $weekPrice = $details['week_price'];
            $weekEndPrice = $details['weekend_price'];

        } else {
            // not parent rate
            $weekPricePercent = $details['week_price'];
            $weekendPricePercent = $details['weekend_price'];
            $weekPlusMinus = $details['week_day_plus_minus'];
            $weekendPlusMinus = $details['weekend_plus_minus'];
            $parentRatePrices = $rateDao->getApartelParentRatePrices($typeId);
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
        $data['refundable_before_hours'] = $details['refundable_before_hours'];
        $data['penalty_percent']         = $details['penalty_percent'];
        $data['penalty_nights']          = $details['penalty_nights'];
        $data['penalty_fixed_amount']    = $details['penalty_fixed_amount'];
        $data['name']                    = $details['rate_name'];

        if (isset($details['penalty_type'])) {
            $data['penalty_type'] = $details['penalty_type'];
        }

        if (!$data['active']) {
            $data['cubilis_id'] = null;
        }

        // if is refundable
        if ($details['is_refundable'] == ApartmentRateService::APARTMENT_RATE_NON_REFUNDABLE) {
            $data['penalty_type'] = $data['penalty_percent'] = $data['penalty_nights'] = $data['penalty_fixed_amount'] = 0;
        }

        // check mode
        if ($isEdit) {

            $rateDao->update($data, ['id' => $rateId]);

            // if change parent rate price, should change child rate price
            if ($isParent) {
                $rateDao->updateChildRatePrice($typeId, $data['week_price'], $data['weekend_price']);
            }
            $msg = TextConstants::SUCCESS_UPDATE;
        } else {
            $data['apartel_id'] = $apartelId;
            $data['apartel_type_id'] = $typeId;
            $rateId = $rateDao->save($data);
            $msg = TextConstants::SUCCESS_CREATED;
            $inventoryService = $this->getServiceLocator()->get('service_apartel_inventory');

            // set yearly inventory data
            if (!$inventoryService->updateAvailability(true, $apartelId)) {
                $status = 'error';
                $msg .= TextConstants::ERROR_NOT_UPDATE_AVAILABILITY_APARTEL;
            }
        }

        return [
            'status' => $status,
            'msg' => $msg,
            'type_id' => $typeId,
            'rate_id' => $rateId,
            'isEdit' => $isEdit,
        ];
    }

    /**
     * @param $rateId
     * @return array
     */
    public function deleteRate($rateId)
    {
        /**
         * @var \DDD\Dao\Apartel\Rate $typeDao
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $rateData = $rateDao->fetchOne(['id' => $rateId], ['cubilis_id']);
        if (!$rateData) {
            return ['status' => 'error', 'msg' => TextConstants::BAD_REQUEST];
        }

        // check link to cubilis
        if ($rateData->getCubilisId()) {
            return ['status' => 'error', 'msg' => TextConstants::HAS_CUBILIS_LINK];
        }

        $rateDao->delete(['id' => $rateId]);
        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_DELETE];
    }

}
