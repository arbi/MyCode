<?php

namespace Parking\Controller;

use DDD\Service\Location;
use Library\Constants\Roles;
use Parking\Form\ParkingUpload;
use DDD\Service\Textline;
use Parking\Controller\Base as ParkingBaseController;
use Parking\Form\General as GeneralForm;
use Parking\Form\InputFilter\General as GeneralFilter;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use DDD\Service\Lock\General as LockService;
use DDD\Service\Translation;

class General extends ParkingBaseController
{
	public function indexAction()
    {
		/** @var \DDD\Service\Parking\General $parkingGeneralService */
        $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');
        $auth                  = $this->getServiceLocator()->get('library_backoffice_auth');

        $hasAMM = $auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT);
        $form = $this->getForm();
        $form->prepare();

        $viewModel = new ViewModel();

        $textline = $active = 0;
        $parkingPermit = '';

        if ($this->parkingLotId) {
            $generalInfo = $parkingGeneralService->getParkingById($this->parkingLotId);

            $usages = iterator_to_array($parkingGeneralService->getUsages($this->parkingLotId));

            foreach ($usages as $usage) {
                $usage['link'] =
                    '<a href="' . $this->url()->fromRoute('apartment/general', ['apartment_id' => $usage['id']]) . '"  target="_blank">
                        <span class="glyphicon glyphicon-share"></span> ' . $usage['name'] .
                    '</a>';
            }

            if (!$generalInfo) {
                Helper::setFlashMessage(['error' => 'Parking lot not found']);
                return $this->redirect()->toRoute('parking/lots');
            }

            $textline = $generalInfo->getDirectionTextlineId();
            $parkingPermit = $generalInfo->getParkingPermit();

            $active = $generalInfo->isActive();

            $viewModel->setVariables([
                'hasAMM'  => $hasAMM,
                'usages'  => $usages,
            ]);
        }

        $uploadForm = new ParkingUpload('parking-permit');

        $viewModel->setVariables([
            'parkingLotId'  => $this->parkingLotId,
            'form'          => $form,
            'textlineId'    => $textline,
            'parkingPermit' => $parkingPermit,
            'isActive'      => $active,
            'uploadForm'    => $uploadForm,
        ]);

		return $viewModel;
	}

    private function getForm()
    {
        /**
         * @var \DDD\Service\Location $generalLocationService
         * @var \DDD\Service\Parking\General $parkingGeneralService
         * @var \DDD\Domain\Geolocation\Provinces[] $provinces
		 * @var \DDD\Domain\Geolocation\Cities[] $cities
         * @var \DDD\Service\ApartmentGroup $apartmentGroupService
         */
        $generalLocationService = $this->getServiceLocator()->get('service_location');
		$parkingGeneralService  = $this->getServiceLocator()->get('service_parking_general');
        $apartmentGroupService  = $this->getServiceLocator()->get('service_apartment_group');

        $lockParkingUsageService = $this->getServiceLocator()->get('service_lock_usages_parking');
        $allLocksArray           = $lockParkingUsageService->getLockByUsage($this->parkingLotId);

        $countries = $generalLocationService->getAllActiveCountries();
        $countryOptions = [0 => '-- Choose Country --'];

        if ($countries->count()) {
            foreach ($countries as $country) {
                if ($country->getChildrenCount() != '') {
                    $countryOptions[$country->getID()] = $country->getName();
                }
            }
        }

		$countryId = false;
        if ($this->parkingLotId > 0) {
            $generalInfo = $parkingGeneralService->getParkingById($this->parkingLotId);
			$countryId = $generalInfo->getCountryId();
        } else {
            $generalInfo = false;
        }

        // province options
        $provinces = $generalLocationService->getActiveChildLocations(Location::LOCATION_TYPE_PROVINCE, $generalInfo ? $generalInfo->getCountryId() : 0);
        $provinceOptions = [];

        foreach ($provinces as $province) {
            $provinceOptions[$province->getID()] = $province->getName();
        }

        // city options
        $cities = $generalLocationService->getActiveChildLocations(Location::LOCATION_TYPE_CITY, $generalInfo ? $generalInfo->getProvinceId() : 0);
        $cityOptions = [];

        foreach ($cities as $city) {
            $cityOptions[$city->getID()] = $city->getName();
        }

		$formOptions = [
            'locks'     => $allLocksArray,
            'countries' => $countryOptions,
            'provinces' => $provinceOptions,
            'cities'    => $cityOptions
        ];

        return new GeneralForm($this->parkingLotId, 'parking-general', $generalInfo, $formOptions);
    }

	public function saveAction()
    {
		/**
		 * @var \DDD\Service\Parking\General $parkingGeneralService
         * @var \DDD\Dao\Textline\Universal $textlineUnDao
		 */
        $lockDao = $this->getServiceLocator()->get('dao_lock_locks');
		$request = $this->getRequest();
		$result  = [
            "status" => "fail",
            "msg"    => "Server side error. Cannot update."
		];

		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$postData = $request->getPost();
			$postData = (array)$postData;

            $form = $this->getForm();

			$form->setInputFilter(new GeneralFilter());
			$filter = $form->getInputFilter();
			$form->setInputFilter($filter);
            $form->setData($postData);

            $lockName = '';

            if (isset($postData['lock_id']) && $postData['lock_id']) {
                $lockInfo = $lockDao->fetchOne(['id' => (int)$postData['lock_id']]);

                if ($lockInfo && (int)$lockInfo->isPhysical()) {
                    $lockName = $lockInfo->getName();
                }
            }

            if ($form->isValid()) {
                $data = $form->getData();

                $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');

                //check parking lot name uniqueness
                if ($parkingGeneralService->checkParkingLotExistence($data['name'], $this->parkingLotId)) {
                    return new JsonModel([
                        "status" => "error",
                        "msg"    => 'Parking lot with this name already exists',
                    ]);
                }

				$isNew = false;
                if (!$this->parkingLotId) {
					$isNew = true;
                }

                $parkingLotId = $parkingGeneralService->saveParkingLot($data, $this->parkingLotId);

				if ($isNew) {
					$textlineDao = $this->getServiceLocator()->get('dao_textline_group');
					$insertData = [
						'entity_id'     => $parkingLotId,
						'type'          => Translation::PRODUCT_TYPE_PARKING,
						'entity_type'   => Translation::PRODUCT_TEXTLINE_TYPE_PARKING_LOTS,
						'en'            => '',
						'en_html_clean' => '',
					];

					$textlineId   = $textlineDao->save($insertData);
					$data['direction_textline_id'] = $textlineId;
					$parkingLotId = $parkingGeneralService->saveParkingLot($data, $parkingLotId);
				}

				$url = '';

				if (!$this->parkingLotId) {
					$router = $this->getEvent()->getRouter();
					$url    = $router->assemble(['parking_lot_id' => $parkingLotId], ['name' => 'parking/general']);
					Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
				}

                return new JsonModel([
                    "status" => "success",
                    "msg" => "Successfully updated.",
                    "url" => $url,
                ]);
            } else {
                $messages = '';
                $errors = $form->getMessages();
                foreach ($errors as $key => $row) {
                    if (!empty($message)) {
                        $messages .= ucfirst($key) . ' ';
                        $messagesSub = '';
                        foreach ($row as $subRow) {
                            $messagesSub .= $subRow;
                        }
                        $messages .= $messagesSub . '<br>';
                    }
                }
                $result['status'] = 'error';

                if ($messages == '') {
                    $messages = "<b>" . $lockName . "</b> lock is set as physical and already assigned to other entity.";
                }
                $result['msg'] = $messages;
            }
		}

		return new JsonModel($result);
	}

    public function changeStatusAction()
    {
		/**
         * @var \DDD\Service\Parking\General $parkingGeneralService
         */
        $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');
        $status = $this->params()->fromPost('status', -1);

        if ($status !== -1) {
            $result = $parkingGeneralService->changeStatus($this->parkingLotId, $status);

            if ($status) {
                $successText = TextConstants::SUCCESS_ACTIVATE;
            } else {
                $successText = TextConstants::SUCCESS_DEACTIVATE;
                $parkingGeneralService->removeParkingLotUsages($this->parkingLotId);
            }

            if ($result) {
                Helper::setFlashMessage(['success' => $successText]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        return new JsonModel([1]);
    }

    public function uploadParkingPermitAction()
    {
        $form = new ParkingUpload('parking-permit');
        $request = $this->getRequest();

        $file = [];
        $fileName = '';

        if ($request->isPost()) {
            $form->setData(array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            ));

            if ($form->isValid()) {
                $file = $form->getData()['file'];
                $fileName = pathinfo($file['tmp_name'], PATHINFO_BASENAME);
            }
        }

        if (count($file)) {
            return new JsonModel([
                'status' => 'success',
                'msg' => 'File successfully uploaded.',
                'tmpName' => $fileName
            ]);
        } else {
            return new JsonModel([
                'status' => 'error',
                'msg' => 'Failed to upload the file.'
            ]);
        }
    }
}
