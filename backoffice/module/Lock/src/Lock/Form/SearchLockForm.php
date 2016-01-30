<?php

namespace Lock\Form;

use Zend\Form\Form;
use DDD\Service\Lock\General;

/**
 * Class SearchLockForm
 * @package Lock\Form
 * @author Hrayr Papikyan
 */
class SearchLockForm extends Form
{
    /**
     * @param array $allLocks
     * @param array $allLockTypes
     * @param string $name
     */
    public function __construct($allLockTypes, $name = 'search_lock')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');

        // Lock Types
        $this->add(
            [
                'name'       => 'usage',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => General::getAllUsages()
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'usage'
                ],
            ]
        );

        // Lock Types
        $this->add(
            [
                'name'       => 'type_id',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => $allLockTypes
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'type_id'
                ],
            ]
        );
    }



}
