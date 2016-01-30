<?php
namespace Backoffice\Form;

use Zend\Form\Form;
use DDD\Service\UnitTesting;

final class SearchTestResultsForm extends Form
{

	/**
	 * form constructor
	 * @param string $name
	 * @param array $allCategories
	 */
	public function __construct($name,  $allCategories) {
		// set the form's name
		parent::__construct($name);

		// set the method
		$this->setAttribute('method', 'post');

		// Category
		$this->add(
			array(
				'name' => 'category',
				'type' => 'Zend\Form\Element\Select',
				'options' => array(
					'label' => false,
					'value_options' => $allCategories,
				),
				'attributes' => array(
					'value' => 1,
					'class' => 'form-control selectize',
					'id' => 'category',
					'multiple' => 'multiple'
				),
			)
		);

		// Status
		$this->add(
			array(
				'name' => 'status',
				'type' => 'Zend\Form\Element\Select',
				'options' => array(
					'label' => false,
					'value_options' => UnitTesting::getStatusesForSelect(),
				),
				'attributes' => array(
					'value' => 1,
					'class' => 'form-control selectize',
					'id' => 'status',
					'multiple' => 'multiple'

				),
			)
		);

		// Search Text
		$this->add(
			array(
				'name' => 'test_name',
				'type' => 'Zend\Form\Element\Text',
				'options' => array(
					'label' => false,
				),
				'attributes' => array(
					'placeholder' => 'Test Name',
					'class' => 'form-control',
					'id' => 'test_name',
				),
			)
		);

	}

}
