<?php

namespace DDD\Domain\Booking;

/**
 * Class TransactionSummary
 * Domain class to hold total amount of successful transactions both in apartment and customer currencies
 *
 * @package DDD\Domain\Booking
 *
 * @author Tigran Petrosyan
 */
class TransactionSummary
{
	/**
	 * Total amount of transactions in apartment currency
	 * @var number
	 */
    protected $summaryInApartmentCurrency;
    
    /**
     * Total amount of transactions in customer currency
     * @var number
     */
    protected $summaryInCustomerCurrency;
    
    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->summaryInApartmentCurrency = (isset($data['summary_apartment_currency'])) ? $data['summary_apartment_currency'] : null;
        $this->summaryInCustomerCurrency = (isset($data['summary_customer_currency'])) ? $data['summary_customer_currency'] : null;
    }
  
    /**
     * Returns total amount of transactions in apartment currency
     * @return number
     */
  	public function getSummaryInApartmentCurrency() {
      	return number_format($this->summaryInApartmentCurrency, 2, '.', '');
  	}   
  
  	/**
  	 * Returns total amount of transactions in customer currency
  	 * @return number
  	 */
  	public function getSummaryInCustomerCurrency() {
      	return number_format($this->summaryInCustomerCurrency, 2, '.', '');
  	}   
}