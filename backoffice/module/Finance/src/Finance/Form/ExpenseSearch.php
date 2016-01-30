<?php

namespace Finance\Form;

use Zend\Form\Form;
use Library\Finance\Process\Expense\Helper;

class ExpenseSearch extends Form
{
    protected $resources;

    public function __construct(array $users, array $currencies)
    {
        parent::__construct('expense-search');

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form expense-search');

        $this->add([
            'name' => 'id',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'PO ID',
                'class' => 'form-control tid',
            ],
        ]);

        $this->add([
            'name' => 'currency_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => $currencies,
            ],
            'attributes' => [
                'class' => 'form-control currency-id',
                'data-placeholder' => '-- All Currencies --',
            ],
        ]);

        $this->add([
            'name' => 'category_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => [],
            ],
            'attributes' => [
                'class' => 'form-control category-id',
                'data-placeholder' => '-- Select Category --',
            ],
        ]);

        $this->add([
            'name' => 'manager_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => $users,
            ],
            'attributes' => [
                'class' => 'form-control manager-id',
                'data-placeholder' => 'Managed By',
            ],
        ]);

        $this->add([
            'name' => 'creator_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => $users,
            ],
            'attributes' => [
                'class' => 'form-control creator-id',
                'data-placeholder' => 'Entered By',
            ],
        ]);

        $this->add([
            'name' => 'title',
            'attributes' => [
                'class' => 'form-control title',
                'placeholder' => 'Title',
            ],
        ]);

        $this->add([
            'name' => 'account_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => [],
            ],
            'attributes' => [
                'class' => 'form-control account-id',
                'data-placeholder' => '-- All Suppliers --',
            ],
        ]);

        $this->add([
            'name' => 'cost_center_id',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class' => 'form-control cost-center-id',
                'data-placeholder' => '-- All Cost Centers --',
            ],
        ]);


        $this->add([
            'name' => 'account_reference',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'Supplier Reference',
                'class' => 'form-control account-reference',
            ],
        ]);

        $this->add([
            'name' => 'purpose',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'Purpose',
                'class' => 'form-control purpose',
            ],
        ]);

        $this->add([
            'name' => 'amount',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'Item Amount',
                'class' => 'form-control amount',
            ],
        ]);


        $this->add([
            'name' => 'creation_date',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'Creation Date',
                'class' => 'form-control creation-date',
            ]
        ]);

        $this->add([
            'name' => 'expected_completion_date',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'Validity',
                'class' => 'form-control expected_completion_date'
            ]
        ]);

        $this->add([
            'name' => 'item_period',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'placeholder' => 'Item Period',
                'class' => 'form-control item-period',
            ],
        ]);

        $this->add([
            'name' => 'status',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => Helper::$statuses,
            ],
            'attributes' => [
                'class' => 'form-control status',
                'data-placeholder' => '-- All Approval Statuses --',
            ],
        ]);

        $this->add([
            'name' => 'finance_status',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => Helper::$financeStatuses,
            ],
            'attributes' => [
                'class' => 'form-control finance_status',
                'data-placeholder' => '-- All Statuses --',
            ],
        ]);
    }
}
