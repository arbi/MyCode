<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Apartment\Form\CubilisConnection;
use DDD\Dao\Apartment\Details;
use DDD\Service\Apartment\General;
use DDD\Service\Apartment\OTADistribution;
use DDD\Service\Apartment\Rate;
use Library\ChannelManager\ChannelManager;
use Library\ChannelManager\CivilResponder;
use Library\ChannelManager\Testing\ConnectionTest;
use Library\Constants\Constants;
use Library\Constants\TextConstants;
use Library\OTACrawler\OTACrawler;
use Library\OTACrawler\Product\Apartment;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Library\Constants\Roles;
use Library\Constants\Objects;

class ChannelConnection extends ApartmentBaseController {

	public function indexAction() {
		/**
		 * @var \DDD\Service\Cubilis\Connection $cubilisConnectionService
		 * @var General $apartmentGeneralService
		 */
		$cubilisConnectionService = $this->getServiceLocator()->get('service_cubilis_connection');
		$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
		$cubilisDetails = $apartmentGeneralService->getCubilisDetailsAsArray($this->apartmentId);

		$form = new CubilisConnection($this->url()->fromRoute('apartment/channel-connection/save', ['apartment_id' => $this->apartmentId]));
		$form->prepare();
		$form->populateValues($cubilisDetails);

        $formTemplate  = 'form-templates/cubilis-connection';
        $viewModelForm = new ViewModel();
		$viewModelForm->setVariables([
			'form' => $form
		]);
		$viewModelForm->setTemplate($formTemplate);

		$rates = $apartmentGeneralService->getRoomRates($this->apartmentId);
		$rateConnections = $cubilisConnectionService->getCubilisTypes($this->apartmentId, $rates);
		$urlLinkRates = $this->url()->fromRoute('apartment/channel-connection/link', ['apartment_id' => $this->apartmentId]);

        $apartmentOTAService        = $this->getServiceLocator()->get('service_apartment_ota_distribution');
        $apartmentOTAList           = $apartmentOTAService->getOTAList($this->apartmentId);
        $partnerList                = $apartmentOTAService->getPartnerList();
        $hasApartmentConnectionRole = false;
        $auth                       = $this->getServiceLocator()->get('library_backoffice_auth');

        if ($auth->hasRole(Roles::ROLE_APARTMENT_CONNECTION)) {
            $hasApartmentConnectionRole = true;
        }

		$viewModel = new ViewModel();
		$viewModel->setVariables([
            'apartmentId'        => $this->apartmentId,
            'apartmentStatus'    => $this->apartmentStatus,
            'rateConnections'    => $rateConnections,
            'rates'              => $rates,
            'urlLinkRates'       => $urlLinkRates,
            'cubilisDetails'     => $cubilisDetails,
            'apartmentOTAList'   => $apartmentOTAList,
            'partnerList'        => $partnerList,
            'OTAStatus'          => Objects::getOTADistributionStatusList(),
            'isCubilisConnecter' => $hasApartmentConnectionRole
		]);

		// child view to render form
		$viewModel->addChild($viewModelForm, 'formOutput');
		$viewModel->setTemplate('apartment/channel-connection/index');

		return $viewModel;
	}

	public function saveAction()
    {
		/**
		 * @var Request $request
		 */
		$request = $this->getRequest();
		$result  = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$apartmentDetailsDao = new Details($this->getServiceLocator());

				$duplicateApartelCubilisInfo = $apartmentDetailsDao->checkDuplicateCubilisInfo(
					$this->apartmentId,
					$request->getPost('cubilis_id'),
					$request->getPost('cubilis_username'),
					$request->getPost('cubilis_password')
				);
				$apartmentName = [];
				if ($duplicateApartelCubilisInfo->count()) {
					foreach ($duplicateApartelCubilisInfo as $row) {
						array_push($apartmentName, $row['name']);
					}
					$apartmentNames = implode(',', $apartmentName);

					$text = sprintf(TextConstants::DUPLICATE_CUBILIS_CONNECTION, $apartmentNames);
					throw new \Exception($text);
				}

				$apartmentDetailsDao->updateCubilisDetails(
					$this->apartmentId,
					$request->getPost('cubilis_id'),
					$request->getPost('cubilis_username'),
					$request->getPost('cubilis_password')
				);

				if ($request->getPost('prepare', 0)) {
					$apartmentDetailsDao->connectToCubilis(
						$this->apartmentId, 1
					);
				}

				if ($request->getPost('rollback', 0)) {
					$apartmentDetailsDao->connectToCubilis(
						$this->apartmentId, 0
					);
				}

				$successMessage = TextConstants::SUCCESS_UPDATE_CUBILIS_DATA;
				Helper::setFlashMessage(['success' => $successMessage]);
				$result = [
					'status' => 'success',
					'msg' => $successMessage,
				];
			} else {
				throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
			}
		} catch (\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return new JsonModel($result);
	}

	public function connectAction()
    {
		/**
		 * @var Request $request
		 */
		$request = $this->getRequest();
		$result  = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$details = $request->getPost('data');
				$apartmentDetailsDao = new Details($this->getServiceLocator());
				$apartmentDetailsDao->connectToCubilis(
					$this->apartmentId,
					(int)$request->getPost('connect')
				);

				if ((int)$request->getPost('connect') && is_array($details) && count($details)) {
					$apartmentDetailsDao->updateCubilisDetails(
						$this->apartmentId,
						$details['cubilis_id'],
						$details['cubilis_username'],
						$details['cubilis_password']
					);
				}

				$successMessage = (int)$request->getPost('connect') ? TextConstants::SUCCESS_CONNECTED_TO_CUBILIS :  TextConstants::SUCCESS_DISCONNECTED_FROM_CUBILIS;

				Helper::setFlashMessage(['success' => $successMessage]);

				$result = [
                    'status' => 'success',
                    'msg'    => $successMessage,
				];

			} else {
				throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
			}
		} catch (\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return new JsonModel($result);
	}

	public function testPullReservationsAction()
    {
		/** @var $request Request */
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => TextConstants::ERROR,
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$connectionTest = new ConnectionTest($this->getServiceLocator());
				$result = $connectionTest->testPullReservation($this->apartmentId);
			} else {
				throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
			}
		} catch (\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return new JsonModel($result);
	}

	public function testUpdateAvailabilityAction()
    {
		/** @var $request Request */
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => TextConstants::ERROR,
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$connectionTest = new ConnectionTest($this->getServiceLocator());
				$result = $connectionTest->testUpdateAvailability($this->apartmentId);
			} else {
				throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
			}
		} catch (\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return new JsonModel($result);
	}

	public function testFetchListAction()
    {
		/** @var $request Request */
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => TextConstants::ERROR,
		];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$connectionTest = new ConnectionTest($this->getServiceLocator());
				$result = $connectionTest->testFetchList($this->apartmentId);
			} else {
				throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
			}
		} catch (\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return new JsonModel($result);

	}

	public function linkAction()
    {
		/**
		 * @var General $apartmentGeneralService
		 * @var Request $request
         * @var \DDD\Dao\Apartment\Rate  $rateDao
		 */
        $request = $this->getRequest();
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $rateDao = $this->getServiceLocator()->get('dao_apartment_rate');

		try {
			if ($request->isPost()) {
				$rates = $request->getPost('rate_links');
				$roomId = null;
				$requestParams = [
					'product_room' => [],
					'product_rates' => [],
				];

				if (count($rates)) {
					foreach ($rates as $cubilisRateId => $rateId) {
						// If ones value equals 0 that one cannot be linked with cubilis
						if ($rateId) {
                            $rateDomain = $rateDao->getRoomByRateId($rateId);
							if (is_null($roomId)) {
								$roomId = $rateDomain->getRoomId();
							}

							$requestParams['product_rates'][] = [
                                'rate_id'         => $rateId,
                                'cubilis_rate_id' => $cubilisRateId,
                                'rate_active'     => (int)$rateDomain->getRateActive()
							];
						}
					}

					$requestParams['product_room'] = [
						'room_id' => $roomId,
						'cubilis_room_id' => $request->getPost('room_id'),
					];
				} else {
					throw new \Exception('No rates selected.');
				}

                // link room rates
                $linkRoomRates = $apartmentGeneralService->linkRoomRate($requestParams, $this->apartmentId);
                Helper::setFlashMessage([$linkRoomRates['status'] => $linkRoomRates['msg']]);

			} else {
				throw new \Exception('Bad request.');
			}
		} catch (\Exception $ex) {
			Helper::setFlashMessage(['error' => $ex->getMessage()]);
		}

		return $this->redirect()->toRoute('apartment/channel-connection', [
			"apartment_id" => $this->apartmentId
		], [], true);
	}

    public function ajaxSaveOtaAction()
    {
        /**
         * @var Request $request
         * @var OTADistribution $service
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_ADD,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
               $data = $request->getPost();
               $service  = $this->getServiceLocator()->get('service_apartment_ota_distribution');
               $otaId = $service->saveOTA($data, $this->apartmentId);

               if ($otaId) {

                   try {
                       $apartment = new Apartment($this->apartmentId);
                       $crawler = new OTACrawler($apartment, [$otaId]);
                       $crawler->setServiceLocator($this->getServiceLocator());
                       $crawler->update();
                   } catch (\Exception $ex) {
                       $parserError = ' but with Parser Error';
                   }

                   Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD . (isset($parserError) ? ' but with Parser Error' : '')]);
               }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::ERROR;
        }

        return new JsonModel($result);
    }

    public function removeOtaAction()
    {
        $id         = (int)$this->params()->fromRoute('ota_id', 0);
        $service    = $this->getServiceLocator ()->get('service_apartment_ota_distribution');
        $removeable = $service->removeOTA($id);

        if ($removeable) {
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        $this->redirect()->toRoute('apartment/channel-connection', ['apartment_id' => $this->apartmentId]);
	}

    public function ajaxCheckOtaConnectionAction()
    {
        /**
         * @var Request $request
         */
        $request = $this->getRequest();
        $apartmentId = $this->params()->fromRoute('apartment_id');
        $otaId = $this->params()->fromRoute('ota_id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $apartment = new Apartment($apartmentId);
                $crawler = new OTACrawler($apartment, [$otaId]);
                $crawler->setServiceLocator($this->getServiceLocator());
                $crawler->update();

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $result = [
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_UPDATE,
                ];
            } catch (\Exception $ex) {
                $result['msg'] = $ex->getMessage();
            }
        }

        return new JsonModel($result);
    }
}
