<?php

namespace DDD\Domain\Apartment\Statistics;

/**
 * @author Tigran Petrosyan
 * @final
 * 
 * @package core
 * @subpackage core/domain
 */
final class BookingSources
{
	/**
	 * @var int
	 */
    protected $count;
    
    /**
     * @var string partner name
     */
    protected $partnerName;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->count			= (isset($data['count'])) ? $data['count'] : null;
        $this->partnerName		= (isset($data['partner'])) ? $data['partner'] : null;
    }

    /**
     * Get count of reservations
     * @access public
     * 
     * @return int
     */
	public function getCount() {
		return $this->count;
	}
	
	/**
	 * Get partner name
	 * @access public
	 *
	 * @return string partner name
	 */
	public function getPartnerName() {
		return $this->partnerName;
	}
	
	/**
	 * Alias for getPartnerName()
	 * @access public
	 *
	 * @return string partner name
	 */
	public function getSectionTitle() {
		return $this->getPartnerName();
	}
}