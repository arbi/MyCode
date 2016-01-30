<?php

namespace Website\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class ContactFilter extends InputFilterBase
{
    public function __construct() {
        // Name
        $this->add([
            'name' => 'name',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                
            ]
        ]);
        
        // Email
        $this->add([
            'name' => 'email',
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [[
                'name' => 'EmailAddress',
                'options' => [
                    'messages' => [
                        \Zend\Validator\EmailAddress::INVALID_FORMAT => 'Email address format is invalid'
                        ]
                    ]
                ]
            ]
        ]);
        
        // Guest Remarks
        $this->add([
            'name' => 'remarks',
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                
            ]
        ]);
    }
}
