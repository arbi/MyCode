<?php

namespace Website\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\Validator\EmailAddress;
use Zend\Validator\Digits;
use Zend\Validator\StringLength;

class JobsFilter extends InputFilterBase
{
    public function __construct() {
        // Job Id
        $this->add([
            'name' => 'job_id',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ]
            ]
        ]);

        // First Name
        $this->add([
            'name' => 'firstname',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 25,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'Maximum symbols 25',
                        ),
                    ],
                ],
            ],
        ]);

        // Last Name
        $this->add([
            'name' => 'lastname',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 25,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'Maximum symbols 25',
                        ),
                    ],
                ],
            ],
        ]);

        // Email
        $this->add([
            'name'       => 'email',
            'required'   => true,
            'filters'    => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'    => 'EmailAddress',
                    'options' => [
                        'useDomainCheck' => false,
                        'messages' => [
                            EmailAddress::INVALID => 'Please provide correct email address',
                            EmailAddress::INVALID_FORMAT => 'Please provide correct formated email address',
                        ],
                    ],
                ],
            ],
        ]);

        // Phone
        $this->add([
            'name' => 'phone',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [

            ]
        ]);

        // Referred By
        $this->add([
            'name' => 'referred_by',
            'required' => false,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 50,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'Maximum symbols 50',
                        ),
                    ],
                ],
            ],
        ]);

        // Skype
        $this->add([
            'name' => 'skype',
            'required' => false,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 25,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'Maximum symbols 25',
                        ),
                    ],
                ],
            ],
        ]);

        // Motivation
        $this->add([
            'name' => 'motivation',
            'required' => false,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [

            ]
        ]);

        // CV
        $this->add([
            'name' => 'cv',
            'required' => true,
            'filters' => [
            ],
            'validators' => [

            ]
        ]);
    }
}
