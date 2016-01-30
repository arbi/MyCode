<?php

namespace Venue\Form;

use Zend\Form\Form;

/**
 * Class SearchLockForm
 *
 * @package Venue\Form
 * @author  Harut Grigoryan
 */
class SearchVenueForm extends Form
{
    /**
     * @param string $name
     * @param array $userList
     * @param array $cities
     */
    public function __construct($name = 'search_venue', $userList = [], $cities = [])
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');

        // generate cities list
        $citiesList    = [0 => '-- Choose City --'];
        foreach ($cities as $city) {
            $citiesList[$city->getId()] = $city->getCity();
        }

        $this->add(
            [
                'name' => 'cityId',
                'type' => 'Zend\Form\Element\Select',
                'options' => [
                    'label'         => 'City',
                    'value_options' => $citiesList
                ],
                'attributes' => [
                    'id' => 'cityId',
                    'class' => 'form-control',
                ],
            ]
        );

        // generate member list
        $memberList = [0 => '-- Choose Manager --'];
        foreach ($userList as $member) {
            $memberList[$member['id']] =
                $member['firstname'] . ' ' .
                $member['lastname'];
        }

        $this->add([
            'name' => 'managerId',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'Manager',
                'value_options' => $memberList
            ],
            'attributes' => [
                'id'    => 'managerId',
                'class' => 'form-control',
            ],
        ]);

        $memberList[0] = '-- Choose Cashier --';

        $this->add(
            [
                'name' => 'cashierId',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label'         => 'Cashier',
                    'value_options' => $memberList
                ),
                'attributes' => array(
                    'id'    => 'cashierId',
                    'class' => 'form-control',
                ),
            ]
        );
    }
}
