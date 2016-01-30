<?php

namespace Apartel\Form;

use DDD\Service\User;
use Library\Form\FormBase;

class Type extends FormBase
{

	public function __construct($apartmentList) {
		parent::__construct('apartel-type');

		$this->setAttribute('class', 'form-horizontal');
		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'type_name',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control',
				'id' => 'type_name',
			),
            'options' => [
                'label' => 'Room Type'
            ],
		));

        $list = [];
        foreach ($apartmentList as $key => $apartment) {
            $list[$key] = $apartment;
        }
        $this->add(
            array(
                'name' => 'apartment_list',
                'options' => array(
                    'label' => 'Apartments',
                    'value_options' => $list
                ),
                'type' => 'Zend\Form\Element\Select',
                'attributes' => array(
                    'id' 		=> 'apartment_list',
                    'class' 	=> 'selectize form-control',
                    'multiple' 	=> 'multiple',
                ),
            ));

        $this->add(array(
            'name'       => 'form_type_id',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'form_type_id',
            ),
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
