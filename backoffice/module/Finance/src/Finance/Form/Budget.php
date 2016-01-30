<?php

namespace Finance\Form;

use Library\Form\FormBase;
use DDD\Service\Finance\Budget as BudgetService;

class Budget extends FormBase
{
    /**
     * @param int $budgetId
     * @param bool $isGlobalManager
     * @param array $options
     */
    public function __construct($budgetId, $isGlobalManager, $options) {

        parent::__construct('budget-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'name',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'name',
                'maxlength' => 100,
            ),
        ));

        $this->add(array(
            'name' => 'status',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->statuses($budgetId, $isGlobalManager),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'status',
            ),
        ));

        $this->add(array(
            'name' => 'period',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'period',
            ),
        ));

        $this->add(array(
            'name' => 'amount',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'amount',
            ),
        ));

        $this->add(array(
            'name' => 'description',
            'attributes' => array(
                'type' => 'textarea',
                'class' => 'form-control',
                'rows' => '4',
                'id' => 'description',
                'maxlength' => 1000,
            ),
        ));

        $this->add(array(
            'name' => 'department_id',
            'options' => array(
                'label' => '',
                'value_options' => $options['departments'],
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'department_id',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name' => 'country_id',
            'options' => array(
                'label' => '',
                'value_options' => $options['countries']
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'country_id',
                'class' => 'form-control'
            ),
        ));

        $this->add(
			array(
				'name' 		 => 'is_global',
				'type' 		 => 'Zend\Form\Element\Checkbox',
				'attributes' => array(
                    'id'                 => 'is_global',
                    'use_hidden_element' => true,
                    'checked_value'      => 1,
                    'unchecked_value'    => 0
                )
			)
		);

        $this->add(array(
            'name' => 'save',
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary state save-bank-account col-sm-2 col-xs-12 margin-left-10 pull-right',
                'value' => $budgetId ? 'Save Changes' : 'Add New Budget',
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
