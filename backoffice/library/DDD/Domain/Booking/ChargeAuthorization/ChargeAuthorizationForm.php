<?php

namespace DDD\Domain\Booking\ChargeAuthorization;

use Library\Constants\Constants;

/**
 * Class ChargeAuthorizationForm
 * @package DDD\Domain\Booking\ChargeAuthorization
 *
 * @author Tigran Petrosyan
 */
class ChargeAuthorizationForm
{
    /**
     * @var int
     */
    protected $reservationId;

    /**
     * @var string
     */
    protected $reservationNumber;

    /**
     * @var string
     */
    protected $reservationDateFrom;

    /**
     * @var string
     */
    protected $reservationDateTo;

    /**
     * @var string
     */
    protected $cancellationPolicy;

    /**
     * @var string
     */
    protected $reservationPartner;

    /**
     * @var int
     */
    protected $isRefundable;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    /**
     * @var int
     */
    protected $penaltyType;

    /**
     * @var float
     */
    protected $penaltyValue;

    /**
     * @var string
     */
    protected $customerCurrency;

    /**
     * @var string
     */
    protected $apartmentCurrency;

    /**
     * @var int
     */
    protected $creditCardId;

    /**
     * @var int
     */
    protected $cccaPageStatus;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $partnerId;

    /**
     * @var string
     */
    protected $timezone;
    protected $cccaCreationDate;

    protected $apartmentCurrencyCode;

    public function exchangeArray($data)
    {
        $this->reservationId         = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->reservationNumber     = (isset($data['reservation_number'])) ? $data['reservation_number'] : null;
        $this->reservationDateFrom   = (isset($data['reservation_date_from'])) ? $data['reservation_date_from'] : null;
        $this->reservationDateTo     = (isset($data['reservation_date_to'])) ? $data['reservation_date_to'] : null;
        $this->cancellationPolicy    = null;
        $this->reservationPartner    = (isset($data['reservation_partner'])) ? $data['reservation_partner'] : null;
        $this->isRefundable          = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->penaltyType           = (isset($data['penalty_type'])) ? $data['penalty_type'] : null;
        $this->penaltyValue          = (isset($data['penalty_value'])) ? $data['penalty_value'] : null;
        $this->customerCurrency      = (isset($data['customer_currency'])) ? $data['customer_currency'] : null;
        $this->apartmentCurrency     = (isset($data['apartment_currency'])) ? $data['apartment_currency'] : null;
        $this->creditCardId          = (isset($data['cc_id'])) ? $data['cc_id'] : null;
        $this->cccaPageStatus        = (isset($data['ccca_page_status'])) ? $data['ccca_page_status'] : null;
        $this->status                = (isset($data['status'])) ? $data['status'] : null;
        $this->timezone              = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->cccaCreationDate      = (isset($data['ccca_created_date'])) ? $data['ccca_created_date'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->partnerId             = (isset($data['partner_id'])) ? $data['partner_id'] : null;
    }

    /**
     * @return string
     */
    public function getApartmentCurrency()
    {
        return $this->apartmentCurrency;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
        return $this->reservationNumber;
    }

    /**
     * @return int
     */
    public function getCreditCardId()
    {
        return $this->creditCardId;
    }

    /**
     * @return string
     */
    public function getCancellationPolicy()
    {
        return $this->cancellationPolicy;
    }

    /**
     * @return string
     */
    public function getReservationDateFrom()
    {
        return date(Constants::GLOBAL_DATE_FORMAT, strtotime($this->reservationDateFrom));
    }

    /**
     * @return string
     */
    public function getReservationDateTo()
    {
        return date(Constants::GLOBAL_DATE_FORMAT, strtotime($this->reservationDateTo));
    }

    /**
     * @return int
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * @return string
     */
    public function getReservationPartner()
    {
        return $this->reservationPartner;
    }

    /**
     * @return int
     */
    public function getIsRefundable()
    {
        return $this->isRefundable;
    }

    /**
     * @return int
     */
    public function getPenaltyType()
    {
        return $this->penaltyType;
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
    public function getPenaltyValue()
    {
        return $this->penaltyValue;
    }

    /**
     * @return string
     */
    public function getCustomerCurrency()
    {
        return $this->customerCurrency;
    }

    /**
     * @param string $cancellationPolicy
     */
    public function setCancellationPolicy($cancellationPolicy)
    {
        $this->cancellationPolicy = $cancellationPolicy;
    }

    /**
     * @return int
     */
    public function getCccaPageStatus()
    {
        return $this->cccaPageStatus;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @return mixed
     */
    public function getCccaCreationDate()
    {
        return $this->cccaCreationDate;
    }

    /**
     * @return int
     */
    public function getApartmentCurrencyCode()
    {
        return $this->apartmentCurrencyCode;
    }

    /**
     * @return int
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }
}
