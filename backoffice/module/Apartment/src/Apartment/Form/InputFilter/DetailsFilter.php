<?php

namespace Apartment\Form\InputFilter;

use Library\InputFilter\InputFilterBase;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class DetailsFilter extends InputFilterBase
{
    protected $inputFilter;

	function __construct()
    {

	}

	public function getInputFilter()
    {
		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();
			$factory     = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name' => 'primary_wifi_network',
                'required' => false,
                'allowEmpty' => true,
            )));
            $inputFilter->add($factory->createInput(array(
                'name' => 'primary_wifi_pass',
                'required' => false,
                'allowEmpty' => true,
            )));
            $inputFilter->add($factory->createInput(array(
                'name' => 'secondary_wifi_network',
                'required' => false,
                'allowEmpty' => true,
            )));
            $inputFilter->add($factory->createInput(array(
                'name' => 'secondary_wifi_pass',
                'required' => false,
                'allowEmpty' => true,
            )));
            $inputFilter->add($factory->createInput(array(
                'name' => 'amenities',
                'required' => true,
                'allowEmpty' => false,
            )));
            $inputFilter->add($factory->createInput(array(
                'name' => 'lock_id',
                'required' => true,
                'allowEmpty' => false,
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name' => 'parking_spot_ids',
                'required' => false,
                'allowEmpty' => true,
            )));
            $this->inputFilter = $inputFilter;

		}
        return $this->inputFilter;
	}
}
