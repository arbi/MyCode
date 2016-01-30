<?php

namespace DDD\Domain\Apartment\Cost;

/**
 * Apartment Cost Table Row Domain class
 * @author Tigran Petrosyan
 * @final
 * 
 * @package core
 * @subpackage core/domain
 */
final class CostTableRow
{
	/**
	 * @access private
	 * @var int
	 */
    private $id;
    
    /**
     * @access private
     * @var int
     */
    private $exactCostID;
    
    /**
     * @access private
     * @var int
     */
    private $date;
    
    /**
     * @access private
     * @var int
     */
    private $amount;
    
    /**
     * @access private
     * @var string
     */
    private $category;
    
    /**
     * @access private
     * @var string
     */
    private $purpose;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->exactCostID	= (isset($data['exact_cost_id'])) ? $data['exact_cost_id'] : null;
        $this->date 		= (isset($data['date'])) ? $data['date'] : null;
        $this->amount		= (isset($data['amount'])) ? $data['amount'] : null;
        $this->category		= (isset($data['category'])) ? $data['category'] : null;
        $this->purpose		= (isset($data['purpose'])) ? $data['purpose'] : null;
    }
    
    /**
     * @access public
     * @return int
     */
	public function getID() {
		return $this->id;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getExactCostID() {
		return $this->exactCostID;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getDate() {
		return $this->date;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getAmount() {
		return $this->amount;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getCategory() {
		return $this->category;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getPurpose() {
		return $this->purpose;
	}
}