<?php

namespace DDD\Domain\Booking;

/**
 * Class SaleStatisticsItem
 * @package DDD\Domain\Booking
 *
 * @author Tigran Petrosyan
 */
class SaleStatisticsItem
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
     * @var float
     */
    protected $reservationPriceInApartmentCurrency;

    /**
     * @var stirng
     */
    protected $apartmentCurrencyIsoCode;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->reservationId                        = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->reservationNumber                    = (isset($data['reservation_number'])) ? $data['reservation_number'] : null;
        $this->reservationPriceInApartmentCurrency  = (isset($data['reservation_price_in_apartment_currency'])) ? $data['reservation_price_in_apartment_currency'] : null;
        $this->apartmentCurrencyIsoCode             = (isset($data['apartment_currency_iso_code'])) ? $data['apartment_currency_iso_code'] : null;
    }

    /**
     * @return \DDD\Domain\Booking\stirng
     */
    public function getApartmentCurrencyIsoCode()
    {
        return $this->apartmentCurrencyIsoCode;
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
    public function getReservationNumber()
    {
        return $this->reservationNumber;
    }

    /**
     * @return float
     */
    public function getReservationPriceInApartmentCurrency()
    {
        return $this->reservationPriceInApartmentCurrency;
    }
}
