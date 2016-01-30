<?php

namespace Finance\Form\InputFilter;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

/**
 *
 * @author Hrayr Papikyan
 *
 */
final class LegalEntitiesFilter implements InputFilterAwareInterface
{
	/**
	 * @var InputFilter
	 */
	protected $inputFilter;

	/**
	 * @see \Zend\InputFilter\InputFilterAwareInterface::getInputFilter()
	 */
	public function getInputFilter()
    {
		if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory     = new InputFactory();


            // input filter for id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'id',
                    'required' => false,
                    'filters'  => [
                        ['name' => 'Int'],
                    ],
                ]
            ));


            // input filter for name
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'name',
                    'required' => true,
                    'filters'  => [
                        ['name' => 'StripTags'],
                        ['name' => 'StringTrim'],
                    ],
                    'validators' => [
                        [
                            'name'    => 'StringLength',
                            'options' => [
                                'encoding' => 'UTF-8',
                                'min'      => 1,
                                'max'      => 128,
                            ],
                        ],
                    ],
                ]
            ));

            // input filter for description
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'description',
                    'required' => false,
                    'filters'  => [
                        ['name' => 'StripTags'],
                        ['name' => 'StringTrim'],
                    ],
                    'validators' => [
                        [
                            'name'    => 'StringLength',
                            'options' => [
                                'encoding' => 'UTF-8'
                            ],
                        ],
                    ],
                ]
            ));

            $inputFilter->add(
                [
                    'name'     => 'country_id',
                    'required' => true
                ]
            );

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
	}

	/**
	 * @see \Zend\InputFilter\InputFilterAwareInterface::setInputFilter()
	 */
	public function setInputFilter(InputFilterInterface $inputFilter)
    {
		throw new \Exception("Not used");
	}
}

?>