<?php

namespace Apartment\Form;

use Library\Form\FormBase;

class CubilisConnection extends FormBase {
	public function __construct($action) {
		parent::__construct ( 'apartment_cubilis_connection' );

		$this->setName ( 'apartment_cubilis_connection'  );
		$this->setAttribute ( 'method', 'POST' );
        $this->setAttribute ( 'action', $action );
        $this->setAttribute ( 'class', 'form-horizontal' );

		// Synchronize
		$this->add([
			'name' => 'sync_cubilis',
			'type' => 'Zend\Form\Element\Checkbox',
			'attributes' => [
				'id' => 'sync_cubilis',
				'value' => '0',
			],
			'options' => [
				'label' => 'Synchronize',
			],
		]);

		// Cubilis ID
		$this->add([
			'name' => 'cubilis_id',
			'options' => [
				'label' => 'Cubilis ID'
			],
			'attributes' => [
				'type' => 'text',
				'id' => 'cubilis_id',
                'class' => 'form-control'
			]
		]);

		// Cubilis Username
		$this->add([
			'name' => 'cubilis_username',
			'options' => [
				'label' => 'Cubilis Username'
			],
			'attributes' => [
				'type' => 'text',
				'id' => 'cubilis_username',
                'class' => 'form-control'
			]
		]);

		// Cubilis Password
		$this->add([
			'name' => 'cubilis_password',
			'options' => [
				'label' => 'Cubilis Password'
			],
			'attributes' => [
				'type' => 'text',
				'id' => 'cubilis_password',
                'class' => 'form-control'
			]
		]);

		// Save button
		$this->add ([
			'name' => 'save_button',
			'options' => [
				'label' => false
			],
			'attributes' => [
				'type' => 'submit',
				'data-loading-text' => 'Saving...',
				'class' =>'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right',
				'id' => 'save_button',
				'value' => 'Save'
			]
		]);
	}
}
