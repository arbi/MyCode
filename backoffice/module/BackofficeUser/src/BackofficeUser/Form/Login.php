<?php
namespace BackofficeUser\Form;

use Zend\Form\Form;

/**
 * @package backoffice_user
 * @subpackage backoffice_user_forms
 * @author Tigran Petrosyan
 */
class Login extends Form
{
	/**
	 * @access public
	 * @param string $name
	 */
    public function __construct($name = 'login-form') {
        parent::__construct($name);
        $this->setName($name);

        $this->setAttributes(array(
        	'action' => '',
        	'method' => 'post',
        	'class' => 'form form-signin',
        	'id' => $name
        ));

        $this->add(array(
        	'name' => 'identity',
        	'options' => array(
        		'label' => '',
        	),
        	'attributes' => array(
                'id' => 'identity',
        		'type' => 'text',
        		'class' => 'form-control',
        		'placeholder' => 'Email or Username',
                'onkeypress' => 'submitReLogin(event)'
        	),
        ));

        $this->add(array(
        	'name' => 'credential',
        	'options' => array(
       			'label' => 'Password',
       		),
       		'attributes' => array(
                'id' => 'credential',
       			'type' => 'password',
       			'class' => 'form-control',
       			'placeholder' => 'Password',
                'onkeypress' => 'submitReLogin(event)'
       		),
        ));

        $this->add(array(
            'name' => 'sign_in',
            'options' => array(
                'label' => 'Sign In',
            ),
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary form-control',
                'value'=> 'Sign In'
            ),
        ));

        if(isset($_COOKIE['remember_my_email'])) {
            $objectData = new \ArrayObject();
            $objectData['user_email'] = $_COOKIE['remember_my_email'];
            $this->bind($objectData);
       }

    }
}
