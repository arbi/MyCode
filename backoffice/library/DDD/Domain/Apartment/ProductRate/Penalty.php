<?php

namespace DDD\Domain\Apartment\ProductRate;

/**
 * Class Cubilis
 * @package DDD\Domain\Apartment\ProductRate
 */
class Penalty {
    protected $penalty_percent;

    /**
     * @var int
     */
    protected $penaltyNights;

    /**
     * @var float
     */
    protected $penaltyFixedAmount;

    protected $penalty_type;

    /**
     * @var bool
     */
    protected $isRefundable;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    public function exchangeArray($data)
    {
        $this->penalty_percent = isset($data['penalty_percent']) ? $data['penalty_percent'] : null;
        $this->penaltyNights = isset($data['penalty_nights']) ? $data['penalty_nights'] : null;
        $this->penaltyFixedAmount = isset($data['penalty_fixed_amount']) ? $data['penalty_fixed_amount'] : null;
        $this->penalty_type = isset($data['penalty_type']) ? $data['penalty_type'] : null;
        $this->isRefundable = isset($data['is_refundable']) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours = isset($data['refundable_before_hours']) ? $data['refundable_before_hours'] : null;
    }

	public function getPenaltyPercent()
    {
		return $this->penalty_percent;
	}

	public function getPenaltyType()
    {
		return $this->penalty_type;
	}

    public function setPenaltyType($penalty_type)
    {
        $this->penalty_type = $penalty_type;
    }

    public function setPenaltyAmount($penalty_type)
    {
        $this->penalty_type = $penalty_type;
    }

    public function setPenaltyPercent($penalty_percent)
    {
        $this->penalty_percent = $penalty_percent;
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

    /**
     * @return int
     */
    public function getPenaltyNights()
    {
        return $this->penaltyNights;
    }

    public function setPenaltyNights($penaltyNights)
    {
        $this->penaltyNights = $penaltyNights;
    }
    /**
     * @return float
     */
    public function getPenaltyFixedAmount()
    {
        return $this->penaltyFixedAmount;
    }

    public function setPenaltyFixedAmount($penaltyFixedAmount)
    {
        $this->penaltyFixedAmount = $penaltyFixedAmount;
    }
}
