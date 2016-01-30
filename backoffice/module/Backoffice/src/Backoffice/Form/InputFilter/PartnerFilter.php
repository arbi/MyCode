<?php

namespace Backoffice\Form\InputFilter;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class PartnerFilter implements InputFilterAwareInterface
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

            $inputFilter->add($factory->createInput([
                'name'       => 'partner_name',
                'required'   => true,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 250,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'business_model',
                'required'   => false,
                'filters'    => [
                ],
                'validators' => [
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'contact_name',
                'required'   => true,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 100,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'email',
                'required'   => true,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'EmailAddress',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 0,
                            'max'      => 150,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'mobile',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 10,
                            'max'      => 100,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'phone',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 10,
                            'max'      => 100,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'     => 'cubilis_id',
                'required' => false,
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'commission',
                'required'   => true,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 0,
                            'max'      => 3,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'is_ota',
                'required'   => false,
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'additional_tax_commission',
                'required'   => false,
            ]));

            $inputFilter->add($factory->createInput([
                'name'     => 'apply_fuzzy_logic',
                'required' => false
            ]));

            $inputFilter->add($factory->createInput([
                'name'     => 'is_deducted_commission',
                'required' => false
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'show_partner',
                'required'   => false,
                'filters'    => [

                ],
                'validators' => [

                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'account_holder_name',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 0,
                            'max'      => 55,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'bank_bsr',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 0,
                            'max'      => 255,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'bank_account_num',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 0,
                            'max'      => 200,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'create_date',
                'required'   => false,
                'filters'    => [

                ],
                'validators' => [

                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'     => 'gid',
                'required' => false,
                'filters'  => [
                    ['name' => 'Int'],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'notes',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 0,
                            'max'      => 65000,
                        ],
                    ],
                ],
            ]));

            $inputFilter->add($factory->createInput([
                'name'       => 'notes',
                'required'   => false,
                'filters'    => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                        ],
                    ],
                ],
            ]));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }
}
