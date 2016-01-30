<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Key Instructions Not Viewed Reservations" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class SuspendedApartments {

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
     * Apartment creation date
     * @var string
     */
    protected $dateCreated;

    /**
     * Apartment country
     * @var string
     */
    protected $country;

    /**
     * Apartment city
     * @var string
     */
    protected $city;

    /**
     * Apartment address
     * @var string
     */
    protected $address;

    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->apartmentId 	    = (isset($data['id'])) ? $data['id'] : null;
        $this->apartmentName 	= (isset($data['name'])) ? $data['name'] : null;
        $this->dateCreated 		= (isset($data['create_date'])) ? $data['create_date'] : null;
        $this->country	        = (isset($data['country'])) ? $data['country'] : null;
        $this->city 		    = (isset($data['city'])) ? $data['city'] : null;
        $this->address 		    = (isset($data['address'])) ? $data['address'] : null;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
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
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $dateCreated
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @return string
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return int
     */
    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    /**
     * @param int $apartmentId
     */
    public function setApartmentId($apartmentId)
    {
        $this->apartmentId = $apartmentId;
    }

}

?>
