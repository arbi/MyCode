<?php

namespace Warehouse\Form;

use Library\Form\FormBase;

class AssetValuable extends FormBase {


    public function __construct($activeUsers, $id = false, $allActiveCategoriesArray = false, $valuableAssetsStatuses = false, $data = false) {
        parent::__construct('assets-consumable-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');


        $this->add(array(
            'name' => 'name',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'name',
                'required' => true
            ),
        ));

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
            'name' => 'serial_number',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'serial-number',
                'required' => true
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

        $this->add([
            'name'       => 'description',
            'type'       => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'description',
                'maxlength' => 250,
            ],
        ]);

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


        if (FALSE !== $valuableAssetsStatuses) {
            unset($valuableAssetsStatuses[0]);
            $this->add(array(
                'name' => 'status',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => $valuableAssetsStatuses,
                ),
                'attributes' => array(
                    'class' => 'form-control selectize',
                    'id' => 'status',
                ),
            ));
        }
        if (FALSE !== $id) {
            $this->add([
                'name' => 'id',
                'attributes' => [
                    'type'  => 'hidden',
                    'id'=>'asset-id'
                ],
            ]);
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

            $this->add([
                'name'       => 'comment_status',
                'type'       => 'Zend\Form\Element\Textarea',
                'attributes' => [
                    'class'     => 'form-control',
                    'rows'      => 3,
                    'id'        => 'comment-status',
                    'maxlength' => 250,
                ],
            ]);

                $this->populateValues([
                    'id'             => $id,
                    'category_id'    => $data->getCategoryId(),
                    'name'           => $data->getName(),
                    'serial_number'  => $data->getSerialNumber(),
                    'assignee'       => $data->getAssigneeId(),
                    'status'         => $data->getStatus(),
                    'description'    => $data->getDescription(),
                    'last_updated_by'=> $data->getFirstnameLastUpdated() . ' ' . $data->getLastnameLastUpdated(),
                ]);

        }

    }


}
