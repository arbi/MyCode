<?php

namespace Warehouse\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class Storage extends InputFilterBase {

    public function __construct() {
	    $this->add(array(
		    'name'       => 'name',
		    'required'   => true,
	    ));

        $this->add(array(
            'name'       => 'city',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'address',
            'required'   => true,
        ));
    }

}
