<?php

namespace DDD\Domain\Booking;

class Addons
{
	/**
	 * @var int
	 */
    protected $id;
    
    /**
     * @var string
     */
    protected $name;
    
    
    protected $location_join;
    
    /**
     * @var int
     */
    protected $value;
    
    /**
     * @var int
     */
    protected $currencyID;
    
    /**
     * @var string
     */
    protected $currencyCode;
    
    /**
     * @var float
     */
    protected $currencyRate;
    
    protected $std;

    protected $default_commission;

    public function exchangeArray($data) {
        $this->id     			= (isset($data['id'])) ? $data['id'] : null;
        $this->name 			= (isset($data['name'])) ? $data['name'] : null;
        $this->location_join 	= (isset($data['location_join'])) ? $data['location_join'] : null;
        $this->value 			= (isset($data['value'])) ? $data['value'] : null;
        $this->currencyID 		= (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->currencyCode 	= (isset($data['currency_code'])) ? $data['currency_code'] : null;
        $this->currencyRate 	= (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->std 				= (isset($data['std'])) ? $data['std'] : null;
        $this->default_commission = (isset($data['default_commission'])) ? $data['default_commission'] : null;
    }
    
    public function getDefaultCommission() {
    	return $this->default_commission;
    }

    public function getId() {
    	return $this->id;
    }
    
    public function getName() {
    	return $this->name;
    }
    
    public function getLocation_join() {
    	return $this->location_join;
    }
    
    public function getValue() {
    	return $this->value;
    }
    
    /**
     * @return number
     */
    public function getCurrencyID() {
    	return $this->currencyID;
    }
    
    /**
     * @return string
     */
    public function getCurrencyCode() {
    	return $this->currencyCode;
    }
    
    /**
     * @return number
     */
    public function getCurrencyRate() {
    	return $this->currencyRate;
    }

    public function getStd() {
    	return $this->std;
    }
}