<?php

namespace Contacts\Form\InputFilter;

use Zend\InputFilter\InputFilter;
use Zend\Validator\StringLength;
use Zend\Validator\Digits;

class ContactFilter extends InputFilter
{
    public function __construct()
    {
        $this->add([
            'name'      => 'creator_id',
            'required'  => true,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'date_created',
            'required'  => true,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'Date',
                    'options'   => [
                        'format'    => 'Y-m-d H:i'
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'date_modified',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'      => 'Date',
                    'options'   => [
                        'format'    => 'Y-m-d H:i'
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'name',
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
            'name'      => 'company',
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
            'name'      => 'position',
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
            'name'      => 'city',
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
            'name'      => 'address',
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
            'name'      => 'email',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'  => 'EmailAddress',
                ],
            ],
        ]);

        $this->add([
            'name'      => 'skype',
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
                        'max'       => 45,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 45',
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
            ],
        ]);

        $this->add([
            'name'      => 'phone_mobile_country_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'phone_mobile',
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
                        'max'       => 45,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 45',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'phone_company_country_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'phone_company',
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
                        'max'       => 45,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 45',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'phone_other_country_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'phone_other',
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
                        'max'       => 45,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 45',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'phone_fax_country_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'phone_fax',
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
                        'max'       => 45,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 45',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'notes',
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
                        'max'       => 21844,
                        'messages'  => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 21844',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'      => 'team_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'apartment_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'building_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
            'name'      => 'partner_id',
            'required'  => false,
            'filters'   => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
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
    }
}
