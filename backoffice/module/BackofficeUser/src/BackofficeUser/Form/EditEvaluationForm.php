<?php

namespace BackofficeUser\Form;

use Library\Form\FormBase;

/**
 * Class EditEvaluationForm
 * @package BackofficeUser\Form
 */
class EditEvaluationForm extends FormBase
{
    public function __construct($name = '')
    {
        parent::__construct($name);
        
        $this->setAttributes([
            'action' => '/user/ajax-save-planned-evaluation',
            'method' => 'post',
            'class' => 'form-horizontal',
            'id' => 'edit-planned-evaluation-form'
        ]);

        $this->add([
            'name' => 'creator_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'creator_id'
            ]
        ]);

        $this->add([
            'name' => 'user_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'user_id'
            ]
        ]);

        $this->add([
            'name' => 'evaluation_id',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'evaluation_id'
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
