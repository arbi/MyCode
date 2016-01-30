<?php

namespace Backoffice\Controller;

use CreditCard\Service\Card;
use CreditCard\Service\Retrieve;
use DDD\Domain\Booking\BookingExportRow;
use DDD\Service\Booking;
use DDD\Service\GeoliteCountry;
use DDD\Service\Parking\Spot\Inventory;
use DDD\Service\Task as TaskService;
use DDD\Service\Lock\General as LockService;

use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use Library\Constants\Constants;
use Library\Utility\Currency;
use Library\Utility\Helper;
use Library\Utility\CsvGenerator;
use Library\Constants\TextConstants;
use Library\Constants\Roles;
use Library\Finance\CreditCard\CreditCard;

use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use FileManager\Constant\DirectoryStructure;

use Backoffice\Form\SearchReservationForm;
use Backoffice\Form\Booking as BookingForm;
use Backoffice\Form\BookingDocumentsForm;
use Backoffice\Form\InputFilter\BookingFilter as BookingFilter;

class BookingController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var Booking\BookingManagement $bookingManagementService
         */
        $bookingManagementService = $this->getServiceLocator()->get('service_booking_management');

	    $searchFormResources = $bookingManagementService->prepareSearchFormResources();
	    $form = new SearchReservationForm('search-reservation', $searchFormResources);

	    $doSearchOnLoad = 0;

	    if ($apartmentId = $this->params()->fromRoute('apartment')) {
	    	$form->get('product_id')->setValue($apartmentId);
	    	$apartmentFullAddressDomain = $this->getServiceLocator()->get('service_accommodations')->getAppartmentFullAddressByID($apartmentId);
	    	$apartmentFullAddress = $apartmentFullAddressDomain->getFullAddress();
	    	$form->get('product')->setValue($apartmentFullAddress);
	    	$doSearchOnLoad = 1;
	    }

        if ($email = $this->params()->fromRoute('email')) {
            $form->get('guest_email')->setValue($email);

            if ($status = $this->params()->fromRoute('status')) {
                $form->get('status')->setValue(1);
            } else {
                $form->get('status')->setValue(Constants::NOT_BOOKED_STATUS);
            }

            $doSearchOnLoad = 1;
        }

        if ($groupId = $this->params()->fromQuery('group')) {
            /**
             * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao
             */
            $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
            $apartmentGroupData = $apartmentGroupDao->getRowById($groupId);

            if ($apartmentGroupData) {
                $form->get('group_id')->setValue($groupId);
                $form->get('group')->setValue($apartmentGroupData->getName());

                $doSearchOnLoad = 1;
            }
        }

        $queryParams = $this->params()->fromQuery();

        if (count($queryParams)) {
            $form = $this->fillSearchFormByParameters($form, $queryParams);
        }

        $isDevTest = false;
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        if ($auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING)) {
            $isDevTest = true;
        }

	    //Source code
	    $viewModelForm = new ViewModel();
	    $viewModelForm->setVariables([
            'form'  => $form,
            'isDevTest' => $isDevTest
        ]);
	    $viewModelForm->setTemplate('form-templates/search-reservation');

        $viewModel = new ViewModel([
            'doSearchOnLoad' => $doSearchOnLoad
        ]);

    	$viewModel->addChild($viewModelForm, 'formOutput');
    	$viewModel->setTemplate('backoffice/booking/index');

    	return $viewModel;
    }

    private function fillSearchFormByParameters($form, Array $params = [])
    {
        if (count($params)) {
            foreach ($params as $paramName => $paramValue) {
                if (!empty($paramValue) || is_numeric($paramValue)) {
                    $form->get($paramName)->setValue($paramValue);
                }
            }
        }

        return $form;
    }

    /**
     * Get reservations json to use as source for datatable, filtered by params came from datatable
     */
    public function getReservationsJsonAction()
    {
    	/**
         * @var \DDD\Service\Booking\Charge $bookingChargeService
         * @var \DDD\Service\Booking\BookingManagement $bookingManagementService
         * @var \DDD\Domain\Booking\BookingTableRow $reservation
         */
        $bookingChargeService     = $this->getServiceLocator()->get('service_booking_charge');
        $bookingManagementService = $this->getServiceLocator()->get('service_booking_management');

    	// get query parameters
    	$queryParams    = $this->params()->fromQuery();
    	$iDisplayStart  = $queryParams["iDisplayStart"];
    	$iDisplayLength = $queryParams["iDisplayLength"];
    	$sortCol        = (int)$queryParams['iSortCol_0'];
    	$sortDir        = $queryParams['sSortDir_0'];

    	// get reservations data
    	$result = $bookingManagementService->getReservationsBasicInfo(
            $iDisplayStart,
            $iDisplayLength,
            $queryParams,
            $sortCol,
            $sortDir
        );

        $reservations  = $result['result'];
        $count         = $result['total'];
    	$filteredArray = [];

    	foreach ($reservations as $reservation) {
            $sumCharged = $bookingChargeService->getChargedTotal($reservation->getId());
            $firstField = $reservation->getReservationNumber() . ' ' . $reservation->isLocked() . ' ' . $reservation->getAffiliateID();
    		$filteredArray[] = [
                $firstField,
			    Booking::$bookingStatuses[$reservation->getStatus()],
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getReservationDate())),
			    $reservation->getProductName(),
			    $reservation->getGuestFullName() . ' (' . $reservation->getOccupancy() . ')',
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getArrivalDate())),
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getDepartureDate())),
			    $reservation->getRateName(),
			    $sumCharged['acc'] . ' ' . $reservation->getGuestCurrencyCode(),
			    $reservation->getGuestBalance(),
			    $reservation->getReservationNumber(),
		    ];
    	}

    	return new JsonModel([
            'iTotalRecords'        => $count,
            'iTotalDisplayRecords' => $count,
            'iDisplayStart'        => $iDisplayStart,
            'iDisplayLength'       => (int)$iDisplayLength,
            "aaData"               => $filteredArray,
        ]);
    }

    /**
     * validate download amount volume for csv export
     */
    public function ajaxValidateDownloadCsvAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingManagement $bookingManagementService
         */
        $bookingManagementService = $this->getServiceLocator()->get('service_booking_management');
        $request = $this->getRequest();
        $result = new JsonModel([
            'status' => 'error',
            'msg'    => TextConstants::DOWNLOAD_ERROR_CSV,
        ]);

        try {
            if ($request->isXmlHttpRequest()) {
                // getting query parameters
                $queryParams = $this->params()->fromQuery();
                // first checking about download volume possibility
                $validate = $bookingManagementService->validateDownloadCsv($queryParams);
                if ($validate) {
                    $result =  new JsonModel([
                        'status' => 'success',
                        'msg'    => '',
                    ]);
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return $result;
    }

    /**
     * Use to generate and download reservations CSV file
     */
    public function downloadCsvAction()
    {
    	/**
	     * @var \DDD\Service\Booking\BookingManagement $bookingManagementService
    	 * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var GeoliteCountry $geoliteCountryService
	     * @var BookingExportRow[]|\ArrayObject $reservations
	     */
    	$bookingManagementService = $this->getServiceLocator()->get('service_booking_management');
        $bookingTicketService     = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $geoliteCountryService    = $this->getServiceLocator()->get('service_geolite_country');

    	// getting query parameters
    	$queryParams = $this->params()->fromQuery();

    	// get reservations data
    	$reservations  = $bookingManagementService->getReservationsToExport(null, null, $queryParams);
        $filteredArray = [];

	   	$currencyDao = $this->getServiceLocator()->get('dao_currency_currency');

        $currencyUtility = new Currency($currencyDao);

    	foreach ($reservations as $reservation) {
            $isBlacklist   = $reservation->isBlacklist();
            $isBlacklist = is_null($isBlacklist) ? 'No' : 'Yes';

            $sumAndBalanc = $bookingTicketService->getSumAndBalanc($reservation->getId());
            $filteredArray[] = [
                "Reservation"           => $reservation->getReservationNumber(),
                "Affiliate ID"          => $reservation->getAffiliateID(),
                "Affiliate Name"        => $reservation->getPartnerName(),
                "Affiliate Reference"   => $reservation->getPartnerRef(),
                "Status"                => $reservation->getStatus(),
                "Blacklist"             => $isBlacklist,
                "Apartel"               => $reservation->getApartel(),
                "Booking Date"          => date('Y-m-d', strtotime($reservation->getReservationDate())),
                "Booking Time"          => date('H:i:s', strtotime($reservation->getReservationDate())),
                "Apartment Id"          => $reservation->getApartmentIdAssigned(),
                "Apartment Name"        => $reservation->getProductName(),
                "Apartment Building"    => $reservation->getApartmentBuilding(),
                "Apartment City"        => $reservation->getApartmentCity(),
                "Guest"                 => $reservation->getGuestFullName(),
                "Country"               => $reservation->getCountry_name(),
                "City"                  => $reservation->getGuestCityName(),
                "IP"                    => $geoliteCountryService->composeIPAndCountryNameString($reservation->getIP()),
                "Arrival Date"          => $reservation->getArrivalDate(),
                "Departure Date"        => $reservation->getDepartureDate(),
                "Nights"                => Helper::getDaysFromTwoDate($reservation->getArrivalDate(), $reservation->getDepartureDate()),
                "PAX"                   => $reservation->getPAX(),
                "Rate"                  => $reservation->getRateName(),
                "Base Price (EUR)"      => $currencyUtility->convert(str_replace(',', '', $reservation->getPrice()), $reservation->getApartmentCurrencyCode(), 'EUR'),

                "Base Currency"         => $reservation->getApartmentCurrencyCode(),
                "Base Price"            => $reservation->getPrice(),

                "Charges(Ginosi)"       => $sumAndBalanc['ginosiCollectChargesSummaryInApartmentCurrency'],
                "Transactions(Ginosi)"  => $sumAndBalanc['ginosiCollectTransactionsSummaryInApartmentCurrency'],
                "Balance"               => $reservation->getGuestBalance(),

                "Charges(Partner)"      => $sumAndBalanc['partnerCollectChargesSummaryInApartmentCurrency'],
                "Transactions(Partner)" => $sumAndBalanc['partnerCollectTransactionsSummaryInApartmentCurrency'],
                "Partner Balance"       => $reservation->getPartnerBalance(),

                "No Collection"         => $reservation->getNo_collection(),
			    "Review Score"          => $reservation->getReviewScore(),
			    "Like"                  => $reservation->getLike(),
			    "Dislike"               => $reservation->getDislike(),
    			"Actual Arrival Date"   => $reservation->getActualArrivalDate(),
    			"Actual Departure Date" => $reservation->getActualDepartureDate(),
    		];
    	}

    	if (count($filteredArray)) {
    		$response = $this->getResponse();
    		$headers = $response->getHeaders();

    		$utilityCsvGenerator = new CsvGenerator();
    		$filename = 'reservations_' . str_replace(' ', '_', date('Y-m-d')) . '.csv';
            $utilityCsvGenerator->setDownloadHeaders($headers, $filename);

    		$csv = $utilityCsvGenerator->generateCsv($filteredArray);

    		$response->setContent($csv);

    		return $response;
    	} else {
    		Helper::setFlashMessage(['notice' => 'The search results were empty, nothing to download.']);

    		$url = $this->getRequest()->getHeader('Referer')->getUri();
    		$this->redirect()->toUrl($url);
    	}
    }


    protected function getFormData($reservationNumber)
    {
        /**
         * @var $bookingTicketService \DDD\Service\Booking\BookingTicket
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $data                 = $bookingTicketService->getBookingByResNumber($reservationNumber);

        if (!$data) {
            return false;
        }

        $lockDatas = $bookingTicketService->getLockByReservation(
            $data->getId(),
            $data->getPin(),
            [
                LockService::USAGE_APARTMENT_TYPE,
                LockService::USAGE_BUILDING_TYPE,
                LockService::USAGE_PARKING_TYPE
            ]
        );

        $isAsDsialing  = false;

        foreach ($lockDatas as $key => $lockData) {
            switch ($key) {
                case LockService::USAGE_APARTMENT_TYPE:
                    $data->setPin($lockData['code']);
                    break;
                case LockService::USAGE_BUILDING_TYPE:
                    $data->setOutsideDoorCode($lockData['code']);
                    break;
                case LockService::USAGE_PARKING_TYPE:
                    // TODO: to be or not to be, this is the question.
                    break;
            }
        }

        $options = $bookingTicketService->getBookingOptions([
            'id'                  => $data->getId(),
            'res_number'          => $reservationNumber,
            'status'              => $data->getStatus(),
            'aff_id'              => $data->getPartnerId(),
            'apartmentIdAssigned' => $data->getApartmentIdAssigned()
        ]);

        $form = new BookingForm($data, $options);

	    return [
            'form'         => $form,
            'data'         => $data,
            'options'      => $options,
            'isAsDsialing' => $isAsDsialing,
	    ];
    }

    protected function getBookingDocFormData()
    {
        return new BookingDocumentsForm([]);
    }

    public function editAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var Logger $logger
         * @var \DDD\Dao\Task\Task $tasksDao
         * @var \DDD\Dao\Task\Type $taskTypeDao
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $tasksDao             = $this->getServiceLocator()->get('dao_task_task');
        $taskTypeDao          = $this->getServiceLocator()->get('dao_task_type');


        $logger       = $this->getServiceLocator()->get('ActionLogger');
        $auth         = $this->getServiceLocator()->get('library_backoffice_auth');
        $pinnedResDao = $this->getServiceLocator()->get('service_universal_dashboard_widget_pinned_reservation');
        $docService   = $this->getServiceLocator()->get('service_booking_attachment');

        $userId = $auth->getIdentity()->id;

        $reservationNumber = $this->params()->fromRoute('id', 0);

        $roleAMM = false;

        if ($auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT)) {
            $roleAMM = true;
        }

        $formData = $this->getFormData($reservationNumber);

        if (!$formData) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'booking']);
        }

        $pData = $bookingTicketService->prepareData($formData['data'], $formData);

        $bookingTicketService->updateLastReservationAgent($reservationNumber);

        $pin = 0;

        // Get action logging on edit
        $logData       = false;
        $allTasksCount = $tasksCount = 0;
        $attachment    = 0;

        if ($reservationNumber) {
            $logger->setOutputFormat(Logger::OUTPUT_BOOKING);

            $highlight = $params = $this->params()->fromQuery('highlightLog', 0);

            $logData = $logger->getDatatableData(
                Logger::MODULE_BOOKING,
                $formData['data']->getId(),
                $highlight,
                $userId
            );

            $ginosiComment = $logger->get(
                Logger::MODULE_BOOKING,
                $formData['data']->getId()
            );

            $pinRes = $pinnedResDao->getPinnedReservation($userId, $reservationNumber);
            $pin = ($pinRes) ? 1 : 0;

            $bookingDocLists = $docService->getAttachments($pData['data']->getId());
            $router          = $this->getEvent()->getRouter();
            $bookingDocData  = [];
            $downloadAction  = null;

            foreach ($bookingDocLists as $key => $bookingDocList) {
                if (isset($bookingDocList['filePaths'][0])) {
                    $downloadUrl = $router->assemble([
                        'controller' => 'booking',
                        'action'     => 'download',
                        'doc_id'     => $bookingDocList['id'],
                        'booking_id' => $pData['data']->getId(),
                    ], ['name' => 'booking-download']);

                    $downloadAction = '<button type="button" class="btn btn-xs '.
                        'btn-primary self-submitter state downloadViewButton" value="' .
                        $downloadUrl . '"><i class="glyphicon glyphicon-download"></i> Download</button>';
                }

                $deleteAction =
                    '<a class="btn btn-xs btn-danger pull-right deleteAttachment" '.
                    'href="javascript:void(0)" data-docid="' . $bookingDocList['id'] .
                    '" data-bookingid="' . $pData['data']->getId() . '">Delete</a>';

                array_push($bookingDocData, [
                    $bookingDocList['createdDate'],
                    $bookingDocList['attacher'],
                    $bookingDocList['description'],
                    $downloadAction,
                    $deleteAction,
                ]);
            }
            $tasksCount    = $tasksDao->getTasksCountOnReservation($formData['data']->getId());
            $allTasksCount = $tasksDao->getTasksCountOnReservation($formData['data']->getId(), true);
            isset($bookingDocData[0]) ?
                $attachment = json_encode($bookingDocData) :
                $attachment = 0;

            $taskTypeReservation = $taskTypeDao->fetchOne(['id' => TaskService::TYPE_RESERVATION]);
        }

        $bookingDocForm = $this->getBookingDocFormData();

        /* @var $customerService \DDD\Service\Customer */
        $customerService     = $this->getServiceLocator()->get('service_customer');
        $customerIdentity    = $customerService->getCustomerIdentityByReservationId($formData['data']->getId());
        $apartmentSpotsDao   = $this->getServiceLocator()->get('dao_apartment_spots');

        $parkingSpotPriority = $apartmentSpotsDao->getApartmentParkingPrioritySpots($pData['data']->getApartmentIdAssigned());
        $spotsPriority = [];

        foreach ($parkingSpotPriority as $key => $value) {
            if (!is_null($value)) {
                array_push($spotsPriority, $value);
            }
        }

        $parkingNights = [];

        $i = 0;
        if (isset($pData['nightsData'])) {
            foreach ($pData['nightsData'] as $key => $value) {
                $parkingNights[$i++] = $value;
            }
        }

        $changeApartmentOccupancy = false;

        if ($formData['data']->getOccupancy() > $formData['data']->getApartmentCapacity()) {
            $changeApartmentOccupancy = TextConstants::CHANGE_APARTMENT_OCCUPANCY;
        }

        return [
            'bookingForm' 	           => $formData['form'],
            'res_number' 	           => $reservationNumber,
            'data' 			           => $pData['data'],
            'dataOther' 	           => $pData['otherData'],
            'roleAMM'                  => $roleAMM,
            'ginosiComment'            => isset($ginosiComment) ? $ginosiComment : '',
            'historyData'              => json_encode($logData),
            'tasksCount'               => $tasksCount,
            'allTasksCount'            => $allTasksCount,
            'creditCards'              => $pData['creditCards'],
            'userId'                   => $userId,
            'pin'                      => $pin,
            'bookingDocForm'           => $bookingDocForm,
            'bookingDocList'           => $attachment,
            'nightsData'               => $pData['nightsData'],
            'ratesByDate'              => $pData['ratesByDate'],
            'taskTypeReservation'      => $taskTypeReservation,
            'customerIdentity'         => $customerIdentity,
            'spotsPriority'            => $spotsPriority,
            'possibleExtraPerson'      => (int)$pData['data']->getApartmentCapacity() - (int)$pData['data']->getOccupancy(),
            'parkingNights'            => $parkingNights,
            'userInternalNumber'       => $auth->getIdentity()->internal_number,
            'changeApartmentOccupancy' => $changeApartmentOccupancy,
        ];
    }

    public function ajaxSendMailAction()
    {
        /**
         * @var Request $request
         * @var Booking\BookingTicket $service
         */
        $request = $this->getRequest();
        $result  = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_SEND_MAIL,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $id       = (int)$request->getPost('id');
                $num      = (int)$request->getPost('num');
                $email    = $request->getPost('email');
                $service  = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $response = $service->sendMailFromBookingTicket($id, $num, $email);

                if (!$response) {
                    $result['status'] = 'error';
                    $result['msg']    = TextConstants::ERROR_SEND_MAIL;
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::ERROR_SEND_MAIL;
        }

        return new JsonModel($result);
    }

    public function ajaxGetParkingSpotsAction()
    {
        /**
         * @var Inventory $parkingSpotsInventoryService
         */

        $request = $this->getRequest();
        $result  = ['status' => 'success'];

        try {
            if ($request->isXmlHttpRequest()) {
                $apartmentId    = (int)$request->getPost('apartment_id');
                $allNightsDates = $request->getPost('all_nights_dates');

                $spotsAlreadySelectedInSameChargeSession =
                    ($request->getPost('spots_already_selected_in_this_section'))
                    ? $request->getPost('spots_already_selected_in_this_section')
                    : [];

                if (!$allNightsDates) {
                    $result['status'] = 'error';
                    $result['msg']    = 'No available spots for this period';
                    return new JsonModel($result);
                }

                $startDate = min($allNightsDates);
                $endDate   = max($allNightsDates);
                $parkingSpotsInventoryService = $this->getServiceLocator()->get('service_parking_spot_inventory');

                $allAvailableSpots = $parkingSpotsInventoryService->getAvailableSpotsForApartmentForDateRangeByPriority(
                    $apartmentId,
                    $startDate,
                    $endDate,
                    $spotsAlreadySelectedInSameChargeSession,
                    $request->getPost('is_selected_date')
                );

                if (empty($allAvailableSpots['availableSpots'])) {
                    $result['status'] = 'error';
                    $result['msg']    = 'No available spots for this period';
                } else {
                    $result['allAvailableSpots'] = $allAvailableSpots['availableSpots'];
                    $result['allAvailable']      = $allAvailableSpots['allAvailable'];
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::ERROR_SEND_MAIL;
        }

        return new JsonModel($result);
    }

    public function ajaxChargeAction()
    {
        /**
         * @var Booking\Charge $chargeService
         * @var Booking\BankTransaction $bankTransactionService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_CHARGED,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $data = (array)$request->getPost();

                // check for discount amount, it can't be more than 20% of product value
                if ($data['discount_total_value'] > $data['product_total_value']) {

                    $result = [
                        'status' => 'error',
                        'msg' => TextConstants::ERROR_CHARGE_DISCOUNT_AMOUNT_MORE_THAN_ALLOWED,
                    ];
                } else {
                    $chargeService = $this->getServiceLocator()->get('service_booking_charge');
                    $save = $chargeService->saveCharge($data);

                    if (!$save) {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::ERROR_CHARGED;
                    } else {
                        Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_CHARGED]);

                        /* @var $ticketService \DDD\Service\Booking\BookingTicket */
                        $ticketService = $this->getServiceLocator()->get('service_booking_booking_ticket');

                        $ticketService->markAsUnsettledReservationById(
                            $data['reservation_id']
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Booking: Charge Failed');

            $result['status'] = 'error';
            $result['msg'] = TextConstants::ERROR_CHARGED;
        }

        return new JsonModel($result);
    }

    public function ajaxTransactionAction()
    {
        /**
         * @var Booking\BankTransaction $bankTransactionService
         * @var \DDD\Service\Booking\BookingTicket $ticketService
         */
        $bankTransactionService = $this->getServiceLocator()->get('service_booking_bank_transaction');
        $ticketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $data = (array)$request->getPost();
                $transaction = $bankTransactionService->saveTransaction($data);
                $ticketService->markAsUnsettledReservationById(
                    $data['reservation_id']
                );

                Helper::setFlashMessage([$transaction['status'] => $transaction['msg']]);
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_TRANSACTED,
                ];
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Booking: Transaction Failed');

            $result['msg'] = TextConstants::ERROR_CHARGED;
        }

        return new JsonModel($result);
    }

    public function ajaxChangeCcPartnerIdAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_PARTNER_ID_CHANGED,
        ];
        try {
            if ($request->isXmlHttpRequest()) {
                $data = (array)$request->getPost();

                /**
                 * @var Card $cardService
                 */
                $cardService = $this->getServiceLocator()->get('service_card');
                $cardService->changePartnerId($data['card_id'], $data['partner_id']);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::ERROR_PARTNER_ID_CHANGED;
        }

        return new JsonModel($result);
    }

	public function ajaxsaveAction()
    {
		/**
		 * @var Request $request
		 * @var BookingForm $form
		 * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
		 */
		$request = $this->getRequest();
		$result = array(
		    'result' => [],
		    'status' => 'reload',
		    'msg' => TextConstants::SUCCESS_UPDATE,
		);

        try {
            if ($request->isXmlHttpRequest()) {
				$res_number     = $request->getPost('booking_res_number');
                $check_mail     = $request->getPost('check_mail');
				$addToBlackList = $request->getPost('add_to_blacklist');

				$formData = $this->getFormData($res_number);

                if (!$formData) {
                    throw new \Exception("Bad Data");
                }

				$form = $formData['form'];
	            $form->setInputFilter(new BookingFilter());
				$messages = '';

               if ($request->isPost()) {
               		$bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
                   	$filter               = $form->getInputFilter();
                   	$form->setInputFilter($filter);
                   	$data = $request->getPost();
                   	$form->setData($data);

                   	if ($form->isValid()) {
                       	$vData = $form->getData();
                        $vData['send_mail']      = ($check_mail == 'yes' ? true : false);
                       	$vData['addToBlackList'] = (int)$addToBlackList;

                       	$response = $bookingTicketService->bookingSave((array)$vData);

                       	if ($response['status'] == 'success' && $response['cub_status']['status'] == 'success') {
                           if (!empty($response['cub_status']['msg'])) {
	                           $flash = ['success' => $response['cub_status']['msg']];
                           } else {
	                           $flash = ['success' => TextConstants::SUCCESS_UPDATE];
                           }
                       	} else {
                           $error_msg = isset($response['msg']) ? $response['msg'] : '';
                           $error_msg = isset($response['cub_status']['msg']) ?
                                $error_msg . " ". $response['cub_status']['msg'] :
                                $error_msg;

                           $flash = ['error' => $error_msg];
                       }

                       Helper::setFlashMessage($flash);
                   } else {
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

                       $result['status'] = 'error';
                       $result['msg'] = $messages;
                   }
               }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxGetUserAction()
    {
        $request = $this->getRequest();
        $result = [
            'rc' => '00',
            'result' => [],
        ];

        try {
            if ($request->isXmlHttpRequest()) {
               $txt      = strip_tags(trim($request->getPost('txt')));
               $service  = $this->getServiceLocator()->get('dao_user_user_manager');
               $users  = $service->getUsers($txt);
               $res = [];

               foreach ($users as $key => $row) {
                    $res[$key]['id']   = $row->getId();
                    $res[$key]['name'] = $row->getFirstName() . ' ' . $row->getLastName();
               }

               $result['result'] = $res;
            }
        } catch (\Exception $e) {
            $result['rc'] = '01';
        }

        return new JsonModel($result);
    }

    public function ajaxBlackListAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => '',
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Service\Booking $bookingService
                 */
                $bookingService = $this->getServiceLocator()->get('service_booking');

                $num     = (int)$request->getPost('num');
                $reservationId  = Helper::stripTages($request->getPost('reservation_id'));
                $response = $bookingService->saveBlackList($reservationId, $num);

                if ($response && $response['status'] == 'success') {
                   $flash = ['success'=> $response['msg'] ];
                } else {
                   $flash = ['error'=> $response['msg'] ];
                }

                Helper::setFlashMessage($flash);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxGetPossibleMoveDestinationsAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Booking $bookingService
         */
        $return = [
            'status' => 'error',
            'msg'    => 'There are no available apartments to move to. Please try again later',
        ];

        try {
            $request = $this->getRequest();

            if ($request->isXmlHttpRequest()) {
                $apartmentId    = (int)$request->getPost('id');
                $rateOccupancy  = (int)$request->getPost('rateOccupancy');
                $dateFrom       = trim($request->getPost('dateFrom'));
                $dateTo         = trim($request->getPost('dateTo'));

                if (!$apartmentId || !$dateFrom || !$dateTo) {
                    return new JsonModel($return);
                }
                $bookingService = $this->getServiceLocator()->get('service_booking');
                $moveStart      = $bookingService->getMoveStartDate($dateFrom, $apartmentId);

                /**
                 * @var \DDD\Service\Apartment\General $apartmentService
                 */
                $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
                $result           = $apartmentService->getPossibleMoveDestinations($apartmentId, $moveStart, $dateTo, $rateOccupancy);
                $moveDestinations = [];

                if ($result) {
                    foreach($result as $row) {
                        $moveDestinations[$row['apartel']][$row['apartment_id']] = $row['apartment'];
                    }

                    // Can't be moved on itself
                    unset($moveDestinations[$apartmentId]);

                    $return['status']       = 'success';
                    $return['msg']          = 'success';
                    $return['destinations'] = $moveDestinations;
                }
            }
        } catch (\Exception $e) {
            $return['msg'] = $e->getMessage();
        }

        return new JsonModel($return);
    }

    public function ajaxMoveReservationAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Booking $bookingService
         */
        $return = [
            'status' => 'error',
            'msg'    => 'Moving this apartment is not possible.',
        ];

        try {
            $request = $this->getRequest();

            /**
             * @var \DDD\Service\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');

            if ($request->isXmlHttpRequest()) {
                $apartmentId   = (int)$request->getPost('apartmentId');
                $reservationId = (int)trim($request->getPost('resId'));

                if ($apartmentId && $reservationId) {
                    $reservation      = $bookingService->getBasicInfoById($reservationId);
                    $dateFrom         = $reservation['date_from'];
                    $dateTo           = $reservation['date_to'];
                    $oldApartmentName = $reservation['apartment_name'];
                    $oldApartmentId   = $reservation['apartment_id_assigned'];

                    $moveStart       = $bookingService->getMoveStartDate($dateFrom, $apartmentId);
                    $originFirstDate = $dateFrom;
                    $dateFrom        = $moveStart;
                    $isAvailable     = $bookingService->checkApartmentAvailability($apartmentId, $dateFrom, $dateTo);

                    if ($isAvailable) {
                        $bookingService->moveReservation($reservationId, $reservation['res_number'], $apartmentId, $oldApartmentId, $oldApartmentName, $dateFrom, $dateTo, $originFirstDate);
                        Helper::setFlashMessage(['success'=>'Reservation move was successful.']);

                        $return['status'] = 'reload';
                    }
                } else {
                    $return['msg'] = 'Some required data is missing. Impossible to move the reservation.';
                }
            }
        } catch (\Exception $e) {
            $return['msg'] = $e->getMessage();
        }

        return new JsonModel($return);
    }

    public function changeCcStatusAction()
    {
        /**
         * @var array[]|\ArrayObject $ccList
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $ccId = (int)$request->getPost('cc_id');
        $ccStatus = $request->getPost('status');
        $reservationId = $request->getPost('reservation_id');

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $auth->hasRole(Roles::ROLE_CREDIT_CARD)) {
                /**
                 * @var Card $cardService
                 */
                $cardService = $this->getServiceLocator()->get('service_card');

                if ($cardService->changeCardStatus($ccId, $ccStatus)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE
                    ];

                    $logger->save(
                        Logger::MODULE_BOOKING,
                        $reservationId,
                        Logger::ACTION_CC_STATUS_CHANGED,
                        'Status for CC ' . $ccId . ' changed to "' . CreditCard::getCardStatuses()[$ccStatus][1] . '"'
                    );
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function requestCcDetailsAction()
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => 'Something went wrong while trying to get credit card data',
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $authenticationService->hasRole(Roles::ROLE_CREDIT_CARD)) {
                $creditCardId = intval($request->getPost('cc_id', 0));
                $reservationId = intval($request->getPost('reservation_id', 0));

                if ($creditCardId) {
                    /**
                     * @var Retrieve $retrieveService
                     */
                    $retrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

                    $creditCard = $retrieveService->getCreditCard($creditCardId);

                    if ($creditCard) {
                        $ccHolder = $creditCard->getHolder();
                        $ccExp = $creditCard->getExpirationMonth() . '/' . $creditCard->getExpirationYear();
                        $ccCVC = $creditCard->getSecurityCode();
                        $ccNumber = $creditCard->getPan();

                        $result = [
                            'status' => 'success',
                            'msg' => 'Credit card data requested successfully',
                            'data' => [
                                'number' => $ccNumber,
                                'cvc' => $ccCVC,
                                'exp' => $ccExp,
                                'holder' => $ccHolder,
                            ],
                        ];

                        $logger = $this->getServiceLocator()->get('ActionLogger');
                        $logger->save(
                            Logger::MODULE_BOOKING,
                            $reservationId,
                            Logger::ACTION_REQUEST_CC_COMPLETE_DATA,
                            'Requested CC details from Vault. CC ID: "' . $creditCardId . '"'
                        );
                    }
                } else {
                    $result['msg'] = 'Requested credit card cannot be found.';
                }
            }
        } catch (\Exception $ex) {
            $result['msg'] = 'Something went wrong while trying to get credit card data';
        }

        return new JsonModel($result);
    }

    public function ajaxPinReservationAction()
    {
        $pinnedResDao = $this
            ->getServiceLocator()
            ->get('dao_booking_pinned_reservation');

        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $pin    = (int)$request->getPost('pin');
                $userId = (int)$request->getPost('userId');
                $resNum = $request->getPost('resNum');

                if (!$pin) {
                    $pinnedResDao->save([
                        "user_id"    => $userId,
                        "res_number" => $resNum
                    ]);

                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::SUCCESS_PINNED,
                        'pin'    => 1
                    ];
                } else {
                    $pinnedResDao->deleteWhere([
                        'res_number' => $resNum,
                        'user_id'    => $userId
                    ]);

                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::SUCCESS_UNPINNED,
                        'pin'    => 0
                    ];
                }
            }
        } catch (\Exception $ex) {
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxLockReservationAction()
    {
        /**
         * @var Logger $logger
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $lock  = (int)$request->getPost('lock');
                $resId = (int)$request->getPost('id');

                $affectedRows = $bookingDao->save(['locked' => $lock], ['id' => $resId]);

                $logger->save(
                    Logger::MODULE_BOOKING,
                    $resId,
                    Logger::ACTION_RESERVATION_LOCKED,
                    (int)$lock
                );

                if ($affectedRows) {
                    $result = [
                        'status' => 'success',
                        'msg'    => $lock ? TextConstants::SUCCESS_LOCKED: TextConstants::SUCCESS_UNLOCKED,
                    ];
                } else {
                    $result = [
                        'status' => 'error',
                        'msg'    => 'Invalid reservation details.',
                    ];
                }
            }
        } catch (\Exception $ex) {
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxGetReceiptAction ()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $service
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success'
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $service = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $reservationId = (int)$request->getPost('reservation_id');
                $data = $service->getReceiptData($reservationId);

                if ($data['status'] == 'success') {
                    $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                    $response = $partial('backoffice/booking/receipt.phtml', array('data' => $data));
                    $result['result'] = $response;
                } else {
                    $result['msg'] = $data['msg'];
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxSendReceiptAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $service
         */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    =>  TextConstants::ERROR,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $service       = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $reservationId = (int)$request->getPost('reservation_id');
                $customEmail   = $request->getPost('custom_email');
                $result        = $service->getSendReceipt($reservationId, $customEmail);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxUploadFilesAction()
    {
        /**
         * @var \DDD\Service\Booking\Attachment $bookingDocService
         */
        $bookingDocService = $this->getServiceLocator()->get('service_booking_attachment');

        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $fileSize = 0;
                foreach ($request->getFiles() as $key => $value) {
                    $fileSize += $value['size'];
                }

                if (($fileSize) > DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE)  {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::FILE_SIZE_EXCEEDED,
                    ];
                    return new JsonModel($result);
                }

                $data       = (array)$request->getPost();
                $auth       = $this->getServiceLocator()->get('library_backoffice_auth');
                $attacherId = $auth->getIdentity()->id;

                if (empty(trim(strip_tags($data['doc_description'])))) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::ERROR_DESCRIPTION_EMPTY
                    ];

                    return new JsonModel($result);
                }

                $docData = [
                    'type_id'        => 1,
                    'reservation_id' => $data['booking_id'],
                    'attacher_id'    => $attacherId,
                    'description'    => trim(strip_tags($data['doc_description'])),
                    'created_date'   => date('Y-m-d H:i:s')
                ];

                $docId = $bookingDocService->saveDocData($docData);
                if ($docId) {
                    $response = $bookingDocService->uploadFile($request, $docId, $data['booking_id']);

                    if ($response === true) {

                        $result = [
                            'status' => 'success',
                            'msg'    => TextConstants::SUCCESS_ADD
                        ];

                        $logger = $this->getServiceLocator()->get('ActionLogger');

                        $logger->save(
                            Logger::MODULE_BOOKING,
                            $data['booking_id'],
                            Logger::ACTION_BOOKING_UPLOAD_DOCS,
                            'Upload Document'
                        );

                        Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_ADD]);
                    } elseif (false !== $response) {
                        $result['msg'] = $response;
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new JsonModel($result);
    }

    /**
     * @todo #ziparchiver
     */
    public function downloadAction()
    {
        $documentId = (int)$this->params()->fromRoute('doc_id', 0);
        $reservationId = (int)$this->params()->fromRoute('booking_id', 0);

        if ($documentId && $reservationId) {
            $docFileDao = $this->getServiceLocator()->get('dao_booking_attachment_item');

            $files = $docFileDao->getDocFiles($reservationId, $documentId);

            $attachmentPathArray = [];

            foreach ($files as $file) {
                $year  = date('Y', strtotime($file->getCreatedDate()));
                $month = date('m', strtotime($file->getCreatedDate()));

                $path['path'] = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_BOOKING_DOCUMENTS
                    . $year . '/' .  $month . '/'
                    . $reservationId . '/' . $documentId . '/' . $file->getAttachment();

                $path['name'] = $file->getAttachment();
                $attachmentPathArray[] = $path;
            }

            $archiveFileName = 'reservation_' . $reservationId . '_attachment_' . $documentId . '.zip';
            $archiveFileFullPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_TMP
                . $archiveFileName;
            $archiveFilePath = DirectoryStructure::FS_UPLOADS_TMP . $archiveFileName;

            $zipArchiveUtility = new \ZipArchive;
            $zipArchiveUtility->open($archiveFileFullPath, \ZipArchive::CREATE);

            foreach ($attachmentPathArray as $path) {
                $zipArchiveUtility->addFile($path['path'], $path['name']);
            }

            $zipArchiveUtility->close();

            /**
             * @var \FileManager\Service\GenericDownloader $genericDownloader
             */
            $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');

            $genericDownloader->downloadAttachment($archiveFilePath);

            if ($genericDownloader->hasError()) {
                Helper::setFlashMessage(['error' => $genericDownloader->getErrorMessages(true)]);

                $url = $this->getRequest()->getHeader('Referer')->getUri();
                $this->redirect()->toUrl($url);
            }

            return true;
        }
    }

    public function ajaxDeleteAttachmentAction()
    {
        $bookingDocService = $this->getServiceLocator()->get('service_booking_attachment');

        $request = $this->getRequest();

        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {

                $bookingId = (int)$request->getPost('booking_id');
                $docId     = (int)$request->getPost('doc_id');

                $response = $bookingDocService->deleteAttachment($docId, $bookingId);

                if ($response) {
                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::SUCCESS_DELETE,
                    ];

                    $logger = $this->getServiceLocator()->get('ActionLogger');
                    $logger->save(
                        Logger::MODULE_BOOKING,
                        $bookingId,
                        Logger::ACTION_BOOKING_DELETE_DOCS,
                        'Delete Document'
                    );

                    $result['status'] = 'success';
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            Helper::setFlashMessage(['success' => TextConstants::SERVER_ERROR]);

            return new JsonModel($result);
        }

        return new JsonModel($result);
    }

    public function ajaxChangeDateAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $service
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' =>  TextConstants::ERROR,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $service       = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $reservationId = (int)$request->getPost('reservation_id');
                $dateFrom      = $request->getPost('dateFrom');
                $dateTo        = $request->getPost('dateTo');
                $isGetInfo     = $request->getPost('is_get_info', false);
                $isGetInfo     = ($isGetInfo) ? true : false;
                $changeDate    = $service->changeReservationDate($reservationId, $dateFrom, $dateTo, $isGetInfo);

                if ($isGetInfo && $changeDate['status'] == 'success') {
                    $partial          = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                    $showInfo         = $partial('backoffice/partial/change-date-info.phtml', ['data' => $changeDate['data']]);
                    $result['status'] = $result['msg'] = 'success';
                    $result['data']   = $showInfo;
                } elseif ($changeDate['status'] == 'error') {
                    if (isset($changeDate['msg']) && $changeDate['msg']) {
                        $result['msg'] = $changeDate['msg'];
                    }
                } else {
                    $result['status'] = 'success';
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_CHANGE_DATE]);
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxGetReservationTasksAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Task\Task $tasksDao
         * @var \DDD\Service\Task $taskService
         */
        $tasksDao = $this->getServiceLocator()->get('dao_task_task');
        $taskService = $this->getServiceLocator()->get('service_task');
        $request = $this->params();
        $currentPage = ($request->fromQuery('start') / $request->fromQuery('length')) + 1;

        $results = $tasksDao->getTasksOnReservationForDatatable(
            (integer)$request->fromQuery('reservationId'),
            (integer)$request->fromQuery('start'),
            (integer)$request->fromQuery('length'),
            $request->fromQuery('order'),
            $request->fromQuery('search'),
            $request->fromQuery('all', '1')
        );

        $tasks = $results['result'];
        $tasksCount = $results['total'];

//        $tasksCount = $tasksDao->getTasksCountOnReservationForDatatable((integer)$request->fromQuery('reservationId'), $request->fromQuery('search')['value'], $request->fromQuery('all', '1'));

        foreach ($tasks as $row) {
            $rowClass = '';
            if (strtotime($row->getEndDate()) <= strtotime(date('Y-m-j 23:59')) && $row->getTask_status() < TaskService::STATUS_DONE) {
                $rowClass = 'danger';
            }
            $permissions = $taskService->composeUserTaskPermissions($row->getId());
            $result[] = [
                TaskService::getTaskPriorityLabeled()[$row->getPriority()],
                TaskService::getTaskStatus()[$row->getTask_status()],
                $row->getStartDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getStartDate())) : '',
                $row->getEndDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getEndDate())) : '',
                (strlen($row->getTitle()) > 30) ? substr($row->getTitle(), 0, 30) .'...' : $row->getTitle(),
                $row->getTaskType(),
                $row->getCreatorName(),
                $row->getResponsibleName(),
                (count($permissions)
                    ? '<a href="/task/edit/' . $row->getId() . '" class="btn btn-xs btn-primary" target="_blank">View</a>'
                    : ''
                ),
                "DT_RowClass" => $rowClass
            ];
        }

        if (!isset($result) or $result === null) {
            $result = [];
        }

        $resultArray = [
            'sEcho' => $request->fromQuery('sEcho'),
            'iTotalRecords' => $tasksCount,
            'iTotalDisplayRecords' => $tasksCount,
            'iDisplayStart' => ($currentPage - 1) * (integer)$request->fromQuery('start'),
            'iDisplayLength' => (integer)$request->fromQuery('length'),
            'aaData' => $result,
        ];

        return new JsonModel($resultArray);
    }

    public function ajaxMakeCommentVisibleToFrontierAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Dao\ActionLogs\ActionLogs $loggerDao
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => 'Comment visibility successfully changed!',
        ];

        $loggerDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

        try {
            if ($request->isXmlHttpRequest()) {
                $id = (int)$request->getPost('id');
                $response = $loggerDao->save(['action_id' => Logger::ACTION_HOUSEKEEPING_COMMENT], ['id' => $id]);

                if (!$response) {
                    $result['status'] = 'error';
                    $result['msg'] = TextConstants::ERROR_SEND_MAIL;
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }
}
