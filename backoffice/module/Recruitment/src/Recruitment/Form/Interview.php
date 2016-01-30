<?php

namespace Recruitment\Form;

use Library\Form\FormBase;

class Interview extends FormBase
{
    public function __construct($name, $options = null)
    {
        parent::__construct($name);

        $this->setName($name);

        $participantOptions = [];

        if ($options['users']) {
            foreach($options['users'] as $user) {
                $participantOptions[$user->getId()] = $user->getFirstName() . ' ' . $user->getLastName();
            }
        }

        $this->add([
            'name' => 'id',
            'type' => 'Zend\Form\Element\Text',
        ]);

        $this->add([
            'name'       => 'participants',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'multiple' => true,
            ],
            'options'    => [
                'value_options' => $participantOptions,
            ],
        ]);

        $this->add([
            'name' => 'applicant_id',
        ]);

        $this->add([
            'name' => 'from',
        ]);

        $this->add([
            'name' => 'to',
        ]);

        $this->add([
            'name' => 'place',
            'type' => 'Zend\Form\Element\Text',
        ]);
    }
}
