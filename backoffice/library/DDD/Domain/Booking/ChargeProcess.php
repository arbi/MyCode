<?php

namespace DDD\Domain\Booking;


class ChargeProcess
{
    protected $id;
    protected $price;
    protected $bookerPrice;
    protected $cleaningFee;
    protected $guestEmail;
    protected $occupancy;
    protected $cityTot;
    protected $cityTotType;
    protected $totIncluded;
    protected $totAdditional;
    protected $totMaxDuration;
    protected $cityVat;
    protected $cityVatType;
    protected $vatIncluded;
    protected $vatAdditional;
    protected $vatMaxDuration;
    protected $citySalesTax;
    protected $salesTaxAdditional;
    protected $citySalesTaxType;
    protected $salesTaxIncluded;
    protected $salesTaxMaxDuration;
    protected $cityTax;
    protected $cityTaxType;
    protected $cityTaxIncluded;
    protected $cityTaxAdditional;
    protected $cityTaxMaxDuration;
    protected $countryCurrency;
    protected $guestCurrency;
    protected $dateFrom;
    protected $dateTo;
    protected $partnerName;
    protected $partnerId;
    protected $apartmentId;
    protected $apartmentCurrency;
    protected $currencySymbol;
    protected $checkCurrency;
    protected $remarks;
    protected $apartmentIdAssigned;

    public function exchangeArray($data)
    {
        $this->id                          = (isset($data['id'])) ? $data['id'] : null;
        $this->price                       = (isset($data['price'])) ? $data['price'] : null;
        $this->occupancy                   = (isset($data['occupancy'])) ? $data['occupancy'] : null;

        $this->cityTot                     = (isset($data['city_tot'])) ? $data['city_tot'] : null;
        $this->cityTotType                 = (isset($data['city_tot_type'])) ? $data['city_tot_type'] : null;
        $this->totIncluded                 = (isset($data['tot_included'])) ? $data['tot_included'] : null;
        $this->totAdditional               = (isset($data['tot_additional'])) ? $data['tot_additional'] : null;
        $this->totMaxDuration              = (isset($data['tot_max_duration'])) ? $data['tot_max_duration'] : null;

        $this->cityVat                     = (isset($data['city_vat'])) ? $data['city_vat'] : null;
        $this->cityVatType                 = (isset($data['city_vat_type'])) ? $data['city_vat_type'] : null;
        $this->vatIncluded                 = (isset($data['vat_included'])) ? $data['vat_included'] : null;
        $this->vatAdditional               = (isset($data['vat_additional'])) ? $data['vat_additional'] : null;
        $this->vatMaxDuration              = (isset($data['vat_max_duration'])) ? $data['vat_max_duration'] : null;

        $this->citySalesTax                = (isset($data['city_sales_tax'])) ? $data['city_sales_tax'] : null;
        $this->citySalesTaxType            = (isset($data['city_sales_tax_type'])) ? $data['city_sales_tax_type'] : null;
        $this->salesTaxIncluded            = (isset($data['sales_tax_included'])) ? $data['sales_tax_included'] : null;
        $this->salesTaxAdditional          = (isset($data['sales_tax_additional'])) ? $data['sales_tax_additional'] : null;
        $this->salesTaxMaxDuration         = (isset($data['sales_tax_max_duration'])) ? $data['sales_tax_max_duration'] : null;

        $this->cityTax                     = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->cityTaxAdditional           = (isset($data['city_tax_additional'])) ? $data['city_tax_additional'] : null;
        $this->cityTaxIncluded             = (isset($data['city_tax_included'])) ? $data['city_tax_included'] : null;
        $this->cityTaxMaxDuration          = (isset($data['city_tax_max_duration'])) ? $data['city_tax_max_duration'] : null;
        $this->cityTaxType                 = (isset($data['city_tax_type'])) ? $data['city_tax_type'] : null;

        $this->countryCurrency             = (isset($data['country_currency'])) ? $data['country_currency'] : null;
        $this->guestCurrency               = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->dateFrom                    = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->dateTo                      = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->partnerName                 = (isset($data['partner_name'])) ? $data['partner_name'] : null;
        $this->partnerId                   = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->apartmentId                 = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->apartmentCurrency           = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->bookerPrice                 = (isset($data['booker_price'])) ? $data['booker_price'] : null;
        $this->guestEmail                  = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->currencySymbol              = (isset($data['currency_symbol'])) ? $data['currency_symbol'] : null;
        $this->remarks                     = (isset($data['remarks'])) ? $data['remarks'] : null;
        $this->apartmentIdAssigned         = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
    }

    public function getCheckCurrency()
    {
        return $this->checkCurrency;
    }

    public function setCheckCurrency($checkCurrency)
    {
        return $this->checkCurrency = $checkCurrency;
    }

    public function getCurrencySymbol()
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol($currencySymbol)
    {
        return $this->currencySymbol = $currencySymbol;
    }

    public function getBookerPrice()
    {
        return $this->bookerPrice;
    }

    public function setBookerPrice($bookerPrice)
    {
        return $this->bookerPrice = $bookerPrice;
    }

    public function getApartmentCurrency()
    {
        return $this->apartmentCurrency;
    }

    public function setApartmentCurrency($apartmentCurrency)
    {
        return $this->apartmentCurrency = $apartmentCurrency;
    }

    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    public function getPartnerId()
    {
        return $this->partnerId;
    }

    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    public function getDateTo()
    {
        return $this->dateTo;
    }

    public function getGuestCurrency()
    {
        return $this->guestCurrency;
    }

    public function getCountryCurrency()
    {
        return $this->countryCurrency;
    }

    public function getCityTotType()
    {
        return $this->cityTotType;
    }

    public function getCityVatType()
    {
        return $this->cityVatType;
    }

    public function getCitySalesTaxType()
    {
        return $this->citySalesTaxType;
    }

    public function getCityTaxType()
    {
        return $this->cityTaxType;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getPrice()
    {
        return $this->price;
    }

    public function getGuestEmail()
    {
        return $this->guestEmail;
    }

    public function getCityTot()
    {
        return $this->cityTot;
    }

    public function getCityVat()
    {
        return $this->cityVat;
    }

    public function getCitySalesTax()
    {
        return $this->citySalesTax;
    }

    public function getCityTax()
    {
        return $this->cityTax;
    }

    public function getTotIncluded()
    {
        return $this->totIncluded;
    }

    public function getVatIncluded()
    {
        return $this->vatIncluded;
    }

    public function getSalesTaxIncluded()
    {
        return $this->salesTaxIncluded;
    }

    public function getCityTaxIncluded()
    {
        return $this->cityTaxIncluded;
    }

    public function getOccupancy()
    {
        return $this->occupancy;
    }

    public function getCleaningFee()
    {
        return $this->cleaningFee;
    }

    public function getPartnerName()
    {
        return $this->partnerName;
    }

    /**
     * @return string | null
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @return mixed
     */
    public function getApartmentIdAssigned()
    {
        return $this->apartmentIdAssigned;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setCleaningFee($cleaningFee)
    {
        $this->cleaningFee = $cleaningFee;
    }

    public function setIsRefundable($isRefundable)
    {
        $this->isRefundable = $isRefundable;
    }

    public function setGuestEmail($guestEmail)
    {
        $this->guestEmail = $guestEmail;
    }

    public function setOccupancy($occupancy)
    {
        $this->occupancy = $occupancy;
    }

    public function setCityTot($cityTot)
    {
        $this->cityTot = $cityTot;
    }

    public function setTotIncluded($totIncluded)
    {
        $this->totIncluded = $totIncluded;
    }

    public function setCityVat($cityVat)
    {
        $this->cityVat = $cityVat;
    }

    public function setVatIncluded($vatIncluded)
    {
        $this->vatIncluded = $vatIncluded;
    }

    public function setCitySalesTax($citySalesTax)
    {
        $this->citySalesTax = $citySalesTax;
    }

    public function setSalesTaxIncluded($salesTaxIncluded)
    {
        $this->salesTaxIncluded = $salesTaxIncluded;
    }

    public function setCityTax($cityTax)
    {
        $this->cityTax = $cityTax;
    }

    public function setCityTaxIncluded($cityTaxIncluded)
    {
        $this->cityTaxIncluded = $cityTaxIncluded;
    }

    public function setCityTotType($cityTotType)
    {
        $this->cityTotType = $cityTotType;
    }

    public function setCityVatType($cityVatType)
    {
        $this->cityVatType = $cityVatType;
    }

    public function setCitySalesTaxType($citySalesTaxType)
    {
        $this->citySalesTaxType = $citySalesTaxType;
    }

    public function setCityTaxType($cityTaxType)
    {
        $this->cityTaxType = $cityTaxType;
    }

    public function setCountryCurrency($countryCurrency)
    {
        $this->countryCurrency = $countryCurrency;
    }

    public function setGuestCurrency($guestCurrency)
    {
        $this->guestCurrency = $guestCurrency;
    }

    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    }

    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    }

    public function setPartnerName($partnerName)
    {
        $this->partnerName = $partnerName;
    }

    public function setPartnerId($partnerId)
    {
        $this->partnerId = $partnerId;
    }

    public function setApartmentId($apartmentId)
    {
        $this->apartmentId = $apartmentId;
    }

    /**
     * @return mixed
     */
    public function getTotMaxDuration()
    {
        return $this->totMaxDuration;
    }

    /**
     * @param mixed $totMaxDuration
     */
    public function setTotMaxDuration($totMaxDuration)
    {
        $this->totMaxDuration = $totMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getVatMaxDuration()
    {
        return $this->vatMaxDuration;
    }

    /**
     * @param mixed $vatMaxDuration
     */
    public function setVatMaxDuration($vatMaxDuration)
    {
        $this->vatMaxDuration = $vatMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getSalesTaxMaxDuration()
    {
        return $this->salesTaxMaxDuration;
    }

    /**
     * @param mixed $salesTaxMaxDuration
     */
    public function setSalesTaxMaxDuration($salesTaxMaxDuration)
    {
        $this->salesTaxMaxDuration = $salesTaxMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getCityTaxMaxDuration()
    {
        return $this->cityTaxMaxDuration;
    }

    /**
     * @param mixed $cityTaxMaxDuration
     */
    public function setCityTaxMaxDuration($cityTaxMaxDuration)
    {
        $this->cityTaxMaxDuration = $cityTaxMaxDuration;
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
}
