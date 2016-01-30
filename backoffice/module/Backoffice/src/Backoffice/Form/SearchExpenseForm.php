<?php
namespace Backoffice\Form;

use Zend\Form\Form;
use Library\Constants\Constants;
use Library\Constants\Objects;

/**
 *
 * @author developer
 *
 */
class SearchExpenseForm extends Form
{
	protected $resources;

	public function __construct($name = 'blank', $resources = [])
    {
        // set the form's name
        parent::__construct($name);

	    $this->resources = $resources;

        // set the method
        $this->setAttribute('method', 'post');

        // TID
        $this->add([
            'name' => 'tid',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false
            ],
            'attributes' => [
                'placeholder' => 'TID',
                'class' => 'form-control',
                'id' => 'tid'
            ],
        ]);

        // Category
        $this->add([
            'name' => 'category',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $this->getExpenseCategories(),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'category'
            ],
        ]);

        // Entered by
        $this->add([
            'name' => 'entered_by',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
                'required' => true,
            ],
            'attributes' => [
                'placeholder' => 'Entered By',
                'class' => 'form-control',
                'id' => 'entered_by'
            ],
        ]);

        // Managed by
        $this->add([
            'name' => 'managed_by',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Managed By',
                'class' => 'form-control',
                'id' => 'managed_by'
            ],
        ]);

        $this->add([
            'name' => 'entered_by_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'entered_by_id',
                'class' => 'form-control'
            ],
            'options' => [
                    'label' => false,
            ],
        ]);

        $this->add([
            'name' => 'entered_for_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'entered_for_id',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => false,
            ],
        ]);

        $this->add([
            'name' => 'managed_by_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'managed_by_id',
                'class' => 'form-control'
            ],
            'options' => [
                    'label' => false,
            ],
        ]);

        // Supplier
        $this->add([
            'name' => 'supplier',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $this->getSuppliers(),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'supplier'
            ],
        ]);

        // Supplier Reference
        $this->add([
            'name' => 'supplier_reference',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Supplier Reference',
                'class' => 'form-control',
                'id' => 'supplier_reference'
            ],
        ]);

        // Purpose
        $this->add([
            'name' => 'purpose',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Purpose',
                'class' => 'form-control',
                'id' => 'purpose'
            ],
        ]);

        // Currency
        $this->add([
            'name' => 'currency',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $this->getCurrencies(),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'currency'
            ],
        ]);

        // Bank Account
        $this->add([
            'name' => 'bank_account',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $this->getBankAccounts(),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'bank_account'
            ],
        ]);

        // Amount
        $this->add([
            'name' => 'amount',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Amount',
                'class' => 'form-control',
                'id' => 'amount'
            ],
        ]);

        // Transaction Date
        $this->add([
            'name' => 'transaction_date',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Transaction Date',
                'id' => 'transaction_date',
                'class' => 'form-control pull-right',
            ],
        ]);

        // Date Entered
        $this->add([
            'name' => 'date_entered',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Date Entered',
                'id' => 'date_entered',
                'class' => 'form-control pull-right',
            ],
        ]);

        // Global Cost
        $this->add([
            'name' => 'global_cost',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => [
                    2 => 'Global & Specific',
                    1 => 'Global Costs',
                    0 => 'Specific Costs'
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'global_cost'
            ],
        ]);

        // Direct Debit
        $this->add([
            'name' => 'direct_debit',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => array_merge(
                    [2 => 'Debit & Manual'],
                    Objects::getPaymentTypes()
                ),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'direct_debit'
            ],
        ]);

        // Verified
        $this->add([
            'name' => 'verified',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => [
                    2 => 'Verified & Unverified',
                    1 => 'Verified',
                    0 => 'Unverified'
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'verified'
            ],
        ]);

        // Verified
        $this->add([
            'name' => 'debt',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => [
                    0 => 'Deposit & Non',
                    1 => 'Deposit',
                    2 => 'Non Deposit'
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'debt'
            ],
        ]);

        // Paid
        $this->add([
            'name' => 'paid',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => [
                    2 => 'Paid & Unpaid',
                    1 => 'Paid',
                    0 => 'Unpaid'
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'paid'
            ],
        ]);

        // Approved
        $this->add([
            'name' => 'approved',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => [
                    2 => 'Approved & New',
                    1 => 'Approved',
                    0 => 'New'
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'approved'
            ],
        ]);

        // Cost Center
        $this->add([
            'name' => 'cost_center_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $this->getAccommodations(),
            ],
            'attributes' => [
                'data-placeholder' => 'Cost Center',
                'class' => 'form-control',
                'id' => 'cost_center_id'
            ],
        ]);
    }

    /**
     * @return array
     */
    public function getAccommodations() {
    	$accommodations = $this->resources['accommodations'];

    	$accommodationsArray = [
            0 => "-- Cost Centers --"
    	];
    	foreach ($accommodations as $accommodation) {
    		$accommodationsArray[$accommodation->getId()] = $accommodation->getName();
    	}

    	return $accommodationsArray;
    }

    /**
     * Get Currencies to populate Select element
     * @access public
     *
     * @return array
     */
	public function getCurrencies() {
		$currencies = $this->resources['currencies'];

		$currenciesArray = [
            0 => "-- All Currencies --"
		];
		foreach ($currencies as $currency) {
			$currenciesArray[$currency->getId()] = $currency->getCode();
		}

		return $currenciesArray;
	}

	public function getExpenseCategories() {
		$categories = $this->resources['expense_categories'];

		$categoriesArray = [
			0 => "-- All Categories --"
		];
		foreach ($categories as $category) {
			$categoriesArray[$category->getId()] = $category->getName();
		}

		return $categoriesArray;
	}

	public function getSuppliers() {
		$suppliers = $this->resources['suppliers'];

		$suppliersArray = [
				0 => "-- All Suppliers --"
		];
		foreach ($suppliers as $supplier) {
			$suppliersArray[$supplier->getId()] = $supplier->getName();
		}

		return $suppliersArray;
	}

	public function getBankAccounts() {
		$bankAccounts = $this->resources['bank_accounts'];

		$bankAccountArray = [
			0 => "-- All Money Accounts --"
		];
		foreach ($bankAccounts as $bankAccount) {
			$bankAccountArray[$bankAccount->getId()] = $bankAccount->getName() . ' (' . $bankAccount->getCname() . ')';
		}

		return $bankAccountArray;
	}
}
