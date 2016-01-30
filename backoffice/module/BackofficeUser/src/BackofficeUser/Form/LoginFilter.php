<?php

namespace BackofficeUser\Form;

use Zend\InputFilter\InputFilter;

/**
 * @package backoffice_user
 * @subpackage backoffice_user_forms
 * @author Tigran Petrosyan
 */
class LoginFilter extends InputFilter
{
	public function __construct() {
        $this->add(
        	array(
        		'name'       => 'identity',
        		'required'   => true,
        		'validators' => array(
        			
        		),
                'filters'   => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'StripTags'),
                ),
        	)
        );

        $this->add(array(
            'name'       => 'credential',
            'required'   => true,
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'min' => 6,
                    ),
                ),
            ),
        ));
    }
}