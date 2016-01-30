<?php

namespace BackofficeUser\Form;

use DDD\Service\User\Evaluations;
use Library\Form\FormBase;

/**
 * Class AddEvaluationForm
 * @package BackofficeUser\Form
 */
class AddEvaluationForm extends FormBase
{
    public function __construct($name = '')
    {
        parent::__construct($name);
        
        $this->setAttributes([
            'action' => '/user/ajax-add-evaluation',
            'method' => 'post',
            'class' => 'form-horizontal',
            'id' => 'add-evaluation-form'
        ]);

        $this->add([
            'name' => 'evaluation_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'evaluation_id'
            ]
        ]);
        
        $this->add([
            'name' => 'evaluation_user_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'evaluation_user_id'
            ]
        ]);
        
        $this->add([
            'name' => 'evaluation_creator_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'evaluation_creator_id'
            ]
        ]);
        
        $this->add([
            'name' => 'evaluation_type_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => Evaluations::getEvaluationTypeOptions(),
            ],
            'attributes' => [
                'id' => 'evaluation_type_id',
                'class' => 'form-control',
            ]
        ]);
        
        $this->add([
            'name' => 'evaluation_date_created',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'evaluation_date_created'
            ]
        ]);
        
        $this->add([
            'name' => 'evaluation_description',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id' => 'evaluation_description',
                'class' => 'form-control evaluation-description tinymce',
                'rows' => 10,
            ]
        ]);
    }
}
