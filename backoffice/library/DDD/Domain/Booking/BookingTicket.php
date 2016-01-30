<?php

namespace DDD\Domain\Booking;

class BookingTicket
{
    protected $id;
    protected $resNumber;
    protected $guestFirstName;
    protected $guestLastName;
    protected $guestEmail;
    protected $secondaryEmail;
    protected $provide_cc_page_status;
    protected $getGuestLanguageIso;
    protected $provide_cc_page_hash;
    protected $guestCountryId;
    protected $guestCityName;
    protected $guestAddress;
    protected $guestZipCode;
    protected $guestPhone;
    protected $IP;
    protected $res_number;
    protected $partner_id;
    protected $partner_ref;
    protected $channel_name;
    protected $acc_name;
    protected $rate_name;
    protected $occupancy;
    protected $overbookingStatus;
    protected $timestamp;
    protected $date_from;
    protected $date_to;
    protected $guestArrivalTime;
    protected $pax;
    protected $ki_mail_sent;
    protected $outside_door_code;
    protected $pin;
    protected $building;
    protected $unit;
    protected $apartmentAssignedBuilding;
    protected $apartmentAssignedBuildingId;
    protected $apartmentAssignedUnit;
    protected $apartmentOriginBuilding;
    protected $apartmentOriginUnit;
    protected $ki_page_status;
    protected $parking;
    protected $ki_page_hash;
    protected $cancelation_date;
    protected $isRefundable;
    protected $refundableBeforeHours;
    protected $penalty_val;
    protected $penalty;
    protected $apartmentCurrencyCode;
    protected $status;
    protected $apartmentAssigned;
    protected $apartmentIdAssigned;
    protected $apartmentIdOrigin;
    protected $funds_confirmed;
    protected $has_review;
    protected $tot;
    protected $tot_type;
    protected $totIncluded;
    protected $totAdditional;
    protected $totMaxDuration;
    protected $vat;
    protected $vat_type;
    protected $vatIncluded;
    protected $vatAdditional;
    protected $vatMaxDuration;
    protected $sales_tax;
    protected $sales_tax_type;
    protected $salesTaxIncluded;
    protected $salesTaxAdditional;
    protected $salesTaxMaxDuration;
    protected $city_tax;
    protected $city_tax_type;
    protected $cityTaxIncluded;
    protected $cityTaxAdditional;
    protected $cityTaxMaxDuration;
    protected $price;
    protected $currency_rate;
    protected $guestCurrencyCode;
    protected $penaltyFixedAmount;
    protected $customer_currency_rate;
    protected $acc_currency_rate;
    protected $acc_currency_sign;
    protected $ki_viewed;
    protected $payment_settled;
    protected $settled_date;
    protected $partner_settled;
    protected $no_collection;
    protected $issue;
    protected $partner_commission;
    protected $arrivalStatus;
    protected $actualArrivalDate;
    protected $actualDepartureDate;
    protected $penaltyHours;
    protected $room_id;
    protected $booker_price;
    protected $model;
    protected $guestTravelPhone;
    protected $black_res;
    protected $ki_viewed_date;
    protected $ki_page_type;
    protected $apartmentAssignedBlock;
    protected $country_currecny;
    protected $customer_id;
    protected $customer_email;
    protected $rateCapacity;
    protected $apartmentCapacity;
    protected $CccaVerified;
    protected $locked;
    protected $apartelId;
    protected $apartmentCityId;
    protected $timezone;
    protected $bedroomCountAssigned;
    protected $apartmentCheckInTime;
    protected $apartmentCheckOutTime;
    protected $channelResId;
    protected $checkCharged;

    /**
     * @var boolean $partnerTaxCommission
     */
    protected $partnerTaxCommission;

    public function exchangeArray($data)
    {
        $this->id                          = (isset($data['id'])) ? $data['id'] : null;
        $this->resNumber                   = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->guestFirstName              = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName               = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->guestEmail                  = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->secondaryEmail              = (isset($data['secondary_email'])) ? $data['secondary_email'] : null;
        $this->provide_cc_page_status      = (isset($data['provide_cc_page_status'])) ? $data['provide_cc_page_status'] : null;
        $this->getGuestLanguageIso         = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->provide_cc_page_hash        = (isset($data['provide_cc_page_hash'])) ? $data['provide_cc_page_hash'] : null;
        $this->guestCountryId              = (isset($data['guest_country_id'])) ? $data['guest_country_id'] : null;
        $this->guestCityName               = (isset($data['guest_city_name'])) ? $data['guest_city_name'] : null;
        $this->guestAddress                = (isset($data['guest_address'])) ? $data['guest_address'] : null;
        $this->guestZipCode                = (isset($data['guest_zip_code'])) ? $data['guest_zip_code'] : null;
        $this->guestPhone                  = (isset($data['guest_phone'])) ? $data['guest_phone'] : null;
        $this->IP                          = (isset($data['ip_address'])) ? $data['ip_address'] : null;
        $this->res_number                  = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->partner_id                  = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->partner_ref                 = (isset($data['partner_ref'])) ? $data['partner_ref'] : null;
        $this->partnerTaxCommission        = (isset($data['partner_tax_commission'])) ? $data['partner_tax_commission'] : null;
        $this->channel_name                = (isset($data['channel_name'])) ? $data['channel_name'] : null;
        $this->acc_name                    = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->rate_name                   = (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->overbookingStatus           = (isset($data['overbooking_status'])) ? $data['overbooking_status'] : null;
        $this->timestamp                   = (isset($data['timestamp'])) ? $data['timestamp'] : null;
        $this->date_from                   = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to                     = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->guestArrivalTime            = (isset($data['guest_arrival_time'])) ? $data['guest_arrival_time'] : null;
        $this->pax                         = (isset($data['pax'])) ? $data['pax'] : null;
        $this->ki_mail_sent                = (isset($data['ki_mail_sent'])) ? $data['ki_mail_sent'] : null;
        $this->outside_door_code           = (isset($data['outside_door_code'])) ? $data['outside_door_code'] : null;
        $this->pin                         = (isset($data['pin'])) ? $data['pin'] : null;
        $this->building                    = (isset($data['building']) && !empty($data['usage_building'])) ? $data['building'] : null;
        $this->unit                        = (isset($data['unit'])) ? $data['unit'] : null;
        $this->apartmentAssignedBuilding   = (isset($data['apartment_assigned_building'])) ? $data['apartment_assigned_building'] : null;
        $this->apartmentAssignedBuildingId = (isset($data['apartment_assigned_building_id'])) ? $data['apartment_assigned_building_id'] : null;
        $this->apartmentAssignedUnit       = (isset($data['apartment_assigned_unit'])) ? $data['apartment_assigned_unit'] : null;
        $this->apartmentOriginBuilding     = (isset($data['apartment_origin_building'])) ? $data['apartment_origin_building'] : null;
        $this->apartmentOriginUnit         = (isset($data['apartment_origin_unit'])) ? $data['apartment_origin_unit'] : null;
        $this->ki_page_status              = (isset($data['ki_page_status'])) ? $data['ki_page_status'] : null;
        $this->parking                     = (isset($data['parking'])) ? $data['parking'] : null;
        $this->ki_page_hash                = (isset($data['ki_page_hash'])) ? $data['ki_page_hash'] : null;
        $this->cancelation_date            = (isset($data['cancelation_date'])) ? $data['cancelation_date'] : null;
        $this->isRefundable                = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours       = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->penalty_val                 = (isset($data['penalty_val'])) ? $data['penalty_val'] : null;
        $this->penalty                     = (isset($data['penalty'])) ? $data['penalty'] : null;
        $this->apartmentCurrencyCode       = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->status                      = (isset($data['status'])) ? $data['status'] : null;
        $this->apartmentIdAssigned         = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartmentIdOrigin           = (isset($data['apartment_id_origin'])) ? $data['apartment_id_origin'] : null;
        $this->apartmentAssigned           = (isset($data['apartment_assigned'])) ? $data['apartment_assigned'] : null;
        $this->funds_confirmed             = (isset($data['funds_confirmed'])) ? $data['funds_confirmed'] : null;

        $this->tot            = (isset($data['tot'])) ? $data['tot'] : null;
        $this->tot_type       = (isset($data['tot_type'])) ? $data['tot_type'] : null;
        $this->totIncluded    = (isset($data['tot_included'])) ? $data['tot_included'] : null;
        $this->totAdditional  = (isset($data['tot_additional'])) ? $data['tot_additional'] : null;
        $this->totMaxDuration = (isset($data['tot_max_duration'])) ? $data['tot_max_duration'] : null;

        $this->vat            = (isset($data['vat'])) ? $data['vat'] : null;
        $this->vat_type       = (isset($data['vat_type'])) ? $data['vat_type'] : null;
        $this->vatIncluded    = (isset($data['vat_included'])) ? $data['vat_included'] : null;
        $this->vatAdditional  = (isset($data['vat_additional'])) ? $data['vat_additional'] : null;
        $this->vatMaxDuration = (isset($data['vat_max_duration'])) ? $data['vat_max_duration'] : null;

        $this->sales_tax           = (isset($data['sales_tax'])) ? $data['sales_tax'] : null;
        $this->sales_tax_type      = (isset($data['sales_tax_type'])) ? $data['sales_tax_type'] : null;
        $this->salesTaxIncluded    = (isset($data['sales_tax_included'])) ? $data['sales_tax_included'] : null;
        $this->salesTaxAdditional  = (isset($data['sales_tax_additional'])) ? $data['sales_tax_additional'] : null;
        $this->salesTaxMaxDuration = (isset($data['sales_tax_max_duration'])) ? $data['sales_tax_max_duration'] : null;

        $this->city_tax           = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->city_tax_type      = (isset($data['city_tax_type'])) ? $data['city_tax_type'] : null;
        $this->cityTaxIncluded    = (isset($data['city_tax_included'])) ? $data['city_tax_included'] : null;
        $this->cityTaxAdditional  = (isset($data['city_tax_additional'])) ? $data['city_tax_additional'] : null;
        $this->cityTaxMaxDuration = (isset($data['city_tax_max_duration'])) ? $data['city_tax_max_duration'] : null;

        $this->price                       = (isset($data['price'])) ? $data['price'] : null;
        $this->currency_rate               = (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->guestCurrencyCode           = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->penaltyFixedAmount          = (isset($data['penalty_fixed_amount'])) ? $data['penalty_fixed_amount'] : null;
        $this->customer_currency_rate      = (isset($data['customer_currency_rate'])) ? $data['customer_currency_rate'] : null;
        $this->acc_currency_rate           = (isset($data['acc_currency_rate'])) ? $data['acc_currency_rate'] : null;
        $this->acc_currency_sign           = (isset($data['acc_currency_sign'])) ? $data['acc_currency_sign'] : null;
        $this->ki_viewed                   = (isset($data['ki_viewed'])) ? $data['ki_viewed'] : null;
        $this->payment_settled             = (isset($data['payment_settled'])) ? $data['payment_settled'] : null;
        $this->settled_date                = (isset($data['settled_date'])) ? $data['settled_date'] : null;
        $this->partner_settled             = (isset($data['partner_settled'])) ? $data['partner_settled'] : null;
        $this->no_collection               = (isset($data['no_collection'])) ? $data['no_collection'] : null;
        $this->issue                       = (isset($data['issue'])) ? $data['issue'] : null;
        $this->partner_commission          = (isset($data['partner_commission'])) ? $data['partner_commission'] : null;
        $this->arrivalStatus               = (isset($data['arrival_status'])) ? $data['arrival_status'] : null;
        $this->actualArrivalDate           = (isset($data['arrival_date'])) ? $data['arrival_date'] : null;
        $this->actualDepartureDate         = (isset($data['departure_date'])) ? $data['departure_date'] : null;
        $this->penaltyHours                = (isset($data['penaltyHours'])) ? $data['penaltyHours'] : null;
        $this->room_id                     = (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->booker_price                = (isset($data['booker_price'])) ? $data['booker_price'] : null;
        $this->model                       = (isset($data['model'])) ? $data['model'] : null;
        $this->guestTravelPhone            = (isset($data['guest_travel_phone'])) ? $data['guest_travel_phone'] : null;
        $this->black_res                   = (isset($data['black_res'])) ? $data['black_res'] : null;
        $this->ki_viewed_date              = (isset($data['ki_viewed_date'])) ? $data['ki_viewed_date'] : null;
        $this->apartelId                   = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->apartmentCityId             = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->ki_page_type                = (isset($data['ki_page_type'])) ? $data['ki_page_type'] : null;
        $this->apartmentAssignedBlock      = (isset($data['apartment_assigned_block'])) ? $data['apartment_assigned_block'] : null;
        $this->occupancy                   = (isset($data['occupancy'])) ? $data['occupancy'] : null;

        $this->country_currecny            = (isset($data['country_currecny'])) ? $data['country_currecny'] : null;

        $this->customer_id                 = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->customer_email              = (isset($data['customer_email'])) ? $data['customer_email'] : null;

        $this->timezone                    = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->rateCapacity                = (isset($data['rate_capacity'])) ? $data['rate_capacity'] : null;

        $this->bedroomCountAssigned        = (isset($data['bedroom_count_assigned'])) ? $data['bedroom_count_assigned'] : null;
        $this->CccaVerified                = (isset($data['ccca_verified'])) ? $data['ccca_verified'] : null;
        $this->locked                      = (isset($data['locked'])) ? $data['locked'] : null;

        $this->apartmentCapacity           = (isset($data['apartment_capacity'])) ? $data['apartment_capacity'] : null;
        $this->apartmentCheckInTime        = (isset($data['check_in'])) ? $data['check_in'] : null;
        $this->apartmentCheckOutTime       = (isset($data['check_out'])) ? $data['check_out'] : null;
        $this->channelResId                = (isset($data['channel_res_id'])) ? $data['channel_res_id'] : null;
        $this->checkCharged                = (isset($data['check_charged'])) ? $data['check_charged'] : null;
    }

    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function getCustomerEmail()
    {
        return $this->customer_email;
    }

    public function getCountryCurrecny()
    {
        return $this->country_currecny;
    }

    public function getTotType()
    {
        return $this->tot_type;
    }

    public function getVatType()
    {
        return $this->vat_type;
    }

    public function getSalesTaxType()
    {
        return $this->sales_tax_type;
    }

    public function getCityTaxType()
    {
        return $this->city_tax_type;
    }

    public function getApartmentAssignedBlock()
    {
        return $this->apartmentAssignedBlock;
    }

    public function getki_viewed_date()
    {
        return $this->ki_viewed_date;
    }

    public function getBlack_res()
    {
        return $this->black_res;
    }

    public function getGuestTravelPhone()
    {
        return $this->guestTravelPhone;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getBooker_price()
    {
        return $this->booker_price;
    }

    public function getRoom_id()
    {
        return $this->room_id;
    }

    public function getPenaltyHours()
    {
        return $this->penaltyHours;
    }

    public function getActualArrivalDate()
    {
        return $this->actualArrivalDate;
    }

    public function getActualDepartureDate()
    {
        return $this->actualDepartureDate;
    }

    public function getPartnerCommission()
    {
        return $this->partner_commission;
    }

    public function getIssue()
    {
        return $this->issue;
    }

    public function getNo_collection()
    {
        return $this->no_collection;
    }

    public function getPartnerSettled()
    {
        return $this->partner_settled;
    }

    public function getSettled_date()
    {
        return $this->settled_date;
    }

    public function getPayment_settled()
    {
        return $this->payment_settled;
    }

    public function isKiViewed()
    {
        return $this->ki_viewed;
    }

    public function getAcc_currency_rate()
    {
        return $this->acc_currency_rate;
    }

    public function getAcc_currency_sign()
    {
        return $this->acc_currency_sign;
    }

    public function getCustomer_currency_rate()
    {
        return $this->customer_currency_rate;
    }

    public function getGuestCurrencyCode()
    {
        return $this->guestCurrencyCode;
    }

    public function getCurrency_rate()
    {
        return $this->currency_rate;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getCity_tax()
    {
        return $this->city_tax;
    }

    public function getSales_tax()
    {
        return $this->sales_tax;
    }

    public function getVat()
    {
        return $this->vat;
    }

    public function getTot()
    {
        return $this->tot;
    }

    public function getHas_review()
    {
        return $this->has_review;
    }

    public function setHas_review($val)
    {
        $this->has_review = $val;
        return $this;
    }

    public function getFunds_confirmed()
    {
        return $this->funds_confirmed;
    }

    public function getApartmentIdAssigned()
    {
        return $this->apartmentIdAssigned;
    }

    public function getApartmentIdOrigin()
    {
        return $this->apartmentIdOrigin;
    }

    public function getApartmentAssigned()
    {
        return $this->apartmentAssigned;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getApartmentCurrencyCode()
    {
        return $this->apartmentCurrencyCode;
    }

    public function getPenalty()
    {
        return $this->penalty;
    }

    public function getPenalty_val()
    {
        return $this->penalty_val;
    }

    public function getCancelation_date()
    {
        return $this->cancelation_date;
    }

    public function getKiPageHash()
    {
        return $this->ki_page_hash;
    }

    public function getParking()
    {
        return $this->parking;
    }

    public function getKiPageStatus()
    {
        return $this->ki_page_status;
    }

    public function getPin()
    {
        return $this->pin;
    }

    public function getOutsideDoorCode()
    {
        return $this->outside_door_code;
    }

    public function getKiMailSent()
    {
        return $this->ki_mail_sent;
    }

    public function getPAX()
    {
        return $this->pax;
    }

    public function getBuilding()
    {
        return $this->building;
    }

    public function getApartmentAssignedBuilding()
    {
        return $this->apartmentAssignedBuilding;
    }

    public function getApartmentAssignedBuildingId()
    {
        return $this->apartmentAssignedBuildingId;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function getApartmentAssignedUnit()
    {
        return $this->apartmentAssignedUnit;
    }

    public function getApartmentOriginBuilding()
    {
        return $this->apartmentOriginBuilding;
    }

    public function getApartmentOriginUnit()
    {
        return $this->apartmentOriginUnit;
    }

    /**
     * @return string
     */
    public function getGuestArrivalTime()
    {
        return $this->guestArrivalTime;
    }

    public function getDate_to()
    {
        return $this->date_to;
    }

    public function getDate_from()
    {
        return $this->date_from;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getOverbookingStatus()
    {
        return $this->overbookingStatus;
    }

    public function getRate_name()
    {
        return $this->rate_name;
    }

    public function getApartmentName()
    {
        return $this->acc_name;
    }

    public function getChannel_name()
    {
        return $this->channel_name;
    }

    public function getPartnerRef()
    {
        return $this->partner_ref;
    }

    public function getPartnerId()
    {
        return $this->partner_id;
    }

    public function getReservationNumber()
    {
        return $this->res_number;
    }

    public function getIP()
    {
        return long2ip($this->IP);
    }

    public function setIP($val)
    {
        $this->IP = $val;
        return $this;
    }

    public function getGuestPhone()
    {
        return $this->guestPhone;
    }

    public function getGuestZipCode()
    {
        return $this->guestZipCode;
    }

    public function getGuestAddress()
    {
        return $this->guestAddress;
    }

    public function getGuestCityName()
    {
        return $this->guestCityName;
    }

    public function getGuestCountryId()
    {
        return $this->guestCountryId;
    }

    public function getProvideCcPageHash()
    {
        return $this->provide_cc_page_hash;
    }

    public function getGetGuestLanguageIso()
    {
        return $this->getGuestLanguageIso;
    }

    public function getProvideCcPageStatus()
    {
        return $this->provide_cc_page_status;
    }

    public function getSecondaryEmail()
    {
        return $this->secondaryEmail;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getResNumber()
    {
        return $this->resNumber;
    }

    public function getGuestFirstName()
    {
        return $this->guestFirstName;
    }

    public function getGuestLastName()
    {
        return $this->guestLastName;
    }

    public function isVirtual()
    {
        return $this->apartelId ? 1 : 0;
    }

    public function getApartelId()
    {
        return $this->apartelId;
    }

    public function getOccupancy()
    {
        return $this->occupancy ? $this->occupancy : $this->pax;
    }

    public function getKiPageType()
    {
        return $this->ki_page_type;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getRateCapacity()
    {
        return $this->rateCapacity;
    }

    /**
     * @param boolean $isRefundable
     */
    public function setIsRefundable($isRefundable)
    {
        $this->isRefundable = $isRefundable;
    }

    /**
     * @return boolean
     */
    public function getIsRefundable()
    {
        return $this->isRefundable;
    }

    /**
     * @param int $refundableBeforeHours
     */
    public function setRefundableBeforeHours($refundableBeforeHours)
    {
        $this->refundableBeforeHours = $refundableBeforeHours;
    }

    /**
     * @return int
     */
    public function getRefundableBeforeHours()
    {
        return $this->refundableBeforeHours;
    }

    /**
     * @return float
     */
    public function getPenaltyFixedAmount()
    {
        return $this->penaltyFixedAmount;
    }

    /**
     * @param int $apartmentCityId
     */
    public function setApartmentCityId($apartmentCityId)
    {
        $this->apartmentCityId = $apartmentCityId;
    }

    /**
     * @return int
     */
    public function getApartmentCityId()
    {
        return $this->apartmentCityId;
    }

    /**
     * @return int
     */
    public function getBedroomCountAssigned()
    {
        return $this->bedroomCountAssigned;
    }

    /**
     * @return int
     */
    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }

    /**
     * @return int|null
     */
    public function getCityTaxIncluded()
    {
        return $this->cityTaxIncluded;
    }

    /**
     * @return int|null
     */
    public function getSalesTaxIncluded()
    {
        return $this->salesTaxIncluded;
    }

    /**
     * @return int|null
     */
    public function getVatIncluded()
    {
        return $this->vatIncluded;
    }

    /**
     * @return int|null
     */
    public function getTotIncluded()
    {
        return $this->totIncluded;
    }

    public function getCccaVerified()
    {
        return (int) $this->CccaVerified;
    }

    public function isLocked()
    {
        return (int) $this->locked;
    }

    /**
     * @return int
     */
    public function getApartmentCapacity()
    {
        return $this->apartmentCapacity;
    }

    public function getApartmentCheckInTime()
    {
        return $this->apartmentCheckInTime;
    }

    public function getApartmentCheckOutTime()
    {
        return $this->apartmentCheckOutTime;
    }

    /**
     * @param $pin
     * @return $this
     */
    public function setPin($pin)
    {
        $this->pin = $pin;
        return $this;
    }

    /**
     * @param $outsideDoorCode
     * @return $this
     */
    public function setOutsideDoorCode($outsideDoorCode)
    {
        $this->outside_door_code = $outsideDoorCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannelResId()
    {
        return $this->channelResId;
    }

    /**
     * @return string|null
     */
    public function getGuestEmail()
    {
        return $this->guestEmail;
    }

    /**
     * @return string|null
     */
    public function getCheckCharged()
    {
        return $this->checkCharged;
    }

    /**
     * @return mixed
     */
    public function getTotMaxDuration()
    {
        return $this->totMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getVatMaxDuration()
    {
        return $this->vatMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getSalesTaxMaxDuration()
    {
        return $this->salesTaxMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getCityTaxMaxDuration()
    {
        return $this->cityTaxMaxDuration;
    }

    /**
     * @return int|null
     */
    public function getTotAdditional()
    {
        return $this->totAdditional;
    }

    /**
     * @return int|null
     */
    public function getVatAdditional()
    {
        return $this->vatAdditional;
    }

    /**
     * @return int|null
     */
    public function getSalesTaxAdditional()
    {
        return $this->salesTaxAdditional;
    }

    /**
     * @return int|null
     */
    public function getCityTaxAdditional()
    {
        return $this->cityTaxAdditional;
    }

    /**
     * @return boolean
     */
    public function isPartnerTaxCommission()
    {
        return $this->partnerTaxCommission;
    }
}
