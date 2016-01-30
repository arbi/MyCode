<?php

namespace Finance\Form\InputFilter;

use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Int;
use Library\InputFilter\InputFilterBase;
use Zend\I18n\Validator\Float;

class Espm extends InputFilterBase {

    public function __construct() {
	    $this->add(array(
		    'name'       => 'amount',
		    'required'   => false,
	    ));

        $this->add(array(
            'name'       => 'currency',
            'required'   => false,
        ));

        $this->add(array(
            'name'       => 'transaction_account',
            'required'   => false,
        ));

        $this->add(array(
            'name'       => 'account',
            'required'   => false,
        ));

        $this->add(array(
            'name'       => 'status',
            'required'   => false,
        ));


        $this->add(array(
            'name'       => 'type',
            'required'   => false,
        ));
    }

}
