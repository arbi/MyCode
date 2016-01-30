<?php

namespace WHOrder\Form;

use DDD\Domain\Team\ForSelect;
use Library\Constants\Constants;
use Library\Form\FormBase;
use Zend\ServiceManager\ServiceLocatorInterface;
use DDD\Service\WHOrder\Order;

class OrderForm extends FormBase
{
    /**
     * @param array $data
     * @param array $orderTargetData
     * @param array $supplierData
     * @param array $currencyList
     */
    public function __construct($data = [], $orderTargetData = [], $supplierData = [], $currencyList = [])
    {
        parent::__construct();

        $this->setName('order_form');
        $this->setAttribute('method', 'post')
             ->setAttribute('class', 'form-horizontal');

        $this->add([
            'name' => 'title',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'title',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Name'
            ]
        ]);

        $priceAttributes = [
            'id' => 'price',
            'class' => 'form-control'
        ];

        if ($data && $data->getStatus() != Order::STATUS_ORDER_NEW) {
            $priceAttributes['readonly'] = true;
        }
        $this->add([
            'name' => 'price',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => $priceAttributes,
            'options' => [
                'label' => 'Price'
            ]
        ]);

        if (!empty($data)) {
            $categoryId = $data->getAssetCategoryId();
        } else {
            $categoryId = '';
        }
        $this->add([
            'name' => 'asset_category_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'asset_category_id',
                'class' => 'form-control',
                'data-id' => $categoryId
            ],
            'options' => [
                'label' => 'Category',
                'disable_inarray_validator' => true,
                'empty_option' => ' -- Select a Category -- ',
            ]
        ]);

        if (!empty($data)) {
            $target = $data->getTargetType() . '_' . $data->getTargetId();
        } else {
            $target = '';
        }
        $this->add([
            'name' => 'location_target',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'location_target',
                'class' => 'form-control',
                'data-item' => !empty($orderTargetData) ? json_encode($orderTargetData) : '',
                'data-id' => $target
            ],
            'options' => [
                'label' => 'Delivery',
                'disable_inarray_validator' => true,
                'empty_option' => ' -- Select Delivery Location -- ',
            ]
        ]);

        $this->add([
            'name' => 'status_shipping',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'status_shipping',
                'class' => 'form-control',
                'data-id' => (!empty($data)) ? $data->getStatusShipping() : ''
            ],
            'options' => [
                'label' => 'Shipment',
                'disable_inarray_validator' => true,
                'empty_option' => ' -- Select a Status -- ',
            ]
        ]);

        $this->add([
            'name' => 'team_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id'    => 'team_id',
                'class' => 'form-control'
            ],
        ]);

        $this->add([
            'name' => 'currency',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'currency',
                'class' => 'form-control',
            ],
            'options' => [
                'value_options' => $this->currencyList($currencyList),
                'label' => 'Currency',
            ]
        ]);

        $this->add([
            'name' => 'quantity',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'quantity',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Quantity',
            ]
        ]);

        $this->add([
            'name' => 'quantity_type',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'quantity_type',
                'class' => 'form-control',
            ],
            'options' => [
                'value_options' => [
                    ''                                 => ' -- Select a Type -- ',
                    Order::ORDER_QUANTITY_TYPE_PIECE   => 'Piece(s)',
                    Order::ORDER_QUANTITY_TYPE_PACK    => 'Pack(s)',
                    Order::ORDER_QUANTITY_TYPE_PALETTE => 'Palette(s)',
                ],
            ]
        ]);

        $this->add([
            'name' => 'received_quantity',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'received-quantity',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Received Quantity',
            ]
        ]);

        $this->add([
            'name' => 'received_date',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'received-date',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Received Date',
            ]
        ]);

        $supplierJsonStr = '';
        if (!empty($supplierData)) {
            $supplierJsonStr = '{"id":"' . $supplierData['id'] . '","name":"' . $supplierData['name'] . '"}';
        }

        $this->add([
            'name' => 'supplier_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'supplier_id',
                'class' => 'form-control',
                'data-item' => $supplierJsonStr,
                'data-id' => (!empty($data)) ? $data->getSupplierId() : ''
            ],
            'options' => [
                'label' => 'Name',
                'disable_inarray_validator' => true,
                'empty_option' => ' -- Select a Supplier -- ',
            ]
        ]);

        $this->add([
            'name' => 'supplier_tracking_number',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'supplier_tracking_number',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Tracking Number',
            ]
        ]);

        $this->add([
            'name' => 'supplier_transaction_id',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'supplier_transaction_id',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Transaction ID',
            ]
        ]);

        $this->add([
            'name' => 'tracking_url',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'tracking_url',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Tracking URL',
            ]
        ]);

        $this->add([
            'name' => 'url',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'url',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'URL',
            ]
        ]);

        $this->add([
            'name' => 'estimated_delivery_date_range',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'estimated_delivery_date_range',
                'class' => 'form-control pull-right'
            ],
            'options' => [
                'label' => 'Delivery',
            ]
        ]);

        $this->add([
            'name' => 'order_date',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'order_date',
                'class' => 'form-control pull-right'
            ],
            'options' => [
                'label' => 'Order',
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
            ],
            'options' => [
                'label' => 'Description',
            ]
        ]);


        $buttonOptions = [
            'label'         => 'Create Order',
        ];

        if (!empty($data)) {
            $buttonOptions = [
                'label'         => 'Save Order',
            ];
        }

        $this->add(
            [
                'name' => 'save_button',
                'type' => 'Zend\Form\Element\Submit',
                'attributes' => [
                    'id'                => 'save_button',
                    'value'             => $buttonOptions['label'],
                    'data-loading-text' => 'Saving...',
                    'class'             =>
                        'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                ],
                'options' => [
                    'label' => $buttonOptions['label'],
                ],
            ]
        );
    }

    /**
     * @param \ArrayObject|\DDD\Domain\Currency\Currency[] $currencyList
     * @return array
     */
    private function currencyList($currencyList)
    {
        $list = [0 => '-- Choose currency --'];
        foreach ($currencyList as $key => $currency) {
            $list[$currency->getId()] = $currency->getCode();
        }
        return $list;
    }
}
