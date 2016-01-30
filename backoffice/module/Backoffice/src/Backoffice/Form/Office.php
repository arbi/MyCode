<?php

namespace Backoffice\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;
use Library\Utility\Debug;

class Office extends FormBase {
	public function __construct($name, $data, $options, $global) {
		parent::__construct($name);

		$this->setName($name);

		$name_attr = [
			'type'      => 'text',
			'class'     => 'form-control',
			'id'        => 'name',
			'maxlength' => 50,
		];

		$desc_attr = [
			'type'      => 'text',
			'class'     => 'form-control',
			'id'        => 'description',
			'maxlength' => 255,
		];

		$members_attr = [
			'id'       => 'staff',
			'class'    => 'form-control selectize',
			'multiple' => true
		];

		$office_manager_attr = [
			'class' => 'form-control',
			'id'    => 'office_manager_id',
		];

	    $it_manager_attr = [
			'class' => 'form-control',
			'id'    => 'it_manager_id',
		];

	    $finance_manager_attr = [
			'class' => 'form-control',
			'id'    => 'finance_manager_id',
		];

	    $country_attr = [
			'class' => 'form-control',
			'id'    => 'country_id',
		];

	    $province_attr = [
			'class' => 'form-control',
			'id'    => 'province_id',
		];

	    $city_attr = [
			'class' => 'form-control',
			'id'    => 'city_id',
		];

		$section_attr = [
			'type'      => 'text',
			'class'     => 'form-control',
			'id'        => 'section[]',
			'maxlength' => 50,
		];

		$this->add([
            'name'       => 'name',
            'attributes' => $name_attr,
        ]);

        $this->add([
            'name'       => 'description',
            'attributes' => $desc_attr,
        ]);

        $this->add([
            'name'    => 'phone',
            'type'    => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Office Phone',
                'class'       => 'form-control',
                'id'          => 'phone',
            ],
        ]);

		$memberList    = ['' => '-- Choose Manager --'];
		$memberRawList = $options->get('ginosiksList');

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

        $staffUsers = $memberList;
        unset($staffUsers[0]);

        if (isset($data['disabledStaff'])) {
            $disedStaffs = $data['disabledStaff'];

            foreach ($disedStaffs as $disedStaff) {
                if (!is_null($disedStaff)) {
                    $staffUsers += $disedStaff;
                }
            }
        }

		$this->add([
            'name'       => 'staff',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'value_options' => $staffUsers,
                'empty_option' => 'Please select staffs'
            ],
            'attributes' => $members_attr,
        ]);

        $officeUsers = $memberList;
		if (isset($data['disabledOM'])) {
            $disabledOM = $data['disabledOM'];

            if (!is_null($disabledOM)) {
                $officeUsers += $disabledOM;
            }
        }

		$this->add([
            'name'       => 'office_manager_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $office_manager_attr,
            'options'    => [
                'value_options' => $officeUsers,
            ],
        ]);

        $itUsers = $memberList;
		if (isset($data['disabledIM'])) {
            $disabledIM = $data['disabledIM'];

            if (!is_null($disabledIM)) {
                $itUsers += $disabledIM;
            }
        }

		$this->add([
            'name'       => 'it_manager_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $it_manager_attr,
            'options'    => [
                'value_options' => $itUsers,
            ],
        ]);

        $financeUsers = $memberList;
		if (isset($data['disabledFM'])) {
            $disabledFM = $data['disabledFM'];

            if (!is_null($disabledFM)) {
                $financeUsers += $disabledFM;
            }
        }

		$this->add([
            'name'       => 'finance_manager_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $finance_manager_attr,
            'options'    => [
                'value_options' => $financeUsers
            ],
        ]);

		$this->add([
            'name'       => 'country_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $country_attr,
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $countryList
            ],
        ]);

		$this->add([
            'name'       => 'province_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $province_attr,
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $provinceList
            ],
        ]);

		$this->add([
            'name'       => 'city_id',
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => $city_attr,
            'options'    => [
                'disable_inarray_validator' => true,
                'value_options' => $cityList
            ],
        ]);

        $this->add([
            'name'       => 'address',
            'options'    => [
                'label' => '',
            ],
            'type'       => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'class'     => 'form-control',
                'rows'      => 3,
                'id'        => 'address',
                'maxlength' => 250,
            ],
        ]);

        $this->add([
            'name'       => 'section[]',
            'attributes' => $section_attr,
        ]);

		$buttons_save = 'Create Office';

		if (isset($data['office'])) {
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
            'name' => 'addMore',
            'options' => [
                'label' => 'Add More Section',
            ],
            'attributes' => [
                'type'              => 'button',
                'class'             =>
                    'btn btn-info state pull-right',
                'data-loading-text' => 'Adding...',
                'id'                => 'addMore',
                'value'             => 'Add More Section',
            ],
        ]);

        $this->add([
            'name' => 'disable',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id'    => 'disable',
                'value' => isset($data['office']) ? $data['office']->getDisable() : 0,
            ],
        ]);

        $this->add([
            'name' => 'map_attachment',
            'type' => 'Zend\Form\Element\File',
            'attributes' => [
                'id' => 'map_attachment',
                'class' => 'hide',
                'accept' => 'image/*',
            ],
        ]);

        $this->add([
            'name' => 'map_attachment_name',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'map_attachment_name',
            ],
        ]);

        $this->add([
            'name' => 'delete_attachment',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'delete_attachment',
                'class' => 'hide',
            ],
        ]);

		if ($global) {
			$this->add([
                'name'       => 'office_delete_button',
                'options'    => [
                    'label' => 'Delete Office',
                ],
                'attributes' => [
                    'type'              => 'button',
                    'class'             =>
                        'btn btn-danger btn-large ' .
                        'pull-right helper-margin-left-04em state',
                    'data-loading-text' => 'Deleteing...',
                    'id'                => 'office_delete_button',
                    'value'             => 'Delete Office',
                ],
            ]);
		}

		if (isset($data['office'])) {
			$objectData   = new \ArrayObject();

			$office       = $data['office'];
			$sections     = $data['sections'];
			$managersInfo = $data['managersInfo'];
            $staffs       = $data['staffsId'];

			foreach ($managersInfo as $managerInfo){
				$objectData[$managerInfo['id']] = $managerInfo['userId'];
			}

            foreach ($sections as $key => $section) {
                $objectData['section[' . $key . ']'] = $section->getName();
            }

            $objectData['name']        = $office->getName();
            $objectData['description'] = $office->getDescription();
            $objectData['address']     = $office->getAddress();
            $objectData['country_id']  = $office->getCountryId();
            $objectData['province_id'] = $office->getProvinceId();
            $objectData['city_id']     = $office->getCityId();
            $objectData['phone']       = $office->getPhone();
            $objectData['staff']       = $staffs;

			$this->bind($objectData);
		}
	}
}
