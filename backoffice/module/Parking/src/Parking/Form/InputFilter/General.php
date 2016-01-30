<?php
namespace Parking\Form\InputFilter;

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

class General extends InputFilterBase
{
    public function __construct()
    {

        $this->add(
            [
                'name'     => 'country_id',
                'required' => true
            ]
        );

        $this->add(
            [
                'name'     => 'province_id',
                'required' => true
	        ]
        );

	    $this->add(
            [
                'name'     => 'city_id',
                'required' => true
	        ]
        );
    }
}
