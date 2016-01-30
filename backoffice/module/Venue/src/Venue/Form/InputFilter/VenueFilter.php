<?php

namespace Venue\Form\InputFilter;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\Validator\Digits;

/**
 * Class    VenueFilter
 * @package Venue\Form\InputFilter
 * @author  Harut Grigoryan
 */
final class VenueFilter implements InputFilterAwareInterface
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
            $inputFilter->add([
                'name'       => 'id',
                'required'   => false,
                'validators' => [
                    [
                        'name' => 'Digits',
                        'options' => [
                            'messages' => [
                                Digits::NOT_DIGITS => 'Can contain only digits.',
                            ],
                        ],
                    ],
                ],
            ]);

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
                                'max'      => 256,
                            ],
                        ],
                    ],
                ]
            ));

            // input filter for status
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'status',
                    'required' => false,
                    'filters'  => [
                        ['name' => 'Int'],
                    ],
                ]
            ));

            // input filter for city Id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'cityId',
                    'required' => true,
                    'filters'  => [
                        ['name' => 'Int'],
                    ],
                ]
            ));

            // input filter for currency Id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'currencyId',
                    'required' => true,
                    'filters'  => [
                        ['name' => 'Int'],
                    ],
                ]
            ));

            // input filter for manager Id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'managerId',
                    'required' => false,
                ]
            ));

            // input filter for cashier Id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'cashierId',
                    'required' => false,
                ]
            ));

            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'acceptOrders',
                    'required' => false,
                ]
            ));

            // input filter for thresholdPrice
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'thresholdPrice',
                    'required' => false,
                ]
            ));

            // input filter for discountPrice
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'discountPrice',
                    'required' => false,
                ]
            ));

            // input filter for perdayMinPrice
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'perdayMinPrice',
                    'required' => false,
                ]
            ));

            // input filter for perdayMaxPrice
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'perdayMaxPrice',
                    'required' => false,
                ]
            ));

            // input filter for type
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'type',
                    'required' => false,
                    'filters'  => [
                        ['name' => 'Int'],
                    ],
                ]
            ));

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