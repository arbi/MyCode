<?php

namespace WHOrder\Form\InputFilter;

use Zend\InputFilter\InputFilter;
use Zend\Validator\StringLength;
use Zend\Validator\Digits;
use Zend\Validator\Hostname;

use DDD\Service\WHOrder\Order as OrderService;

class OrderFilter extends InputFilter
{
    public function __construct()
    {

        $this->add([
            'name'      => 'title',
            'required'  => true,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'StringLength',
                    'options'   => [
                        'encoding'  => 'UTF-8',
                        'max'       => 255,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 255',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'asset_category_id',
            'required'  => true,
            'filters'   => [],
            'validators' => [
                [
                    'name'      => 'Digits',
                    'options'   => [
                        'messages'  => [
                            Digits::NOT_DIGITS => 'Can contain only digits',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'location_target',
            'required'  => true,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [],
        ]);

        $this->add([
            'name'      => 'status_shipping',
            'required'  => false,
            'filters'   => [],
            'validators' => [
                [
                    'name'      => 'Digits',
                    'options'   => [
                        'messages'  => [
                            Digits::NOT_DIGITS => 'Can contain only digits',
                        ],
                    ],
                ],
                [
                    'name' => 'Between',
                    'options' => [
                        'min' => OrderService::STATUS_TO_BE_ORDERED,
                        'max' => OrderService::STATUS_PARTIALLY_RECEIVED,
                    ],
                ]
            ],
        ]);

        $this->add([
            'name'      => 'quantity',
            'required'  => true,
            'filters'   => [],
            'validators' => [
                [
                    'name'      => 'Digits',
                    'options'   => [
                        'messages'  => [
                            Digits::NOT_DIGITS => 'Can contain only digits',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'quantity_type',
            'required'  => true,
            'filters'   => [],
            'validators' => [
                [
                    'name'      => 'Digits',
                    'options'   => [
                        'messages'  => [
                            Digits::NOT_DIGITS => 'Can contain only digits',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'received_quantity',
            'required'  => false,
            'filters'   => [],
            'validators' => [
                [
                    'name'      => 'Digits',
                    'options'   => [
                        'messages'  => [
                            Digits::NOT_DIGITS => 'Can contain only digits',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'received_date',
            'required'  => false,
            'filters'   => [],
            'validators' => [],
        ]);
        
        $this->add([
            'name'      => 'order_date',
            'required'  => false,
            'filters'   => [],
            'validators' => [],
        ]);

        $this->add([
            'name'      => 'supplier_id',
            'required'  => false,
            'filters'   => [],
            'validators' => [
                [
                    'name'      => 'Digits',
                    'options'   => [
                        'messages'  => [
                            Digits::NOT_DIGITS => 'Can contain only digits',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'supplier_tracking_number',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'StringLength',
                    'options'   => [
                        'encoding'  => 'UTF-8',
                        'max'       => 255,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 255',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'supplier_transaction_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'StringLength',
                    'options'   => [
                        'encoding'  => 'UTF-8',
                        'max'       => 255,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 255',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'url',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'StringLength',
                    'options'   => [
                        'encoding'  => 'UTF-8',
                        'max'       => 255,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 255',
                        ],
                    ],
                ],
                [
                    'name' => 'Uri',
                    'options' => [
                        'allowRelative' => false
                    ],
                ]
            ],
        ]);

        $this->add([
            'name'      => 'description',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'StringLength',
                    'options'   => [
                        'encoding'  => 'UTF-8',
                        'max'       => 5000,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 5000',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
