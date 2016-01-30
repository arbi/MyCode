<?php

namespace Apartment\Form;

use DDD\Domain\Location\Country;
use DDD\Service\Currency\Currency;
use DDD\Service\Location;
use DDD\Service\User;
use DDD\Service\MoneyAccount as MoneyAccountService;
use Library\Form\FormBase;
use Library\Utility\Debug;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;

class ApartmentReviewCategory extends FormBase {

	/**
	 * @param ServiceLocatorInterface $sm
	 */
	public function __construct($categoryId) {
		parent::__construct('apartment-review-category-form');

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
			'name' => 'type',
			'type' => 'Zend\Form\Element\Select',
			'options' => array(
				'value_options' => Objects::getApartmentReviewCategoryStatus(),
			),
			'attributes' => array(
				'class' => 'form-control',
				'id' => 'type',
			),
		));

		$this->add(array(
			'name' => 'save',
			'attributes' => array(
				'type' => 'submit',
				'class' => 'btn btn-primary state save-bank-account col-sm-2 col-xs-12 margin-left-10 pull-right',
				'value' => ($categoryId > 0) ? 'Save Changes' : 'Add New Code',
			),
		));
	}
}
