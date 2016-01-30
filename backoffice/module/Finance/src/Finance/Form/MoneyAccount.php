<?php

namespace Finance\Form;

use DDD\Dao\Finance\Bank;
use DDD\Dao\Finance\LegalEntities;
use DDD\Domain\Finance\Bank as BankDomain;
use DDD\Service\Currency\Currency;
use DDD\Service\Location;
use DDD\Service\User;
use DDD\Service\MoneyAccount as MoneyAccountService;
use Library\Form\FormBase;
use Zend\Db\ResultSet\ResultSet;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class MoneyAccount extends FormBase
{
	use ServiceLocatorAwareTrait;

	protected $userList;

	/**
	 * @param ServiceLocatorInterface $sm
	 * @param \DDD\Domain\MoneyAccount\MoneyAccount|bool $bankAccountData
	 * @param int $moneyAccountId
	 */
	public function __construct(ServiceLocatorInterface $sm, $bankAccountData, $moneyAccountId)
	{
		parent::__construct('bank-account');
		$this->setServiceLocator($sm);

		$this->setAttribute('class', 'form-horizontal');
		$this->setAttribute('method', 'post');

		$hidden = $moneyAccountId ? ' hidden' : '';

		$this->add([
			'name'       => 'name',
			'attributes' => [
				'type'  => 'text',
				'class' => 'form-control',
				'id'    => 'name',
			],
		]);

		$this->add([
			'name'       => 'type',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => MoneyAccountService::getMoneyAccountTypes(),
			],
			'attributes' => [
				'class' => 'form-control' . $hidden,
				'id'    => 'type',
			],
		]);

		$this->add([
			'name'       => 'currency_id',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getCurrencyList(),
			],
			'attributes' => [
				'class' => 'form-control' . $hidden,
				'id'    => 'currency_id',
			],
		]);


		$this->add([
			'name'       => 'account_ending',
			'attributes' => [
				'type'      => 'text',
				'class'     => 'form-control',
				'id'        => 'account_ending',
				'size'      => 4,
				'maxlength' => 4,
			],
		]);

		$this->add([
			'name'       => 'description',
			'attributes' => [
				'type'  => 'textarea',
				'class' => 'form-control',
				'id'    => 'description',
				'rows'  => 2,
			],
		]);

		$userList    = $this->getUserList();
		$userList[0] = '-- Please Select --';
		asort($userList);

		// Smart dropdown
		if ($moneyAccountId) {
			if (!isset($userList[$bankAccountData['responsible_person_id']])) {
				$userList[$bankAccountData['responsible_person_id']] = $bankAccountData['responsible_person_name'];
			}

			if (!isset($userList[$bankAccountData['card_holder_id']])) {
				$userList[$bankAccountData['card_holder_id']] = $bankAccountData['card_holder_name'];
			}
		}

		$this->add([
			'name'       => 'card_holder_id',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $userList,
			],
			'attributes' => [
				'class' => 'form-control',
				'id'    => 'card_holder_id',
			],
		]);

		$this->add([
			'name'       => 'responsible_person_id',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $userList,
			],
			'attributes' => [
				'class' => 'form-control',
				'id'    => 'responsible_person_id',
			],
		]);

		$this->add([
			'name'       => 'is_searchable',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => [
					\DDD\Service\MoneyAccount::SEARCHABLE_YES => 'Yes',
					\DDD\Service\MoneyAccount::SEARCHABLE_NO  => 'No',
				],
			],
			'attributes' => [
				'class' => 'form-control is-searchable',
			],
		]);

		$this->add([
			'name'       => 'legal_entity_id',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getLegalEntityList(
					$moneyAccountId ? $bankAccountData['legal_entity_id'] : null
				),
			],
			'attributes' => [
				'type'  => 'text',
				'class' => 'form-control',
				'id'    => 'legal_entity_id',
			],
		]);

		$this->add([
			'name'       => 'bank_id',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getBankList(),
			],
			'attributes' => [
				'type'  => 'text',
				'class' => 'form-control',
				'id'    => 'bank_id',
			],
		]);

		$this->add([
			'name'       => 'bank_account_number',
			'attributes' => [
				'type'  => 'text',
				'class' => 'form-control',
				'id'    => 'bank_account_number',
			],
		]);


		$this->add([
			'name'       => 'view_transactions',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getUserList(),
			],
			'attributes' => [
				'class'    => 'form-control selectize',
				'id'       => 'view_transactions',
				'multiple' => true,
			],
		]);

		$this->add([
			'name'       => 'add_transactions',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getUserList(),
			],
			'attributes' => [
				'class'    => 'form-control selectize',
				'id'       => 'add_transactions',
				'multiple' => true,
			],
		]);

		$this->add([
			'name'       => 'manage_transactions',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getUserList(),
			],
			'attributes' => [
				'class'    => 'form-control selectize',
				'id'       => 'manage_transactions',
				'multiple' => true,
			],
		]);

		$this->add([
			'name'       => 'manage_account',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $this->getUserList(),
			],
			'attributes' => [
				'class'    => 'form-control selectize',
				'id'       => 'manage_account',
				'multiple' => true,
			],
		]);

		$this->add([
			'name'       => 'save',
			'attributes' => [
				'type'  => 'submit',
				'class' => 'btn btn-primary state save-bank-account pull-right col-sm-2 col-xs-12 margin-left-10',
				'value' => 'Save',
			],
		]);
	}

	public function getCurrencyList()
	{
		/** @var Currency $currencyService */
		$currencyService    = $this->getServiceLocator()->get('service_currency_currency');
		$currencyDomainList = $currencyService->getCurrenciesToPopulateSelect();
		$currencies         = ['-- Please Select --'];

		if ($currencyDomainList->count()) {
			foreach ($currencyDomainList as $currencyDomain) {
				$currencies[$currencyDomain->getId()] = "{$currencyDomain->getName()} ({$currencyDomain->getCode()})";
			}
		}

		return $currencies;
	}


	public function getBankList()
	{
		/**
		 * @var Bank $bankDao
		 * @var BankDomain[]|ResultSet $bankDomainList
		 */
		$bankDao        = $this->getServiceLocator()->get('dao_finance_bank');
		$bankDomainList = $bankDao->fetchAll();
		$banks          = ['-- Please Select --'];

		if ($bankDomainList->count()) {
			foreach ($bankDomainList as $bankDomain) {
				$banks[$bankDomain->getId()] = $bankDomain->getName();
			}
		}

		return $banks;
	}

	/**
	 * @param int $legalEntityId
	 * @return array
	 */
	public function getLegalEntityList($legalEntityId = null)
	{
		/**
		 * @var LegalEntities $legalDao
		 */
		$legalDao = $this->getServiceLocator()->get('dao_finance_legal_entities');
		return $legalDao->getForSelect($legalEntityId);
	}

	public function getUserList()
	{
		if (!$this->userList) {
			/** @var User $userService */
			$userService    = $this->getServiceLocator()->get('service_user');
			$userDomainList = $userService->getUsersList();
			$users          = [];

			if ($userDomainList->count()) {
				foreach ($userDomainList as $userDomain) {
					if ($userDomain->getSystem()) {
						continue;
					}

					$users[$userDomain->getId()] = $userDomain->getFirstName() . ' ' . $userDomain->getLastName();
				}
			}

			$this->userList = $users;
		}

		return $this->userList;
	}
}
