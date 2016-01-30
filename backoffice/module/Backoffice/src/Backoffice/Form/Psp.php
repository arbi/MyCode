<?php

namespace Backoffice\Form;

use DDD\Domain\Location\Country;
use DDD\Service\Currency\Currency;
use DDD\Service\Location;
use DDD\Service\User;
use DDD\Service\MoneyAccount as MoneyAccountService;
use Library\Form\FormBase;
use Library\Utility\Debug;
use Zend\ServiceManager\ServiceLocatorInterface;

class Psp extends FormBase {
	protected $sm;

	/**
	 * @param ServiceLocatorInterface $sm
	 * @param int $pspId
	 */
	public function __construct($sm, $pspId) {
		parent::__construct('psp');

		$this->sm = $sm;
		$this->setAttribute('class', 'form-horizontal');
		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'name',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control',
				'id' => 'name',
			),
		));

		$this->add(array(
			'name' => 'short_name',
			'attributes' => array(
				'type' => 'text',
				'class' => 'form-control',
				'id' => 'short_name',
			),
		));

		$this->add(array(
			'name' => 'money_account_id',
			'type' => 'Zend\Form\Element\Select',
			'options' => array(
				'value_options' => $this->getBankList($pspId),
			),
			'attributes' => array(
				'class' => 'form-control',
				'id' => 'money_account_id',
			),
		));

		$this->add(array(
			'name' => 'authorization',
			'type' => 'Zend\Form\Element\Radio',
			'attributes' => array(
				'id' => 'authorization',
				'value' => 0,
			),
			'options' => array(
				'label_attributes' => [
					'class' => 'radio-inline',
				],
				'value_options' => array(
					'0' => 'No',
					'1' => 'Yes',
				),
			),
		));

		$this->add(array(
			'name' => 'rrn',
			'type' => 'Zend\Form\Element\Radio',
			'attributes' => array(
				'id' => 'rrn',
				'value' => 0,
			),
			'options' => array(
				'label_attributes' => [
					'class' => 'radio-inline',
				],
				'value_options' => array(
					'0' => 'No',
					'1' => 'Yes',
				),
			),
		));

		$this->add(array(
			'name' => 'error_code',
			'type' => 'Zend\Form\Element\Radio',
			'attributes' => array(
				'id' => 'error_code',
				'value' => 0,
			),
			'options' => array(
				'label_attributes' => [
					'class' => 'radio-inline',
				],
				'value_options' => array(
					'0' => 'No',
					'1' => 'Yes',
				),
			),
		));

		$this->add(array(
			'name' => 'save',
			'attributes' => array(
				'type' => 'submit',
				'class' => 'btn btn-primary state save-bank-account col-sm-2 col-xs-12 margin-left-10 pull-right',
				'value' => 'Save',
			),
		));
	}

	public function getBankList($pspId)
    {
		$bankDao = $this->getServiceLocator()->get('dao_money_account_money_account');
		$moneyList = $bankDao->getAllMoneyAccounts(1);
		$moneys = ['-- All Money Account --'];
        $pspDao = $this->getServiceLocator()->get('dao_psp_psp');
        $inActiveBank = $pspDao->getPspInActiveBank($pspId);

        if ($inActiveBank) {
            $moneys[$inActiveBank['money_account_id']] = "{$inActiveBank['money_account_name']} ({$inActiveBank['code']})";
        }

        foreach($moneyList as $money) {
            $moneys[$money->getId()] = "{$money->getName()} ({$money->getCurrencyName()})";
        }

		return $moneys;
	}

	protected function getServiceLocator() {
		return $this->sm;
	}
}
