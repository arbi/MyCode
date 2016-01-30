<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Apartment\Form\Rate as RateForm;

use Library\Utility\Debug;
use Library\Constants\Objects;
use Library\Utility\Helper;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use DDD\Service\Apartment\Inventory;
use DDD\Service\Apartment\Rate as RateService;

class Rate extends ApartmentBaseController
{
	/**
	 * (non-PHPdoc)
	 *
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	public function indexAction()
    {
        if($this->apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            $this->redirect()->toRoute('apartment/general', ['apartment_id' => $this->apartmentId]);
        }

		/**
		 * @var \DDD\Service\Apartment\General $productGeneralService
		 * @var \DDD\Service\Apartment\Rate $rateService
		 * @var \DDD\Dao\Apartment\Rate $rateDao
		 */
        $rateService           = $this->getServiceLocator()->get('service_apartment_rate');
        $productGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $rateDao               = $this->getServiceLocator()->get('dao_apartment_rate');
        $rates                 = $rateService->getApartmentRates($this->apartmentId);

		// get selected rate ID
		$selectedRateID = $this->params ()->fromRoute ( 'rate_id', 0 );
		$maxCapacity    = $productGeneralService->getMaxCapacity($this->apartmentId);

		if ($selectedRateID) {
			$selectedRate = $rateService->getRateDetails ( $selectedRateID );
			$currency     = $productGeneralService->getCurrency($this->apartmentId);

			$isParent     = ($selectedRate['type'] == RateService::TYPE1) ? 1 : 0;
            // get parent rate price
            $parentPrices = $viewPriceData = [];
            if (!$isParent) {
                $parentPrices = $rateDao->getApartmentParentRatePrices($this->apartmentId);
                $weekPercent = $selectedRate['week_percent'];
                $weekendPercent = $selectedRate['weekend_percent'];
                $viewPriceData['week_price'] = $selectedRate['weekday_price'];
                $viewPriceData['is_week_minus'] = $weekPercent > 0 ? false : true;
                $viewPriceData['weekend_price'] = $selectedRate['weekend_price'];
                $viewPriceData['is_weekend_minus'] = $weekendPercent > 0 ? false : true;
                $selectedRate['weekday_price'] = abs($weekPercent);
                $selectedRate['weekend_price'] = abs($weekendPercent);

			}

			$data = [
				'currency'          => $currency,
				'rate_count'        => count($rates),
				'apartment_max_pax' => $maxCapacity,
				'is_parent'         => $isParent,
                'parentPrices'      => $parentPrices,
			];
			$selectedRate['open_next_month_availability'] = $productGeneralService->getOpenNextMonthAvailability($this->apartmentId);
			$form = new RateForm('apartment_rate', $data);
			$form->prepare();
			$form->populateValues( $selectedRate );

			// set form type and template
			$formTemplate = 'form-templates/rate';
			// passing form and map to the view
			$viewModelForm = new \Zend\View\Model\ViewModel();
			$viewModelForm->setVariables(
				[
					'form'     => $form,
					'isParent' => $isParent,
					'currency' => $currency,
					'rateId'   => $selectedRateID,
					'viewPriceData' => $viewPriceData,
				]
			);

			$viewModelForm->setTemplate( $formTemplate );

			$viewModel = new \Zend\View\Model\ViewModel ();
			$viewModel->setVariables(
				[
					'apartmentId'     => $this->apartmentId,
					'apartmentStatus' => $this->apartmentStatus,
					'rates'           => $rates,
					'selectedRateID'  => $selectedRateID,
					'maxCapacity'     => $maxCapacity,
				]
			);

			// child view to render form
			$viewModel->addChild ($viewModelForm, 'formOutput');
			$viewModel->setTemplate ('apartment/rate/index');

			return $viewModel;
		} else {
			$viewModel = new \Zend\View\Model\ViewModel ();
			$viewModel->setVariables (
				[
					'apartmentId'    => $this->apartmentId,
					'rates'          => $rates,
					'selectedRateID' => $selectedRateID,
					'maxCapacity'    => $maxCapacity,
				]
			);

			return $viewModel;
		}
	}

	public function addAction()
    {
        if($this->apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            $this->redirect()->toRoute('apartment/general', ['apartment_id' => $this->apartmentId]);
        }

		/**
		 * @var \DDD\Service\Apartment\Rate $rateService
		 * @var \DDD\Service\Apartment\General $productGeneralService
         * @var \DDD\Dao\Apartment\Rate $rateDao
		 */
		$rateService           = $this->getServiceLocator ()->get ( 'service_apartment_rate' );
		$productGeneralService = $this->getServiceLocator ()->get ( 'service_apartment_general' );
        $rateDao               = $this->getServiceLocator ()->get ( 'dao_apartment_rate' );
		$rates                 = $rateService->getApartmentRates ( $this->apartmentId );
		$currency              = $productGeneralService->getCurrency($this->apartmentId);
		$maxCapacity           = $productGeneralService->getMaxCapacity($this->apartmentId);

        // get parent rate price
        $parentPrices = [];
        if (count($rates)) {
            $parentPrices = $rateDao->getApartmentParentRatePrices($this->apartmentId);
        }

		$data = [
			'currency'          => $currency,
			'rate_count'        => count($rates),
			'apartment_max_pax' => $maxCapacity,
            'parentPrices'      => $parentPrices,
		];

        $isParent = $data['is_parent'] = count($rates) ? 0 : 1;
        $form     = new RateForm ('apartment_rate', $data);
		$form->get('save_button')->setValue('Add');

		// set form type and template
		$formTemplate = 'form-templates/rate';

		// passing form and map to the view
		$viewModelForm = new \Zend\View\Model\ViewModel ();
		$viewModelForm->setVariables ([
            'form'           => $form,
            'currency'       => $currency,
            'selectedRateID' => 0,
            'isParent'       => $isParent,
		]);
		$viewModelForm->setTemplate ( $formTemplate );

		$viewModel = new \Zend\View\Model\ViewModel ();
		$viewModel->setVariables ([
			'apartmentId'     => $this->apartmentId,
			'apartmentStatus' => $this->apartmentStatus,
			'rates'           => $rates,
			'selectedRateID'  => 0,
			'maxCapacity'     => $maxCapacity,
		]);

		// child view to render form
		$viewModel->addChild ( $viewModelForm, 'formOutput' );
		$viewModel->setTemplate ( 'apartment/rate/add' );

		return $viewModel;
	}

	public function saveAction()
    {
        if($this->apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            $this->redirect()->toRoute('apartment/general', ['apartment_id' => $this->apartmentId]);
        }

		$request = $this->getRequest();

		$result = [
			'status' => 'error',
			'msg' => 'Something went wrong. Cannot update.'
		];

		if ($request->isPost()) {
			$postData = $request->getPost();

			if (count($postData)) {
				/**
				 * @var \DDD\Service\Apartment\Rate $rateService
				 *
				 */
				$rateService = $this->getServiceLocator()->get('service_apartment_rate');
				$postDataAsArray = $postData->toArray();
				if ($postDataAsArray['min_stay'] > $postDataAsArray['max_stay']) {
					$result = [
						'status' => 'error',
						'msg'    => 'Invalid number for stay days.'
					];

					return $result;
				}

				if (   (($postDataAsArray['release_window_start'] != 0)
					&& ($postDataAsArray['release_window_end'] != 0))
					&& ($postDataAsArray['release_window_start']
					> $postDataAsArray['release_window_end'])
				) {
					$result = [
						'status' => 'error',
						'msg'    => 'Invalid number for release window.'
					];

					return $result;
				}

				if (isset($postDataAsArray['open_next_month_availability']) && $postDataAsArray['open_next_month_availability'] !== false) {
					$productGeneralService = $this->getServiceLocator()->get ( 'service_apartment_general' );
					$productGeneralService->saveOpenNextMonthAvailability($this->apartmentId, $postDataAsArray['open_next_month_availability']);
					unset($postDataAsArray['open_next_month_availability']);
				}

				$response = $rateService->saveRate($postDataAsArray, $this->apartmentId);

                $result = [
                    'status'      => $response['status'],
                    'msg'         => $response['msg'],
                    'apartmentId' => $this->apartmentId,
                    'rateId'      => $response['rate_id']
                ];

                $message = [$response['status'] => $response['msg']];
				if (!$postDataAsArray['active']) {
					$message['warning'] = 'Please note that this rate is inactive.';
				}

				Helper::setFlashMessage($message);
			}
		}

		return new JsonModel($result);
	}

	public function deleteAction() {
		$rateID = (int) $this->params()->fromRoute('rate_id', 0);
        $apartmentId = (int) $this->params()->fromRoute('apartment_id', 0);

		if (!$rateID) {
			Helper::setFlashMessage(['error' => 'Invalid rate ID given.']);
		} else {
			/**
			 * @var \DDD\Service\Apartment\Rate $rateService
			 */
			$rateService  = $this->getServiceLocator()->get ( 'service_apartment_rate' );
			$apartmentDetailsService = $this->getServiceLocator()->get('service_apartment_details');
			$selectedRate = $rateService->getRateDetails ( $rateID );
            $isRateConnectedToCubilis =  $rateService->isRateConnectedToCubilis($rateID);
			$isApartmentConnectedToCubilis = $apartmentDetailsService->isApartmentConnectedToCubilis($apartmentId);
			$isParent     = ($selectedRate['type'] == RateService::TYPE1) ? 1 : 0;
            if (!$isRateConnectedToCubilis || !$isApartmentConnectedToCubilis) {
				if (!$isParent) {
					$result = $rateService->deleteRate($rateID);

					if ($result) {
						Helper::setFlashMessage(['success' => 'Rate was removed successfully.']);
					} else {
						Helper::setFlashMessage(['error' => 'Cannot remove given rate.']);
					}
				} else {
					Helper::setFlashMessage(['error' => 'You can\'t remove Standard Rate.']);
				}
			}
			else {
				Helper::setFlashMessage(['error' => 'The Rate is connected to cubilis. Please disconnect it first']);
			}

		}

		return $this->redirect()->toRoute('apartment/rate/index', ['apartment_id' => $this->apartmentId]);
	}

    public function ajaxCheckNameAction()
    {
        $rateService = $this->getServiceLocator()->get ( 'service_apartment_rate' );
        $request     = $this->getRequest();
        $response    = $this->getResponse();

        $response->setStatusCode(200);

        try{
            if ($request->isXmlHttpRequest()) {
                $rateName = strip_tags(trim($request->getPost('name')));
                $rateId   = (int)$request->getPost('id');
                $result = $rateService->checkDuplicateRateName($this->apartmentId, $rateId, $rateName);

                if (!$result) {
                    $response->setContent("true");
                } else {
                    $response->setContent("false");
                }
            }
        } catch (\Exception $e) {
            $response->setContent("false");
        }
        return $response;
    }
}
