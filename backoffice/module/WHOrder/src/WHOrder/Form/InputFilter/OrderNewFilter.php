<?php

namespace WHOrder\Form\InputFilter;

use Zend\InputFilter\InputFilter;
use Zend\Validator\StringLength;
use Zend\Validator\Digits;
use Zend\Validator\Hostname;

use DDD\Service\WHOrder\Order as OrderService;

class OrderNewFilter extends InputFilter
{
    public function __construct()
    {

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
