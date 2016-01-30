<?php

namespace Backoffice\Form\ApartmentGroup;

use Library\Form\FormBase;

class ConciergeForm extends FormBase {
    /**
     * @param int|null|string $name
     * @param object $data
     */
    public function __construct($name, $data, $options) {
        parent::__construct($name);

        $pspList = $options->get('pspList');
        $pspArray = [
            '' => '-- Choose PSP --'
        ];
        if ($pspList && $pspList->count()) {
            foreach ($pspList as $psp) {
                $pspArray[$psp->getId()] = $psp->getShortName();
            }
        }

        $this->setName($name);

        $name_attr = array(
            'type'      => 'text',
            'class'     => 'form-control',
            'id'        => 'name',
            'maxlength' => 150,
            'value'     => $data->getName()
        );

        $conciergeEmailAttr = [
            'type'      => 'text',
            'class'     => 'form-control',
            'id'        => 'concierge_email',
            'maxlength' => 255,
        ];

        $this->add(
            [
            'name'       => 'concierge_email',
            'attributes' => $conciergeEmailAttr,
            ]
        );

        $this->add(array(
            'name'       => 'name',
            'attributes' => $name_attr,
        ));

        $buttons_save = 'Save Changes';

        $this->add(array(
            'name' => 'save_button',
            'options' => array(
                'label' => $buttons_save,
            ),
            'attributes' => array(
                'type'              => 'button',
                'class'             => 'btn btn-primary state col-sm-2 col-xs-12 margin-left-10 pull-right',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
                'value'             => 'Save',
            ),
        ));

        $this->add([
            'name' => 'psp_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'PSP',
                'value_options' => $pspArray
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'psp-id',
            ],
        ]);

        if (is_object($data)) {
            $objectData = new \ArrayObject();
            $objectData['concierge_email'] = $data->getEmail();
            $objectData['psp_id']          = $data->getPspId();
            $this->bind($objectData);
        }
    }
}
