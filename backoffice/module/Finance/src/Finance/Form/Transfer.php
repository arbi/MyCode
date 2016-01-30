<?php

namespace Finance\Form;

use Finance\Form\InputFilter\TransferFilter;
use DDD\Service\User;
use Library\Form\FormBase;

class Transfer extends FormBase
{
	public function __construct($partnerList, $partnerListFiltered, $pspList)
    {
		parent::__construct('add-transfer');

        $this->setInputFilter(new TransferFilter());
        $this->setAttribute('class', 'form-horizontal add-transfer-form');
        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'account_type',
            'attributes' => array(
                'type' => 'hidden',
                'class' => 'account-type form-control',
            ),
        ));

        $this->add(array(
            'name' => 'dist_total_amount',
            'attributes' => array(
                'type' => 'hidden',
                'class' => 'dist-total-amount form-control',
            ),
        ));

        $this->add(array(
            'name' => 'description',
            'type' => 'Textarea',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control description',
                'placeholder' => 'Write something clever...',
                'rows' => 4,
            ),
        ));

		$this->add(array(
			'name' => 'amount_from',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control amount-from amount',
			),
		));

        $this->add(array(
            'name' => 'amount_to',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control amount-to amount',
            ),
        ));

        $this->add(array(
            'name' => 'supplier_from',
            'type' => 'select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'supplier supplier-from',
                'data-placeholder' => '-- Select Supplier --',
            ),
        ));

        $this->add(array(
            'name' => 'supplier_to',
            'type' => 'select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'supplier supplier-to',
                'data-placeholder' => '-- Please Select --',
            ),
        ));

		$this->add(array(
			'name' => 'money_account_from',
			'type' => 'select',
			'options' => array(
				'value_options' => [],
			),
			'attributes' => array(
				'class' => 'money-account money-account-from',
                'data-placeholder' => '-- Please Select --',
			),
		));

        $this->add(array(
            'name' => 'money_account_to',
            'type' => 'select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'money-account money-account-to',
                'data-placeholder' => '-- Please Select --',
            ),
        ));

        $this->add(array(
            'name' => 'partners_from',
            'type' => 'select',
            'options' => array(
                'value_options' => ['' => ''] + $partnerListFiltered,
            ),
            'attributes' => array(
                'class' => 'partners-from',
                'data-placeholder' => '-- Select Partner --',
            ),
        ));

        $this->add(array(
            'name' => 'partners_to',
            'type' => 'select',
            'options' => array(
                'value_options' => ['' => ''] + $partnerList,
            ),
            'attributes' => array(
                'class' => 'partners-to',
                'data-placeholder' => '-- Please Select --',
            ),
        ));

        $this->add(array(
            'name' => 'res_numbers',
            'type' => 'select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'res-numbers',
                'data-placeholder' => '-- Please Select --',
                'multiple' => true,
            ),
        ));

        $this->add(array(
            'name' => 'dist_res_numbers',
            'type' => 'select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'dist-res-numbers',
                'data-placeholder' => '-- Please Select --',
                'multiple' => true,
            ),
        ));

        $this->add(array(
            'name' => 'apartments_and_apartels',
            'type' => 'select',
            'options' => array(
                'value_options' => [],
            ),
            'attributes' => array(
                'class' => 'apartments-and-apartels',
                'data-placeholder' => '-- Select an Apartment, Apartel or Fiscal --',
                'multiple' => true,
            ),
        ));

        $this->add(array(
            'name' => 'date_from',
            'attributes' => array(
                'type' => 'text',
                'class' => 'date date-from form-control',
            ),
        ));

        $this->add(array(
            'name' => 'date_to',
            'attributes' => array(
                'type' => 'text',
                'class' => 'date date-to form-control',
            ),
        ));

        $this->add(array(
            'name' => 'date_to_from',
            'attributes' => array(
                'type' => 'text',
                'class' => 'date date-to-from form-control',
                'placeholder' => 'Date From',
            ),
        ));

        $this->add(array(
            'name' => 'date_to_to',
            'attributes' => array(
                'type' => 'text',
                'class' => 'date date-to-to form-control',
                'placeholder' => 'Date To',
            ),
        ));

        $this->add(array(
            'name' => 'expense_id_list[]',
            'attributes' => array(
                'type' => 'text',
                'class' => 'dynamic-expense-id form-control',
            ),
        ));

        $this->add(array(
            'name' => 'expense_amount_list[]',
            'attributes' => array(
                'type' => 'text',
                'class' => 'dynamic-expense-amount form-control',
            ),
        ));

        $this->add(array(
            'name' => 'psp',
            'type' => 'select',
            'options' => array(
                'value_options' => $pspList,
            ),
            'attributes' => array(
                'class' => 'psp',
                'data-placeholder' => '-- Select PSP --',
            ),
        ));

        $this->add(array(
            'name' => 'collection_period',
            'attributes' => array(
                'type' => 'text',
                'class' => 'collection-period form-control',
            ),
        ));

		$this->add(array(
			'name' => 'add',
			'attributes' => array(
				'type' => 'submit',
				'class' => 'btn btn-primary pull-right add-transfer',
				'value' => 'Add Transfer',
			),
		));
	}
}
