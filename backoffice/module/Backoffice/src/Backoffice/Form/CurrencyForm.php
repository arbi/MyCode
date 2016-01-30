<?php
namespace Backoffice\Form;

use Zend\Form\Form;

/**
 *
 * @author developer
 *
 */
class CurrencyForm extends Form
{
	/**
	 *
	 * @param string $name
	 */
    public function __construct($name = 'currency'){
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'currency-form');

        //General
        
        $this->add(array(
            'name' => 'id',
            'type' => 'Zend\Form\Element\Hidden',
        ));
        
        $this->add(array(
            'name' => 'name',
            'type' => 'Zend\Form\Element\Text',
            'attributes'    => array(
                'id' => 'name',
                'class' => 'form-control',
            ),
            'options'   => array(
                'label' => 'Name',
            ),
        ));
        $this->add(array(
            'name' => 'code',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'id' => 'code',
                'class' => 'form-control',
            ),
            'options' => array(
                'label' => 'Code',
            ),
        ));
        $this->add(array(
            'name' => 'symbol',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'id' => 'symbol',
                'class' => 'form-control',
            ),
            'options' => array(
                'label' => 'Symbol',
            ),
        ));
        $this->add(array(
            'name' => 'auto_update',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'label' => 'Auto Update',
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            ),
            'attributes' => array(
                'id' => 'auto-update',
            ),
        ));
        $this->add(array(
            'name' => 'visible',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'label' => 'Visible',
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            ),
            'attributes' => array(
                'id' => 'visible',
            ),
        ));
        $this->add(array(
            'name' => 'gate',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'label' => 'Gateway Availability',
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            ),
            'attributes' => array(
                'id' => 'gate',
            ),
        ));

        //Submit button
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
            ),
            'attributes' => array(
                'value' => 'Submit',
                'class' => 'btn btn-primary pull-right col-xs-12 col-sm-2'
            ),
            'options'    => array(
                'primary' => true,
            ),
        ));

    }
}
