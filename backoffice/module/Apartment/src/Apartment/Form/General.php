<?php

namespace Apartment\Form;

use Apartment\Form\InputFilter\GeneralFilter;
use Library\Form\FormBase;
use Library\Constants\Objects;

class General extends FormBase
{
    protected $buildingOptions = [];

	public function __construct($apartmentId, $name = 'apartment_general', $preparedData = [])
    {
		parent::__construct ( $name );

        if (count($preparedData)) {
			$this->setBuildingOptions($preparedData['buildingOptions']);
		}

		$this->setName ( $name );
		$this->setAttribute ( 'method', 'POST' );
		$this->setAttribute ( 'action', 'general/save' );
        $this->setAttribute ( 'class', 'form-horizontal' );

        // add filter for form
        $generalFilter = new GeneralFilter();
        $this->setInputFilter($generalFilter->getInputFilter());

		// ID
		$this->add([
            'name' => 'id',
            'attributes' => [
                'type'  => 'hidden',
                'value' => 0,
                'id' =>'aId'
            ],
		]);

		// Name
		$this->add ([
            'name' => 'apartment_name',
            'options' => [
                'label' => 'Promotional Name',
                'required' => true
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'apartment_name',
                'class' => 'form-control',
                'data-toggle'    => 'tooltip',
                'data-placement' => 'right',
                'title'          => 'Apartment name can only contain letters and a space, and the length should not exceed 40 characters'
            ]
		]);

        // Building
        $this->add([
            'name' => 'building_id',
            'options' => [
                'label' => 'Building',
                'value_options' => $this->buildingOptions,
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'building_id',
                'class' => 'form-control'
            ],
		]);

        $this->add([
            'type' => 'Zend\Form\Element\Select',
            'name' => 'building_section',
            'options' => [
                'label' => 'Building Section',
                'value_options' => [],
                'disable_inarray_validator' => true,
                'required' => true,
            ],
            'attributes' => [
                'id' => 'building_section',
                'class' => 'form-control'
            ],
        ]);

		// Status
        if($apartmentId > 0) {
            $statuses = Objects::getProductStatuses();
        } else {
            $statuses = [Objects::PRODUCT_STATUS_SANDBOX => 'Sandbox'];
        }

		$this->add([
            'name' => 'status',
            'options' => [
                'label' => 'Status',
                'value_options' => $statuses,
                'required' => true
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array (
                'id' => 'status',
                'class' => 'form-control'
            )
		]);

		/************************************* METRICS *************************************/

		// Room count
		$this->add([
            'name' => 'room_count',
            'options' => [
                'label' => 'Room Count'
            ],
            'attributes' => [
                'type' => 'number',
                'id' => 'room_count',
                'class' => 'form-control',
            ],
		]);

		// Square meters
		$this->add ([
            'name' => 'square_meters',
            'options' => array (
                'label' => 'Square Meters'
            ),
            'attributes' => [
                'type' => 'text',
                'id' => 'square_meters',
                'class' => 'form-control',
            ]
		]);

		// Max. capacity
		$this->add([
            'name' => 'max_capacity',
            'options' => [
                'label' => 'Max. Capacity',
                'required' => true
            ],
            'attributes' => [
                'type' => 'number',
                'id' => 'max_capacity',
                'class' => 'form-control',
            ],
		]);

		// Bedrooms
		$this->add([
            'name' => 'bedrooms',
            'options' => [
                'label' => 'Bedrooms'
            ],
            'attributes' => [
                'type' => 'number',
                'id' => 'bedrooms',
                'class' => 'form-control',
            ],
		]);

		// Bathrooms
		$this->add([
            'name' => 'bathrooms',
            'options' => array (
                'label' => 'Bathrooms'
            ),
            'attributes' => [
                'type' => 'number',
                'id' => 'bathrooms',
                'class' => 'form-control',
            ],
		]);

		/************************************* POLICY *************************************/
		// Check in time
		$this->add ([
            'name' => 'chekin_time',
            'options' => [
                'label' => 'Check-in',
            ],
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'chekin_time',
                'class' => 'form-control datetimepicker',
            ],
		]);

		// Check out time
		$this->add([
            'name' => 'chekout_time',
            'options' => [
                'label' => 'Check-out',
            ],
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'chekout_time',
                'class' => 'form-control datetimepicker'
            ],
		]);

		/************************************* DESCRIPTIONS *************************************/
		// General description Textline ID
		$this->add([
            'name' => 'general_description_textline',
            'attributes' => [
                'type'  => 'hidden',
                'value' => 0,
                'class' => 'form-control',
            ],
		]);

		// General description
		$this->add ([
            'name' => 'general_description',
            'options' => [
                'label' => 'General Description',
                'required' => true
            ],
            'attributes' => [
                'type' => 'textarea',
                'id' => 'general_description',
                'class' => 'form-control tinymce',
            ],
		]);

        $btnName = 'Save Changes';
        if (!$apartmentId) {
            $btnName = 'Add Apartment';
        }
        // Save button
        $this->add([
			'name' => 'save_button',
			'type' => 'Zend\Form\Element\Button',
			'options' => [
				'label' => $btnName,
			],
			'attributes' => [
				'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => $btnName,
                'class' => 'btn btn-primary col-sm-2 col-xs-12 pull-right',
			],
		]);
	}

    public function setBuildingOptions($buildingOptions)
    {
		$this->buildingOptions = $buildingOptions;
	}
}
