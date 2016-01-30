<?php

namespace WHOrder\Form;

use DDD\Service\WHOrder\Order;
use Library\Constants\Constants;
use Library\Form\FormBase;
use Zend\ServiceManager\ServiceLocatorInterface;

class OrderNewForm extends FormBase
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->setName('order_form');
        $this->setAttribute('method', 'post')
             ->setAttribute('class', 'form-horizontal');

        $this->add([
            'name' => 'title_template',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Order Name',
            ],
            'options' => [
                'label' => 'Name'
            ]
        ]);

        $this->add([
            'name' => 'location_target',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'location_target',
                'class' => 'form-control',
                'data-item' => '',
                'data-id' => ''
            ],
            'options' => [
                'label' => 'Location',
                'disable_inarray_validator' => true,
                'empty_option' => ' -- Select Delivery Location -- ',
            ]
        ]);

        $this->add([
            'name' => 'quantity_template',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Qty',
                'type'  => 'number'

            ],
            'options' => [
                'label' => 'Qty',
            ]
        ]);

        $this->add([
            'name' => 'quantity_type_template',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'quantity_type',
                'class' => 'form-control',
            ],
            'options' => [
                'value_options' => [
                    Order::ORDER_QUANTITY_TYPE_PIECE   => 'Piece(s)',
                    Order::ORDER_QUANTITY_TYPE_PACK    => 'Pack(s)',
                    Order::ORDER_QUANTITY_TYPE_PALETTE => 'Palette(s)',
                ],
            ]
        ]);

        $this->add([
            'name' => 'url_template',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => 'Url',
            ],
            'options' => [
                'label' => 'Url'
            ]
        ]);

        $this->add([
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id'    => 'description',
                'class' => 'form-control',
                'rows'      => 8,
                'maxlength' => 5000,
                'placeholder' => 'Description'
            ],
            'options' => [
                'label' => 'Description',
            ]
        ]);

        $this->add(
            [
                'name' => 'save_button',
                'type' => 'Zend\Form\Element\Submit',
                'attributes' => [
                    'id'                => 'save_button',
                    'value'             => 'Create Order',
                    'data-loading-text' => 'Saving...',
                    'disabled'          => true,
                    'class'             => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10 disabled',
                ],
                'options' => [
                    'label' => 'Create Order',
                ],
            ]
        );
    }
}
