<?php

namespace Recruitment\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;
use Library\Utility\Debug;

class Job extends FormBase
{
    const JOB_DRAFT    = 1;
    const JOB_LIVE     = 2;
    const JOB_DIACTIVE = 3;

    public function __construct($name, $data, $options, $departmentsId)
    {
        parent::__construct($name);

        $this->setName($name);

        $this->add([
            'name'       => 'title',
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'title',
                'maxlength' => 50,
            ],
        ]);

        $this->add([
            'name'       => 'subtitle',
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'subtitle',
                'maxlength' => 50,
            ],
        ]);

        $this->add([
            'name' => 'requirements',
            'options' => [
                'label' => '',
            ],
            'attributes' =>[
                'type'  => 'textarea',
                'class' => 'tinymce',
                'rows'  => '4',
                'id'    => 'requirements',
            ],
        ]);


        $this->add([
            'name' => 'description',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'  => 'textarea',
                'class' => 'tinymce',
                'rows'  => '4',
                'id'    => 'description',
            ],
        ]);
        $this->add([
            'name' => 'meta_description',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'  => 'textarea',
                'class' => 'form-control',
                'rows'  => '2',
                'id'    => 'description',
            ],
        ]);

        $this->add([
            'name' => 'start_date',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control datepicker',
                'id'        => 'start_date',
                'maxlength' => 50,
            ],
        ]);

        $memberList    = [0 => '-- Choose Manager --'];
        $memberRawList = $options['ginosiksList'];

        if ($memberRawList->count()) {
            foreach ($memberRawList as $member) {
                $memberList[$member['id']] = $member['firstname'] . ' ' . $member['lastname'];
            }
        }

        $countryList  = [0 => ''];
        $provinceList = [0 => ''];
        $cityList     = [0 => ''];

        $locationInfo   = $data['location'];
        $countryRawList = $locationInfo['countryOptions'];
        if (count($countryRawList)) {
            foreach ($countryRawList as $key => $value) {
                $countryList[$key] = $value;
            }
        }

        $provinceRawList = $locationInfo['provinceOptions'];
        if (count($provinceRawList)) {
            foreach ($provinceRawList as $key => $value) {
                $provinceList[$key] = $value;
            }
        }

        $cityRawList = $locationInfo['cityOptions'];
        if (count($cityRawList)) {
            foreach ($cityRawList as $key => $value) {
                $cityList[$key] = $value;
            }
        }

        $users = $memberList;
        if (isset($data['disabledHM'])) {
            $disabledHM = $data['disabledHM'];

            if (!is_null($disabledHM)) {
                $users += $disabledHM;
            }
        }

        $this->add([
            'name'       => 'hiring_manager_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'hiring_manager_id',
            ],
            'options'    => [
                'value_options' => $users
            ],
        ]);

        $teams = [0 => '-- Choose Team --'];

        if (isset($options['teamList']) && !is_null($options['teamList'])) {
            foreach ($options['teamList'] as $team) {
                $teams[$team->getId()] = $team->getName();
            }
        }

        if (isset($data['hiring_team']) && count($data['hiring_team'])) {
            $teams = $teams + $data['hiring_team'];
        }

        $this->add([
            'name'       => 'hiring_team_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'hiring_team_id',
            ],
            'options'    => [
                'value_options' => $teams
            ],
        ]);

        $depList    = [0 => '-- Choose Department --'];
        foreach ($departmentsId as $dep) {
            $depList[$dep->getId()] = $dep->getName();
        }

        $this->add([
            'name'       => 'department_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'department_id',
            ],
            'options'    => [
                'value_options' => $depList
            ],
        ]);

        $this->add([
            'name'       => 'country_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'country_id',
            ],
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $countryList,
            ],
        ]);

        $this->add([
            'name'       => 'province_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'province_id',
            ],
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $provinceList,
            ],
        ]);

        $this->add([
            'name'       => 'city_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'city_id',
            ],
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $cityList,
            ],
        ]);

        $buttons_save = 'Create Job';

        if (isset($data['job'])) {
            $buttons_save = 'Save';
        }

        $this->add([
            'name' => 'save_button',
            'options' => [
                'label' => $buttons_save,
            ],
            'attributes' => [
                'type'              => 'button',
                'class'             =>
                    'btn btn-primary state col-sm-2 ' .
                    'col-xs-12 margin-left-10 pull-right',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
                'value'             => 'Save',
            ],
        ]);

        $this->add([
            'name'       => 'job_delete_button',
            'options'    => [
                'label' => 'Delete Job',
            ],
            'attributes' => [
                'type'              => 'button',
                'class'             =>
                    'btn btn-danger btn-large ' .
                    'pull-right helper-margin-left-04em state',
                'data-loading-text' => 'Deleteing...',
                'id'                => 'job_delete_button',
                'value'             => 'Delete Job',
            ],
        ]);

        $jobStatus[self::JOB_DRAFT]    = 'Draft';
        $jobStatus[self::JOB_LIVE]     = 'Live';
        $jobStatus[self::JOB_DIACTIVE] = 'Inactive';

        $this->add([
            'name'       => 'status',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'status',
            ],
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $jobStatus,
            ],
        ]);

        $this->add([
            'name' => 'cv_required',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id' => 'cv_required',
            ],
            'options' => [
                'label' => 'Cv required',
            ],
        ]);

        $this->add([
            'name' => 'notify_manager',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id' => 'notify_manager',
            ],
            'options' => [
                'label' => 'Notify Manager',
            ],
        ]);

        if (isset($data['job'])) {
            $objectData   = new \ArrayObject();
            $job          = $data['job'];

            $objectData['title']             = $job->getTitle();
            $objectData['subtitle']          = $job->getSubtitle();
            $objectData['description']       = $job->getDescription();
            $objectData['requirements']      = $job->getRequirements();
            $objectData['hiring_manager_id'] = $job->getHiringManagerId();
            $objectData['hiring_team_id']    = $job->getHiringTeamId();
            $objectData['country_id']        = $job->getCountryId();
            $objectData['province_id']       = $job->getProvinceId();
            $objectData['city_id']           = $job->getCityId();
            $objectData['start_date']        = $job->getStartDate();
            $objectData['department_id']     = $job->getDepartmentId();
            $objectData['status']            = $job->getStatus();
            $objectData['cv_required']       = $job->getCvRequired();
            $objectData['notify_manager']    = $job->getNotifyManager();
            $objectData['meta_description']  = $job->getMetaDescription();

            $this->bind($objectData);
        }
    }
}
