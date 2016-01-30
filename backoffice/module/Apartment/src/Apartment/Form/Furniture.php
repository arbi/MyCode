<?php

namespace Apartment\Form;

use Library\Form\FormBase;

class Furniture extends FormBase {
	public function __construct($name = 'apartment_furniture', $furnitureTypes) {
		parent::__construct ( $name );

		$this->setName ( $name );
		$this->setAttribute ( 'method', 'POST' );
		$this->setAttribute ( 'action', 'furniture/add' );

		// ID
		$this->add(array(
			'name' => 'apartment_id',
			'attributes' => array(
				'type'  => 'hidden',
				'value' => 0
			),
		));

		// Type
		$furnitureTypeOptions = array();
		foreach ($furnitureTypes as $type) {
			$furnitureTypeOptions[$type['id']] = $type['title'];
		}
		$this->add ( array (
			'name' => 'type',
			'options' => array (
				'label' => 'Type',
				'value_options' => $furnitureTypeOptions
			),
			'type' => 'Zend\Form\Element\Select',
			'attributes' => array (
				'id' => 'type'
			)
		) );
		
		// Count
		$this->add ( array (
				'name' => 'count',
				'options' => array (
						'label' => 'Count'
				),
				'attributes' => array (
						'type' => 'text',
						'id' => 'count'
				)
		) );

		$this->add ( array (
				'name' => 'save_button',
				'options' => array (
						'label' => false
				),
				'attributes' => array (
						'type' => 'submit',
						'id' => 'save_button',
						'value' => 'Add'
				)
		) );
	}
}