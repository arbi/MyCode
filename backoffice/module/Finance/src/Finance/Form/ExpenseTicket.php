<?php

namespace Finance\Form;

use Library\Finance\Process\Expense\Helper;
use Zend\Form\Form;

class ExpenseTicket extends Form
{
    public function __construct($managersList, $budgetList, $type = false, $disabled = false)
    {
        parent::__construct('expense');

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'expense-form');

        if ($disabled) {
            $disabled = 'disabled';
        }

        $this->add([
            'name'       => 'account',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class'            => 'form-control account',
                'data-placeholder' => 'Supplier',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => [],
            ],
        ]);

        $this->add([
            'name'       => 'supplier_reference',
            'attributes' => [
                'class'       => 'form-control supplier-reference',
                'placeholder' => 'Supplier Reference',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'item_comment',
            'type'       => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'class'       => 'form-control item-comment',
                'placeholder' => 'Description',
                'rows'        => '3',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'title',
            'attributes' => [
                'id'          => 'title',
                'class'       => 'po-title',
                'placeholder' => 'Brief Title',
                'maxlength'   => '64',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'purpose',
            'type'       => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id'          => 'purpose',
                'class'       => 'form-control',
                'placeholder' => 'Describe the main reason for this purchase order',
                'rows'        => '4',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'budget',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'               => 'budget',
                'class'            => 'form-control',
                'data-placeholder' => '-- Please Select --',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => $budgetList,
            ],
        ]);

        $this->add([
            'name'       => 'ticket_manager',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'               => 'ticket-manager',
                'class'            => 'form-control selectize',
                'data-placeholder' => '-- Please Select --',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => $managersList,
            ],
        ]);

        $types = Helper::$types;

        if ($type !== false) {
            if ($type != Helper::TYPE_ORDER_EXPENSE) {
                unset($types[Helper::TYPE_ORDER_EXPENSE]);
            }
        } else {
            unset($types[Helper::TYPE_ORDER_EXPENSE]);
        }

        $this->add([
            'name'       => 'type',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control type',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => ['' => '-- Type --'] + $types,
            ],
        ]);

        $this->add([
            'name'       => 'attachments',
            'type'       => 'Zend\Form\Element\File',
            'attributes' => [
                'id'       => 'attachments',
                'class'    => 'form-control',
                'multiple' => true,
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'comment_writer',
            'attributes' => [
                'id'          => 'comment-writer',
                'class'       => 'form-control',
                'placeholder' => 'Write a comment...',
            ],
        ]);

        $this->add([
            'name'       => 'amount',
            'attributes' => [
                'class'       => 'form-control text-right amount',
                'placeholder' => 'Amount',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'period',
            'attributes' => [
                'class'       => 'form-control period',
                'placeholder' => 'Period',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'cost_centers',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class'            => 'item-cost-centers form-control',
                'multiple'         => true,
                'data-placeholder' => 'Cost Center',
                'disabled' => $disabled,
                'style'  =>'display:none'
            ],
            'options'    => [
                'value_options' => [],
            ],
        ]);

        $this->add([
            'name'       => 'category',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => '-- Category --',
                'class'            => 'form-control category',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => [],
            ],
        ]);

        $this->add([
            'name'       => 'sub_category',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => '-- Subcategory --',
                'class'            => 'form-control sub-category',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => [],
            ],
        ]);

        $this->add([
            'name'       => 'is_startup',
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'class' => 'is_startup',
                'disabled' => $disabled,
            ],
            'options'    => [
                'use_hidden_element' => false,
            ],
        ]);

        $this->add([
            'name'       => 'is_deposit',
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'class' => 'is_deposit',
                'disabled' => $disabled,
            ],
            'options'    => [
                'use_hidden_element' => false,
            ],
        ]);

        $this->add([
            'name'       => 'is_refund',
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'class' => 'is_refund',
                'disabled' => $disabled,
            ],
            'options'    => [
                'use_hidden_element' => false,
            ],
        ]);

        $this->add([
            'name'       => 'account_from',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class'            => 'form-control account-from',
                'data-placeholder' => '-- From  Account --',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => [],
            ],
        ]);

        $this->add([
            'name'       => 'account_to',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control account-to',
                'disabled' => $disabled,
            ],
            'options'    => [
                'value_options' => [],
            ],
        ]);

        $this->add([
            'name'       => 'transaction_date',
            'attributes' => [
                'class'       => 'form-control transaction-date',
                'placeholder' => 'Transaction Date',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'expected_completion_date',
            'attributes' => [
                'id'    => 'expected-completion-date',
                'class' => 'form-control expected-completion-date',
                'disabled' => $disabled,
            ],
        ]);

        $this->add([
            'name'       => 'submit',
            'attributes' => [
                'value' => 'Add Purchase Order',
                'class' => 'btn btn-primary submit',
                'disabled' => $disabled,
            ],
        ]);

        /**
         * These elements not draws in page, but used to automatically get data after binding
         */
        $this->add([
            'name' => 'id',
        ]);
        $this->add([
            'name' => 'ticket_creator_id',
        ]);
        $this->add([
            'name' => 'ticket_creator',
        ]);
        $this->add([
            'name' => 'currency_id',
        ]);
        $this->add([
            'name' => 'date_created',
        ]);
        $this->add([
            'name' => 'ticket_balance',
        ]);
        $this->add([
            'name' => 'deposit_balance',
        ]);
        $this->add([
            'name' => 'status',
        ]);
    }
}
