<?php

namespace Apartment\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class LocationFilter extends InputFilterBase {
    protected $inputFilter;

	public function __construct() {
	}

	public function getInputFilter() {

		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();
			$factory     = new InputFactory();

			// input filter for country_id
			$inputFilter->add($factory->createInput(array(
					'name'     => 'country_id',
					'required' => false,
					'filters'  => array(
							array('name' => 'Int'),
					),
			)));

			// input filter for province_id
			$inputFilter->add($factory->createInput(array(
					'name'     => 'province_id',
					'required' => true,
					'filters'  => array(
							array('name' => 'Int'),
					),
			)));

			// input filter for city_id
			$inputFilter->add($factory->createInput(array(
					'name'     => 'city_id',
					'required' => true,
					'filters'  => array(
							array('name' => 'Int'),
					),
			)));

            // input filter for building
			$inputFilter->add($factory->createInput(array(
					'name'     => 'building',
					'required' => true,
					'filters'  => array(
							array('name' => 'Int'),
					),
			)));

			// input filter for address
			$inputFilter->add($factory->createInput(array(
					'name'     => 'address',
					'required' => true,
			)));

			// input filter for postal_code
			$inputFilter->add($factory->createInput(array(
					'name'     => 'postal_code',
					'required' => true,
			)));

			// input filter for building_id
			$inputFilter->add($factory->createInput(array(
					'name'     => 'building_id',
					'required' => false,
			)));

			$inputFilter->add($factory->createInput(array(
				'name'     => 'building_section',
				'required' => false,
			)));
            $this->inputFilter = $inputFilter;
		}
		return $this->inputFilter;
	}
}
