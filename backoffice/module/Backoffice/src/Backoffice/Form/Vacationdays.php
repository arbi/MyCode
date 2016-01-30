<?php
namespace Backoffice\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;

class Vacationdays extends FormBase
{

    public function __construct($name = 'vacationdays-form') {
        parent::__construct($name);

	    $this->setAttribute('method', 'post');
	    $this->setAttribute('class', 'form-horizontal');
	    $this->setAttribute('id', 'vacationdays-form');

        $this->add(array(
            'name' => 'interval',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control pull-right',
                'id' => 'interval',
            ),
        ));
        
        $this->add(array(
            'name' => 'from',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'from',
            ),
        ));

        $this->add(array(
            'name' => 'to',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'to',
            ),
        ));

        $this->add(array(
            'name' => 'total_number',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'total_number',
                'max' => 365,
                'min' => 0,
                'maxlength' => 5,
            ),
        ));

        $this->add(array(
            'name' => 'vacation_type',
            'options' => array(
                'value_options' => Objects::getVacationType()
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'vacation_type',
                'class' => 'form-control',
            ),
        ));

        $this->add(array(
            'name' => 'comment',
            'attributes' => array(
                'type' => 'textarea',
                'class' => 'form-control',
                'rows' => '4',
                'id' => 'comment',
                'maxlength' => 255,
            ),
        ));


        $this->add(array(
            'name' => 'save_button',
            'options' => array(
                'label' => 'Submit Request',
            ),
            'attributes' => array(
                'type' => 'button',
                'class' => 'btn btn-primary state',
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save',
            ),
        ));
    }
}
