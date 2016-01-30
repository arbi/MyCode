<?php

namespace Warehouse\Form;

use Library\Form\FormBase;

class AssetValuableSearch extends FormBase {


    public function __construct($activeUsers, $assetValuableStatuses) {
        parent::__construct('assets-consumable-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');



        $this->add(array(
            'name' => 'location',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'location',
            ),
        ));

        $this->add(array(
            'name' => 'status',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $assetValuableStatuses,
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'status',
            ),
        ));



        $this->add(array(
            'name' => 'assignee',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $activeUsers,
            ),
            'attributes' => array(
                'class' => 'form-control selectize',
                'id' => 'assignee',
            ),
        ));


    }


}
