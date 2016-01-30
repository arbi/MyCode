<?php

namespace Website\Form;

use Library\Form\FormBase;

class ContactForm extends FormBase
{
    public function __construct($name = 'contact-us', $options = array())
    {
        parent::__construct($name);
        
        $this->setAttributes([
            'action' => '',
            'method' => 'POST',
            'id' => 'contact-us-form',
        ]);
        
        // Name
        $this->add([
            'name' => 'name',
            'required' => true,
            'attributes' => [
                'type' => 'text',
                'id' => 'name',
                'class' => 'form-control',
                'placeholder' => 'Name'
            ],
            'options' => [
                
            ]
        ]);
        
        // Email
        $this->add([
            'name' => 'email',
            'required' => true,
            'attributes' => [
                'type' => 'email',
                'id' => 'email',
                'class' => 'form-control',
                'placeholder' => 'Email'
            ],
            'options' => [
                
            ]
        ]);
        
        // Guest Remarks
        $this->add([
            'name' => 'remarks',
            'required' => true,
            'attributes' => [
                'type' => 'textarea',
                'id' => 'remarks',
                'class' => 'form-control',
                'placeholder' => 'Guest Remarks',
                'rows' => 5
            ],
            'options' => [
                
            ]
        ]);
        
        // Submit
        $this->add([
            'name' => 'submit',
            'type' => 'button',
            'attributes' => [
                'id' => 'submit',
                'class' => 'btn btn-primary form-control',
                'disabled' => 'disabled',
            ],
            'options' => [
                'label' => 'Send'
            ]
        ]);
    }
}
