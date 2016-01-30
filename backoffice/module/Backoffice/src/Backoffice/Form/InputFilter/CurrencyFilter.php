<?php

namespace Backoffice\Form\InputFilter;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

/**
 *
 * @author developer
 *        
 */
final class CurrencyFilter implements InputFilterAwareInterface {
	
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

            
            // input filter for id
            $inputFilter->add($factory->createInput(array(
                'name'     => 'id',
                'required' => true,
                'filters'  => array(
                    array('name' => 'Int'),
                ),
            )));

            
            // input filter for title
            $inputFilter->add($factory->createInput(array(
                'name'     => 'name',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 35,
                        ),
                    ),
                ),
            )));

            // input filter for code
            $inputFilter->add($factory->createInput(array(
                'name'     => 'code',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 100,
                        ),
                    ),
                ),
            )));
            
            // input filter for code
            $inputFilter->add($factory->createInput(array(
            		'name'     => 'symbol',
            		'required' => true,
            		'filters'  => array(
            				array('name' => 'StripTags'),
            				array('name' => 'StringTrim'),
            		),
            		'validators' => array(
            				array(
            						'name'    => 'StringLength',
            						'options' => array(
            								'encoding' => 'UTF-8',
            								'min'      => 1,
            								'max'      => 5,
            						),
            				),
            		),
            )));
            
            $inputFilter->add($factory->createInput(array(
            		'name'     => 'auto_update',
            		'required' => false,
            		'validators' => array(
            		),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'visible',
                'required' => false,
                'validators' => array(
                ),
            )));
            
            $inputFilter->add($factory->createInput(array(
            		'name'     => 'gate',
            		'required' => false,
            		'validators' => array(
            		),
            )));
            
            $this->inputFilter = $inputFilter;
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