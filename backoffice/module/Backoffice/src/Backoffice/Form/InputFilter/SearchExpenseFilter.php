<?php

namespace Backoffice\Form\InputFilter;

use Library\Validator\ClassicValidator;
use Zend\I18n\Validator\Float;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\Validator\Digits;
use Zend\Validator\Regex;

/**
 *
 * @author developer
 *
 */
final class SearchExpenseFilter implements InputFilterAwareInterface {

	/**
	 *
	 * @var InputFilter
	 */
	protected $inputFilter;

	/**
	 */
	function __construct() {

		// TODO - Insert your code here
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Zend\InputFilter\InputFilterAwareInterface::getInputFilter()
	 *
	 */
	public function getInputFilter() {

		if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory     = new InputFactory();

            // Transaction Date
            $inputFilter->add($factory->createInput(array(
                'name' => 'transaction_date',
                'required' => true,
                'validators' => array(
	                array(
		                "name" => "regex",
		                'options' => array(
			                'pattern'=>  ClassicValidator::getDateRegex(),
			                'messages' => array(
				                Regex::INVALID => "Date format is invalid.",
				                Regex::NOT_MATCH => "Date format is invalid.",
				                Regex::ERROROUS => "Date format is invalid."
			                )
		                )
	                )
                ),
            )));

            // Currency
            $inputFilter->add($factory->createInput(array(
                'name' => 'currency',
                'required' => true,
                'validators' => array(
                    array(
	                    'name' => 'Digits',
	                    'options' => array(
		                    'min' => 5,
		                    'max' => 1000,
		                    'messages' => array(
			                    Digits::NOT_DIGITS => 'Can contain only digits.',
		                    ),
	                    ),
                    ),
                ),
            )));

			// Category
			$inputFilter->add($factory->createInput(array(
				'name' => 'category',
				'required' => true,
				'validators' => array(
					array(
						'name' => 'Int',
						'options' => array(
							'min' => 1,
							'max' => 1000,
						),
					),
				),
			)));

			// Bank Account
			$inputFilter->add($factory->createInput(array(
				'name' => 'bank_account',
				'required' => true,
				'validators' => array(
					array(
						'name' => 'Int',
						'options' => array(
							'min' => 1,
							'max' => 1000,
						),
					),
				),
			)));

			// Supplier
			$inputFilter->add($factory->createInput(array(
				'name' => 'supplier',
				'required' => true,
				'validators' => array(
					array(
						'name' => 'Int',
						'options' => array(
							'min' => 1,
							'max' => 1000,
						),
					),
				),
			)));

			// Purpose
			$inputFilter->add($factory->createInput(array(
				'name' => 'purpose',
				'required' => true,
				'filters' => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name' => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min' => 1,
							'max' => 3500,
						),
					),
				),
			)));

			// Entered For
			$inputFilter->add($factory->createInput(array(
				'name' => 'entered_for',
				'required' => true,
				'validators' => array(
					array(
						'name' => 'Int',
						'options' => array(
							'min' => 1,
							'max' => 1000,
						),
					),
				),
			)));

			// Global Cost
			$inputFilter->add($factory->createInput(array(
				'name' => 'type',
				'validators' => array(
					array(
						'name' => 'Int',
						'options' => array(
							'min' => 1,
							'max' => 1000,
						),
					),
				),
			)));

            $mainFilter = new InputFilter();
            $mainFilter->add($inputFilter, 'fsOne');

            $this->inputFilter = $mainFilter;
        }

        return $this->inputFilter;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Zend\InputFilter\InputFilterAwareInterface::setInputFilter()
	 *
	 */
	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new \Exception("Not used");
	}
}

?>
