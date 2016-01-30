<?php

namespace DDD\Service\Reservation;

use DDD\Dao\Apartment\Rate;
use DDD\Dao\Booking\Booking;
use DDD\Domain\Apartment\ProductRate\Penalty as PenaltyDomain;
use DDD\Domain\Apartment\Rate\CancellationPolicy;
use DDD\Service\PenaltyCalculation;
use DDD\Service\ServiceBase;
use DDD\Service\Apartment\Rate as RateService;
use Library\Utility\Helper;

/**
 * Class WorstCXLPolicySelector
 * @package DDD\Service\Reservation
 *
 * @author Tigran Petrosyan
 */
class WorstCXLPolicySelector extends ServiceBase
{
    /**
     * @param $rates
     * @param $reservationPrice
     * @param $reservationId
     * @param bool $isNew
     * @param bool $isApartel
     * @return array
     */
    public function select($rates, $reservationPrice, $reservationId , $isNew = false, $isApartel = false)
    {
        /**
         * @var Booking $bookingDao
         * @var WorstCXLPolicySelector $policyService
         * @var Rate $rateDao
         * @var \DDD\Dao\Apartel\Rate $apartelRateDao
         */
        $worstPolicyData = [
            'is_refundable' => RateService::APARTMENT_RATE_NON_REFUNDABLE,
            'penalty' => 0,
            'penalty_fixed_amount' => $reservationPrice,
            'refundable_before_hours' => 0,
            'penalty_val' => 0
        ];

        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $ratesWithPolicy = [];

        // Reservation Data
        $reservationPolicyData = $bookingDao->getReservationPolicyData($reservationId);

        if (!$reservationPolicyData) {
            return $worstPolicyData;
        }

        // Existing policy
        if (!$isNew) {
            $ratesWithPolicy[] = [
                'is_refundable' => $reservationPolicyData['is_refundable'],
                'penalty_type' => $reservationPolicyData['penalty'],
                'refundable_before_hours' => $reservationPolicyData['refundable_before_hours'],
                'penalty_val' => $reservationPolicyData['penalty_val']
            ];


        }

        // Incoming Rates
        if (!empty($rates)) {
            if ($isApartel) {
                $apartelRateDao = $this->getServiceLocator()->get('dao_apartel_rate');
                $ratesData = $apartelRateDao->getRatesPolicyData($rates);
            } else {
                $rateDao = $this->getServiceLocator()->get('dao_apartment_rate');
                $ratesData = $rateDao->getRatesPolicyData($rates);
            }

            if ($ratesData->count()) {
                foreach ($ratesData as $row) {
                    $ratesWithPolicy[] = [
                        'is_refundable' => $row['is_refundable'],
                        'penalty_type' => $row['penalty_type'],
                        'refundable_before_hours' => $row['refundable_before_hours'],
                        'penalty_val' => ($row['penalty_type'] == RateService::PENALTY_TYPE_PERCENT ? $row['penalty_percent']
                                            : ($row['penalty_type'] == RateService::PENALTY_TYPE_FIXED_AMOUNT ? $row['penalty_fixed_amount']
                                            : $row['penalty_nights'])
                                         )
                    ];
                }
            }
        }

        // Calculate best penalty policy
        $nights = Helper::getDaysFromTwoDate($reservationPolicyData['date_from'], $reservationPolicyData['date_to']);
        $penaltyVal = $penalty =  $penaltyAmount = $refundableBeforeHours = 0;
        $isRefundablePenalty = false;

        foreach ($ratesWithPolicy as $policy) {
            if ($policy['is_refundable'] == RateService::APARTMENT_RATE_NON_REFUNDABLE) {
                //Non Refundable
                return $worstPolicyData;
            } elseif ($policy['is_refundable'] == RateService::APARTMENT_RATE_REFUNDABLE
                      && $reservationPolicyData['penalty_hours'] <= $policy['refundable_before_hours']) {
                // Refundable penalty period
                $penaltyData = $this->penaltyCalculateData([
                                                            'penalty_val' => $policy['penalty_val'],
                                                            'penalty_type' => $policy['penalty_type'],
                                                           ], $nights, $reservationPrice, 1);
                $penaltyAmountNew = $penaltyData['penalty_amount'];

                if ($penaltyAmountNew > $penaltyAmount) {
                    $penalty = $policy['penalty_type'];
                    $penaltyAmount = $penaltyAmountNew;
                    $penaltyVal = $penaltyData['penalty_val'];
                    $refundableBeforeHours = $policy['refundable_before_hours'];
                }
                $isRefundablePenalty = true;
            } elseif($policy['is_refundable'] == RateService::APARTMENT_RATE_REFUNDABLE && !$isRefundablePenalty) {
                // Refundable flexible period, Nothing change, apply what has

                $penaltyData = $this->penaltyCalculateData([
                    'penalty_val' => $policy['penalty_val'],
                    'penalty_type' => $policy['penalty_type'],
                ], $nights, $reservationPrice, 1);
                $penaltyAmountNew = $penaltyData['penalty_amount'];

                if ($penaltyAmountNew > $penaltyAmount) {
                    $penalty = $policy['penalty_type'];
                    $penaltyAmount = $penaltyAmountNew;
                    $penaltyVal = $penaltyData['penalty_val'];
                    $refundableBeforeHours = $policy['refundable_before_hours'];
                }
            }
        }

        $worstPolicyData = [
            'is_refundable' => RateService::APARTMENT_RATE_REFUNDABLE,
            'penalty' => $penalty,
            'penalty_fixed_amount' => $penaltyAmount,
            'refundable_before_hours' => $refundableBeforeHours,
            'penalty_val' => $penaltyVal
        ];
        return $worstPolicyData;
    }

    /**
     * @param array $rateData
     * @param int $nights
     * @param int $price
     * @param int $rooms
     * @return array
     */
    public function penaltyCalculateData($rateData, $nights, $price, $rooms)
    {
        $penalty = 0;
        $penaltyValue = $rateData['penalty_val'];
        switch ($rateData['penalty_type']) {
            case RateService::PENALTY_TYPE_PERCENT:
                $penalty = $price * $rateData['penalty_val'] / 100;
                break;
            case RateService::PENALTY_TYPE_FIXED_AMOUNT:
                $penalty = $rateData['penalty_val'] * $rooms;
                break;
            case RateService::PENALTY_TYPE_NIGHTS:
                if ($nights < $rateData['penalty_val']) {
                    $penalty = ($price / $nights) * $nights;
                    $penaltyValue = $nights;
                } else {
                    $penalty = ($price / $nights) * $rateData['penalty_val'];
                }

                break;
        }
        return ['penalty_amount' => $penalty, 'penalty_val' => $penaltyValue];
    }

    /**
     * @param $penaltyPrice
     * @param $perPrice
     * @param $penaltyType
     * @param $penaltyVal
     * @return float|int
     */
    public function  penaltyCalculatePerNight($penaltyPrice, $perPrice, $penaltyType, $penaltyVal, $nightCount)
    {
        $penalty = 0;
        switch ($penaltyType) {
            case RateService::PENALTY_TYPE_PERCENT:
                $penalty = $perPrice * $penaltyVal / 100;
                break;
            case RateService::PENALTY_TYPE_FIXED_AMOUNT:
                $penalty = $penaltyPrice;
                break;
            case RateService::PENALTY_TYPE_NIGHTS:
                $penalty = $penaltyPrice / $nightCount;
                break;
        }
        return $penalty;
    }
}
