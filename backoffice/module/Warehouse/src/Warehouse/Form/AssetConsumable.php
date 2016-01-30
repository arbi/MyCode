<?php

namespace Warehouse\Form;

use Library\Form\FormBase;

class AssetConsumable extends FormBase {


    public function __construct( $id = false, $allActiveCategoriesArray = false, $data = false) {
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
                'required' => true
            ),
        ));

        $this->add(array(
            'name' => 'quantity',
            'attributes' => array(
                'type' => 'number',
                'class' => 'form-control',
                'id' => 'quantity',
                'required' => true
            ),
        ));

        $this->add(array(
            'name' => 'sku',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'sku',
                'required' => true
            ),
        ));


        $this->add([
            'name'       => 'description',
            'options'    => [
                'label' => '',
            ],
            'type'       => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'description',
                'maxlength' => 250,
            ],
        ]);

        if (FALSE !== $id) {
            $this->add([
                'name' => 'id',
                'attributes' => [
                    'type'  => 'hidden',
                    'id'=>'asset-id'
                ],
            ]);
        }


        if (FALSE !== $allActiveCategoriesArray) {
            $this->add(array(
                'name' => 'category_id',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => $allActiveCategoriesArray,
                ),
                'attributes' => array(
                    'class' => 'form-control selectize',
                    'id' => 'category-id',
                ),
            ));
        }

        if(FALSE !== $data) {

            $this->add(array(
                'name' => 'last_updated_by',
                'attributes' => array(
                    'type' => 'text',
                    'class' => 'form-control disabled',
                    'disabled' => true,
                    'id' => 'last-updated-by',
                ),
            ));

            $this->add(array(
                'name' => 'threshold',
                'attributes' => array(
                    'type' => 'number',
                    'class' => 'form-control',
                    'id' => 'threshold',
                    'required' => true
                ),
            ));

            $this->populateValues([
                'id'             => $id,
                'category_id'    => $data->getCategoryId(),
                'quantity'       => $data->getQuantity(),
                'threshold'      => $data->getThreshold(),
                'description'    => $data->getDescription(),
                'last_updated_by'=> $data->getFirstnameLastUpdated() . ' ' . $data->getLastnameLastUpdated(),
            ]);

        }


    }


}
