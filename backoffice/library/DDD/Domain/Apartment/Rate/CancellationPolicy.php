<?php

namespace DDD\Domain\Apartment\Rate;

/**
 * Class CancellationPolicy
 * @package DDD\Domain\Apartment\Rate
 *
 * @author Tigran Petrosyan
 */
final class CancellationPolicy
{
    /**
     * @var int
     */
    private $rateId;

    /**
     * @var boolean
     */
    private $isRefundable;

    /**
     * @var int
     */
    private $refundableBeforeHours;

    /**
     * Penalty type (percent, fixed amount, nights)
     * @var int
     */
    private $penaltyType;

    /**
     * @var int
     */
    private $penaltyPercent;

    /**
     * @var decimal
     */
    private $penaltyFixedAmount;

    /**
     * @var int
     */
    private $penaltyNights;

	/**
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->rateId                   = (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->isRefundable             = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours    = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->penaltyType              = (isset($data['penalty_type'])) ? $data['penalty_type'] : null;
        $this->penaltyPercent           = (isset($data['penalty_percent'])) ? $data['penalty_percent'] : null;
        $this->penaltyFixedAmount       = (isset($data['penalty_fixed_amount'])) ? $data['penalty_fixed_amount'] : null;
        $this->penaltyNights            = (isset($data['penalty_nights'])) ? $data['penalty_nights'] : null;
    }

    /**
     * @param boolean $isRefundable
     */
    public function setIsRefundable($isRefundable)
    {
        $this->isRefundable = $isRefundable;
    }

    /**
     * @return boolean
     */
    public function getIsRefundable()
    {
        return $this->isRefundable;
    }

    /**
     * @param \DDD\Domain\Apartment\Rate\decimal $penaltyFixedAmount
     */
    public function setPenaltyFixedAmount($penaltyFixedAmount)
    {
        $this->penaltyFixedAmount = $penaltyFixedAmount;
    }

    /**
     * @return \DDD\Domain\Apartment\Rate\decimal
     */
    public function getPenaltyFixedAmount()
    {
        return $this->penaltyFixedAmount;
    }

    /**
     * @param int $penaltyNights
     */
    public function setPenaltyNights($penaltyNights)
    {
        $this->penaltyNights = $penaltyNights;
    }

    /**
     * @return int
     */
    public function getPenaltyNights()
    {
        return $this->penaltyNights;
    }

    /**
     * @param int $penaltyPercent
     */
    public function setPenaltyPercent($penaltyPercent)
    {
        $this->penaltyPercent = $penaltyPercent;
    }

    /**
     * @return int
     */
    public function getPenaltyPercent()
    {
        return $this->penaltyPercent;
    }

    /**
     * @param int $penaltyType
     */
    public function setPenaltyType($penaltyType)
    {
        $this->penaltyType = $penaltyType;
    }

    /**
     * @return int
     */
    public function getPenaltyType()
    {
        return $this->penaltyType;
    }

    /**
     * @param int $rateId
     */
    public function setRateId($rateId)
    {
        $this->rateId = $rateId;
    }

    /**
     * @return int
     */
    public function getRateId()
    {
        return $this->rateId;
    }

    /**
     * @param int $refundableBeforeHours
     */
    public function setRefundableBeforeHours($refundableBeforeHours)
    {
        $this->refundableBeforeHours = $refundableBeforeHours;
    }

    /**
     * @return int
     */
    public function getRefundableBeforeHours()
    {
        return $this->refundableBeforeHours;
    }
}