<?php

namespace Finance\Form\InputFilter;

use Library\InputFilter\InputFilterBase;

class MoneyAccountFilter extends InputFilterBase {
    public function __construct() {
	    $this->add(array(
		    'name'       => 'name',
		    'required'   => true,
	    ));

	    $this->add(array(
		    'name'       => 'currency_id',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'country_id',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'type',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'account_ending',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'description',
		    'required'   => false,
	    ));

		$this->add(array(
		    'name'       => 'card_holder_id',
		    'required'   => true,
	    ));

		$this->add(array(
		    'name'       => 'legal_entity_id',
		    'required'   => true,
	    ));

		$this->add(array(
		    'name'       => 'bank_id',
		    'required'   => false,
	    ));

        $this->add(array(
            'name'       => 'is_searchable',
            'required'   => true,
        ));

	    $this->add(array(
		    'name'       => 'responsible_person_id',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'bank_account_number',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'address',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'view_transactions',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'add_transactions',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'manage_transactions',
		    'required'   => false,
	    ));

	    $this->add(array(
		    'name'       => 'manage_account',
		    'required'   => false,
	    ));
    }
}
