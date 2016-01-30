<?php

namespace Backoffice\Form\ApartmentGroup;

use Library\Form\FormBase;
use Library\Constants\Objects;
use Library\Utility\Debug;

class BuildingForm extends FormBase {
    /**
     * @param string $name
     * @param \DDD\Domain\ApartmentGroup\ApartmentGroup $apartmentGroupData
     */
    public function __construct($name, $apartmentGroupData, $options) {
        parent::__construct($name);
        $this->setName($name);
        $this->setAttributes([
            'enctype' => 'multipart/form-data',
        ]);

        $name_attr = array(
            'type'      => 'hidden',
            'class'     => 'form-control',
            'id'        => 'name',
            'maxlength' => 150,
            'value'     => $apartmentGroupData->getName()
        );

        $this->add(array(
            'name' => 'name',
            'attributes' => $name_attr,
        ));

        $this->add(
            [
                'name'    => 'building_phone',
                'type'    => 'Zend\Form\Element\Text',
                'options' => [
                    'label' => false,
                ],
                'attributes' => [
                    'placeholder' => 'Building Phone',
                    'class'       => 'form-control',
                    'id'          => 'building_phone'
                ],
            ]
        );

        $buttons_save = 'Save Changes';

        $this->add(array(
            'name' => 'save_button',
            'options' => array(
                'label' => $buttons_save,
            ),
            'attributes' => array(
                'type'              => 'button',
                'class'             => 'btn btn-primary state col-sm-2 col-xs-12 margin-left-10 pull-right',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
                'value'             => 'Save',
            ),
        ));

        $this->add([
            'name' => 'map_attachment',
            'options' => [
                'label' => 'Map Image For KI',
            ],
            'attributes' => [
                'type' => 'file',
                'id' => 'map_attachment',
                'class' => 'hidden-file-input',
                'accept' => 'image/*',
            ],
        ]);

        $this->add([
            'name' => 'delete_attachment',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'delete_attachment',
                'class' => 'hide',
            ],
        ]);

        // Key Instruction Page Type
        $this->add([
            'name' => 'key_instruction_page_type',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'key_instruction_page_type',
                'value' => '1',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Key Retrieval Type',
                'value_options' => (isset($options['keyInstructionPageTypes']) ? $options['keyInstructionPageTypes'] : []),
                'disable_inarray_validator' => true
            ],
        ]);

        $this->add([
            'name' => 'assigned_office_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'assigned_office_id',
                'value' => '1',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Office Assigned',
                'value_options' => (isset($options['officeOptions']) ? $options['officeOptions'] : []),
                'disable_inarray_validator' => true
            ],
        ]);

        if (is_object($apartmentGroupData)) {
            $objectData = new \ArrayObject();
            $objectData['building_phone'] = $apartmentGroupData->getBuildingPhone();
            $objectData['key_instruction_page_type'] = $apartmentGroupData->getKIPageType();
            $objectData['assigned_office_id'] = $apartmentGroupData->getAssignedOfficeId();
            $this->bind($objectData);
        }

    }
}
