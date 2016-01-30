<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Apartment\Form\General as GeneralForm;
use DDD\Dao\ApartmentGroup\ApartmentGroupItems;
use DDD\Service\Translation;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use Library\Constants\Objects;
use Library\Utility\Helper;
use DDD\Dao\Booking\Booking;
use Zend\View\Model\ViewModel;

class General extends ApartmentBaseController
{
    /**
     * @return \Zend\View\Model\ViewModel
     */
	public function indexAction()
    {
		/**
		 * @var \DDD\Service\Apartment\General $apartmentGeneralService
		 */
		$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $form = $this->getForm();
        $form->prepare();
        $generalInfo = false;

        if ($this->apartmentId) {
            $generalInfo = $apartmentGeneralService->getApartmentGeneral($this->apartmentId);

            $form->populateValues([
                'id'                           => $this->apartmentId,
                'apartment_name'               => $generalInfo['name'],
                'status'                       => $generalInfo['status'],

                'room_count'                   => $generalInfo['room_count'],
                'square_meters'                => $generalInfo['square_meters'],
                'max_capacity'                 => $generalInfo['max_capacity'],
                'bedrooms'                     => $generalInfo['bedroom_count'],
                'bathrooms'                    => $generalInfo['bathroom_count'],

                'chekin_time'                  => date('H:i', strtotime($generalInfo['check_in'])),
                'chekout_time'                 => date('H:i', strtotime($generalInfo['check_out'])),

                'general_description_textline' => $generalInfo['general_descr'],
                'general_description'          => $generalInfo['general_description'],
            ]);
        } else {
            // Pre-filled default values when adding apartment
            $form->populateValues([
                'chekin_time'                  => '15:00',
                'chekout_time'                 => '11:00',
            ]);
        }

		// passing form and map to the view
		$viewModelForm = new ViewModel();
		$viewModelForm->setVariables([
			'form'         => $form,
			'date_created' => isset($generalInfo['create_date']) ? $generalInfo['create_date'] : '',
            'apartmentStatus' => $this->apartmentStatus,
		]);
		$viewModelForm->setTemplate('form-templates/general');

		$viewModel = new ViewModel();
		$viewModel->setVariables([
            'apartment'       => $generalInfo,
			'apartmentId'     => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus
		]);

		// child view to render form
		$viewModel->addChild($viewModelForm, 'formOutput');
		$viewModel->setTemplate('apartment/general/index');

		return $viewModel;
	}

    /**
     * @return GeneralForm
     */
    private function getForm()
    {
		/* @var $apartmentGroupService \DDD\Service\ApartmentGroup */
		$apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

        // building options
		$buildings = $apartmentGroupService->getBuildingsListForSelect();
		$buildingOptions = ['-- Choose Building --'];

		foreach ($buildings as $building) {
			$buildingOptions[$building['id']] = $building['name'];
		}

		$preparedData['buildingOptions'] = $buildingOptions;

        return new GeneralForm($this->apartmentId, 'apartment_general', $preparedData);
    }

	/**
	 *
	 * @return \Zend\View\Model\JsonModel
	 */
	public function saveAction()
    {
		/**
		 * @var \DDD\Service\Apartment\General $generalService
         * @var ApartmentGroupItems $apartmentGroupItemsDao
		 */
		$request = $this->getRequest();
		$result  = [
            'status' => 'error',
            'msg'    => 'Something went wrong. Cannot update.',
		];

		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$postData = $request->getPost();

            try {
                if (count($postData) && $this->apartmentStatus != Objects::PRODUCT_STATUS_DISABLED) {
                    $form = $this->getForm();

                    if ($postData->id <= 0 || !isset($postData->id)) {
                        $postData->status = Objects::PRODUCT_STATUS_SANDBOX;
                    }

                    if ($postData->id > 0) {
                        $form->getInputFilter()->remove('building_id');
                        $form->getInputFilter()->remove('building_section');
                    }

                    $form->setData($postData);
                    $form->prepare();

                    if ($form->isValid()) {
                        $data = $form->getData();
                        $generalService = $this->getServiceLocator()->get('service_apartment_general');

                        if ($data['status'] == Objects::PRODUCT_STATUS_LIVEANDSELLIG || $data['status'] == Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE) {
                            $isLiveAndSelling = $generalService->isLiveAndSelling($this->apartmentId);

                            if ($isLiveAndSelling) {
                                return new JsonModel([
                                    "status" => "error",
                                    "msg"    => $isLiveAndSelling,
                                ]);
                            }
                        } else if ($data['status'] == Objects::PRODUCT_STATUS_DISABLED) {
                            $generalService->disableApartment($this->apartmentId);
                        }

                        //check apartment name
                        if (!$generalService->checkApartmentName($data, $this->apartmentId)) {
                            return new JsonModel([
                                "status" => "error",
                                "msg"    => TextConstants::APARTMENT_NAME_ALREADY_EXIST,
                            ]);
                        }

                        if ($postData->id > 0 && isset($data['building_id'])) {
                            unset($data['building_id']);
                        }

                        $apartmentId = $generalService->saveApartmentGeneral($this->apartmentId, $data);
                        $url = '';

                        if (!$this->apartmentId) {
                            $router = $this->getEvent()->getRouter();
                            $url    = $router->assemble(['apartment_id' => $apartmentId], ['name' => 'apartment/general']);
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                        }

                        if ($postData->id == 0) {
                            $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
                            $apartmentGroupItemsDao->insert([
                                'apartment_group_id' => $data['building_id'],
                                'apartment_id'       => $apartmentId,
                            ]);
                            // set texline
                            $texlineApartmentDao = $this->getServiceLocator()->get('dao_textline_apartment');
                            $texlineApartmentDao->insert([
                                'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_USAGE,
								'entity_id'   => $apartmentId,
                                'type'        => Translation::PRODUCT_TYPE_APARTMENT,
                                'en'          => '',
                            ]);
                        }

                        return new JsonModel([
                            "status" => "success",
                            "msg"    => "Successfully updated.",
                            "url"    => $url,
                        ]);
                    } else {
                        $messages = '';
                        $errors   = $form->getMessages();

                        foreach ($errors as $key => $row) {
                            if (!empty($row)) {
                                $messages .= ucfirst($key) . ' ';
                                $messages_sub = '';

                                foreach ($row as $keyer => $rower) {
                                    $messages_sub .= $rower;
                                }

                                $messages .= $messages_sub . '<br>';
                            }
                        }

                        $result['status'] = 'error';
                        $result['msg'] = $messages;
                    }
                }
            } catch (\Exception $ex) {
                // do nothing
                $result['status'] = 'error';
                $result['msg'] = $ex->getMessage();
            }
		}

		return new JsonModel($result);
	}

    public function checkDisablePossibilityAction()
    {
        $bookingDao = new Booking($this->getServiceLocator(), 'ArrayObject');

        $currentReservation = $bookingDao->getCurrentReservationByAcc($this->apartmentId, date('Y-m-d'));
        $nextReservation = false;

        if (!$currentReservation) {
            $nextReservation = $bookingDao->getNextReservationByAcc($this->apartmentId, date('Y-m-d'));
        }

        return new JsonModel(['isPossible' => (!$currentReservation && !$nextReservation)]);
    }
}
