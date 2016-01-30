<?php

namespace Backoffice\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;

use DDD\Service\Location as LocationService;
use DDD\Service\Taxes;

class Location extends FormBase
{
    public function __construct($name = null, $datas, $options, $type)
    {
        /**
         * @var \DDD\Domain\Geolocation\Details $data
         */
        parent::__construct($name);

        $name = $this->getName();

        if (null === $name) {
            $this->setName('location');
        }

        $this->add([
            'name'    => 'name',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'name',
                'maxlength' => 150,
            ],
        ]);

        $this->add([
            'name'    => 'slug',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'name',
                'maxlength' => 255,
            ],
        ]);

        if (isset($datas) || $type == LocationService::LOCATION_TYPE_CITY) {
            $timeZoneOptions = Objects::getTimezoneOptions();

            $this->add([
                'name' => 'timezone',
                'type' => 'Zend\Form\Element\Select',
                'options' => [
                    'label' => false,
                    'value_options' => $timeZoneOptions,
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id' => 'timezone',
                ],
            ]);
        }

        $this->add([
            'name' => 'autocomplete_txt',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'autocomplete_txt',
                'maxlength' => 150,
            ],
        ]);

        $this->add([
            'name' => 'latitude',
            'options' => [
                'label' => 'LONGITUDE',
            ],
            'attributes' => [
                'type' => 'hidden',
                'class' => 'form-control',
                'id' => 'latitude'
            ],
        ]);

        $this->add([
            'name' => 'longitude',
            'options' => [
                'label' => 'LONGITUDE',
            ],
            'attributes' => [
                'type' => 'hidden',
                'class' => 'form-control',
                'id' => 'longitude'
            ],
        ]);

        $this->add([
            'name' => 'information',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type' => 'textarea',
                'class' => 'tinymce',
                'rows' => '15',
                'id' => 'information',
            ],
        ]);

        $this->add([
            'name' => 'cover_image',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type' => 'file',
                'id' => 'img1',
                'class' => 'hidden-file-input invisible'
            ],
        ]);

        $this->add([
            'name' => 'thumbnail',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type' => 'file',
                'id' => 'img2',
                'class' => 'hidden-file-input invisible'
            ],
        ]);

        /**
         * Selling
         */
        $is_salling = [
            'id' => 'is_selling',
            'use_hidden_element' => false,
            'checked_value' => 1,
            'unchecked_value' => 0,
        ];
        if (is_array($datas)) {
            $data = $datas['details'];
        } else {
            $data = '';
        }

        if (is_object($data) && $data->getIs_selling() > 0) {
            $is_salling['checked'] = 'checked';
        }

        $this->add([
            'name' => 'is_selling',
            'options' => [
                'label' => '',
            ],
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => $is_salling
        ]);

        /**********Country**********/
        $this->add([
            'name' => 'iso',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'iso',
                'maxlength' => 10,
            ],
        ]);

        $this->add([
            'name' => 'currency',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $options['currency_list'],
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'currency',
            ],
        ]);

        $this->add([
            'name' => 'required_postal_code',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => \DDD\Service\Location::getRequiredPostalCodes(),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'required_postal_code',
            ],
        ]);

        $this->add([
            'name' => 'contact_phone',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'Contact Phone',
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'contact_phone',
            ],
        ]);

        /************City**************/
        $is_searchable = [
            'id' => 'is_searchable',
            'use_hidden_element' => false,
            'checked_value' => 1,
            'unchecked_value' => 0
        ];
        if (is_object($data) && $data->getIs_searchable() > 0) {
            $is_searchable['checked'] = 'checked';
        }

        $this->add([
            'name' => 'is_searchable',
            'options' => [
                'label' => 'Searchable',
            ],
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => $is_searchable
        ]);

        $taxIncludedOptions = [
            LocationService::TAX_INCLUDED     => 'Included',
            LocationService::TAX_EXCLUDED     => 'Excluded',
        ];

        $totIncluded = [
            'id' => 'tot_included',
            'class' => 'form-control'
        ];

        $vatIncluded = [
            'id' => 'vat_included',
            'class' => 'form-control'
        ];

        $cityTaxIncluded = [
            'id' => 'city_tax_included',
            'class' => 'form-control'
        ];

        $salesTaxIncluded = [
            'id' => 'city_tax_included',
            'class' => 'form-control'
        ];

        $this->add([
            'name' => 'tot',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'tot',
                'placeholder' => 'Pure',
            ],
        ]);

        $this->add([
            'name' => 'tot_additional',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'tot',
                'placeholder' => 'Additional',
            ],
        ]);

        $this->add([
            'name' => 'tot_type',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => Taxes::getTaxesType(),
            ],
            'attributes' => [
                'class' => 'form-control tax-type',
                'id' => 'tot_type',
            ],
        ]);

        $this->add([
            'name' => 'tot_included',
            'options' => [
                'label' => 'TOT Included',
                'value_options' => $taxIncludedOptions,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $totIncluded
        ]);

        $this->add([
            'name' => 'tot_max_duration',
            'attributes' => [
                'type' => 'text',
                'placeholder' => 'Max. Duration',
                'class' => 'form-control tax-duration',
                'id' => 'tot-max-duration'
            ],
        ]);

        $this->add([
            'name' => 'vat',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'vat',
                'placeholder' => 'Pure',
            ],
        ]);

        $this->add([
            'name' => 'vat_additional',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'vat',
                'placeholder' => 'Additional',
            ],
        ]);

        $this->add([
            'name' => 'vat_type',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => Taxes::getTaxesType(),
            ],
            'attributes' => [
                'class' => 'form-control tax-type',
                'id' => 'vat_type',
            ],
        ]);

        $this->add([
            'name' => 'vat_included',
            'options' => [
                'label' => 'VAT Included',
                'value_options' => $taxIncludedOptions,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $vatIncluded
        ]);

        $this->add([
            'name' => 'vat_max_duration',
            'attributes' => [
                'type' => 'text',
                'placeholder' => 'Max. Duration',
                'class' => 'form-control tax-duration',
                'id' => 'vat-max-duration'
            ],
        ]);

        $this->add([
            'name' => 'sales_tax',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'sales_tax',
                'placeholder' => 'Pure',
            ],
        ]);

        $this->add([
            'name' => 'sales_tax_additional',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'sales_tax',
                'placeholder' => 'Additional',
            ],
        ]);

        $this->add([
            'name' => 'sales_tax_type',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => Taxes::getTaxesType(),
            ],
            'attributes' => [
                'class' => 'form-control tax-type',
                'id' => 'sales_tax_type',
            ],
        ]);

        $this->add([
            'name' => 'sales_tax_included',
            'options' => [
                'label' => 'Sales Tax Included',
                'value_options' => $taxIncludedOptions,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $salesTaxIncluded
        ]);

        $this->add([
            'name' => 'sales_tax_max_duration',
            'attributes' => [
                'type' => 'text',
                'placeholder' => 'Max. Duration',
                'class' => 'form-control tax-duration',
                'id' => 'sales-tax-max-duration'
            ],
        ]);

        $this->add([
            'name' => 'city_tax',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'city_tax',
                'placeholder' => 'Pure',
            ],
        ]);

        $this->add([
            'name' => 'city_tax_additional',
            'attributes' => [
                'type' => 'text',
                'class' => 'form-control tax-value',
                'id' => 'city_tax',
                'placeholder' => 'Additional',
            ],
        ]);

        $this->add([
            'name' => 'city_tax_type',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => Taxes::getTaxesType(true),
            ],
            'attributes' => [
                'class' => 'form-control tax-type',
                'id' => 'city_tax_type',
            ],
        ]);

        $this->add([
            'name' => 'city_tax_included',
            'options' => [
                'label' => 'City Tax Included',
                'value_options' => $taxIncludedOptions,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $cityTaxIncluded
        ]);

        $this->add([
            'name' => 'city_tax_max_duration',
            'attributes' => [
                'type' => 'text',
                'placeholder' => 'Max. Duration',
                'class' => 'form-control tax-duration',
                'id' => 'city-tax-max-duration'
            ],
        ]);

        /**********POI**********/
        $poi_type = [];
        if (isset($options['poi_types'])){
            foreach ($options['poi_types'] as $row){
                $poi_type[$row->getId()] = $row->getName();
            }
        }
        $this->add([
            'name' => 'poi_type',
            'options' => [
                'label' => 'POI Type',
                'value_options' => $poi_type,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'poi_type',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'ws_show_right_column',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => 'Show Right Column',
                'for'   => "ws_show_right_column"
            ],
            'attributes' => [
                'id' => 'ws_show_right_column',
            ]
        ]);

        $buttons_save = 'Create New Location';
        if (is_object($data)) {
            $buttons_save = 'Save Changes';
        }
        $this->add([
            'name' => 'save_button',
            'options' => [
                'label' => $buttons_save,
            ],
            'attributes' => [
                'type' => 'button',
                'class' => 'btn btn-primary pull-right col-sm-2 col-xs-12 margin-left-10',
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save',
            ],
        ]);

        $this->add([
            'name' => 'edit_id',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'edit_id',
            ],
        ]);

        $this->add([
            'name' => 'type_location',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'type_location',
            ],
        ]);

        $this->add([
            'name' => 'cover_image_post',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'img1_post',
            ],
        ]);

        $this->add([
            'name' => 'thumbnail_post',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'img2_post',
            ],
        ]);

        $this->add([
            'name' => 'autocomplete_id',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'autocomplete_id',
            ],
        ]);

        $this->add([
            'name' => 'edit_name',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'edit_name',
            ],
        ]);

        $this->add([
            'name' => 'edit_location_id',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'edit_location_id',
            ],
        ]);

        $this->add([
            'name' => 'poi_type_chaneg',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'poi_type_chaneg',
            ],
        ]);

        $this->add([
            'name' => 'province_short_name',
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'maxlength' => 150,
                'id'        => 'province_short_name',
            ],
        ]);

        if (is_object($data)) {
            $this->add([
                'name' => 'delete_button',
                'options' => [
                    'label' => 'Delete',
                ],
                'attributes' => [
                    'type' => 'button',
                    'class' => 'btn btn-danger pull-right col-sm-2 col-xs-12 margin-left-10',
                    'data-loading-text' => 'Deleteing...',
                    'id' => 'delete_button',
                    'value' => 'Delete',
                ],
            ]);
        }

        if (is_object($data)) {
            $objectData = new \ArrayObject();
            $objectData['name']          = $data->getName();
            $objectData['edit_name']     = $data->getName();
            $objectData['latitude']      = $data->getLatitude();
            $objectData['longitude']         = $data->getLongitude();
            $objectData['information']   = $data->getInformation_text();
            $objectData['edit_id']       = $data->getId();
            $objectData['type_location'] = $type;
            $objectData['cover_image_post']     = '';
            $objectData['thumbnail_post']     = '';

            if ($data->getCover_image()) {
                $objectData['cover_image_post']     = '/locations/' . $data->getId() .'/' . $data->getCover_image();
            }

            if ($data->getThumbnail()) {
                $objectData['thumbnail_post']     = '/locations/' . $data->getId() .'/' . $data->getThumbnail();
            }

            if ($type == LocationService::LOCATION_TYPE_COUNTRY) {
                $objectData['iso']                  = $data->getIso();
                $objectData['currency']             = (isset($datas['currency']) ? $datas['currency'] : 0);
                $objectData['required_postal_code'] = (isset($datas['required_postal_code']) ? $datas['required_postal_code'] : 0);
                $objectData['contact_phone']        = (isset($datas['contactPhone']) ? $datas['contactPhone'] : 0);
            }

            if ($type == LocationService::LOCATION_TYPE_PROVINCE) {
                $objectData['province_short_name'] = $datas['province_short_name'];
            }

            if ($type == LocationService::LOCATION_TYPE_CITY) {
                if ($data->getTotType()) {
                    $objectData['tot_type']         = $data->getTotType();
                    $objectData['tot']              = ($data->getTot()) ? $data->getTot() : '';
                    $objectData['tot_additional']   = ($data->getTotAdditional()) ? $data->getTotAdditional() : '';
                    $objectData['tot_included']     = $data->getTotIncluded();
                    $objectData['tot_max_duration'] = $data->getTotMaxDuration() ? $data->getTotMaxDuration() : '';
                }

                if ($data->getVatType()) {
                    $objectData['vat_type']         = $data->getVatType();
                    $objectData['vat']              = ($data->getVat()) ? $data->getVat() : '';
                    $objectData['vat_additional']   = ($data->getVatAdditional()) ? $data->getVatAdditional() : '';
                    $objectData['vat_included']     = $data->getVatIncluded();
                    $objectData['vat_max_duration'] = $data->getVatMaxDuration() ? $data->getVatMaxDuration() : '';
                }

                if ($data->getSalesTaxType()) {
                    $objectData['sales_tax_type']         = $data->getSalesTaxType();
                    $objectData['sales_tax']              = ($data->getSales_tax()) ? $data->getSales_tax() : '';
                    $objectData['sales_tax_additional']   = $data->getSalesTaxAdditional();
                    $objectData['sales_tax_included']     = $data->getSalesTaxIncluded();
                    $objectData['sales_tax_max_duration'] = $data->getSalesTaxMaxDuration() ? $data->getSalesTaxMaxDuration() : '';
                }

                if ($data->getCityTaxType()) {
                    $objectData['city_tax_type']         = $data->getCityTaxType();
                    $objectData['city_tax']              = ($data->getCity_tax()) ? $data->getCity_tax() : '';
                    $objectData['city_tax_additional']   = ($data->getCityTaxAdditional()) ? $data->getCityTaxAdditional() : '';
                    $objectData['city_tax_included']     = $data->getCityTaxIncluded();
                    $objectData['city_tax_max_duration'] = $data->getCityTaxMaxDuration() ? $data->getCityTaxMaxDuration() : '';
                }

                $objectData['timezone'] = (isset($datas['timezone']) ? $datas['timezone'] : '');
            }

            if ($type == LocationService::LOCATION_TYPE_POI) {
                $poitype                       = $datas['poitype'];
                $objectData['poi_type']        = $poitype->getType_id();
                $objectData['poi_type_chaneg'] = $poitype->getType_id();
                $objectData['ws_show_right_column'] = $poitype->getWsShowRightColumn();
            }

            $this->bind($objectData);
        }
    }
}
