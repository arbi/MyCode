<?php

namespace Website\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\Validator\Digits;

class ReviewFilter extends InputFilterBase
{
    public function __construct() {
        // Like
        $this->add([
            'name' => 'like',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                
            ]
        ]);
        
        // Suggestions
        $this->add([
            'name' => 'suggestions',
            'required' => false,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                
            ]
        ]);
        
        // Reservation Ticket Review Hash
        $this->add([
            'name' => 'review-hash',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                
            ]
        ]);
        
        $this->add(array(
            'name'       => 'stars',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            'validators' => array(
                array(
                        'name' => 'Digits',
                        'options' => array(
                            'messages' => array(
                                Digits::NOT_DIGITS => 'Can contain only digits.',
                            ),
                        ),
                    ),
                ),
        ));
    }
}
