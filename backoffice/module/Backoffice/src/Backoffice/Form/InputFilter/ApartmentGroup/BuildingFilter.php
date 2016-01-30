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

class BuildingFilter extends InputFilterBase
{
    public function __construct($global)
    {
        $this->add([
            'name' => 'key_instruction_page_type',
            'required' => true,
            'allowEmpty' => false,
        ]);
    }
}
