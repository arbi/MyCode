<?php

namespace BackofficeUser\Form;

use Library\Form\FormBase;

/**
 * Class PlanEvaluationForm
 * @package BackofficeUser\Form
 */
class PlanEvaluationForm extends FormBase
{
    public function __construct($name = '')
    {
        parent::__construct($name);

        $this->setAttributes([
            'action' => '/user/ajax-plan-evaluation',
            'method' => 'post',
            'class' => 'form-horizontal',
            'id' => 'plan-evaluation-form'
        ]);

        $this->add([
            'name' => 'plan_date',
            'type' => 'Text',
            'attributes' => [
                'id' => 'plan_date',
                'class' => 'form-control datepicker',
            ]
        ]);

        $this->add([
            'name' => 'plan_creator_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'plan_creator_id'
            ]
        ]);

        $this->add([
            'name' => 'plan_user_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'plan_user_id'
            ]
        ]);

        $this->add([
            'name' => 'plan_evaluation_description',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id' => 'plan_evaluation_description',
                'class' => 'form-control evaluation-description tinymce',
                'rows' => 10,
            ]
        ]);
    }
}
