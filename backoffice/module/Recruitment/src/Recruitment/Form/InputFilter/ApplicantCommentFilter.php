<?php

namespace Recruitment\Form\InputFilter;

use Zend\Validator\StringLength;
use Library\InputFilter\InputFilterBase;

class ApplicantCommentFilter extends InputFilterBase
{
    public function __construct()
    {
        $this->add([
            'name'       => 'comment',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 100,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum number of symbols 100',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
