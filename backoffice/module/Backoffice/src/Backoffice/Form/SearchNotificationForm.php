<?php

namespace Backoffice\Form;

use Zend\Form\Form;


class SearchNotificationForm extends Form
{

    public function __construct($allSenders, $name = 'search_notifications')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name'       => 'sender',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => $allSenders
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'sender'
                ],
            ]
        );

        $this->add(array(
            'name'       => 'active_archived',
            'attributes' => array(
                'type'  => 'hidden',
                'id'    => 'active_archived',
            ),
        ));
    }



}
