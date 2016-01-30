<?php

namespace Finance\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class TransferFilter extends InputFilterBase {
    public function __construct() {
	    $this->add(array(
		    'name'       => 'account_type',
		    'required'   => true,
	    ));

        $this->add(array(
            'name'       => 'amount',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'currency',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'from_money_account',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'from_supplier',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'to',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'fee',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'fee_value',
            'required'   => false,
        ));

        $this->add(array(
            'name'       => 'date',
            'required'   => true,
        ));

        $this->add(array(
            'name'       => 'dealer',
            'required'   => false,
        ));

        $this->add(array(
            'name'       => 'dealer_id',
            'required'   => true,
        ));
    }
}
