<?php

namespace Backoffice\Form\ApartmentGroup;

use Library\Form\FormBase;
use Library\Constants\Objects;

class GeneralForm extends FormBase {
    /**
     * @param int|null|string $name
     * @param object $data
     * @param object $options
     * @param bool $global
     */
    public function __construct($name, $data, $options, $global) {
        parent::__construct($name);

        $this->setName($name);

        /**
         * General assignements
         *
         * @var \DDD\Domain\ApartmentGroup\ApartmentGroup $accGroupManageMain
         */
        if ($data) {
            $accGroupManageMain = $data->get('accGroupManageMain');
            $allUsers           = $data->get('usersList');
            $allApartments      = $data->get('apartmentList');
        }

        $name_attr = array(
            'type'      => 'text',
            'class'     => 'form-control',
            'id'        => 'name',
            'maxlength' => 150,
        );

        $accommodation_attr = array(
            'id'       => 'skills',
            'class'    => 'form-control selectize',
            'multiple' => true,
        );

        $group_manager_attr = array(
            'class' => 'form-control',
            'id'    => 'group_manager_id',
        );

        $timezone_attr = array(
            'id'    => 'timezone',
            'class' => 'form-control',
        );

        $checkbox = array(
            'id' => 'check_users',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
        );

        $usage_cost_center = array(
            'id'                 => 'usage_cost_center',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
        );

        $isBuilding = array(
            'id'                 => 'usage_building',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
        );

        $isPerformanceGroup = array(
            'id'                 => 'usage_performance_group',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
        );

        $country_attr = array(
            'id' => 'country_id',
            'class' => 'form-control',
        );

        if (!$global) {
            $name_attr['disabled']           = true;
            $accommodation_attr['disabled']  = true;
            $group_manager_attr['disabled']  = true;
            $timezone_attr['disabled']       = true;
            $checkbox['disabled']            = true;
            $usage_cost_center['disabled']   = true;
            $isBuilding['disabled']          = true;
            $isAppartel['disabled']          = true;
            $isPerformanceGroup['disabled']  = true;
            $country_attr['disabled']        = true;
        }

        $this->add(array(
            'name' => 'name',
            'attributes' => $name_attr,
        ));

        $this->add([
            'name' => 'active',
            'type' => 'Zend\Form\Element\Hidden',
        ]);

        $buildingUsageVal = isset($accGroupManageMain) ? $accGroupManageMain->isBuilding() : 0;
        $this->add([
            'name' => 'usage_building_val',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id'    => 'usage_building_val',
                'value' => $buildingUsageVal
            ]
        ]);

        $accommodations = [];

        foreach ($options->get('accommodationList') as $row) {
            $accommodations[$row->getId()] = $row->getName();
        }

        $this->add(array(
            'name' => 'accommodations',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => isset($accGroupManageMain) ? $accommodations : [],
                'disable_inarray_validator' => true,
            ),
            'attributes' => $accommodation_attr,
        ));

        $countryOptions = $options->get('countryList');

        $this->add(array(
            'name' => 'country_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $countryOptions
            ),
            'attributes' => $country_attr,
        ));


        $this->add(array(
            'name' => 'apartment_group_id',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'apartment_group_id',
            ),
        ));

        $peopleList = [0 => ''];
        $peopleRawList = $options->get('peopleList');

        if ($peopleRawList->count()) {
            foreach ($peopleRawList as $people) {
                $peopleList[$people['id']] = $people['firstname'] . ' ' . $people['lastname'];
            }
        }

        $peopleListForGroupManagerId = $peopleList;
        if (isset($accGroupManageMain)) {
            if ($accGroupManageMain->getGroupManagerId() && !isset($peopleListForGroupManagerId[$accGroupManageMain->getGroupManagerId()])) {
                $peopleListForGroupManagerId[$accGroupManageMain->getGroupManagerId()] = $allUsers[$accGroupManageMain->getGroupManagerId()];
            }
        }

        $this->add(array(
            'name'       => 'group_manager_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $group_manager_attr,
            'options' => array(
                'value_options' => $peopleListForGroupManagerId,
            ),
        ));

        $this->add(array(
            'name' => 'timezone',
            'options' => array(
                'value_options' => $this->getTimeZoneList(),
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $timezone_attr,
        ));

        if (is_object($data)) {
            if ($accGroupManageMain->getIsArrivalsDashboard()) {
                $checkbox['checked'] = 'checked';
            }
        }

        $this->add(array(
            'name' => 'check_users',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => $checkbox
        ));

        if (is_object($data)) {
            if ($accGroupManageMain->getCostCenter()) {
                $usage_cost_center['checked'] = 'checked';
            }
        }

        $this->add(array(
            'name' => 'usage_cost_center',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => $usage_cost_center
        ));

        if (is_object($data)) {
            if($accGroupManageMain->isBuilding()) {
                $isBuilding['checked'] = 'checked';
            }
        }

        $this->add(array(
            'name' => 'usage_building',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => $isBuilding
        ));

        if (is_object($data)) {
            if ($accGroupManageMain->getIsPerformanceGroup()) {
                $isPerformanceGroup['checked'] = 'checked';
            }
        }

        $this->add(array(
            'name' => 'usage_performance_group',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => $isPerformanceGroup
        ));

        $buttons_save = 'Create Group';

        if (is_object($data)) {
            $buttons_save = 'Save Changes';
        }

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

        if ($global) {
            $this->add(array(
                'name' => 'group_disable_button',
                'options' => array(
                    'label' => 'Disable Group',
                ),
                'attributes' => array(
                    'type'              => 'button',
                    'class'             => 'btn btn-danger col-sm-2 col-xs-12 pull-right margin-left-10 state',
                    'data-loading-text' => 'Disabling...',
                    'id'                => 'group_disable_button',
                    'value'             => 'Delete Group',
                ),
            ));
        }

        if (is_object($data)) {
            $accommodationsArray = [];
            $accommodationsNew = $accommodations;
            $objectData        = new \ArrayObject();

            $objectData['name']                   = $accGroupManageMain->getName();
            $objectData['active']                 = $accGroupManageMain->getActive();
            $objectData['timezone']               = $accGroupManageMain->getTimezone();
            $objectData['user']                   = $accGroupManageMain->getFirstName() . ' ' . $accGroupManageMain->getLastName();
            $objectData['apartment_group_id']     = $accGroupManageMain->getId();
            $objectData['group_manager_id']       = $accGroupManageMain->getGroupManagerId();
            $objectData['concierge_email']        = $accGroupManageMain->getEmail();
            $objectData['country_id']             = $accGroupManageMain->getCountryId();
            $objAccommodationsList                = $data->get('accommodationsList');

            foreach ($objAccommodationsList as $row) {
                $accommodationsArray[] = $row->getApartmentId();

                if (!isset($accommodationsNew[$row->getApartmentId()])) {
                    $accommodationsNew[$row->getApartmentId()] = $allApartments[$row->getApartmentId()];
                }
            }

            if (count($accommodationsNew) != $accommodations) {
                $this->get('accommodations')->setOptions(['value_options' => $accommodationsNew]);
            }

            $objectData['accommodations'] = $accommodationsArray;
            $this->bind($objectData);
        }
    }

    /**
     *
     * @return array
     */
    private function getTimeZoneList()
    {
        return (['' => '-- Choose Timezone --'] + Objects::getTimezoneOptions());
    }
}
