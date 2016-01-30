<?php
namespace Backoffice\Controller;

use CreditCard\Service\Card;
use DDD\Service\Frontier;
use DDD\Service\Reservation\ChargeAuthorization as ChargeAuthorizationService;
use DDD\Service\Reservation\ChargeAuthorization;
use DDD\Service\Task;
use Library\ActionLogger\Logger;
use Library\Utility\Helper;
use Library\Validator\ClassicValidator;
use Library\Utility\Currency;
use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Controller\ControllerBase;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use \DDD\Service\Booking\BookingTicket;
use Backoffice\Form\CreditCard;
use Backoffice\Form\InputFilter\CreditCardFilter;
use DDD\Service\Booking\Charge;
use \DDD\Service\Booking;
use Library\Constants\DomainConstants;
use FileManager\Constant\DirectoryStructure;

class FrontierController extends ControllerBase
{
    public function cardsAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Frontier $frontierService
         * @var \DDD\Service\Task $taskService
         */
        $request         = $this->getRequest();
        $frontierService = $this->getServiceLocator()->get('service_frontier');
        $taskService     = $this->getServiceLocator()->get('service_task');
        $query           = $request->getQuery('id', false);
        $auth            = $this->getServiceLocator()->get('library_backoffice_auth');
        $bookingDao      = $this->getServiceLocator()->get('dao_booking_booking');
        $userId          = $auth->getIdentity()->id;


        preg_match('/(?P<entityType>\d)_(?P<entityId>\d+)/', $query, $params);

        $result = [
            'entityType' => 0,
            'entityId'   => 0,
            'card'       => [],
            'tasks'      => []
        ];

        if (   isset($params['entityId'])
            && $params['entityId']
            && !$auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)
        ) {
            $hasAccess = $bookingDao->checkFrontierPermission($userId, $params['entityId']);

            if (!$hasAccess) {
                return $this->redirect()->toUrl('/');
            }
        }

        if (count($params)) {
            $card = $frontierService->getTheCard($params['entityId'], $params['entityType'], $this);
            if ($card) {
                $tasks = $frontierService->getEntityTasks($params['entityId'], $params['entityType']);

                $result = [
                    'entityType' => $params['entityType'],
                    'entityId'   => $params['entityId'],
                    'card'       => $card,
                    'tasks'      => $tasks
                ];
            }
        }

        $taskTypes = $taskService->getTaskTypesForSelect(['group' => Task::TASK_GROUP_FRONTIER]);
        $result['taskTypes'] = $taskTypes;

        // get bad email list
        $getBadEmail = BookingTicket::getBadEmail();
        $result['getBadEmail'] = json_encode($getBadEmail);

        return new ViewModel($result);
    }

    public function ajaxGetTheCardAction()
    {
        /**
         * @var Request $request
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\Frontier $frontierService
         */
        $request = $this->getRequest();
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if (   $request->isPost()
                && $request->isXmlHttpRequest()
                && $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)
            ) {
                $id   = (int)trim(strip_tags($request->getPost('id')));
                $type = (int)trim(strip_tags($request->getPost('type')));

                if ($id && $type) {
                    /* @var $frontierService \DDD\Service\Frontier */
                    $frontierService = $this->getServiceLocator()->get('service_frontier');
                    $card = $frontierService->getTheCard($id, $type, $this);

                    if (!$card) {
                        $result = [
                            'status' => 'error',
                            'msg'    => 'Can\'t find the card. Please choose from the list.',
                        ];
                    } else {
                            $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                            $booked = (isset($card['bookingStatus']) && $card['bookingStatus'] == Booking::BOOKING_STATUS_BOOKED) ? true : false;
                            $cardsPartial = $partial('backoffice/frontier/partial/cards', [
                                'entityType' => $type,
                                'card'       => $card,
                                'booked'     => $booked
                            ]);
                            if ($type != Frontier::CARD_BUILDING) {
                                $tasks = $frontierService->getEntityTasks($id, $type);
                                $tasksPartial = $partial('backoffice/frontier/partial/tasks', [
                                    'entityType' => $type,
                                    'entityId' => $id,
                                    'card' => $card,
                                    'tasks' => $tasks,
                                ]);
                            } else {
                                $tasksPartial = '';
                            }

                            $result = [
                                'status' => 'success',
                                'cardsPartial'    => $cardsPartial,
                                'tasksPartial'    => $tasksPartial
                            ];
                    }
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxCardSearchAction()
    {
        /**
         * @var Request $request
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\Frontier $frontierService
         */
        $request = $this->getRequest();
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $user    = $auth->getIdentity();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];
        try {
            if (   $request->isPost()
                && $request->isXmlHttpRequest()
                && $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)
            ) {
                $query = trim(strip_tags($request->getPost('query')));
                if ($query) {
                    $frontierService = $this->getServiceLocator()->get('service_frontier');
                    $result          = $frontierService->findCards($query);
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }
        return new JsonModel($result);
    }

    public function ajaxGetApartmentsAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Frontier $frontierService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];
        try {
            if (   $request->isPost() && $request->isXmlHttpRequest()
            ) {
                $search = $request->getPost('search');
                $apartmentGroupId = $request->getPost('apartment_group_id');
                $frontierService = $this->getServiceLocator()->get('service_frontier');
                $apartmentsInfo = $frontierService->getApartmentsForBuilding($apartmentGroupId, $search);
                $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                $html = $partial('backoffice/frontier/partial/building-apartments', array('apartments' => $apartmentsInfo));
                $result = [
                    'status' => 'success',
                    'html' => $html
                ];
            }
        } catch (\Exception $ex) {
            // do nothing
        }
        return new JsonModel($result);
    }

    public function chargeAction()
    {
        $detailDao = $this->getServiceLocator()->get('dao_apartment_details');

        $request   = $this->getRequest();
        $bookingId = (int)$this->params()->fromRoute('booking_id', 0);
        $hash      = $this->params()->fromRoute('hash', '');
        $itemId    = $this->params()->fromRoute('item_id', 0);

        if (!$bookingId || Helper::hashForFrontierCharge($bookingId) != $hash) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'concierge', 'action' => 'view']);
        }

        /**
         * @var \DDD\Service\Frontier $service
         */
        $service = $this->getServiceLocator()->get('service_frontier');
        $data = $service->getDataForFrontierCharge($bookingId, $itemId);

        if (!$data) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'concierge', 'action' => 'view']);
        }

        $creditCards = false;
        if (isset($data['booking_data']['customer_id']) && $data['booking_data']['customer_id'] > 0) {
            /**
             * @var Card $cardService
             */
            $cardService = $this->getServiceLocator()->get('service_card');
            $creditCards = $cardService->getCustomerCreditCardsForFrontier($data['booking_data']['customer_id']);
        }

        $creditCardForm  = new CreditCard();

        $currencyDao     = $this->getServiceLocator()->get('dao_currency_currency');
        $currencyUtility = new Currency($currencyDao);
        $limitAmount     = $currencyUtility->convert(200, 'USD', $data['booking_data']['acc_currency_sign']);

        $apartmentSpotsDao   = $this->getServiceLocator()->get('dao_apartment_spots');
        $parkingSpotPriority = $apartmentSpotsDao->getApartmentParkingPrioritySpots($data['booking_data']['apartment_id_assigned']);
        $spotsPriority = [];

        foreach ($parkingSpotPriority as $key => $value) {
            if (!is_null($value)) {
                array_push($spotsPriority, $value);
            }
        }
        return array(
            'data'           => $data,
            'itemId'         => $itemId,
            'creditCards'    => $creditCards,
            'creditCardForm' => $creditCardForm,
            'limitAmount'    => $limitAmount,
            'spotsPriority'  => $spotsPriority
        );
    }

    public function ajaxFrontierChargeAction()
    {
        $result = ['error' => TextConstants::ERROR_CHARGED];
        $request = $this->getRequest();
        try{
            if ($request->isXmlHttpRequest()) {
                $data = (array)$request->getPost();
                /**
                 * @var $chargeService Charge
                 */
                $chargeService = $this->getServiceLocator()->get('service_booking_charge');
                $response = $chargeService->saveFrontierCharge($data);
                if ($response) {
                    $result = ['success' => TextConstants::SUCCESS_CHARGED . ' #' .$response];

                    /* @var $ticketService \DDD\Service\Booking\BookingTicket */
                    $ticketService = $this->getServiceLocator()->get('service_booking_booking_ticket');

                    $ticketService->markAsUnsettledReservationById(
                        $data['bookingId']
                    );
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Frontier Charge Failed');
        }

        Helper::setFlashMessage($result);
        return new JsonModel($result);
    }

    public function ajaxCcNewDataAction()
    {
        /**
         * @var Request $request
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::CREDIT_CARD_DATA_NOT_VALID,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {

                $reservationId = intval($request->getPost('reservation_id', 0));

                if (!$reservationId) {
                    return new JsonModel($result);
                }

                $form = new CreditCard();
                $form->setInputFilter(new CreditCardFilter());

                $filter = $form->getInputFilter();
                $form->setInputFilter($filter);
                $data = $request->getPost();
                $form->setData($data);

                if ($form->isValid()) {
                    /**
                     * @var \DDD\Service\Frontier $service
                     */
                    $service = $this->getServiceLocator()->get('service_frontier');

                    $validatedCreditCardData = $form->getData();

                    $response = $service->addCreditCard($reservationId, $validatedCreditCardData);

                    Helper::setFlashMessage([$response['status'] => $response['msg']]);

                    $result = [
                        'status' => $response['status'],
                        'msg'    => $response['msg'],
                    ];
                }
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }
        return new JsonModel($result);
    }

    public function ajaxGetTasksAction()
    {
        /**
         * @var Request $request
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\Frontier $frontierService
         */
        $request = $this->getRequest();
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');

        $result = [
            'status' => 'error',
            'msg'    => 'Failed to retrieve tasks list. Please try again.',
        ];

        try {
            if (   $request->isPost()
                && $request->isXmlHttpRequest()
                && $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)
            ) {
                $id   = (int)trim(strip_tags($request->getPost('id')));
                $type = (int)trim(strip_tags($request->getPost('type')));

                if ($id && $type) {
                    $frontierService = $this->getServiceLocator()->get('service_frontier');
                    $result = $frontierService->getEntityTasks($id, $type);

                    if (!$result) {
                        $result = [];
                    }
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxChangeArrivalStatusAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $reservationTicket
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];
        try{
            if($request->isXmlHttpRequest()) {
                $resId      = (int)$request->getPost('resId');
                $status     = (int)$request->getPost('status');

                $reservationTicket = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $response  = $reservationTicket->changeArrivalStatus($resId, $status);
                $responseText = '';
                if($response){
                    switch ($status) {
                        case BookingTicket::BOOKING_ARRIVAL_STATUS_CHECKED_IN:
                            $responseText = TextConstants::ARRIVAL_STATUS_CHECK_IN;
                            break;
                        case BookingTicket::BOOKING_ARRIVAL_STATUS_CHECKED_OUT:
                            $responseText = TextConstants::ARRIVAL_STATUS_CHECK_OUT;
                            break;
                        case BookingTicket::BOOKING_ARRIVAL_STATUS_NO_SHOW:
                            $responseText = TextConstants::ARRIVAL_STATUS_NO_SHOW;
                            break;
                    }

                    $result['status'] = 'success';
                    $result['msg']    = $responseText;
                }
            }
        } catch (\Exception $e) {

        }
        return new JsonModel($result);
    }

    public function ajaxSendCommentAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Frontier $frontierService
         */
        $request = $this->getRequest();
        $result = [
            'result' => [],
            'status' => 'success',
            'msg' => TextConstants::HOUSEKEEPER_SEND_COMMENT,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $txt = strip_tags(trim($request->getPost('txt')));
                $res_id = (int)$request->getPost('res_id');
                $frontierService = $this->getServiceLocator()->get('service_frontier');

                if ($txt != '' && $res_id > 0) {
                    $res = $frontierService->sendComment($txt, $res_id);

                    if ($res) {
                        $result['result'] = $res;
                    } else {
                        $result['status'] = 'error';
                        $result['msg'] = TextConstants::SERVER_ERROR;
                    }
                } else {
                    $result['status'] = 'error';
                    $result['msg'] = TextConstants::SERVER_ERROR;
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxSaveEmailAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Frontier $frontierService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $email = $request->getPost('email');
                $reservationId = (int)$request->getPost('res_id');
                if ($reservationId && ClassicValidator::validateEmailAddress($email)) {
                    $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
                    $bookingDao->save(['guest_email' => $email], ['id' => $reservationId]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ADD_EMAIL,
                    ];
                }
            }
        } catch (\Exception $e) {

        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxGenerateCccaPageAction()
    {
        $result['status'] = 'success';
        $result['msg']    = 'CCCA page successfully generated.';

        try {
            $request = $this->getRequest();

            if ($request->isXmlHttpRequest()) {
                $reservationId = (int)$request->getPost('reservation_id');
                $ccId          = (int)$request->getPost('cc_id');
                $amount          = $request->getPost('amount');

                /**
                 * @var ChargeAuthorizationService $chargeAuthorizationService
                 * @var Logger $logger
                 */
                $chargeAuthorizationService = $this->getServiceLocator()->get('service_reservation_charge_authorization');
                $logger                     = $this->getServiceLocator()->get('ActionLogger');

                $cccaResponse  = $chargeAuthorizationService->generateChargeAuthorizationPageLink($reservationId, $ccId, $amount);
                $cmd    = 'ginosole reservation-email send-ccca --id=' . escapeshellarg($reservationId) . ' --ccca_id=' . $cccaResponse['cccaId'];
                $output = shell_exec($cmd);

                if (strstr(strtolower($output), 'error')) {
                    $result['status'] = 'error';
                    $result['msg']    = TextConstants::ERROR_SEND_MAIL;

                    return new JsonModel($result);
                }
                // log
                $logger->save(
                    Logger::MODULE_BOOKING,
                    $reservationId,
                    Logger::ACTION_RESERVATION_CCCA_FORM_GENERATED_AND_SENT,
                    ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED
                );
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::ERROR;
        }

        return new JsonModel($result);
    }

    public function printParkingPermitsAction()
    {
        $reservationNumber =  $this->params()->fromRoute('res_num', 0);
        $page              =  (int) $this->params()->fromRoute('page', 1);
        $index = $page - 1;
        $bookingChargeService = $this ->getServiceLocator() ->get('service_booking_charge');
        $parkingInfo = $bookingChargeService->getParkingInfoByReservationId($reservationNumber);
        $parkingInfoArray = [];
        $lotName = '';
        $isLotVirtual = 0;
        $parkingPermit = '';
        $showWarning = 0;

        $officePhoneNumber = '';

        foreach($parkingInfo as $row) {
            if ($row['guest_balance'] < 0) {
                $showWarning = 1;
            }
            if (!is_null($row['parking_permit']) && $row['parking_permit'] != '') {
                $uploadFolder = DomainConstants::IMG_DOMAIN_NAME . '/' . DirectoryStructure::FS_IMAGES_PARKING_ATTACHMENTS . $row['lot_id'] . '/';
                $parkingPermitTemp = $uploadFolder . $row['parking_permit'];
            } else {
                $parkingPermitTemp = '';
            }
            if (!is_null($row['permit_id']) && $row['permit_id'] != '') {
                $premitIdTemp = $row['permit_id'];
            } else {
                $premitIdTemp = '';

            }

            $spotName = $row['spot_unit'] . '##' . $premitIdTemp . '##' . $row['lot_name'] . '##' . $row['is_lot_virtual'] . '##' . $parkingPermitTemp;

            if (!isset($parkingInfoArray[$spotName])) {
                $parkingInfoArray[$spotName] = [];
            }

            if ($officePhoneNumber == '') {
                $officePhoneNumber = $row['office_phone'];
            }

            array_push($parkingInfoArray[$spotName], $row['reservation_nightly_date']);
        }
        $combinedParkingInfo = $bookingChargeService->calculateDateRangesForSpot($parkingInfoArray);
        if (!isset($combinedParkingInfo[$index])) {
            $index = 0;
        }
        if (isset($combinedParkingInfo[$index + 1])) {
            $nextUrl = '/frontier/print-parking-permits/' . $reservationNumber . '/' . ($page + 1);
        } else {
            $nextUrl = false;
        }
        if (isset($combinedParkingInfo[$index -1])) {
            $prevUrl = '/frontier/print-parking-permits/' . $reservationNumber . '/' . ($page -1);
        } else {
            $prevUrl = false;
        }
        $datesFromToArray = explode('-', $combinedParkingInfo[$index]['date']);
        $fromDate = $datesFromToArray[0];
        $toDate   = $datesFromToArray[1];
        $spotUnitAndPermitIdArray = explode('##', $combinedParkingInfo[$index]['spot']);
        $spotName = $spotUnitAndPermitIdArray[0];
        $permitId = (isset($spotUnitAndPermitIdArray[1]) && $spotUnitAndPermitIdArray[1] != '') ? $spotUnitAndPermitIdArray[1] : '';
        $lotName  = $spotUnitAndPermitIdArray[2];
        $isLotVirtual = $spotUnitAndPermitIdArray[3];
        $parkingPermit = $spotUnitAndPermitIdArray[4];

        return new ViewModel([
            'permitImage'       => $parkingPermit,
            'nextUrl'           => $nextUrl,
            'prevUrl'           => $prevUrl,
            'lotName'           => $lotName,
            'isLotVirtual'      => $isLotVirtual,
            'fromDate'          => $fromDate,
            'toDate'            => $toDate,
            'spotName'          => $spotName,
            'permitId'          => $permitId,
            'showWarning'       => $showWarning,
            'officePhoneNumber' => $officePhoneNumber,
            'permitCount'       => sizeof($parkingInfo)
        ]);
    }
}
