<?php

namespace Finance\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class PspFilter extends InputFilterBase {
    public function __construct() {
	    $this->add(array(
		    'name'       => 'name',
		    'required'   => true,
	    ));

	    $this->add(array(
		    'name'       => 'shor_name',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'money_account_id',
		    'required'   => true,
	    ));

	    $this->add(array(
		    'name'       => 'authorization',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'rrn',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'error_code',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'batch',
		    'required'   => false,
	    ));
    }
}
