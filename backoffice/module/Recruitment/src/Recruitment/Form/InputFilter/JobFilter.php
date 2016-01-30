<?php

namespace Recruitment\Form\InputFilter;

use Zend\Validator\StringLength;

use Library\InputFilter\InputFilterBase;

class JobFilter extends InputFilterBase
{
    public function __construct()
    {
        $this->add([
            'name'       => 'title',
            'required'   => true,
            'filters'    => [
                ['name' => 'StripTags'],
                ['name' => 'stringtrim'],

            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 100,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 100',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'description',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
            ]
        ]);

        $this->add([
            'name'       => 'requirements',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
            ]
        ]);

        $this->add([
            'name' => 'meta_description',
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
                        'max'      => 700,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 70',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'     => 'hiring_manager_id',
            'required' => true,
            'filters'    => [
                ['name' => 'StripTags']
            ]
        ]);

        $this->add([
            'name'     => 'hiring_team_id',
            'required' => false,
            'filters'    => [
                ['name' => 'StripTags']
            ]
        ]);

        $this->add([
            'name'     => 'department_id',
            'required' => true,
            'filters'    => [
                ['name' => 'StripTags']
            ]
        ]);

        $this->add([
            'name'       => 'start_date',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
            ]
        ]);

        $this->add([
            'name'     => 'country_id',
            'filters'    => [
                ['name' => 'StripTags']
            ],
            'required' => true
        ]);

        $this->add([
            'name'     => 'province_id',
            'required' => true,
            'filters'    => [
                ['name' => 'StripTags']
            ]
        ]);

        $this->add([
            'name'     => 'city_id',
            'required' => true,
            'filters'    => [
                ['name' => 'StripTags']
            ]
        ]);
    }
}
