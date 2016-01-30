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

class TeamFilter extends InputFilterBase
{
    public function __construct($global, $isFrontier)
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

	    $this->add(
            [
                'name'     => 'managers',
                'required' => true
	        ]
        );

        if (!$global) {
            $name['required'] = false;
        }

        $this->add($name);

        $this->add($description);

        $this->add(
            [
                'name'     => 'director',
                'required' => true
            ]
        );

        $this->add(
            [
                'name'     => 'officers',
                'required' => false
            ]
        );

        $this->add(
            [
                'name'     => 'members',
                'required' => false
            ]
        );

        $this->add(
            [
                'name'     => 'usage_frontier',
                'required' => false
            ]
        );

        $this->add(
            [
                'name'     => 'frontier_apartments',
                'required' => false
            ]
        );

        $this->add(
            [
                'name'     => 'frontier_buildings',
                'required' => false
            ]
        );

        $required = false;
        if ((int)$isFrontier) {
            $required = true;
        }

        $this->add(
            [
                'name'     => 'timezone',
                'required' => $required
            ]
        );
    }
}
