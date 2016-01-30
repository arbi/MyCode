<?php

namespace Backoffice\Form\InputFilter\ApartmentGroup;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Float;
use Library\InputFilter\InputFilterBase;

class DocumentFilter extends InputFilterBase
{

    public function __construct()
    {
        $this->add([
            'name'       => 'category',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'signatory_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'Int',
                    'options' => [
                        'min' => 1,
                        'max' => 1000,
                    ],
                ],
            ],
        ]);


        $this->add([
            'name'       => 'legal_entity_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'Int',
                    'options' => [
                        'min' => 1,
                        'max' => 1000,
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'     => 'security_level',
            'required' => true,
        ]);

        $this->add([
            'name'       => 'username',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 50,
                        'messages' => [
                            StringLength::TOO_LONG => 'maximum symbols 150',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'password',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 50,
                        'messages' => [
                            StringLength::TOO_LONG => 'maximum symbols 150',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'url',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 500,
                        'messages' => [
                            StringLength::TOO_LONG => 'maximum symbols 500',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'     => 'description',
            'required' => false,
            'filters'  => [
                ['name' => 'StringTrim'],
            ]
        ]);

        $this->add([
            'name'       => 'account_number',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 255,
                        'messages' => [
                            StringLength::TOO_LONG => 'maximum symbols 255',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'account_holder',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 255,
                        'messages' => [
                            StringLength::TOO_LONG => 'maximum symbols 255',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'supplier_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'Int',
                    'options' => [
                        'min' => 1,
                        'max' => 1000,
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'edit_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);
    }

}
