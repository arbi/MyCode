<?php

namespace Backoffice\Form;

use DDD\Service\User\Evaluations;
use Zend\Form\Element;
use Library\Form\FormBase;
use Library\Constants\Objects;
use Library\Constants\Constants;

class User extends FormBase
{
    public function __construct($name = null, $data, $options)
    {
        parent::__construct($name);

        $name = $this->getName();
        if (null === $name) {
            $this->setName('user');
        }

        $email_attr = array(
            'type' => 'Zend\Form\Element\Email',
            'class' => 'form-control',
            'id' => 'email',
            'maxlength' => 40,
        );

        $this->add(array(
            'name' => 'email',
            'options' => array(
                'label' => '',
            ),
            'attributes' => $email_attr,
        ));

        $this->add(array(
            'name' => 'alt_email',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'Zend\Form\Element\Email',
                'class' => 'form-control',
                'id' => 'alt_email',
                'maxlength' => 40,
            ),
        ));

        $this->add(array(
            'name' => 'password',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'type' => 'password',
                'id' => 'password',
                'maxlength' => 20,
            ),
        ));

        $this->add(array(
            'name' => 'firstname',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'firstname',
                'maxlength' => 35,
            ),
        ));

        $this->add(array(
            'name' => 'lastname',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'lastname',
                'maxlength' => 35,
            ),
        ));

        $this->add(array(
            'name' => 'birthday',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control datepicker',
                'id' => 'birthday',
            ),
        ));

        $this->add(array(
            'name' => 'living_city',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'living_city',
                'maxlength' => 200,
            ),
        ));

        $this->add([
            'name' => 'external',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id' => 'external',
            ],
            'options' => [
            ],
        ]);

        $managers = [0 => '-- Choose Manager --'];
        foreach ($options->get('managers') as $row){
            $managers[$row->getId()] = $row->getFirstName() . ' ' . $row->getLastName();
        }
        $this->add(array(
            'name' => 'manager',
            'options' => array(
                'label' => '',
                'value_options' => $managers
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'manager',
                'class' => 'form-control'
            ),
        ));

        $countries = array('0'=>'-- Choose Countries --');
        foreach ($options->get('countries') as $row){
            $countries[$row->getId()] = $row->getName();
        }

        $cities = $options->get('cities');
        $this->add(array(
            'name' => 'country',
            'options' => array(
                'label' => '',
                'value_options' => $countries
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'country',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name' => 'city',
            'options' => array(
                'label' => '',
                'value_options' => $cities
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'city',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name' => 'timezone',
            'options' => array(
                'label' => '',
                'value_options' => Objects::getTimezoneOptions(),
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'timezone',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name' => 'last_login',
            'options' => array(
                'label' => '',
            ),
            'type' => 'Text',
            'attributes' => array(
                'id' => 'last_login',
                'class' => 'form-control',
                'disabled' => true,
            ),
        ));

        $this->add(array(
            'name' => 'internal_number',
            'options' => array(
                'label'         => '',
                'required'      => false
            ),
            'type' => 'Number',
            'attributes' => array(
                'id'      => 'internal_number',
                'class'   => 'form-control',
                'min'     => 0,
                'value'   => 0
            ),
        ));

        $this->add(array(
            'name' => 'startdate',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control datepicker',
                'id' => 'startdate',
            ),
        ));

        $this->add(array(
            'name' => 'end_date',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control datepicker',
                'id' => 'startdate',
            ),
        ));

        $this->add(array(
            'name' => 'vacationdays',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'Float',
                'class' => 'form-control',
                'id' => 'vacationdays',
                'max' => 365,
                'min' => -365,
                'maxlength' => 16,
            ),
        ));

        $this->add(array(
            'name' => 'vacation_days_per_year',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'vacation_days_per_year',
                'max' => 365,
                'min' => 0,
                'maxlength' => 6,
            ),
        ));

        $this->add(array(
            'name' => 'personalphone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'personalphone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'businessphone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'businessphone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'emergencyphone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'emergencyphone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'housephone',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'housephone',
                'maxlength' => 15,
            ),
        ));

        $this->add(array(
            'name' => 'asana_id',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'asana_id',
                'maxlength' => 25,
            ),
        ));

        $this->add(array(
            'name' => 'address_permanent',
            'options' => array(
                'label' => '',
            ),
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'address_permanent',
                'maxlength' => 250,
            ),
        ));

        $this->add(array(
            'name' => 'address_residence',
            'options' => array(
                'label' => '',
            ),
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'address_residence',
                'maxlength' => 250,
            ),
        ));

        $dashboards = array();
        foreach ($options->get('dashboards') as $dashboard) {
        	$dashboards[$dashboard->getId()] = $dashboard->getName();
        }
        $this->add(
        	array(
        		'name' => 'dashboards',
        		'options' => array(
        			'label' => '',
        			'value_options' => $dashboards
        		),
        		'type' => 'Zend\Form\Element\Select',
        		'attributes' => array(
        			'id' 		=> 'dashboards',
        			'class' 	=> 'selectize form-control',
        			'multiple' 	=> 'multiple',
        		),
        ));

        $this->add(array(
		    'name' => 'system',
		    'type' => 'Zend\Form\Element\Checkbox',
		    'options' => array(
			    'label' => 'System User',
			    'use_hidden_element' => true,
			    'checked_value' => 1,
			    'unchecked_value' => 0,
		    ),
		    'attributes' => ['class' => 'margin-top-10', 'id' =>'system'],
	    ));

        $user_concierge_array = [];

        if (!is_null($data)) {
            $obj_user_concierge = $data->get('user_conciergegroups');
            foreach ($obj_user_concierge as $row){
                $user_concierge_array[] = $row->getApartmentGroupId();
            }
        }

        $conciergegroups = [];
        foreach ($options->get('concierges') as $row) {
            $conciergegroups[$row->getId()] = $row->getName();
        }

        $this->add(array(
            'name' => 'conciergegroups',
            'options' => array(
                'label' => '',
                'value_options' => $conciergegroups
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'conciergegroups',
                'class' => 'selectize form-control',
                'multiple' => 'multiple',
            ),
        ));

        $submitLable = 'Save Changes';
        if (is_null($data)) {
            $submitLable = 'Create User';
        }

        $this->add(array(
            'name' => 'user_button',
            'options' => array(
                'label' => $submitLable,
            ),
            'attributes' => array(
                'type' => 'button',
                'class' => 'btn btn-primary personal-tab-btn administration-tab-btn permission-tab-btn pull-right margin-left-10 col-sm-2 col-xs-12',
                'data-loading-text' => 'Saving...',
                'id' => 'user_button',
                'value' => $submitLable,
            ),
        ));

        $officeRawList = $options->get('office');
        $offices = ['-1' => '-- Choose Office --'];
        if (count($officeRawList)) {
			foreach ($officeRawList as $key => $office) {
				$offices[$key] = $office;
			}
		}
		if (is_object($data)) {
            $userOffice = $data->get('userOffice');
            if (!is_null($userOffice)) {
                $offices += $userOffice;
            }
        }

        $this->add(array(
            'name' => 'reporting_office_id',
            'options' => array(
                'label' => '',
                'value_options' => $offices
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'reporting_office_id',
                'class' => 'form-control'
            ),
        ));

        $departments = $options->get('departments');

        $this->add(array(
            'name' => 'department',
            'options' => array(
                'label' => '',
                'value_options' => $departments,
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'department',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name' => 'position',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'position',
                'maxlength' => 50,
            ),
        ));

        $this->add(array(
            'name' => 'city_hidden_id',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'city_hidden_id',
            ),
        ));

        $this->add(array(
            'name' => 'period_of_evaluation',
            'options' => array(
                'label' => '',
                'value_options' => Evaluations::getEvaluationPeriodOptions(),
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'period_of_evaluation',
                'class' => 'form-control',
            ),
        ));

        $this->add(array(
            'name' => 'user_hidden_id',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'user_hidden_id',
            ),
        ));

        $this->add(array(
            'name' => 'disabled',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'disabled',
            ),
        ));
        $this->add(array(
            'name'          => 'employment',
            'type'          => 'Zend\Form\Element\Select',
            'options'       => array(
                'label'         => 'Employment (%)',
                'value_options' => $this->getSelectPercentValues(),
                'required'      => true
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'employment'
            )
        ));

        $this->add(array(
            'name' => 'sick_days',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'int',
                'class'     => 'form-control',
                'id'        => 'sick_days',
                'max'       => 3,
                'min'       => -1,
                'maxlength' => 2,
                'class'     => 'form-control'
            ),
        ));

        if (is_object($data)) {
            $objectData = new \ArrayObject();
            $obj        = $data->get('user_main');

            $objectData['firstname']              = $obj->getFirstName();
            $objectData['lastname']               = $obj->getLastName();
            $objectData['birthday']               = \Library\Utility\DateLocal::convertDateFromYMD($obj->getBirthDay());
            $objectData['email']                  = $obj->geteMail();
            $objectData['internal_number']        = $obj->getInternalNumber();
            $objectData['alt_email']              = $obj->getAlt_email();
            $objectData['manager']                = $obj->getManager_id();
            $objectData['country']                = $obj->getCountry_id();
            $objectData['city']                   = $obj->getCity_id();
            $objectData['living_city']            = $obj->getLivingCity();
            $objectData['city_hidden_id']         = $obj->getCity_id();
            $objectData['user_hidden_id']         = $obj->getId();
            $objectData['startdate']              = ((int)$obj->getStart_date()) ? date('d M Y', strtotime($obj->getStart_date())) : '';
            $objectData['end_date']               = ((int)$obj->getEndDate()) ? date('d M Y', strtotime($obj->getEndDate())) : '';
            $objectData['vacationdays']           = ($obj->getVacation_days()) ? $obj->getVacation_days() : '';
            $objectData['vacation_days_per_year'] = ($obj->getVacation_days_per_year()) ? $obj->getVacation_days_per_year() : '';
            $objectData['personalphone']          = ($obj->getPersonal_phone()) ? $obj->getPersonal_phone() : '';
            $objectData['businessphone']          = ($obj->getBusiness_phone()) ? $obj->getBusiness_phone() : '';
            $objectData['emergencyphone']         = ($obj->getEmergency_phone()) ? $obj->getEmergency_phone() : '';
            $objectData['housephone']             = ($obj->getHouse_phone()) ? $obj->getHouse_phone() : '';
            $objectData['address_permanent']      = $obj->getAddressPermanent();
            $objectData['address_residence']      = $obj->getAddressResidence();
            $objectData['timezone']               = $obj->getTimezone();
            $objectData['shift']                  = $obj->getShift();
            $objectData['department']             = $data->get('userDepId');
            $objectData['position']               = $obj->getPosition();
            $objectData['period_of_evaluation']   = $obj->getPeriodOfEvaluation();
            $objectData['system']                 = $obj->getSystem();
            $objectData['disabled']               = $obj->getDisabled();
            $objectData['reporting_office_id']    = $obj->getReportingOfficeId();
            $objectData['asana_id']               = $obj->getAsanaId();
            $objectData['employment']             = $obj->getEmployment();
            $objectData['sick_days']              = $obj->getSickDays();
            $objectData['external']               = $obj->isExternal();

            $objectData['last_login'] = ($obj->getLastLogin() == '0000-00-00 00:00:00') ? 'Unknown' : date(Constants::GLOBAL_DATE_FORMAT . ' \a\t H:i', strtotime($obj->getLastLogin())) . " (Amsterdam time)";

            $dashboardsArray = array();
            $obj_user_dashboards = $data->get('user_dashboards');
            foreach ($obj_user_dashboards as $row){
            	$dashboardsArray[] = $row->getDashboardID();
            }
            $objectData['dashboards'] = $dashboardsArray;

            $objectData['conciergegroups'] = $user_concierge_array;

            $this->bind($objectData);
        }
    }

    private function getSelectPercentValues()
    {
        return range(0, 100);
    }
}
