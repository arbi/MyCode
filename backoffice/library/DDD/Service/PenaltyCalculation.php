<?php

namespace DDD\Service;

use DDD\Dao\Booking\Booking as BookingForPenalty;
use DDD\Domain\Apartment\ProductRate\Penalty;
use DDD\Service\Apartment\Rate;
use DDD\Service\ServiceBase;
use Library\Constants\Constants;
use Library\Constants\Objects;
use Library\Constants\DbTables;
use Library\Upload\Images;
use Library\Utility\Debug;
use Library\Utility\Helper;

/**
 * Class PenaltyCalculation
 * @package DDD\Service
 */
class PenaltyCalculation extends ServiceBase {
	/**
	 * @param int $productRateId
	 * @param array $dates
	 * @param int $roomCount
	 * @param float $price
	 * @see \DDD\Service\ChannelManager::getDates()
	 * @return array
	 * <pre>
	 * array(
	 *    'cancel' => $cancel,
	 *    'penalty' => $penalty,
	 *    'currency_rate' => $currencyRate,
	 *    'penalty_fixed_amount' => $penaltyAmount,
	 *    'refundable_before_hours' => $refBeforeHours,
	 *    'penalty_val' => $penaltyValue,
	 * )
	 * </pre>
	 */
	public function getPenalty($productRateId, $dates, $roomCount, $price, $resNumber = false) {

        if($resNumber) { // for charging
            $bookingDao = new BookingForPenalty($this->getServiceLocator(), 'DDD\Domain\Apartment\ProductRate\Penalty');
            $productRateDomain = $bookingDao->getRateDataForReservation($resNumber);
        } else { // for reservation
            /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
            $productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');
            $productRateDomain = $productRateDao->getRateById($productRateId);
        }
        
		$dayCount = $this->dateDiff($dates['date_from'], $dates['date_to']);
		$penaltyValue = $this->getPenaltyValue($productRateDomain, $dayCount);

		if ($productRateDomain->getPenaltyType() == Rate::APARTMENT_RATE_NON_REFUNDABLE) {
			$penaltyValue *= $roomCount;
		} else {

        }

		return [
			'is_refundable' => $productRateDomain->getIsRefundable(),
			'penalty' => $productRateDomain->getPenaltyType(),
			'penalty_fixed_amount' => ($productRateDomain->getIsRefundable() == Rate::APARTMENT_RATE_NON_REFUNDABLE) ? $price : $this->calculatePenalty($productRateDomain, $dayCount, $price, $roomCount),
			'refundable_before_hours' => ($productRateDomain->getIsRefundable() == Rate::APARTMENT_RATE_REFUNDABLE ? $productRateDomain->getRefundableBeforeHours() : 0),
			'penalty_val' => $penaltyValue,
		];
	}

	/**
	 * @param Penalty $productRateDomain
	 * @param int $nights
	 * @param float $price
	 * @param int $rooms
	 * @return float
	 */
	public function calculatePenalty($productRateDomain, $nights, $price, $rooms) {
		$penalty = 0;

		switch ($productRateDomain->getPenaltyType()) {
			case 1:
				$penalty = $price * $productRateDomain->getPenaltyPercent() / 100;

				break;
			case 2:
				$penalty = $productRateDomain->getPenaltyFixedAmount() * $rooms;

				break;
			case 3:
				if ($nights < $productRateDomain->getPenaltyNights()) {
					$penalty = ($price / $nights) * $nights;
				} else {
					$penalty = ($price / $nights) * $productRateDomain->getPenaltyNights();
				}

				break;
		}

		return $penalty;
	}

	/**
	 * @param Penalty $productRateDomain
	 * @param int $nights
	 * @return float|int
	 */
	private function getPenaltyValue($productRateDomain, $nights) {
		$penaltyValue = '';

		switch ($productRateDomain->getPenaltyType()) {
			case Rate::PENALTY_TYPE_PERCENT:
				$penaltyValue = $productRateDomain->getPenaltyPercent();

				break;
			case Rate::PENALTY_TYPE_FIXED_AMOUNT:
				$penaltyValue = $productRateDomain->getPenaltyFixedAmount();
				break;
			case Rate::PENALTY_TYPE_NIGHTS:
				if ($nights < $productRateDomain->getPenaltyNights()) {
					$penaltyValue = $nights;
				} else {
					$penaltyValue = $productRateDomain->getPenaltyNights();
				}

				break;
		}

		return $penaltyValue;
	}

	private function dateDiff($dateFrom, $dateTo) {
		$dateFrom = new \DateTime($dateFrom);
		$dateTo = new \DateTime($dateTo);

		$interval = $dateFrom->diff($dateTo);

		return $interval->format('%a');
	}
}
