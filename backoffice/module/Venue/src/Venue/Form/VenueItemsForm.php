<?php

namespace Venue\Form;

use DDD\Service\Venue\Items as ItemsService;
use Library\Form\FormBase;

class VenueItemsForm extends FormBase
{
    public function __construct()
    {
        parent::__construct('venue-items-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'titles[]',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class' => 'form-control venue-item-title',
                'id' => 'add-item-title',
                'placeholder' => 'Name'
            ]
        ]);

        $this->add([
            'name' => 'descriptions[]',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class' => 'form-control venue-item-description',
                'id' => 'add-item-description',
                'placeholder' => 'Description'
            ],
        ]);

        $this->add([
            'name' => 'prices[]',
            'type' => 'Zend\Form\Element\Number',
            'attributes' => [
                'class' => 'form-control venue-item-price',
                'step'  => '0.01',
                'id' => 'add-item-price',
                'placeholder' => 'Price'
            ],
        ]);

        $this->add([
            'name' => 'availabilities[]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control venue-item-availability',
                'id' => 'add-item-availability'
            ],
            'options' => [
                'label' => 'Charged User',
                'disable_inarray_validator' => true,
                'value_options'     => ItemsService::getStatuses()
            ]
        ]);
    }
}