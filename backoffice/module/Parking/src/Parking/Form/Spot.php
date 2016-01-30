<?php

namespace Parking\Form;

use Library\Form\FormBase;

class Spot extends FormBase
{
	public function __construct($parkingLotId, $name = 'parking-spot', $options = [])
    {
		parent::__construct($name);

		$this->setName($name);
		$this->setAttribute('method', 'POST');
        $this->setAttribute('class', 'form-horizontal');

		// Id
		$this->add([
            'name' => 'id',
            'attributes' => [
                'type'  => 'hidden',
                'id'=>'spot-id'
            ],
		]);

		// Unit
		$this->add ([
            'name' => 'unit',
            'options' => [
                'label' => 'Unit',
                'required' => true
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'parking-spot-unit',
                'class' => 'form-control',
                'data-toggle'    => 'tooltip',
                'data-placement' => 'right',
                'title'          => 'Spot unit can only contain letters and a space, and the length should not exceed 45 characters',
            ]
		]);

		// Price
		$this->add ([
            'name' => 'price',
            'options' => [
                'label' => 'Price',
                'required' => true
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'price',
                'class' => 'form-control',
            ]
		]);

        // Permit Id
        $this->add ([
            'name' => 'permit_id',
            'options' => [
                'label' => 'Permit Id',
                'required' => false
            ],
            'attributes' => [
                'type' => 'text',
                'id' => 'permit_id',
                'class' => 'form-control',
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
                'id' => 'save-button',
                'value' => 'Save',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right',
			],
		]);
	}
}
