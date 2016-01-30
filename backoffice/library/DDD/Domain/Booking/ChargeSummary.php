<?php

namespace DDD\Domain\Booking;

/**
 * Domain class to hold total amount of charges both in apartment and customer currencies
 * @author Tigran Petrosyan
 */
class ChargeSummary
{
	/**
	 * Total amount of charges in apartment currency
	 * @var number
	 */
    protected $summaryInApartmentCurrency;
    
    /**
     * Total amount of "ginosi collect" charges partner commissions in apartment currency
     * @var number
     */
    protected $commissionSummaryInApartmentCurrency;
    
    /**
     * Total amount of "ginosi collect" charges partner commissions in customer currency
     * @var number
     */
    protected $commissionSummaryInCustomerCurrency;
    
    /**
     * Total amount of charges in customer currency
     * @var number
     */
    protected $summaryInCustomerCurrency;
    
    /**
     * @var number
     */
    protected $totalInApartmentCurrency;
    
    /**
     * @var number
     */
    protected $totalInCustomerCurrency;
    
    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->summaryInApartmentCurrency = (isset($data['summary_apartment_currency'])) ? $data['summary_apartment_currency'] : null;
        $this->commissionSummaryInApartmentCurrency = (isset($data['commission_summary_apartment_currency'])) ? $data['commission_summary_apartment_currency'] : null;
        $this->commissionSummaryInCustomerCurrency = (isset($data['commission_summary_customer_currency'])) ? $data['commission_summary_customer_currency'] : null;
        $this->summaryInCustomerCurrency = (isset($data['summary_customer_currency'])) ? $data['summary_customer_currency'] : null;
        $this->totalInApartmentCurrency = (isset($data['total_apartment_currency'])) ? $data['total_apartment_currency'] : null;
        $this->totalInCustomerCurrency = (isset($data['total_customer_currency'])) ? $data['total_customer_currency'] : null;
    }
  
    /**
     * Returns total amount of charges in apartment currency
     * @return number
     */
  	public function getSummaryInApartmentCurrency() {
      	return number_format($this->summaryInApartmentCurrency, 2, '.', '');
  	}   
  	
  	/**
  	 * Returns total amount of "ginosi collect" charges partner commissions in apartment currency
  	 * @return number
  	 */
  	public function getCommissionSummaryInApartmentCurrency() {
  		return number_format($this->commissionSummaryInApartmentCurrency, 2, '.', '');
  	}
  
  	/**
  	 * Returns total amount of "ginosi collect" charges partner commissions in customer currency
  	 * @return number
  	 */
  	public function getCommissionSummaryInCustomerCurrency() {
  		return number_format($this->commissionSummaryInCustomerCurrency, 2, '.', '');
  	}
  	
  	/**
  	 * Returns total amount of charges in customer currency
  	 * @return number
  	 */
  	public function getSummaryInCustomerCurrency() {
      	return number_format($this->summaryInCustomerCurrency, 2, '.', '');
  	}
  	
  	public function getTotalInApartmentCurrency() {
  		return number_format($this->totalInApartmentCurrency, 2, '.', '');
  	}
  	
  	public function getTotalInCustomerCurrency() {
  		return number_format($this->totalInCustomerCurrency, 2, '.', '');
  	}
}