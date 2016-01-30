<?php
namespace Backoffice\Form\InputFilter;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Float;
use Zend\I18n\Validator\Int;
use Zend\InputFilter;
use Library\InputFilter\InputFilterBase;

class TaskFilter extends InputFilterBase
{
    public function __construct($check)
    {
        $this->add([
            'name'       => 'title',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
        ]);

        $this->add([
            'name'       => 'creator_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'responsible_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'verifier_id',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'follower_ids',
            'required'   => false,
            'validators' => [],
        ]);

        $this->add([
            'name'       => 'helper_ids',
            'required'   => false,
            'validators' => [],
        ]);


        $this->add([
            'name'       => 'tags',
            'required'   => false,
            'validators' => [],
        ]);

        $this->add([
            'name'       => 'team_id',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Int',
                    'options' => [
                        'messages' => [
                            Int::NOT_INT=> 'Can contain only number.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'following_team_id',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Int',
                    'options' => [
                        'messages' => [
                            Int::NOT_INT=> 'Can contain only number.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'date',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
        ]);

        $this->add([
            'name'       => 'property_id',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'building_id',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'url',
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
                        'max'      => 150,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'task_status',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'task_priority',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'task_type',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                        'name' => 'Digits',
                        'options' => [
                            'messages' => [
                                Digits::NOT_DIGITS => 'Can contain only digits.',
                            ],
                        ],
                    ],
                ],
        ]);

        $this->add([
            'name'       => 'related_task',
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
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'res_id',
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
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ],
                    ],
                ],
            ]
        ]);

        $this->add([
            'name'       => 'description',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
        ]);

        $this->add([
            'name'       => 'comments',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'subtask_id',
            'required'   => false
        ]);

        $this->add([
            'name'       => 'subtask_status',
            'required'   => false,
        ]);

        $this->add([
            'name'       => 'subtask_description',
            'required'   => false,
        ]);

        $this->add([
            'name'       => 'auto_verifiable',
            'required'   => false,
        ]);
    }
}