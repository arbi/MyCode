<?php

namespace Parking\Controller;

use Parking\Controller\Base as ParkingBaseController;
use Parking\Form\Spot as SpotForm;
use Zend\View\Model\JsonModel;
use Library\Utility\Helper;

class Spots extends ParkingBaseController
{
	public function indexAction()
    {
		/**
		 * @var \DDD\Service\Parking\Spot $parkingSpotService
         * @var \DDD\Dao\Parking\Spot $parkingSpotDao
         * @var \DDD\Service\Parking\General $parkingGeneralService
         */
        $parkingSpotService = $this->getServiceLocator()->get('service_parking_spot');
        $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');
        $parkingLot = $parkingGeneralService->getParkingById($this->parkingLotId);
        $spots = $parkingSpotService->getParkingSpots($this->parkingLotId);
        $filteredArray = [];
        foreach ($spots as $spot) {
            array_push($filteredArray,
                [
                    $spot->getUnit(),
                    $spot->getPrice() . ' ' . $parkingLot->getCurrency(),
                    '<span>'.$spot->getPermitId().'</span><div class="input-group" style="display:none;"><input type="text"class="form-control" value=""><label class="input-group-addon">Cancel</label></div>',
                    '<a href="/parking/'. $this->parkingLotId . '/spots/edit/'.$spot->getId().'" class="btn btn-xs btn-primary" data-html-content="Edit"></a>'.
                    '<a class="btn btn-success btn-xs save_parking_permit" style="display:none" data-spot-id="' . $spot->getId() . '" href="#">Save</a>'
                ]
                );
        }
        $viewModel = new \Zend\View\Model\ViewModel ();
        $viewModel->setVariables (
            [
                'parkingLotId'   => $this->parkingLotId,
                'aaData'         => json_encode($filteredArray),
            ]
        );
        return $viewModel;
    }

    public function editAction()
    {
        /**
         * @var \DDD\Service\Parking\Spot $parkingSpotService
         * @var \DDD\Service\Parking\General $parkingGeneralService
         */
        $parkingSpotService    = $this->getServiceLocator ()->get('service_parking_spot');
        $parkingGeneralService = $this->getServiceLocator()->get('service_parking_general');
        $spots                 = $parkingSpotService->getParkingSpots($this->parkingLotId);
        $parkingLot = $parkingGeneralService->getParkingById($this->parkingLotId);
        $selectedSpotId = $this->params()->fromRoute('spot_id', 0);
        $viewModel = new \Zend\View\Model\ViewModel();
        $form = new SpotForm($this->parkingLotId, 'parking-spot');
        if ($selectedSpotId) {
            $usages = $parkingSpotService->getUsages($selectedSpotId);
            $parkingSpotDao = $this->getServiceLocator()->get('dao_parking_spot');
            $selectedSpot = $parkingSpotDao->getParkingSpotById($selectedSpotId);
            if (!$selectedSpot) {
                Helper::setFlashMessage(['error' => 'Spot not found']);
                return $this->redirect()->toRoute('parking/spots', ['parking_lot_id' => $this->parkingLotId]);
            }
            $form->prepare();
            $form->populateValues([
                'id' => $selectedSpot->getId(),
                'lot_id' => $selectedSpot->getLotId(),
                'unit' => $selectedSpot->getUnit(),
                'price' => $selectedSpot->getPrice(),
                'permit_id' => $selectedSpot->getPermitId()
            ]);
            $viewModel->setVariables([
                'usages' => $usages
            ]);
        }
        $viewModel->setVariables ([
            'parkingLotId'    => $this->parkingLotId,
            'spots'           => $spots,
            'spotCount'       => count($spots),
            'form'            => $form,
            'selectedSpotId'  => $selectedSpotId,
            'parkingLot'      => $parkingLot
        ]);

        return $viewModel;
    }

    public function saveAction()
    {
        /**
         * @var \DDD\Service\Parking\Spot $parkingSpotService
         */
        $request = $this->getRequest();

        $result = [
            'status' => 'error',
            'msg' => 'Something went wrong. Cannot update.'
        ];

        try {
            if ($request->isPost()) {
                $postData = $request->getPost();

                if (count($postData)) {
                    $parkingSpotService = $this->getServiceLocator()->get('service_parking_spot');
                    $postArray = $postData->toArray();
                    $isUnitUniqueInLot = $parkingSpotService->isUnitUniqueInLot($postArray);
                    if (!$isUnitUniqueInLot) {
                        return new JsonModel([
                            'status' => 'error',
                            'msg'    => 'A spot with this Unit already exists in this lot'
                        ]);
                    }
                    $spotId = $parkingSpotService->saveSpot($postArray, $postArray['id']);
                    if ($postArray['id']) {
                        $spotId = $postArray['id'];
                    }

                    // "Edit" mode if id existed, "Add" mode if it was not
                    if (!$postArray['id']) {
                        $router = $this->getEvent()->getRouter();
                        $result = [
                            'status' => 'success',
                            'url' => $router->assemble(['parking_lot_id' => $this->parkingLotId, 'spot_id' => $spotId], ['name' => 'parking/spots'])
                        ];
                        $message = ['success' => 'Spot was successfully added.'];
                        Helper::setFlashMessage($message);
                    } else {
                        $result = ['status' => 'success', 'msg' => 'Spot was successfully updated.'];
                    }

                }
            }
        } catch (\Exception $ex) {
            // Do nothing
        }

        return new JsonModel($result);
    }

    public function deleteAction()
    {
        $spotId = (int) $this->params()->fromRoute('spot_id', 0);

        if (!$spotId) {
            Helper::setFlashMessage(['error' => 'Invalid spot id given.']);
        } else {
            /**
             * @var \DDD\Service\Parking\Spot $spotService
             */
            $spotService = $this->getServiceLocator()->get('service_parking_spot');
            $result      = $spotService->deleteSpot($spotId);
            if ($result) {
                Helper::setFlashMessage(['success' => 'Spot was removed successfully.']);
            } else {
                Helper::setFlashMessage(['error' => 'Cannot remove given spot.']);
            }
        }

        return $this->redirect()->toRoute('parking/spots', ['parking_lot_id' => $this->parkingLotId]);
    }

	public function ajaxCheckSpotIsUsedAction()
	{
		$request = $this->getRequest();
        $result  = [
			'status'          => 'success',
			'hasReservations' => 0,
			'links'			  => ''
		];

		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		$links      = '<p><b>This spot is assigned to the following reservations. Please replace it to be able to proceed.</b></p><div class="row">';

        try {
            if ($request->isXmlHttpRequest()) {
				$spotId       = (int)$request->getPost('spotId');
				$reservations = $bookingDao->getReservationByspotId($spotId);

				if ($reservations->count()) {
					foreach ($reservations as $reservation) {
						$link = $this->url()->fromRoute(
							'backoffice/default',
							['controller' => 'booking', 'action' => 'edit', 'id' => $reservation['res_number']],
							['fragment' => 'financial_details']
						);

						$links .= '<div class="col-sm-3"><li><a target="_blank" href="' . $link . '">' . $reservation['res_number'] . '</a></li></div>';
					}

					$links .=  '</div>';

					$result['links'] 		   = $links;
					$result['hasReservations'] = 1;
				}
			}
		} catch (\Exception $e) {

		}

		return new JsonModel($result);
	}

    public function savePermitIdAction()
    {
        /**
         * @var \DDD\Service\Parking\Spot $parkingSpotService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => 'Something went wrong. Cannot update.'
        ];
        try {
            if ($request->isPost()) {
                $postData = $request->getPost();

                if (count($postData)) {
                    $parkingSpotService = $this->getServiceLocator()->get('service_parking_spot');
                    $postArray = $postData->toArray();
                    $parkingSpotService->saveSpot($postArray, $postArray['id']);
                    $result = ['status' => 'success', 'msg' => 'Permit Id was successfully updated.'];
                }
            }
        } catch (\Exception $ex) {
            // Do nothing
        }
        return new JsonModel($result);
    }
}
