<?php
namespace Backoffice\Form\InputFilter;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\Validator\Digits;

/**
 * Class    SalarySchemeFilter
 * @package Backoffice\Form\InputFilter
 * @author  Harut Grigoryan
 */
final class SalarySchemeFilter implements InputFilterAwareInterface
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

            // input filter for pay frequency type
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'payFrequencyType',
                    'required' => true,
                    'filters'  => [
                        ['name' => 'Int'],
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

            // input filter for user account Id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'userAccountId',
                    'required' => false,
                    'filters'  => [
                        ['name' => 'Int'],
                    ],
                ]
            ));

            // input filter for currency Id
            $inputFilter->add($factory->createInput(
                [
                    'name'     => 'currencyId',
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