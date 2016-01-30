<?php

namespace Warehouse\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class Category extends InputFilterBase {

    public function __construct() {
	    $this->add(array(
		    'name'       => 'name',
		    'required'   => true,
	    ));

        $this->add(array(
            'name'       => 'type',
            'required'   => true,
        ));

    }

}
