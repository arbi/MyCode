<?php

namespace Apartment\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;
use DDD\Service\Task as TaskService;
use Library\Utility\Helper;

class Statistics extends FormBase
{
    public function __construct($months = null)
    {
        parent::__construct('statistics-form');

        $this->add(
            [
                'name' => 'apt_location',
                'type' => 'Zend\Form\Element\Text',
                'options' => [
                    'label' => false,
                ],
                'attributes' => [
                    'id' => 'apt_location',
                    'placeholder' => 'Apartment Location, City',
                    'class' => 'form-control'
                ],
            ]
        );

        $this->add(
            [
                'name' => 'apt_location_id',
                'type' => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id' => 'apt_location_id',
                    'class' => 'form-control'
                ],
                'options' => [
                    'label' => false,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'building',
                'type' => 'Zend\Form\Element\Text',
                'options' => [
                    'label' => false,
                ],
                'attributes' => [
                    'id' => 'building',
                    'placeholder' => 'Building Name',
                    'class' => 'form-control'
                ],
            ]
        );

        $this->add(
            [
                'name' => 'building_id',
                'type' => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id' => 'building_id',
                    'class' => 'form-control'
                ],
                'options' => [
                    'label' => false,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'request_date',
                'type' => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id' => 'request_date',
                    'class' => 'form-control'
                ],
                'options' => [
                    'label' => false,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'bedroom_count',
                'type' => 'Zend\Form\Element\Text',
                'options' => [
                    'label' => false,
                ],
                'attributes' => [
                    'id'          => 'bedroom_count',
                    'placeholder' => 'Bedrooms Count',
                    'class'       => 'form-control'
                ],
            ]
        );


        $starting = [];
        for($i = date('Y'); $i <= date('Y') + 1; $i++) {
             for($j = 1; $j <= 12; $j++) {

                if($i == date('Y') && $j <date('n') - 1) {
                    continue;
                }


                if($i > date('Y') && $j >date('n') - 2) {
                    break;
                }
                $starting[$i.'_'.$j] = $months[$j - 1].', '.$i;
                // if($sel_year == $i && $sel_month == $j) {  echo 'selected="selected"' }
            }
        }

        $this->add(
            [
                'name'    => 'starting_form',
                'options' => [
                    'label'         => '',
                    'value_options' => $starting
                ],
                'type'       => 'Zend\Form\Element\Select',
                'attributes' => [
                    'id'    => 'starting_form',
                    'class' => 'form-control'
                ],
            ]
        );
    }
}