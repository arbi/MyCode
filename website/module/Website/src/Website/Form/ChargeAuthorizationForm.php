<?php

namespace Website\Form;

use CreditCard\Service\Card as CardService;
use Library\Form\FormBase;

/**
 * Class ChargeAuthorizationForm
 * @package Website\Form
 *
 * @author Tigran Petrosyan
 */
class ChargeAuthorizationForm extends FormBase
{
    /**
     * @param array $resources
     */
    public function __construct($resources = [])
    {
        parent::__construct('ccca-form');

        $this->setAttributes([
            'action' => '',
            'method' => 'post',
	        'class' => 'form',
            'id' => 'ccca-form',
        ]);

        // Reservation Ticket CCCA page token
        $this->add([
            'name' => 'ccca-page-token',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        // CC security code
        $this->add([
            'name' => 'security-code',
            'required' => true,
            'attributes' => [
                'type' => 'text',
                'id' => 'cc-security-code',
                'class' => 'form-control hidden-print',
                'placeholder' => 'Type your CIV/CID here',
                'autocomplete' => 'off',
                'oninput' => 'validateForm()',
                'maxlength' => '5'
            ],
        ]);

        // CC expiration month
         $this->add([
            'name' => 'cc-expiration-month',
            'options' => [
                'value_options' => CardService::getExpirationMonthOptions()
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'cc-expiration-month',
                'class' => 'form-control',
                'onchange' => 'validateForm()'
            ],
        ]);

        // CC expiration year
        $this->add([
            'name' => 'cc-expiration-year',
            'options' => [
                'value_options' => CardService::getExpirationYearOptions()
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'cc-expiration-year',
                'class' => 'form-control',
                'onchange' => 'validateForm()'
            ],
        ]);

        // Billing address
        $this->add([
            'name' => 'billing-address',
            'required' => true,
            'attributes' => [
                'type' => 'text',
                'id' => 'billing-address',
                'class' => 'form-control hidden-print',
                'placeholder' => 'Type your billing address here',
                'autocomplete' => 'off',
                'oninput' => 'validateForm()'
            ],
        ]);


        // Print
        $this->add([
            'name' => 'print-ccca-form',
            'type' => 'button',
            'attributes' => [
                'id' => 'print-ccca-form',
                'class' => 'btn btn-primary btn-lg pull-left',
                'onclick' => 'printCccaForm()',
                'disabled' => true
            ],
            'options' => [
                'label' => '<span class="icon icon-print"></span> Print',
                'label_options' => [
                    'disable_html_escape' => true,
                ]
            ],
        ]);
    }
}
