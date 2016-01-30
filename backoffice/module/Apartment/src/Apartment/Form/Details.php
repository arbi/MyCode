<?php

namespace Apartment\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;

class Details extends FormBase
{
	public function __construct($name = 'apartment_details', $options = [])
    {
		parent::__construct ( $name );

		$this->setName ( $name );
		$this->setAttribute ( 'method', 'POST' );
		$this->setAttribute ( 'action', 'details/save' );
        $this->setAttribute ( 'class', 'form-horizontal' );

		/********************** FINANCIAL DETAILS ***************************/

		// Monthly Budget
		$this->add([
            'name' => 'monthly_cost',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'monthly_cost',
                'class' => 'form-control text-right'
            ],
            'options' => [
                'label' => 'Monthly Budget',
            ],
		]);

		// Monthly Budget
		$this->add([
            'name' => 'notify_negative_profit',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id' => 'notify_negative_profit',
            ],
            'options' => [
                'label' => 'Monitor Performance',
            ],
		]);

		// Startup Budget
		$this->add([
            'name' => 'startup_cost',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'startup_cost',
                'class' => 'form-control text-right'
            ],
            'options' => [
                'label' => 'Startup Budget',
            ],
		]);

        $this->add([
            'name' => 'cleaning_fee',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'cleaning_fee',
                'class' => 'form-control text-right'
            ],
            'options' => [
                'label' => 'Cleaning Fee',
            ],
        ]);


        $this->add([
            'name' => 'extra_person_fee',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'extra_person_fee',
                'class' => 'form-control text-right'
            ],
            'options' => [
                'label' => 'Extra Person Fee',
            ],
        ]);

        /********************** INTERNET ***************************/


		$this->add([
            'name' => 'primary_wifi_network',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'primary_wifi_network',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Primary WiFi Network',
            ],
		]);
		$this->add( [
            'name' => 'primary_wifi_pass',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'primary_wifi_pass',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Primary WiFi Password',
            ],
		]);
		$this->add([
            'name' => 'secondary_wifi_network',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'secondary_wifi_network',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Secondary WiFi Network',
            ],
		]);
		$this->add([
            'name' => 'secondary_wifi_pass',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'secondary_wifi_pass',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Secondary WiFi Password',
            ],
		]);

        // lock
        $this->add([
            'name' => 'lock_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'lock_id',
                'value' => '0',
                'class' => 'form-control',
            ],
            'options' => [
                'label' => 'Lock',
                'value_options' => (isset($options['freeLocks']) ? $options['freeLocks']: []),
                'disable_inarray_validator' => true,
            ],
        ]);

        // Parking Spot
        $this->add([
            'name' => 'parking_spot_ids',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'parking_spot_ids',
                'value' => '0',
                'class' => 'form-control',
                'multiple' => 'multiple',
            ],
            'options' => [
                'label' => 'Preferable Spots',
                'value_options' => (isset($options['parkingSpots']) ? $options['parkingSpots']: []),
                'disable_inarray_validator' => true,
            ],
        ]);


        // Show Entry Code
		$this->add( [
            'name' => 'show_apartment_entry_code',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id' => 'show_apartment_entry_code',
                'value' => '0',
            ],
            'options' => [
                'label' => 'Show Entry Code',
            ],
		]);

		// Save button
		$this->add ([
            'name' => 'save_button',
            'options' =>[
                'label' => false
            ],
            'attributes' =>[
                'type' => 'submit',
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save Changes',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right'
            ]
		]);
	}
}
