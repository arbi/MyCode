<?php
namespace Backoffice\Form;

use Zend\Form\Form;

/**
 * Class    SalarySchemeForm
 * @package Backoffice\Form
 * @author  Harut Grigoryan
 */
class SalarySchemeForm extends Form
{
    /**
     * @param string $name
     * @param array $accounts
     * @param array $currencies
     */
    public function __construct($name = 'salary-scheme-form', $accounts = [], $currencies = [])
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
                'label' => 'Scheme Name',
            ],
            'attributes'    => [
                'id'    => 'salary-scheme-name',
                'class' => 'form-control',
            ],
        ]);

        // Pay Frequency type
        $this->add(
            [
                'name'       => 'payFrequencyType',
                'type'       => 'Zend\Form\Element\Select',
                'required'   => true,
                'options'    => [
                    'label'         => 'Pay Frequency',
                    'value_options' => [
                        -1 => '-- Choose Type --',
                        \DDD\Service\User\SalaryScheme::SALARY_SCHEME_PAY_FREQUENCY_TYPE_WEEKLY    => 'Weekly',
                        \DDD\Service\User\SalaryScheme::SALARY_SCHEME_PAY_FREQUENCY_TYPE_BI_WEEKLY => 'Bi-Weekly',
                        \DDD\Service\User\SalaryScheme::SALARY_SCHEME_PAY_FREQUENCY_TYPE_MONTHLY   => 'Monthly',
                    ]
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'pay-frequency-type'
                ],
            ]
        );

        $this->add([
            'name'     => 'effectiveFrom',
            'type'     => 'Zend\Form\Element\Text',
            'required' => true,
            'options'   => [
                'label' => 'Effective From',
            ],
            'attributes' => [
                'type'  => 'text',
                'class' => 'form-control daterangepicker',
                'id'    => 'effectiveFrom',
            ],
        ]);

        $this->add([
            'name'     => 'effectiveTo',
            'type'     => 'Zend\Form\Element\Text',
            'required' => true,
            'options'   => [
                'label' => 'Effective To',
            ],
            'attributes' => [
                'type'  => 'text',
                'class' => 'form-control daterangepicker',
                'id'    => 'effectiveTo',
            ],
        ]);

        // generate currency list
        $accountList = [0 => '-- Choose Account --'];
        foreach ($accounts as $account) {
            $accountList[$account->getId()] = $account->getName();
        }

        $this->add([
            'name' => 'externalAccountId',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label'         => 'Account',
                'value_options' => $accountList
            ],
            'attributes' => [
                'id'    => 'externalAccountId',
                'class' => 'form-control',
            ],
        ]);

        // Scheme type
        $this->add(
            [
                'name'       => 'type',
                'type'       => 'Zend\Form\Element\Select',
                'required'   => true,
                'options'    => [
                    'label'         => 'Type',
                    'value_options' => [
                        -1 => '-- Choose Type --',
                        \DDD\Service\User\SalaryScheme::SALARY_SCHEME_TYPE_LOAN         => 'Loan',
                        \DDD\Service\User\SalaryScheme::SALARY_SCHEME_TYPE_SALARY       => 'Salary',
                        \DDD\Service\User\SalaryScheme::SALARY_SCHEME_TYPE_COMPENSATION => 'Compensation',
                    ]
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'type'
                ],
            ]
        );

        $this->add([
            'name'     => 'salary',
            'type'     => 'Zend\Form\Element\Text',
            'required' => true,
            'options'   => [
                'label' => 'Amount',
            ],
            'attributes' => [
                'type'  => 'text',
                'class' => 'form-control',
                'id'    => 'salary',
            ],
        ]);

        // generate currency list
        $currencyList = [0 => '-- Choose --'];
        foreach ($currencies as $currencyID => $currencyCode) {
            $currencyList[$currencyID] = $currencyCode;
        }

        $this->add([
            'name' => 'currencyId',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label'         => 'Currency',
                'value_options' => $currencyList
            ],
            'attributes' => [
                'id'    => 'currencyId',
                'class' => 'form-control',
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
