<?php
namespace Finance\Form;

use Zend\Form\Form;

/**
 *
 * @author developer
 *
 */
class LegalEntitiesForm extends Form
{
	/**
	 *
	 * @param string $name
	 */
    public function __construct($name = 'entities', $countryOptions){
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'entities-form');

        //General

        $this->add(array(
            'name' => 'id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'legal_id',
            ),
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

        $this->add(
            [
                'name'       => 'country_id',
                'type'       => 'Zend\Form\Element\Select',
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'country_id',
                ],
                'options'    => [
                    'label'                     => 'Country',
                    'disable_inarray_validator' => true,
                    'value_options'             => $countryOptions
                ],
            ]
        );

        //Submit button
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
            ),
            'attributes' => array(
                'value' => 'Submit',
                'class' => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                'id'    => 'submit'
            ),
            'options'    => array(
                'primary' => true,
            ),
        ));

    }
}
