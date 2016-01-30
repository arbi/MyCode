<?php

namespace Apartment\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\Validator\Digits;

class FurnitureFilter extends InputFilterBase {
	public function getInputFilter() {
		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();
			$factory     = new InputFactory();

			// input filter for province_id
			$inputFilter->add($factory->createInput(array(
				'name'     => 'room_id',
				'required' => true,
				'filters'  => array(
					array('name' => 'Int'),
				),
				'validators' => array(
					array('name' => 'not_empty'),
					array(
						'name' => 'Digits',
						'options' => array(
							'min' => 1,
							'messages' => array(
								Digits::NOT_DIGITS => 'Can contain only digits.',
							),
						),
					),
				),
			)));

			// input filter for province_id
			$inputFilter->add($factory->createInput(array(
				'name'     => 'count',
				'required' => true,
				'filters'  => array(
					array('name' => 'Int'),
				),
				'validators' => array(
					array('name' => 'not_empty'),
					array(
						'name' => 'Digits',
						'options' => array(
							'min' => 1,
							'max' => 10,
							'messages' => array(
								Digits::NOT_DIGITS => 'Can contain only digits.',
							),
						),
					),
				),
			)));
		}
	}
}
