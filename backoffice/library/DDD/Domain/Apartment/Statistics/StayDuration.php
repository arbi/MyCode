<?php

namespace DDD\Domain\Apartment\Statistics;

/**
 * @author Tigran Petrosyan
 * @final
 * 
 * @package core
 * @subpackage core/domain
 */
final class StayDuration
{
	/**
	 * @var int
	 */
    protected $oneDayStays;
    
    /**
     * @var int
     */
    protected $twoDayStays;
    
    /**
     * @var int
     */
    protected $treeDayStays;
    
    /**
     * @var int
     */
    protected $fourDayStays;
    
    /**
     * @var int
     */
    protected $fiveDayStays;
    
    /**
     * @var int
     */
    protected $sixDayStays;
    
    /**
     * @var int
     */
    protected $sevenDayStays;
    
    /**
     * @var int
     */
    protected $longStays;
    
    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->oneDayStays		= (isset($data['one'])) ? $data['one'] : null;
        $this->twoDayStays		= (isset($data['two'])) ? $data['two'] : null;
        $this->treeDayStays		= (isset($data['tree'])) ? $data['tree'] : null;
        $this->fourDayStays		= (isset($data['four'])) ? $data['four'] : null;
        $this->fiveDayStays		= (isset($data['five'])) ? $data['five'] : null;
        $this->sixDayStays		= (isset($data['six'])) ? $data['six'] : null;
        $this->sevenDayStays	= (isset($data['seven'])) ? $data['seven'] : null;
        $this->longStays		= (isset($data['long'])) ? $data['long'] : null;
    }
    
	/**
	 * @return the $oneDayStays
	 */
	public function getOneDayStays() {
		return $this->oneDayStays;
	}

	/**
	 * @return the $twoDayStays
	 */
	public function getTwoDayStays() {
		return $this->twoDayStays;
	}

	/**
	 * @return the $treeDayStays
	 */
	public function getTreeDayStays() {
		return $this->treeDayStays;
	}

	/**
	 * @return the $fourDayStays
	 */
	public function getFourDayStays() {
		return $this->fourDayStays;
	}

	/**
	 * @return the $fiveDayStays
	 */
	public function getFiveDayStays() {
		return $this->fiveDayStays;
	}

	/**
	 * @return the $sixDayStays
	 */
	public function getSixDayStays() {
		return $this->sixDayStays;
	}

	/**
	 * @return the $sevenDayStays
	 */
	public function getSevenDayStays() {
		return $this->sevenDayStays;
	}

	/**
	 * @return the $longStays
	 */
	public function getLongStays() {
		return $this->longStays;
	}
}