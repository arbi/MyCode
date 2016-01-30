<?php

namespace Apartel\Form;

use DDD\Service\Apartel\General as GeneralService;

use Library\Form\FormBase;

class General extends FormBase
{
    public function __construct($apartelId, $name = 'apartel_general', $data = [])
    {
        parent::__construct($name);

        if (count($data)) {
            $this->setBuildingOptions($data['buildingOptions']);
        }

        $this->setAttribute('method', 'POST');
        $this->setAttribute('action', 'general/save');
        $this->setAttribute('class', 'form-horizontal');

        // ID
        $this->add([
            'name' => 'id',
            'attributes' => [
                'type'  => 'hidden',
                'value' => 0,
                'id'=>'aId'
            ],
        ]);

        $this->add(array(
            'name' => 'status',
            'type' => 'Select',
            'options' => array(
                'label' => 'Website Status',
                'value_options' => $this->getStatuses(),
            ),
            'attributes' => array(
                'id' => 'status',
                'class' => 'form-control',
            )
        ));

        $this->add(
            [
                'name'       => 'default_availability',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => [
                    'id'                 => 'default_availability',
                    'use_hidden_element' => false,
                    'checked_value'      => 1,
                    'unchecked_value'    => 0
                ]
            ]
        );

        $this->add([
            'name' => 'save_button',
            'type' => 'Button',
            'options' => [
                'label' => 'Save Changes'
            ],
            'attributes' => [
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save Changes',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 pull-right',
            ],
        ]);
    }

    private function getStatuses()
    {
        return [
            GeneralService::APARTEL_STATUS_ACTIVE   => 'Active',
            GeneralService::APARTEL_STATUS_INACTIVE => 'Inactive'
        ];
    }
}
