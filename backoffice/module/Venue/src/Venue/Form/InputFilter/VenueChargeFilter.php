<?php

namespace Venue\Form\InputFilter;

use Zend\InputFilter\InputFilter;
use Zend\Validator\Digits;
use Zend\Validator\StringLength;


class VenueChargeFilter extends InputFilter
{
    public function __construct()
    {
        $this->add([
            'name'      => 'id',
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
            'name'      => 'venue_id',
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
            'name'      => 'status_id',
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
            'name'      => 'order_status_id',
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

        $this->add([
            'name'      => 'amount',
            'required'  => true,
            'filters'   => [],
        ]);
    }
}