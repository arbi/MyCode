<?php
namespace Backoffice\Form;

use Zend\Form\Element;
use Library\Form\FormBase;
use Library\Constants\Objects;

class ProfileDetails extends FormBase
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $name = $this->getName();
        if (null === $name) {
            $this->setName('changeDetails');
        }
        $this->add(array(
            'name' => 'personalphone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'personalphone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'businessphone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'businessphone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'emergencyphone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'emergencyphone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'housephone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'housephone',
                'maxlength' => 15,
            ),
        ));

	    $this->add(array(
		    'name' => 'birthday',
		    'options' => array(
			    'label' => '',
		    ),
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'form-control',
			    'id' => 'birthday',
		    ),
	    ));

        $this->add(array(
            'name' => 'address_permanent',
            'options' => array(
                'label' => '',
            ),
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'address_permanent',
                'maxlength' => 250,
            ),
        ));

        $this->add(array(
            'name' => 'address_residence',
            'options' => array(
                'label' => '',
            ),
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'address_residence',
                'maxlength' => 250,
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
