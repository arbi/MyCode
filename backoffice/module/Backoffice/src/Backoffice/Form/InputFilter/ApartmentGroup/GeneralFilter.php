<?php
namespace Backoffice\Form\InputFilter\ApartmentGroup;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Float;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Int;
use Library\InputFilter\InputFilterBase;

class GeneralFilter extends InputFilterBase
{
    public function __construct($global)
    {
        $name = array(
            'name'       => 'name',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 50,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ),
                    ),
                ),
            ),
        );

        $apartmentGroupId = array(
            'name'       => 'apartment_group_id',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                        'name' => 'Digits',
                        'options' => array(
                            'messages' => array(
                                Digits::NOT_DIGITS => 'Can contain only digits.',
                            ),
                        ),
                    ),
                ),
        );
        $timezone = array(
            'name'       => 'timezone',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
        );

	    $this->add(array(
		    'name'       => 'group_manager_id',
		    'required'   => false
	    ));

        if(!$global){
            $name['required'] = false;
            $apartmentGroupId['required'] = false;
            $timezone['required'] = false;
        }

        $this->add($name);
        $this->add($apartmentGroupId);
        $this->add($timezone);

        $this->add(array(
            'name'       => 'active',
            'required'   => false
        ));

        $this->add(array(
            'name'       => 'accommodations',
            'required'   => false
        ));

        $this->add(array(
            'name'       => 'check_users',
            'required'   => false
        ));

        $this->add(array(
            'name'       => 'usage_cost_center',
            'required'   => false
        ));

        $this->add(array(
            'name'       => 'usage_performance_group',
            'required'   => false
        ));

        $this->add(array(
            'name'       => 'usage_building',
            'required'   => false
        ));
    }
}
