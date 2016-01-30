<?php
namespace Backoffice\Form;

use DDD\Service\User\ExternalAccount;
use Zend\Form\Form;

/**
 * Class    UserAccountForm
 * @package Backoffice\Form
 * @author  Harut Grigoryan
 */
class UserAccountForm extends Form
{
    /**
     * @param string $name
     * @param array  $countries
     */
    public function __construct($name = 'user-account-form', $countries = [])
    {
        parent::__construct($name);
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', $name);

        $this->add([
            'name'       => 'id',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        $this->add([
            'name'     => 'name',
            'type'     => 'Zend\Form\Element\Text',
            'required' => true,
            'options'   => [
                'label' => 'Account Name',
            ],
            'attributes'    => [
                'id'    => 'user-account-',
                'class' => 'form-control',
            ],
        ]);

        // User account type
        $this->add(
            [
                'name'       => 'type',
                'type'       => 'Zend\Form\Element\Select',
                'required'   => true,
                'options'    => [
                    'label'         => 'Type',
                    'value_options' => [
                        -1 => '-- Choose --',
                        \DDD\Service\User\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_DIRECT_DEPOSIT  => 'Direct Deposit',
                        \DDD\Service\User\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_CHECK           => 'Check',
                        \DDD\Service\User\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_CASH            => 'Cash',
                        \DDD\Service\User\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_COMPANY_CARD    => 'Company Card',
                    ]
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'user-account-type'
                ],
            ]
        );

        $this->add([
            'name'     => 'fullLegalName',
            'type'     => 'Zend\Form\Element\Text',
            'required' => true,
            'options'   => [
                'label' => 'Full Legal Name',
            ],
            'attributes'    => [
                'id'    => 'fullLegalName',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name'     => 'billingAddress',
            'type'     => 'Zend\Form\Element\Text',
            'required' => false,
            'options'   => [
                'label' => 'Billing Address',
            ],
            'attributes'    => [
                'id'    => 'billingAddress',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name'     => 'mailingAddress',
            'type'     => 'Zend\Form\Element\Text',
            'required' => true,
            'options'   => [
                'label' => 'Mailing Address',
            ],
            'attributes'    => [
                'id'    => 'mailingAddress',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name'     => 'bankAddress',
            'type'     => 'Zend\Form\Element\Text',
            'required' => false,
            'options'   => [
                'label' => 'Bank Address',
            ],
            'attributes'    => [
                'id'    => 'bankAddress',
                'class' => 'form-control',
            ],
        ]);

        // generate currency list
        $countryList = [0 => '-- Choose Country --'];
        foreach ($countries as $country) {
            $countryList[$country->getId()] = $country->getName();
        }

        $this->add([
            'name'     => 'countryId',
            'type'     => 'Zend\Form\Element\Select',
            'required' => true,
            'options' => [
                'label'         => 'Country',
                'value_options' => $countryList
            ],
            'attributes' => [
                'id'    => 'countryId',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name'     => 'iban',
            'type'     => 'Zend\Form\Element\Text',
            'required' => false,
            'options' => [
                'label' => 'Account Number (IBAN)'
            ],
            'attributes' => [
                'id'           => 'user-account-iban',
                'class'        => 'form-control',
            ],
        ]);

        $this->add([
            'name'     => 'swft',
            'type'     => 'Zend\Form\Element\Text',
            'required' => false,
            'options'  => [
                'label' => 'Routing Number (SWFT/BIC)'
            ],
            'attributes' => [
                'id'           => 'user-account-swft',
                'class'        => 'form-control',
            ],
        ]);

        $this->add([
            'name'     => 'isDefault',
            'type'     => 'Zend\Form\Element\Checkbox',
            'options'  => [
                'required'        => false,
                'label'           => 'Is Default',
                'checked_value'   => ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT,
                'unchecked_value' => 0,
            ],
            'attributes' => [
                'id'    => 'user-account-is-default',
                'value' => ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT
            ],
        ]);

        $this->add([
            'name'       => 'status',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        //Submit button
        $this->add([
            'name' => 'submit',
            'options' => [
                'primary' => true,
            ],
            'attributes' => [
                'value'             => 'Submit',
                'class'             => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                'data-loading-text' => 'Saving...'
            ],
        ]);
    }
}
