<?php
namespace Backoffice\Form;

use Zend\Form\Element;
use Library\Form\FormBase;
use Library\Constants\Objects;

class ProfilePassword extends FormBase
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $name = $this->getName();
        if (null === $name) {
            $this->setName('changePassword');
        }

        $this->add(array(
            'name' => 'password',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'type' => 'password',
                'id' => 'password',
                'maxlength' => 20,
            ),
        ));
        
        $this->add(array(
            'name' => 'currentPassword',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'type' => 'password',
                'id' => 'currentPassword',
                'maxlength' => 20,
            ),
        ));

        $this->add(array(
            'name' => 'passwordVerify',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'type' => 'password',
                'id' => 'passwordVerify',
                'maxlength' => 20,
            ),
        ));

        $this->add(array(
            'name' => 'userId',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'userId',
            ),
        ));
    }
}
