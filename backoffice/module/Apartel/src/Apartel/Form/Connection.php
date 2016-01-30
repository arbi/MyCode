<?php

namespace Apartel\Form;

use DDD\Service\User;
use Library\Form\FormBase;

class Connection extends FormBase {

	public function __construct() {
		parent::__construct('connection');

		$this->setAttribute('class', 'form-horizontal');
		$this->setAttribute('method', 'post');
		$this->setAttribute('action', 'apartel/connection/save');

		$this->add(array(
			'name' => 'cubilis_id',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control',
				'id' => 'cubilis_id',
			),
            'options' => [
                'label' => 'Cubilis ID'
            ],
		));

        $this->add(array(
			'name' => 'cubilis_username',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control',
				'id' => 'cubilis_username',
			),
            'options' => [
                'label' => 'Cubilis Username'
            ],
		));

        $this->add(array(
			'name' => 'cubilis_password',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control',
				'id' => 'cubilis_password',
			),
            'options' => [
                'label' => 'Cubilis Password'
            ],
		));

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
