<?php
namespace Finance\Form;

use Zend\Form\Form;

/**
 *
 * @author developer
 *
 */
class SupplierForm extends Form
{
	/**
	 *
	 * @param string $name
	 */
    public function __construct($name = 'supplier'){
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'supplier-form');

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
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'id' => 'description',
                'class' => 'form-control',
                'rows'  => '5'
            ),
            'options' => array(
                'label' => 'Description',
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
                'class' => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10'
            ),
            'options'    => array(
                'primary' => true,
            ),
        ));

    }
}
