<?php

namespace Recruitment\Form\InputFilter;

use Zend\Validator\Hostname as HostnameValidator;
use Library\InputFilter\InputFilterBase;

class InterviewFilter extends InputFilterBase
{
    public function __construct()
    {
        $this->add([
            'name'       => 'id',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'Digits'
                ],
            ],
        ]);

        $this->add(['name'       => 'participants',
            'required'   => true,
        ]);

        $this->add([
            'name'       => 'applicant_id',
            'required'   => true
        ]);

        $this->add([
            'name'       => 'from',
            'required'   => true
        ]);

        $this->add([
            'name'     => 'to',
            'required' => false
        ]);

        $this->add([
            'name'     => 'place',
            'required' => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
        ]);
    }
}
