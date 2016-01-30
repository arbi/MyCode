<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Apartment\Form\Furniture;
use Apartment\Form\InputFilter\FurnitureFilter;
use Apartment\Form\Details as DetailsForm;
use Apartment\Form\InputFilter\DetailsFilter;
use DDD\Service\Apartment\Details as DetailsService;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Library\Utility\Debug;


class Details extends ApartmentBaseController
{
	public function indexAction()
    {
		/** @var \DDD\Service\Apartment\Details $apartmentDetailsService */
		$apartmentDetailsService = $this->getServiceLocator()->get('service_apartment_details');

        /** @var \DDD\Service\Apartment\Furniture $apartmentFurnitureService */
		$apartmentFurnitureService = $this->getServiceLocator()->get('service_apartment_furniture');

        /** @var \DDD\Service\Apartment\General $apartmentGeneralService */
		$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');

        /** @var \DDD\Service\Apartment\Amenities $apartmentAmenitiesService */
		$apartmentAmenitiesService = $this->getServiceLocator()->get('service_apartment_amenities');

        /** @var \DDD\Service\Apartment\AmenityItems $apartmentAmenityItemsService */
		$apartmentAmenityItemsService = $this->getServiceLocator()->get('service_apartment_amenity_items');

        /** @var \DDD\Service\Parking\General $parkingGeneralService */
        $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');

        /** @var \DDD\Service\Parking\Spot $parkingSpotService */
        $parkingSpotService = $this->getServiceLocator()->get('service_parking_spot');

        /** @var \DDD\Dao\Accommodation\Accommodations $accDao */
		$accDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');

		/** @var \DDD\Dao\Textline\Apartment $texlineApartmentDao */
		$texlineApartmentDao = $this->getServiceLocator()->get('dao_textline_apartment');

		/** @var \DDD\Dao\Apartment\Spots $apartmentSpotsDao */
		$apartmentSpotsDao = $this->getServiceLocator()->get('dao_apartment_spots');

        $formOptions = [];

        $details                             = $apartmentDetailsService->getApartmentDetails($this->apartmentId);
        $furnitureList                       = $apartmentFurnitureService->getApartmentFurnitureList($this->apartmentId);
        $furnitureTypes                      = $apartmentFurnitureService->getFurnitureTypes();
        $roomId                              = $apartmentGeneralService->getRoomID($this->apartmentId);
		$generalDetails                      = $apartmentGeneralService->getInfoForDetailsController($this->apartmentId);
		$lockId                              = $generalDetails['lock_id'];
		$formOptions['parkingLots']          = $parkingGeneralService->getParkingLotsForSelect($this->apartmentId);
        $apartmentAmenities                  = $apartmentAmenityItemsService->getApartmentAmenities($this->apartmentId);
        $amenitiesList                       = $apartmentAmenitiesService->getAmenitiesList();
        $accInfo                             = $accDao->getAccById($this->apartmentId);
        $accCurrency                         = $accInfo->getCurrencyCode();
        $facilities							 = $apartmentGeneralService->getBuildingFacilitiesByApartmentId($this->apartmentId);
        $moneyAccountId                      = isset($details['money_account_id']) ? $details['money_account_id'] : null;
        $lockApartmentUsageService           = $this->getServiceLocator()->get('service_lock_usages_apartment');
        $formOptions['freeLocks']            = $lockApartmentUsageService->getLockByUsage($this->apartmentId);
        $bankAccountDao                      = new \DDD\Dao\MoneyAccount\MoneyAccount($this->getServiceLocator());
        $formOptions['bankAccounts']         = $bankAccountDao->getMoneyAccountOptions($moneyAccountId);
		$lotId                               = $accInfo->getLotId();

        $parkingLotData = $parkingGeneralService->getParkingById($lotId);

        $spots = $parkingSpotService->getSpotsByBuilding($accInfo->getBuildingId());

        $formOptions['parkingSpots'] = ['' => '-- Choose Parking Spot --'];
        if ($spots->count()) {
            foreach ($spots as $spot) {
                $formOptions['parkingSpots'][$spot->getId()] = $spot->getLotName() . ': ' . $spot->getUnit();
            }
        }
		$apartmentSpots = $apartmentSpotsDao->getApartmentSpots($this->apartmentId);
        $apartmentParkingSpots = [];
        if ($apartmentSpots->count()) {
			foreach ($apartmentSpots as $apartmentSpot) {
				array_push($apartmentParkingSpots, $apartmentSpot['spot_id']);
			}
        }

		$form = new DetailsForm('apartment_details', $formOptions);
		$form->prepare();
		$details['lock_id'] = $lockId;
		$form->populateValues($details);

		// set form type and template
		$formTemplate = 'form-templates/details';
        $router = $this->getEvent()->getRouter();
        $editKiDirectTypeTextlineLink = $router->assemble([
			'controller' => 'translation',
			'action'     => 'editkidirectentry',
			'id'         => $this->apartmentId
        ], [
            'name' => 'backoffice/default'
        ]);

		// get apartment usage information
		$apartmentUsage = $texlineApartmentDao->getApartmentUsageByApartmentId($this->apartmentId);

		// get building usage information
		$apartmentBuildingUsage = $texlineApartmentDao->getApartmentBuildingUsageByApartmentId($this->apartmentId);

		// get building facilities information
		$apartmentBuildingFacility = $texlineApartmentDao->getApartmentBuildingFacilityByApartmentId($this->apartmentId);

		// get building policy information
		$apartmentBuildingPolicy = $texlineApartmentDao->getApartmentBuildingPolicyByApartmentId($this->apartmentId);

		$buildingDirectEntryTextline = $texlineApartmentDao->getBuildingDirectEntryTextline($this->apartmentId);

        // passing form and map to the view
		$viewModelForm = new \Zend\View\Model\ViewModel();
		$viewModelForm->setVariables([
			'form'                        				 => $form,
			'apartmentUsage'            				 => $apartmentUsage,
			'apartmentBuildingUsage'        			 => $apartmentBuildingUsage,
			'apartmentBuildingFacility'      			 => $apartmentBuildingFacility,
			'apartmentBuildingPolicy'        			 => $apartmentBuildingPolicy,
			'buildingDirectEntryTextline' 				 => $buildingDirectEntryTextline,
			'editKiDirectEntryTypeLink'   				 => $editKiDirectTypeTextlineLink,
			'furnitureList'               				 => $furnitureList,
			'amenitiesList'              			     => $amenitiesList,
			'apartmentAmenities'        			     => $apartmentAmenities,
			'furnitureTypes'               				 => $furnitureTypes,
			'apartmentId'                			     => $this->apartmentId,
			'apartmentStatus'              				 => $this->apartmentStatus,
			'roomId'                       				 => $roomId,
			'accCurrency'                  				 => $accCurrency,
			'facilities'								 => $facilities
		]);

		$viewModelForm->setTemplate($formTemplate);

		$viewModel = new \Zend\View\Model\ViewModel();
		$viewModel->setVariables([
			'apartmentId'               => $this->apartmentId,
            'apartmentStatus'           => $this->apartmentStatus,
            'apartmentParkingSpots'     => $apartmentParkingSpots,
            'parkingLotIsVirtualStatus' => ($parkingLotData) ? $parkingLotData->isVirtual() : false
        ]);

		// child view to render form
		$viewModel->addChild($viewModelForm, 'formOutput');
		$viewModel->setTemplate('apartment/details/index');

		return $viewModel;
	}

	public function saveAction()
    {
        /**
         * @var \DDD\Service\Apartment\Details $apartmentDetailsService
         */
        $request      = $this->getRequest();
        $lockService  = $this->getServiceLocator()->get('service_lock_general');

		$fail = array(
			"status" => "fail",
			"msg" => "Something went wrong. Cannot update."
		);

		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$postData = $request->getPost();
			if (count($postData)) {
				$form        = new DetailsForm();
                $inputFilter = new DetailsFilter();

				$form->setInputFilter($inputFilter->getInputFilter());
				$form->setData($postData);
				$form->prepare();

				if ($form->isValid()) {
					$data = $form->getData();
					$apartmentDetailsService = $this->getServiceLocator()->get('service_apartment_details');

                    $lockId = $data['lock_id'];
					unset($data['lock_id']);

                    if ($lockId) {
                        $isDuplicatePhysicalLock = $lockService->checkDuplicatePhysicalLock(
                            $this->apartmentId,
                            $lockId,
                            '\DDD\Dao\Accommodation\Accommodations'
                        );

                        if ($isDuplicatePhysicalLock['isDuplicate']) {
                            return new JsonModel(
                                [
                                    "status" => "error",
                                    "msg"    => "<b>" . $isDuplicatePhysicalLock['name'] . "</b> lock is set as physical and already assigned to other entity."
                                ]
                            );
                        }
                    }

					$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
					$apartmentGeneralService->saveLock($this->apartmentId, $lockId);
					$result = $apartmentDetailsService->saveApartmentDetails($this->apartmentId, $data);

					if (!$result) {
						return new JsonModel($fail);
					}

					return new JsonModel([
						"status" => "success",
						"msg" => "Successfully updated."
					]);
				}
			}
		}

		return new JsonModel($fail);
	}

	public function addFurnitureAction() {
		/**
		 * @var Request $request
		 */
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => 'Unable to add furniture. Please try again later.',
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$postData = $request->getPost();

				if (count($postData)) {
					/**
					 * @var \DDD\Service\Apartment\Furniture $apartmentFurnitureService
					 */
					$apartmentFurnitureService = $this->getServiceLocator()->get('service_apartment_furniture');
					$furnitureTypes = $apartmentFurnitureService->getFurnitureTypes();

					$form = new Furniture('apartment_furniture', $furnitureTypes);
					$form->setInputFilter(new FurnitureFilter());

					$form->setData($postData);
					$form->prepare();

					if ($form->isValid()) {
						$data = $form->getData();

						unset($data['save_button']);
						$data['furniture_type_id'] = $data['type'];
						unset($data['type']);

						$lastInsertId = $apartmentFurnitureService->addFurniture($data);

						$router = $this->getEvent()->getRouter();
						$furnitureDeleteLink = $router->assemble(['id' => $lastInsertId, 'apartment_id' => $this->apartmentId], ['name' => 'apartment/details/delete-furniture']);

						$result = [
							'status' => 'success',
							'msg' => 'Furniture was added successfully.',
							'data' => [
								'id' => $lastInsertId,
								'title' => $postData['title'],
								'count' => $postData['count'],
								'url' => $furnitureDeleteLink,
							],
						];
					} else {
						$messages = '';
						$errors = $form->getMessages();

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

						throw new \UnderflowException($messages);
					}
				} else {
					throw new \UnderflowException('Something Went Wrong.');
				}
			} else {
				throw new \UnderflowException('Bad Request.');
			}
		} catch (\UnderflowException $ex) {
			$result['msg'] = $ex->getMessage();
		} catch (\Exception $ex) {
			// do nothing
		}

		return new JsonModel($result);
	}

	public function deleteFurnitureAction() {
		/**
		 * @var Request $request
		 */
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => 'Unable to delete furniture. Please try again later.',
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$furnitureId = (int)$this->params()->fromRoute('id', 0);

				if ($furnitureId) {
					$apartmentFurnitureService = $this->getServiceLocator()->get('service_apartment_furniture');

					$result = $apartmentFurnitureService->removeFurniture($furnitureId);

					if ($result) {
						$result = [
							'status' => 'success',
							'msg' => 'Furniture was removed successfully.'
						];
					} else {
						throw new \UnderflowException('Problem during removing furniture.');
					}
				} else {
					throw new \UnderflowException('Problem during removing furniture.');
				}
			} else {
				throw new \UnderflowException('Bad Request.');
			}
		} catch (\UnderflowException $ex) {
			$result['msg'] = $ex->getMessage();
		} catch (\Exception $ex) {
			// do nothing
		}

		return new JsonModel($result);
	}

    public function getParkingSpotsAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            $parkingLotId = $request->getPost('parking_lot_id');

            /* @var $parkingGeneralService \DDD\Service\Parking\General */
            $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');

            /* @var $parkingLotData \DDD\Domain\Parking\General */
            $parkingLotData = $parkingGeneralService->getParkingById($parkingLotId);

            if ($parkingLotData->isVirtual()) {
                $result = [
                    'is_virtual' => true
                ];
            } else {
                /* @var $parkingSpotService \DDD\Service\Parking\Spot */
                $parkingSpotService = $this->getServiceLocator()->get('service_parking_spot');

                $parkingSpots = $parkingSpotService->getParkingSpots($parkingLotId);
                $result = [];
                foreach ($parkingSpots as $row) {
                    array_push($result, [
                        'id' => $row->getId(),
                        'unit' => $row->getUnit()
                    ]);
                }
            }

            return new JsonModel($result);
        } else {
            throw new \UnderflowException('Bad Request.');
        }
    }
}
