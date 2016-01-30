<?php

namespace Finance\Form;

use Library\Form\FormBase;

class Espm extends FormBase
{
    /**
     * @param int|null|string $espmId
     * @param array $options
     */
    public function __construct($espmId, $options) {

        parent::__construct('espm-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'amount',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'amount',
                'maxlength' => 12,
            ),
        ));

        $this->add(array(
            'name' => 'currency',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $options['currencyList'],
            ),
            'attributes' => array(
                'data-placeholder' => 'Currency',
                'class' => 'form-control',
                'id' => 'currency',
            ),
        ));

        $this->add(array(
            'name' => 'transaction_account',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control  selectized',
                'id' => 'transaction_account',
                'data-placeholder' => 'Supplier',
            ),
            'options' => array(
                'value_options' => [],
                'disable_inarray_validator' => true
            ),
        ));

        $this->add(array(
            'name' => 'account',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control selectized',
                'id' => 'account',
                'data-placeholder' => 'Account',
            ),
            'options' => array(
                'value_options' => [],
                'disable_inarray_validator' => true
            ),
        ));

        $this->add(array(
            'name' => 'type',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control  selectized',
                'id' => 'type',
                'data-placeholder' => 'Type',
            ),
            'options' => array(
                'value_options' => $options['types'],
            ),
        ));

        $this->add(array(
            'name' => 'status',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control  selectized',
                'id' => 'status',
                'data-placeholder' => 'Status',
            ),
            'options' => array(
                'value_options' => $options['statuses'],
            ),
        ));

        $this->add([
            'name' => 'action_date',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'    => 'action_date',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Action date',
            ]
        ]);

        $this->add(array(
            'name' => 'reason',
            'attributes' => array(
                'type' => 'textarea',
                'class' => 'form-control',
                'rows' => '4',
                'id' => 'reason',
                'maxlength' => 1000,
            ),
        ));

        $this->add(array(
            'name' => 'save',
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right save-button',
                'value' => $espmId ? 'Save Changes' : 'Add Payment',
            ),
        ));
    }

    /**
     * @param int $budgetId
     * @param bool $isGlobalManager
     * @return array
     */
    private function statuses($budgetId, $isGlobalManager)
    {
        $budgetList = [0 => '-- Choose Status --'];

        if ($budgetId && $isGlobalManager) {
            $budgetList += BudgetService::$budgetStatuses;
        } else {
            $budgetList += [
                BudgetService::BUDGET_STATUS_DRAFT => BudgetService::$budgetStatuses[BudgetService::BUDGET_STATUS_DRAFT],
                BudgetService::BUDGET_STATUS_PENDING => BudgetService::$budgetStatuses[BudgetService::BUDGET_STATUS_PENDING],
            ];
        }

        return $budgetList;
    }
}
