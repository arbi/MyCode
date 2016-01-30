<?php

namespace Finance\Form\InputFilter;

use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Int;
use Library\InputFilter\InputFilterBase;
use Zend\I18n\Validator\Float;

class Budget extends InputFilterBase {

    public function __construct() {
	    $this->add(array(
		    'name'       => 'name',
		    'required'   => true,
	    ));

        $this->add(array(
            'name'       => 'status',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'period',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'amount',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'description',
            'required'   => true,
        ));

        $this->add(array(
            'name'     => 'department_id',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'country_id',
            'required' => false,
            'filters'  => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

        $this->add(
            array(
                'name'     => 'is_global',
                'required' => false
            )
        );

    }

}
