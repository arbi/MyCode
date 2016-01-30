<?php

namespace Apartel\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\Validator\Digits;

class Type extends InputFilterBase {

    public function __construct() {
	    $this->add(array(
		    'name'       => 'type_name',
		    'required'   => true,
	    ));

        $this->add(array(
		    'name'       => 'form_type_id',
		    'required'   => false,
	    ));

        $this->add(array(
            'name'       => 'apartment_list',
            'required'   => true,
        ));
    }

}
