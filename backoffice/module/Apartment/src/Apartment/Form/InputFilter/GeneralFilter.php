<?php

namespace Apartment\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class GeneralFilter extends InputFilterBase {

	/**
	 * @var
	 */
	protected $inputFilter;

	/**
	 * Filters for form fields
	 */
	public function getInputFilter() {

		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();

			$inputFilter
				->add([
					'name'     => 'apartment_name',
					'required' => true,
					'filters'  => [
						['name' => 'StringTrim']
					],
				])
				->add([
					'name'     => 'room_count',
					'required' => true
				])
				->add([
					'name'     => 'bedrooms',
					'required' => true
				]);

			$this->inputFilter = $inputFilter;

			return $inputFilter;
		}
	}
}
