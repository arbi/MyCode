<?php

namespace Apartel\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class Connection extends InputFilterBase {

    public function __construct() {
	    $this->add(array(
		    'name'       => 'cubilis_id',
		    'required'   => true,
	    ));

        $this->add(array(
            'name'       => 'cubilis_username',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'cubilis_password',
            'required'   => true,
        ));
    }

}
