<?php

namespace DDD\Domain\Accommodation;

/**
 * Specific domain to use in product search
 * @author Tigran Petrosyan
 * @final
 * 
 * @package core
 * @subpackage core/domain
 */
final class ProductFullAddress
{
	/**
	 * @var int
	 */
    protected $id;
    
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var string
     */
    protected $cityName;
    
    /**
     * @var string
     */
    protected $countryName;
    
    /**
     * @var string
     */
    protected $address;
    
    /**
     * @var string
     */
    protected $unitNumber;
    
    /**
     * @var string
     */
    protected $postalCode;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->name 		= (isset($data['name'])) ? $data['name'] : null;
        $this->cityName 	= (isset($data['city'])) ? $data['city'] : null;
        $this->countryName	= (isset($data['country'])) ? $data['country'] : null;
        $this->address		= (isset($data['address'])) ? $data['address'] : null;
        $this->unitNumber	= (isset($data['unit_number'])) ? $data['unit_number'] : null;
        $this->postalCode	= (isset($data['postal_code'])) ? $data['postal_code'] : null;
    }

    /**
     * Get product full address with name
     * @access public
     * 
     * @return string
     */
    public function getFullAddress() {
    	$fullAddress = $this->name
            . ($this->address ? " - " . $this->address : '')
            . ($this->unitNumber ? " Unit " . $this->unitNumber : '')
            . ($this->cityName ? ", " . $this->cityName : '')
            . ($this->postalCode ? " " . $this->postalCode : '')
            . ($this->countryName ? ", " . $this->countryName : '');
    	// For example - Republic Square Experience - Hanrapetutyan 45 apt. 7, Yerevan 0009, Armenia 
    	
    	return $fullAddress;
    }
    
    /**
     * Get product full address
     * @access public
     *
     * @return string
     */
    public function getFullAddressWithoutName() {
    	$fullAddress = $this->address
            . ($this->unitNumber ? " Unit " . $this->unitNumber : '')
            . ($this->cityName ? ", " . $this->cityName : '')
            . ($this->postalCode ? " " . $this->postalCode : '')
            . ($this->countryName ? ", " . $this->countryName : '');
    	// For example - Hanrapetutyan 45 apt. 7, Yerevan 0009, Armenia
    	 
    	return $fullAddress;
    }
    
    /**
     * Get product ID
     * @access public
     * 
     * @return int
     */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get product name
	 * @access public
	 * 
	 * @return string product name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get product city name
	 * @access public
	 * 
	 * @return string city name
	 */
	public function getCityName() {
		return $this->cityName;
	}
	
	/**
	 * Get product country name
	 * @access public
	 *
	 * @return string country name
	 */
	public function getCountryName() {
		return $this->countryName;
	}
    
    public function getPostalCode() {
        return $this->postalCode;
    }
    
    public function getAddress() {
        return $this->address;
    }


}