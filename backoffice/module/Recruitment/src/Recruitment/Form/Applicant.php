<?php

namespace Recruitment\Form;

use Library\Form\FormBase;

use DDD\Service\Recruitment\Applicant as ApplicantService;

class Applicant extends FormBase
{
    public function __construct($name, $data, $options = null)
    {
        parent::__construct($name);

        $this->setName($name);

        $this->add([
            'name'       => 'applicants_status',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'class'       => 'form-control input-sm',
                'id'          => 'applicants_status',
                'data-status' => ''
            ],
            'options'    => [
                'value_options' => ApplicantService::$status,
            ],
        ]);

        $memberList    = [0 => ''];
        $memberRawList = $options['ginosiksList'];

        if ($memberRawList->count()) {
            foreach ($memberRawList as $member) {
                $memberList[$member['id']] = $member['firstname'] . ' ' . $member['lastname'];
            }
        }

        $this->add([
            'name'       => 'interviewers',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'value_options' => $memberList
            ],
            'attributes' => [
                'id'       => 'interviewers',
                'class'    => 'form-control selectize',
                'multiple' => true,
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

        $this->add([
            'name' => 'save_button',
            'options' => [
                'label' => 'Save',
            ],
            'attributes' => [
                'type'              => 'button',
                'class'             =>
                    'btn btn-primary state col-sm-2 ' .
                    'col-xs-12 margin-left-10 pull-right soft-hide history-tab-btn',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
                'value'             => 'Save',
            ],
        ]);

        $this->add([
            'name' => 'upload-cv-btn',
            'options' => [
                'label' => 'Upload',
            ],
            'attributes' => [
                'type'              => 'button',
                'class'             =>
                    'btn btn-primary state col-sm-2 ' .
                    'col-xs-12 margin-left-10 pull-right soft-hide',
                'data-loading-text' => 'Uploading...',
                'id'                => 'upload-cv-btn',
                'value'             => 'upload',
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

        $this->add([
            'name' => 'comment',
            'options' => [
                'label' => 'Comment',
            ],
            'attributes' => [
                'type'        => 'textarea',
                'class'       => 'form-control',
                'rows'        => '3',
                'id'          => 'comment',
                'placeholder' => 'Write comment here, Visible for Hiring Manager and HR',
            ],
        ]);

        $this->add([
            'name' => 'hr_only_comment',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => 'HR Only',
            ],
            'attributes' => [
                'id' => 'hr_only_comment'
            ],
        ]);

        $this->add([
            'name' => 'attachment_doc',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type' => 'file',
                'id' => 'attachment_doc',
                'class' => 'hidden-file-input',
                'data-max-size' => '52428800'
            ],
        ]);
    }
}
