<?php

namespace Warehouse\Form;

use Library\Form\FormBase;
use DDD\Service\Warehouse\Asset as AssetService;

class AssetConsumableSearch extends FormBase {


    public function __construct( ) {
        parent::__construct('assets-consumable-search-form');

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
            'name' => 'running_out',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->getRunningOutOptions(),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'running-out',
            ),
        ));


    }

    protected function getRunningOutOptions()
    {
        return [
          0 => '-- All Statuses --',
            AssetService::RUNNING_OUT_YES => 'Running Out',
            AssetService::RUNNING_OUT_NO => 'Not Running Out',
            AssetService::RUNNING_OUT_NOT_SET => 'Threshold is not Set',
        ];
    }


}
