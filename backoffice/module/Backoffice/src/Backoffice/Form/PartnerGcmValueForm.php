<?php
namespace Backoffice\Form;

use Zend\Form\Form;

/**
 * Class    PartnerGcmValueForm
 * @package Backoffice\Form
 * @author  Harut Grigoryan
 */
class PartnerGcmValueForm extends Form
{
    public function __construct()
    {
        parent::__construct('partner-gcm-value-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'keys[]',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class'       => 'form-control',
                'id'          => 'add-partner-gcm-key',
                'placeholder' => 'Key'
            ]
        ]);

        $this->add([
            'name' => 'values[]',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'class'       => 'form-control',
                'id'          => 'add-partner-gcm-value',
                'placeholder' => 'Value'
            ],
        ]);

        $this->add(array(
            'name' => 'partnerId',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'partnerId',
            ),
        ));
    }
}
