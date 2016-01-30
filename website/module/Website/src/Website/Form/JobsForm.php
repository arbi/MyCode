<?php

namespace Website\Form;

use Library\Form\FormBase;

class JobsForm extends FormBase
{
    public function __construct($name = 'announcement')
    {
        parent::__construct($name);

        $this->setAttributes([
            'action' => '',
            'method' => 'post',
	        'class' => 'form',
            'id' => 'announcement-form',
        ]);

        // First Name
        $this->add([
            'name' => 'job_id',
            'required' => true
        ]);

        // First Name
        $this->add([
            'name' => 'firstname',
            'required' => true
        ]);

        // Last Name
        $this->add([
            'name' => 'lastname',
            'required' => true
        ]);

        // Email
        $this->add([
            'name' => 'email',
            'required' => true,
            'attributes' => [
                'type' => 'text'
            ],
        ]);

        // Phone
        $this->add([
            'name' => 'phone',
            'required' => true,
            'attributes' => [
                'type' => 'text'
            ],
        ]);

        // Referred By
        $this->add([
            'name' => 'referred_by',
            'required' => true,
            'attributes' => [
                'type' => 'text'
            ],
        ]);

        // Skype
        $this->add([
            'name' => 'skype',
            'required' => true,
            'attributes' => [
                'type' => 'text'
            ],
        ]);

        // Motivation
        $this->add([
            'name' => 'motivation',
            'required' => true,
            'attributes' => [
                'type' => 'text'
            ],
        ]);

        // Suggestions
        $this->add([
            'name' => 'cv',
            'attributes' => [
                'type' => 'file'
            ]
        ]);
    }
}
