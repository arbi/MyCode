<?php

namespace Apartment\Form;

use Library\Form\FormBase;

class Media extends FormBase
{
	public function __construct($name = 'apartment_media')
    {
		parent::__construct ($name);

		$this->setName($name);
		$this->setAttribute('method', 'POST');
		$this->setAttribute('action', 'media/save');
        $this->setAttribute('class', 'form-horizontal');

        // YouTube Link
		$this->add([
            'name' => 'video',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'YouTube Link',
            ],
            'attributes' => [
                'id' => 'video',
                'class' => 'form-control'
            ]
		]);

        // Key Entry Link
		$this->add([
            'name' => 'key_entry_video',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'Key Entry Link',
            ],
            'attributes' => [
                'id' => 'key_entry_video',
                'class' => 'form-control'
            ]
		]);

		// Save button
		$this->add([
            'name' => 'save_button',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'type' => 'submit',
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save Changes',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 pull-right margin-left-10'
            ]
		]);
	}
}
