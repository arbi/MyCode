<?php
namespace Backoffice\Form\InputFilter;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Float;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Int;
use Library\InputFilter\InputFilterBase;

class OfficeFilter extends InputFilterBase
{
    public function __construct($global)
    {
        $name = [
            'name'       => 'name',
            'required'   => true,
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
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ],
                    ],
                ],
            ],
        ];

        $description = [
            'name'       => 'description',
            'required'   => true,
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
                            StringLength::TOO_LONG  => 'maximum symbols 255',
                        ],
                    ],
                ],
            ],
        ];

        $address = [
            'name'       => 'address',
            'required'   => true,
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
                            StringLength::TOO_LONG  => 'maximum symbols 255',
                        ],
                    ],
                ],
            ],
        ];

        $this->add(
            [
                'name'       => 'phone',
                'required'   => false,
                'filters'    => [
                    ['name'    => 'StringTrim'],
                    ['name' => 'StripTags'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'max'      => 50,
                            'messages' => [
                                StringLength::TOO_LONG  => 'maximum symbols 50',
                            ],
                        ],
                    ],
                ],
            ]
        );


	    $this->add(
            [
                'name'     => 'country_id',
                'required' => true
	        ]
        );

        $this->add(
            [
                'name'     => 'province_id',
                'required' => true
	        ]
        );

	    $this->add(
            [
                'name'     => 'city_id',
                'required' => true
	        ]
        );

	    $this->add(
            [
                'name'     => 'office_manager_id',
                'required' => false,
	        ]
        );

	    $this->add(
            [
                'name'     => 'finance_manager_id',
                'required' => false,
	        ]
        );

	    $this->add(
            [
                'name'     => 'it_manager_id',
                'required' => false,
	        ]
        );

	    $this->add(
            [
                'name'     => 'staff',
                'required' => false,
	        ]
        );


        $this->add($name);

        $this->add($description);
        $this->add($address);

    }
}
