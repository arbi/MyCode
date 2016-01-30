<?php

namespace Parking\Form;

use Library\Form\FormBase;

class General extends FormBase
{
    protected $locks     = [];
    protected $countries = [];
    protected $provinces = [];
    protected $cities    = [];

	public function __construct($parkingLotId, $name = 'parking-general', $data = false, $options = [])
    {
		parent::__construct($name);

        /** @var \DDD\Domain\Parking\General $data */

        if (count($options)) {
			$this->setLocks($options['locks']);
			$this->setCountries($options['countries']);
            $this->setProvinces($options['provinces']);
            $this->setCities($options['cities']);
		}

		$this->setName ( $name );
		$this->setAttribute ( 'method', 'POST' );
        $this->setAttribute ( 'class', 'form-horizontal' );

		// Id
		$this->add([
            'name' => 'id',
            'attributes' => [
                'type'  => 'hidden',
                'id'=>'parking-id'
            ],
		]);

		// Name
		$this->add ([
            'name' => 'name',
            'options' => [
                'label' => 'Parking Lot Name',
                'required' => true
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'parking-lot-name',
                'class' => 'form-control',
                'data-toggle'    => 'tooltip',
                'data-placement' => 'right',
                'title'          => 'Parking lot name can only contain alphanumeric symbols, and the length should not exceed 45 characters',
            ]
		]);

		// Address
		$this->add ([
            'name' => 'address',
            'options' => [
                'label' => 'Address',
                'required' => false
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'address',
                'class' => 'form-control',
            ]
		]);

		// Permit
		$this->add ([
            'name' => 'parking_permit',
            'options' => [
                'label' => 'Parking Permit',
                'required' => false
            ],
            'attributes' => [
                'type' => 'hidden',
                'id' => 'parking-permit',
            ]
		]);

		// Is Virtual
		$this->add ([
            'name' => 'is_virtual',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => 'Is Virtual',
                'use_hidden_element' => true,
                'required' => false,
                'checked_value' => '1',
                'unchecked_value' => '0'
            ],
            'attributes' => [
                'id' => 'is-virtual',
            ]
		]);

		// Lock
		$this->add ([
            'name' => 'lock_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'Entry Lock',
                'required' => true,
                'value_options' => $this->getLocks(),
            ],
            'attributes' => [
                'id' => 'lock-id',
                'class' => 'form-control'
            ]
		]);

        $countryAttributes = [
            'id' => 'country-id',
            'class' => 'form-control'
        ];

        if ($parkingLotId) {
            $countryAttributes['disabled'] = true;
        }

		// Country
		$this->add ([
            'name' => 'country_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'Country',
                'value_options' => $this->getCountries(),
                'required' => true,
            ],
            'attributes' => $countryAttributes
		]);

		// Province
		$this->add ([
            'name' => 'province_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'Province',
                'value_options' => $this->getProvinces(),
                'required' => true,
                'disable_inarray_validator' => true,
            ],
            'attributes' => [
                'id' => 'province-id',
                'class' => 'form-control'
            ]
		]);

		// City
		$this->add ([
            'name' => 'city_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'City',
                'value_options' => $this->getCities(),
                'required' => true,
                'disable_inarray_validator' => true,
            ],
            'attributes' => [
                'id' => 'city-id',
                'class' => 'form-control'
            ]
		]);

        // Save button
        $this->add([
			'name' => 'save_button',
			'type' => 'Zend\Form\Element\Button',
			'options' => [
				'label' => 'Save',
			],
			'attributes' => [
				'data-loading-text' => 'Saving...',
                'id'                => 'save-button',
                'value'             => 'Save',
                'class'             => 'btn btn-primary col-sm-2 col-xs-12 pull-right margin-left-10',
			],
		]);

        if ($data) {
            $this->populateValues([
                'id'          => $data->getId(),
                'name'        => $data->getName(),
                'is_virtual'  => $data->isVirtual(),
                'lock_id'     => $data->getLockId(),
                'country_id'  => $data->getCountryId(),
                'province_id' => $data->getProvinceId(),
                'city_id'     => $data->getCityId(),
                'address'     => $data->getAddress()
            ]);
        }
	}

    /**
     * @param array $locks
     */
    public function setLocks($locks)
    {
        $this->locks = $locks;
    }

    /**
     * @return array
     */
    public function getLocks()
    {
        return $this->locks;
    }

    /**
     * @param $countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @return array
     */
    public function getCities()
    {
        return $this->cities;
    }

    /**
     * @param array $cities
     */
    public function setCities($cities)
    {
        $this->cities = $cities;
    }

    /**
     * @return array
     */
    public function getProvinces()
    {
        return $this->provinces;
    }

    /**
     * @param array $provinces
     */
    public function setProvinces($provinces)
    {
        $this->provinces = $provinces;
    }
}
