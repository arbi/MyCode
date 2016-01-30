<?php

namespace DDD\Domain\Apartment\Rate;

class ForPenalty
{
	
    protected $id;
    protected $penalty_type;
    protected $penalty_percent;

    /**
     * @var float
     */
    protected $penaltyFixedAmount;

    /**
     * @var int
     */
    protected $penaltyNights;
    
    public function exchangeArray($data) {
        $this->id	= (isset($data['id'])) ? $data['id'] : null;
        $this->penalty_type	= (isset($data['penalty_type'])) ? $data['penalty_type'] : null;
        $this->penalty_percent	= (isset($data['penalty_percent'])) ? $data['penalty_percent'] : null;
        $this->penaltyFixedAmount = (isset($data['penalty_fixed_amount'])) ? $data['penalty_fixed_amount'] : null;
        $this->penaltyNights = (isset($data['penalty_nights'])) ? $data['penalty_nights'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getPenalty_type() {
        return $this->penalty_type;
    }

    public function getPenalty_percent() {
        return $this->penalty_percent;
    }

    /**
     * @return int
     */
    public function getPenaltyNights()
    {
        return $this->penaltyNights;
    }

    /**
     * @return float
     */
    public function getPenaltyFixedAmount()
    {
        return $this->penaltyFixedAmount;
    }
}