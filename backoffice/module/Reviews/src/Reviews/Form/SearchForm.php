<?php
namespace Reviews\Form;

use Zend\Form\Form;

final class SearchForm extends Form
{

	public function __construct($name, $options) {
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');

        // Apartment Groups
        $this->add(
            array(
                'name' => 'apartment_groups',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' =>$options['allApartmentGroups'],
                ),
                'attributes' => array(
                    'class' => 'form-control selectize',
                    'id' => 'apartment_groups'
                ),
            )
        );

        // Arrival Date Range
        $this->add([
            'name' => 'arrival_date_range',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Arrival Date Range',
                'class' => 'form-control',
                'id' => 'arrival_date_range'
            ],
        ]);


        // Departure Date Range
        $this->add([
            'name' => 'departure_date_range',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Departure Date Range',
                'class' => 'form-control',
                'id' => 'departure_date_range'
            ],
        ]);


        // Tags
        $this->add(
            array(
                'name' => 'tags',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' =>$options['tags'],
                ),
                'attributes' => array(
                    'class' => 'form-control selectize',
                    'id' => 'tags',
                    'multiple' => 'multiple'
                ),
            )
        );

        // From
        $this->add([
            'name' => 'stay_length_from',
            'type' => 'Zend\Form\Element\Number',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'from',
                'class' => 'form-control',
                'id' => 'stay_length_from'
            ],
        ]);

        // To
        $this->add([
            'name' => 'stay_length_to',
            'type' => 'Zend\Form\Element\Number',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'to',
                'class' => 'form-control',
                'id' => 'stay_length_to'
            ],
        ]);


    }

}
