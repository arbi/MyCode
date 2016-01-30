<?php

namespace DDD\Domain\Apartment;

/**
 * Domain class to use for apartment performance calculation
 * @final
 * @category core
 * @package domain
 * @subpackage apartment
 *
 * @author Tigran Petrosyan
 */
class NotifyPerformanceApartments
{
    /**
     * Apartment ID
     * @var int
     */
    protected $apartmentId;

    /**
     * Apartment name
     * @var string
     */
    protected $apartmentName;
    
    /**
     * Apartment currency code
     * @var string
     */
    protected $currencyCode;
    
    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->apartmentId      = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->apartmentName 	= (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->currencyCode 	= (isset($data['currency_code'])) ? $data['currency_code'] : null;
    }

    /**
     * @param int $apartmentId
     */
    public function setApartmentId($apartmentId)
    {
        $this->apartmentId = $apartmentId;
    }

    /**
     * @return int
     */
    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    /**
     * @param string $apartmentName
     */
    public function setApartmentName($apartmentName)
    {
        $this->apartmentName = $apartmentName;
    }

    /**
     * @return string
     */
    public function getApartmentName()
    {
        return $this->apartmentName;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }
}
