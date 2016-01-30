<?php
namespace Backoffice\Form;

use Zend\Form\Form;
use Library\Constants\Objects;
use DDD\Service\Translation as TranslationService;

final class SearchTranslationForm extends Form
{

	/**
	 * form constructor
	 * @param string $name
	 */
	public function __construct($name = 'search-translation',  $pageTypesList = array(), $permission = []) {
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
					'value_options' => $this->getCategory($permission),
				),
				'attributes' => array(
					'value' => 1,
					'onchange'=>'changeType(this.value)',
					'class' => 'form-control',
					'id' => 'category',
				),
			)
		);

		// Search Text
		$this->add(
			array(
				'name' => 'srch_txt',
				'type' => 'Zend\Form\Element\Text',
				'options' => array(
					'label' => false,
				),
				'attributes' => array(
					'placeholder' => 'Search',
					'class' => 'form-control',
					'id' => 'srch_txt',
				),
			)
		);

		$this->add(
			array(
				'name' => 'description',
				'type' => 'Zend\Form\Element\Text',
				'options' => array(
					'label' => false,
				),
				'attributes' => array(
					'placeholder' => 'Description',
					'class' => 'form-control',
					'id' => 'description',
				),
			)
		);

		// Un Type
		$pageTypes = [];
		foreach ($pageTypesList as $row) {
			$pageTypes[$row->getId()] = $row->getName();
		}
		$this->add(
			array(
				'name' => 'un_type',
				'type' => 'Zend\Form\Element\Select',
				'options' => array(
					'label' => false,
					'value_options' =>  $pageTypes,
				),
				'attributes' => array(
					'data-placeholder' => 'Page Type',
					'class'    => 'form-control selectize',
					'id'       => 'un_type',
					'multiple' => true
				),
			)
		);

		$productTypes = [0 => '-- All Types --'];
		foreach (TranslationService::$PRODUCT_TYPES as $key => $name) {
			$productTypes[$key] = $name;
		}

		$this->add(
			array(
				'name' => 'product_type',
				'type' => 'Zend\Form\Element\Select',
				'options' => array(
					'label' => false,
					'value_options' =>  $productTypes,
				),
				'attributes' => array(
					'class' => 'form-control',
					'id' => 'product_type'
				),
			)
		);

		$this->add(array(
			'name' => 'id_translation',
			'options' => array(
				'label' => '',
			),
			'attributes' => array(
				'type' => 'Zend\Form\Element\Number',
				'class' => 'input-mini',
				'id' => 'id_translation',
				'maxlength' => 10,
				'placeholder' => 'ID',
				'class' => 'form-control'
			),
		));

	}

	private function getCategory($permission){
		$categoryList = [];
		foreach ($permission as $perm){
			if(isset(Objects::getTranslationCategory()[$perm]))
				$categoryList[$perm] = Objects::getTranslationCategory()[$perm];
		}
		return $categoryList;
	}
}
