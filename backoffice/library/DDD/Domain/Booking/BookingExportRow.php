<?php

namespace DDD\Domain\Booking;

/**
 * Domain class to use on CSV export.
 * @final
 * @category core
 * @package domain
 *
 * @author Tigran Petrosyan
 */
class BookingExportRow {
	/**
	 * @access protected
	 * @var int
	 */
    protected $id;

    /**
     * Unique reservation number
     * @access protected
     * @var string
     */
    protected $resNumber;

    /**
     * Affiliate unique id
     * @access protected
     * @var int
     */
    protected $affiliateID;

    /**
     * Reservation status
     * @access protected
     * @var int
     */
    protected $status;

    /**
     * Reservation date
     * @access protected
     * @var string
     */
    protected $reservationDate;

    /**
     * Product name
     * @access protected
     * @var string
     */
    protected $productName;

    /**
     * Apartment Id
     * @access protected
     * @var int
     */
    protected $apartmentIdAssigned;

    /**
     * Apartment Building
     * @access protected
     * @var string
     */
    protected $apartmentBuilding;

    /**
     * Appartment city
     * @access protected
     * @var string
     */
    protected $apartmentCityName;

    /**
     * Guest first name
     * @access protected
     * @var string
     */
    protected $guestFirstName;

    /**
     * Guest last name
     * @access protected
     * @var string
     */
    protected $guestLastName;

    /**
     * Arrival date
     * @access protected
     * @var string
     */
    protected $arrivalDate;

    /**
     * Departure date
     * @access protected
     * @var string
     */
    protected $departureDate;

    /**
     * PAX
     * @access protected
     * @var int
     */
    protected $pax;

    /**
     * @access protected
     * @var string
     */
    protected $rateName;

    /**
     * Reservation total price
     * @access protected
     * @var float
     */
    protected $price;

    /**
     * Reservation normal price
     * @access protected
     * @var float
     */
    protected $normalPrice;

    /**
     * @access protected
     * @var unknown
     */
    protected $apartmentCurrencyCode;

    /**
     * @access protected
     * @var string
     */
    protected $country_name;

    protected $guestCityName;

    protected $IP;

    protected $partner_ref;

    protected $no_collection;

	protected $review_score;
	protected $like;
	protected $dislike;

    /**
     * @var timestamp
     */
    protected $actualArrivalDate;

    /**
     * @var timestamp
     */
    protected $actualDepartureDate;

    protected $apartel;
    protected $partnerName;

    protected $guestBalance;
    protected $partnerBalance;
    protected $isBlacklist;

    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id 					= (isset($data['id'])) ? $data['id'] : null;
        $this->resNumber 			= (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->affiliateID 			= (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->status 				= (isset($data['status'])) ? $data['status'] : null;
        $this->apartel              = (!empty($data['apartel'])) ? $data['apartel'] : (!empty($data['apartel_id']) ? 'Unknown Apartel' : 'Non Apartel');
        $this->reservationDate 		= (isset($data['timestamp'])) ? $data['timestamp'] : null;
        $this->productName 			= (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->apartmentIdAssigned  = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartmentCityName	= (isset($data['acc_city_name'])) ? $data['acc_city_name'] : null;
        $this->apartmentBuilding    = (isset($data['apartment_building'])) ? $data['apartment_building'] : null;
        $this->guestFirstName 		= (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName 		= (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->arrivalDate 			= (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->departureDate		= (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->pax 					= (isset($data['pax'])) ? $data['pax'] : null;
        $this->rateName 			= (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->price 				= (isset($data['price'])) ? $data['price'] : null;
        $this->normalPrice			= (isset($data['normal_price'])) ? $data['normal_price'] : null;
        $this->apartmentCurrencyCode= (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->country_name			= (isset($data['country_name'])) ? $data['country_name'] : null;
        $this->guestCityName        = (isset($data['guest_city_name'])) ? $data['guest_city_name'] : null;
        $this->IP                   = (isset($data['ip_address'])) ? $data['ip_address'] : null;
        $this->partner_ref          = (isset($data['partner_ref'])) ? $data['partner_ref'] : null;
        $this->no_collection        = (isset($data['no_collection'])) ? $data['no_collection'] : null;
	    $this->review_score         = (isset($data['review_score'])) ? $data['review_score'] : null;
	    $this->like                 = (isset($data['like'])) ? $data['like'] : null;
	    $this->dislike              = (isset($data['dislike'])) ? $data['dislike'] : null;
        $this->actualArrivalDate 	= (isset($data['arrival_date'])) ? $data['arrival_date'] : null;
        $this->actualDepartureDate  = (isset($data['departure_date'])) ? $data['departure_date'] : null;
        $this->partnerName          = (isset($data['partner_name'])) ? $data['partner_name'] : null;

        $this->guestBalance         = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->partnerBalance       = (isset($data['partner_balance'])) ? $data['partner_balance'] : null;
        $this->isBlacklist          = (isset($data['is_blacklist'])) ? $data['is_blacklist'] : null;
    }

    public function getApartmentIdAssigned() {
        return $this->apartmentIdAssigned;
    }

	public function getReviewScore() {
		return $this->review_score;
	}

	public function getLike() {
		return $this->like;
	}

	public function getDislike() {
		return $this->dislike;
	}

    public function getNo_collection() {
    	return $this->no_collection;
    }

    public function getIP() {
    	return long2ip($this->IP);
    }

    public function getPartnerRef() {
    	return $this->partner_ref;
    }

    public function getGuestCityName() {
    	return $this->guestCityName;
    }

    public function getCountry_name() {
    	return $this->country_name;
    }

    /**
     * @access public
     * @return string
     */
    public function getReservationNumber()
    {
    	return $this->resNumber;
    }

    /**
     * @access public
     * @return number
     */
    public function getAffiliateID() {
    	return $this->affiliateID;
    }

    /**
     * @access public
     * @return number
     */
    public function getStatus() {
    	return $this->status;
    }

    /**
     * @access public
     * @return string
     */
    public function getReservationDate() {
    	return $this->reservationDate;
    }

    /**
     * @access public
     * @return string
     */
    public function getProductName() {
    	return $this->productName;
    }

    public function getApartmentBuilding() {
    	return $this->apartmentBuilding;
    }

    /**
     * @access public
     * @return string
     */
    public function getApartmentCity() {
    	return $this->apartmentCityName;
    }

    /**
     * @access public
     * @return string
     */
    public function getGuestFullName() {
    	return $this->guestFirstName . ' ' . $this->guestLastName;
    }

    /**
     * @access public
     * @return string
     */
    public function getArrivalDate() {
    	return $this->arrivalDate;
    }

    /**
     * @access public
     * @return string
     */
    public function getDepartureDate() {
    	return $this->departureDate;
    }

    /**
     * @access public
     * @return int
     */
    public function getPAX() {
    	return $this->pax;
    }

    /**
     * @access public
     * @return string
     */
    public function getRateName() {
    	return $this->rateName;
    }

    /**
     * @access public
     * @return number
     */
    public function getPrice() {
    	return $this->price;// . ' ' . $this->currencyCode;
    }

    /**
     * @access public
     * @return number
     */
    public function getNormalPrice() {
    	return $this->normalPrice . ' ' . $this->apartmentCurrencyCode;
    }

    /**
     * @access public
     * @return string
     */
    public function getApartmentCurrencyCode() {
    	return $this->apartmentCurrencyCode;
    }

    public function getActualArrivalDate() {
    	return $this->actualArrivalDate;
    }

    public function getActualDepartureDate() {
    	return $this->actualDepartureDate;
    }

    public function getApartel() {
        return $this->apartel;
    }

    public function getPartnerName() {
        return $this->partnerName;
    }

    public function getAccCityName() {
        return $this->accCityName;
    }

    public function getId() {
        return $this->id;
    }

    public function getGuestBalance() {
        return $this->guestBalance;
    }

    public function getPartnerBalance() {
        return $this->partnerBalance;
    }

    public function isBlacklist() {
        return $this->isBlacklist;
    }
}
