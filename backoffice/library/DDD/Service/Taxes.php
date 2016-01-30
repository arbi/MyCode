<?php
namespace DDD\Service;

use DDD\Service\ServiceBase;
use DDD\Service\Location as LocationService;
use Library\Constants\Constants;
use Library\Constants\Objects;
use Library\Constants\DbTables;
use Library\Utility\Helper;

class Taxes extends ServiceBase
{

    //TAX type
	const TAXES_TYPE_PERCENT = 1;
	const TAXES_TYPE_PER_NIGHT = 2;
	const TAXES_TYPE_PER_PERSON = 3;

    /**
     *
     * @return array
     */
    public static function getTaxesType($hasPerPerson = false)
    {
        $taxType = array(
            0  => '-- None --',
            self::TAXES_TYPE_PERCENT  => 'Percent',
            self::TAXES_TYPE_PER_NIGHT => 'Fixed per night',
        );

        if ($hasPerPerson) {
            $taxType[self::TAXES_TYPE_PER_PERSON] = 'Per night per PAX';
        }
        return $taxType;
    }
    /**
     *
     * @param array $params
     * @return array
     */
    public function getTaxesForCharge($params)
    {
        /** @var \DDD\Service\Currency\Currency $currencyService */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');

        $apartmentCurrencyRate = 1;
        if($params['apartment_currency'] != $params['country_currency']) {
            $apartmentCurrencyRate = $currencyService->getCurrencyConversionRate($params['apartment_currency'], $params['country_currency']);
        }
        //TOT
        $response['taxes']['tot_type'] = $params['tot_type'];
        $response['taxes']['tot_exact_value'] = $params['tot'];
        $response['taxes']['tot_included'] = $params['tot_included'];
        $response['taxes']['tot_max_duration'] = $params['tot_max_duration'];
        $response['taxes']['tot_additional_value'] = $params['tot_additional'];
        if($params['tot_type'] == self::TAXES_TYPE_PER_NIGHT) {
            $response['taxes']['tot_apartment'] = $apartmentCurrencyRate*$params['tot'];
            $response['taxes']['tot_additional_apartment'] = $apartmentCurrencyRate*$params['tot_additional'];
        } elseif($params['tot_type'] == self::TAXES_TYPE_PERCENT) {
            $response['taxes']['tot_apartment'] = $params['tot'];
            $response['taxes']['tot_additional_apartment'] = $params['tot_additional'];
        }
        //VAT
        $response['taxes']['vat_type'] = $params['vat_type'];
        $response['taxes']['vat_exact_value'] = $params['vat'];
        $response['taxes']['vat_included'] = $params['vat_included'];
        $response['taxes']['vat_max_duration'] = $params['vat_max_duration'];
        $response['taxes']['vat_additional_value'] = $params['vat_additional'];
        if($params['vat_type'] == self::TAXES_TYPE_PER_NIGHT) {
            $response['taxes']['vat_apartment'] = $apartmentCurrencyRate*$params['vat'];
            $response['taxes']['vat_additional_apartment'] = $apartmentCurrencyRate*$params['vat_additional'];
        } elseif($params['vat_type'] == self::TAXES_TYPE_PERCENT) {
            $response['taxes']['vat_apartment'] = $params['vat'];
            $response['taxes']['vat_additional_apartment'] = $params['vat_additional'];
        }
        //Sales Tax
        $response['taxes']['sales_tax_type'] = $params['sales_tax_type'];
        $response['taxes']['sales_tax_exact_value'] = $params['sales_tax'];
        $response['taxes']['city_tax_included'] = $params['city_tax_included'];
        $response['taxes']['city_tax_max_duration'] = $params['city_tax_max_duration'];
        $response['taxes']['sales_tax_additional_value'] = $params['sales_tax_additional'];
        if($params['sales_tax_type'] == self::TAXES_TYPE_PER_NIGHT) {
            $response['taxes']['sales_tax_apartment'] = $apartmentCurrencyRate*$params['sales_tax'];
            $response['taxes']['sales_tax_additional_apartment'] = $apartmentCurrencyRate*$params['sales_tax_additional'];
        } elseif($params['sales_tax_type'] == self::TAXES_TYPE_PERCENT) {
            $response['taxes']['sales_tax_apartment'] = $params['sales_tax'];
            $response['taxes']['sales_tax_additional_apartment'] = $params['sales_tax_additional'];
        }
        //City Tax
        $response['taxes']['city_tax_type'] = $params['city_tax_type'];
        $response['taxes']['city_tax_exact_value'] = $params['city_tax'];
        $response['taxes']['sales_tax_included'] = $params['sales_tax_included'];
        $response['taxes']['sales_tax_max_duration'] = $params['sales_tax_max_duration'];
        $response['taxes']['city_tax_additional_value'] = $params['city_tax_additional'];
        if($params['city_tax_type'] == self::TAXES_TYPE_PER_NIGHT) {
            $response['taxes']['city_tax_apartment'] = $apartmentCurrencyRate*$params['city_tax'];
            $response['taxes']['city_tax_additional_apartment'] = $apartmentCurrencyRate*$params['city_tax_additional'];
        } elseif($params['city_tax_type'] == self::TAXES_TYPE_PERCENT) {
            $response['taxes']['city_tax_apartment'] = $params['city_tax'];
        } elseif($params['city_tax_type'] == self::TAXES_TYPE_PER_PERSON) {
            $response['taxes']['city_tax_apartment'] = $apartmentCurrencyRate*$params['occupancy']*$params['city_tax'];
            $response['taxes']['city_tax_additional_apartment'] = $params['city_tax_additional'];
        }
        return $response;
    }
}
