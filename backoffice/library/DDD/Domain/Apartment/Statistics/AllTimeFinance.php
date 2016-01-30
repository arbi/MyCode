<?php

namespace DDD\Domain\Apartment\Statistics;

/**
 * @final
 * 
 * @package core
 * @subpackage core/domain
 * @author Tigran Petrosyan
 */
final class AllTimeFinance
{
	/**
	 * @var int
	 */
    protected $revenue;
    
    /**
     * @var int
     */
    protected $expense;

    /**
     * @var int
     */
    protected $profit;
    
    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->revenue	= (isset($data['revenue'])) ? $data['revenue'] : null;
        $this->expense	= (isset($data['expense'])) ? $data['expense'] : null;
        $this->profit	= (isset($data['profit'])) ? $data['profit'] : null;
    }

    /**
     * Get revenue
     * @access public
     * 
     * @return int
     */
	public function getRevenue() {
		return $this->revenue;
	}
	
	/**
	 * Get all time expenses
	 * @access public
	 *
	 * @return int
	 */
	public function getExpense() {
		return $this->expense;
	}
	
	/**
     * Get all time profit
     * @access public
     * 
     * @return int
     */
	public function getProfit() {
		return $this->profit;
	}
}