<?php

namespace Recruitment\Form;

use Library\Form\FormBase;

use DDD\Service\Recruitment\Applicant;

class SearchApplicants extends FormBase
{
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setName($name);

        $this->add([
            'name'       => 'applicants_status',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class'       => 'form-control selectize',
                'id'          => 'applicants_status',
                'data-status' => '',
                'multiple'    => true,
            ],
            'options'    => [
                'value_options' => Applicant::$status,
            ],
        ]);
    }
}
