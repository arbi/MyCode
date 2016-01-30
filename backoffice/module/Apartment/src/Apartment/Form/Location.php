<?php

namespace Apartment\Form;

use Library\Form\FormBase;

class Location extends FormBase
{
	protected $buildingOptions = [];
	protected $countryOptions = [];
	protected $provinceOptions = [];
	protected $cityOptions = [];

	public function __construct($name = 'apartment_location', $preparedData = [])
    {
		parent::__construct($name);

		if (count($preparedData)) {
			$this->setCountryOptions($preparedData['countryOptions']);
			$this->setProvinceOptions($preparedData['provinceOptions']);
			$this->setCityOptions($preparedData['cityOptions']);
			$this->setBuildingOptions($preparedData['buildingOptions']);
		}

		$this->setName($name);
		$this->setAttribute('method', 'POST');
		$this->setAttribute('action', 'location/save');
		$this->setAttribute('id', 'apartment_location');
        $this->setAttribute('enctype', 'multipart/form-data');
        $this->setAttribute('class', 'form-horizontal');

		// Description Textline ID
		$this->add([
            'name' => 'description_textline',
            'attributes' => [
                'id'    => 'description_textline',
                'type'  => 'hidden',
                'value' => 0,
            ],
		]);

		// Directions Textline ID
		$this->add([
            'name' => 'directions_textline',
            'attributes' => [
                'id'    => 'directions_textline',
                'type'  => 'hidden',
                'value' => 0,
            ],
		]);

		// Country
        $attrCountry =  [
            'id' => 'country_id',
            'class' => 'form-control'
        ];
        if(isset($preparedData['countryId']) && $preparedData['countryId'] > 1) {
            $attrCountry['disabled'] = true;
        }
		$this->add([
            'name' => 'country_id',
            'options' => [
                'label' => 'Country',
                'value_options' => $this->countryOptions,
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $attrCountry,
		]);

		// Province
		$this->add([
            'name' => 'province_id',
            'options' => [
                'label' => 'Province',
                'value_options' => $this->provinceOptions,
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'province_id',
                'class' => 'form-control'
            ],
		]);

		// City
		$this->add([
            'name' => 'city_id',
            'options' => [
                'label' => 'City',
                'value_options' => $this->cityOptions,
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'city_id',
                'class' => 'form-control'
            ],
		]);

        // Building
        $this->add([
            'name' => 'building',
            'options' => [
                'label' => 'Building',
                'value_options' => $this->buildingOptions,
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'building',
                'class' => 'form-control'
            ],
		]);

        $this->add([
            'type' => 'Zend\Form\Element\Select',
            'name' => 'building_section',
            'options' => [
                'label' => 'Building Section',
                'value_options' => isset($preparedData['buildingSectionOptions']) ? $preparedData['buildingSectionOptions'] : [],
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'attributes' => [
                'id' => 'building_section',
                'class' => 'form-control'
            ],
        ]);

		// Address
		$this->add([
            'name' => 'address',
            'options' => [
                'label' => 'Address',
                'required' => true,
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'address',
                'class' => 'form-control'
            ],
		]);

        // Block
        $this->add([
            'name' => 'block',
            'options' => [
                'label' => 'Block',
                'disable_inarray_validator' => true,
            ],
            'attributes' => [
                'id' => 'block',
                'type' => 'text',
                'class' => 'form-control'
            ],
        ]);

		// Floor
		$this->add([
            'name' => 'floor',
            'options' => [
                'label' => 'Floor',
                'value_options' => $this->getFloor()
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'floor',
                'type' => 'text',
                'class' => 'form-control'
            ],
		]);

		// Unit Number
		$this->add([
            'name' => 'unit_number',
            'options' => [
                'label' => 'Unit Number',
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'unit_number',
                'class' => 'form-control'
            ],
		]);

		// Postal Code
		$this->add([
            'name' => 'postal_code',
            'options' => [
                'label' => 'Postal Code',
                'required' => true,
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'postal_code',
                'class' => 'form-control'
            ],
		]);

		// Longitude
		$this->add([
            'name' => 'longitude',
            'options' => [
                'label' => 'Longitude',
                'required' => true,
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'longitude',
                'class' => 'form-control'
            ],
		]);

		// Latitude
		$this->add([
            'name' => 'latitude',
            'options' => [
                'label' => 'Latitude',
                'required' => true,
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'latitude',
                'class' => 'form-control'
            ],
		]);

        // Save button
        $this->add([
			'name' => 'save_button',
			'type' => 'submit',
			'options' => [
				'label' => 'Save Changes',
			],
			'attributes' => [
				'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save Changes',
                'class' => 'btn btn-primary pull-right col-sm-2 col-xs-12'
			],
		]);
	}
    private function getFloor()
    {
        $floor = [-1 => '--', 0 => 'GF', 100 => 'PH'];

        for ($i = 1; $i <= 50; $i++) {
            $floor[$i] = $i;
        }

        return $floor;
    }

	public function setBuildingOptions($buildingOptions)
    {
		$this->buildingOptions = $buildingOptions;
	}

	public function setCountryOptions($countryOptions)
    {
		$this->countryOptions = $countryOptions;
	}

	public function setProvinceOptions($provinceOptions)
    {
		$this->provinceOptions = $provinceOptions;
	}

	public function setCityOptions($cityOptions)
    {
		$this->cityOptions = $cityOptions;
	}
}
