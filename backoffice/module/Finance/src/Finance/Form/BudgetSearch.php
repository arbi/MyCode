<?php

namespace Finance\Form;


use Library\Form\FormBase;
use DDD\Service\Finance\Budget as BudgetService;

class BudgetSearch extends FormBase {

    public function __construct($users, $options) {

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
                'placeholder' => 'Name',
            ),
        ));

        $this->add(array(
            'name' => 'status',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->statuses(),
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
                'placeholder' => 'Period',
            ),
        ));

        $this->add(array(
            'name' => 'frozen',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->frozen(),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'frozen',
            ),
        ));

        $this->add(array(
            'name' => 'archived',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->archived(),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'archived',
            ),
        ));

        $this->add(array(
            'name' => 'user',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->users($users),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'user',
                'data-placeholder' => 'Created By',
            ),
        ));

        $this->add(array(
            'name' => 'department',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $options['departments'],
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'department',
            ),
        ));

        $this->add(array(
            'name' => 'country',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $options['countries'],
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'country',
            ),
        ));

        $this->add(array(
            'name' => 'global',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => [-1 => '-- Global Type --', 0 => 'Not Global', 1 => 'Global'],
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'global',
            ),
        ));
    }

    /**
     * @return array
     */
    private function statuses()
    {
        return [0 => '-- Choose Status --'] + BudgetService::$budgetStatuses;
    }

    /**
     * @return array
     */
    private function frozen()
    {
        return [ -1 => '-- Frozen --'] + BudgetService::$budgetFrozen;
    }

    /**
     * @return array
     */
    private function archived()
    {
        return [ -1 => '-- Archived --'] + BudgetService::$budgetArchived;
    }

    /**
     * @return array
     */
    private function users($users)
    {
        $userList = [];
        foreach ($users as $user) {
            $userList[$user->getId()] = $user->getFirstName() . ' ' . $user->getLastName();
        }

        return $userList;
    }
}
