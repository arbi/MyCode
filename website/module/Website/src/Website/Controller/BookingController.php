<?php

namespace Website\Controller;

use DDD\Dao\User\UserManager;
use DDD\Service\Apartment\Main as ApartmentMainService;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\Partners;
use DDD\Service\Website\Apartment;
use DDD\Service\Partners as PartnersService;
use DDD\Service\Team\Team as TeamService;

use League\Flysystem\Exception;
use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

use Website\Form\Booking as BookingForm;
use Website\Form\CCUpdate;
use Website\Form\InputFilter\BookingFilter;
use Website\Form\InputFilter\CCUpdateFilter;

use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Constants\DomainConstants;
use Library\Controller\WebsiteBase;

class BookingController extends WebsiteBase
{
    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $domain  = '//' . DomainConstants::WS_DOMAIN_NAME;

        try {
            if (!isset($_SERVER['HTTPS'])) {
                return $this->redirect()->toUrl($domain);
            }

            // Data for reservation
            $data = $request->getQuery();

            /**
             * @var \DDD\Service\Website\Booking $bookingService
             * @var \DDD\Service\Website\Apartment $apartmentService
             */
            $bookingService   = $this->getServiceLocator()->get('service_website_booking');
            $apartmentService = $this->getServiceLocator()->get('service_website_apartment');

            $result = $bookingService->bookingReservationData($data);
            if ($result['status'] != 'success') {
                $query = [
                    'city=' . $data['city'],
                    'guest=' . $data['guest'],
                    'arrival=' . Helper::dateForUrl($data['arrival']),
                    'departure=' . Helper::dateForUrl($data['departure'])
                ];

                if ($result['status'] == 'not_av') {
                    $query[] = 'no_av=yes';
                }

                $redirectUrl = $domain . '/search?' . implode('&', $query);

                return $this->redirect()->toUrl($redirectUrl);
            }

            if ($result['status'] == 'success' && !$result['result']) {
                return $this->redirect()->toUrl($domain);
            }

            $options   = $bookingService->getOptions();
            $form      = new BookingForm($options);
            $apartels  = $apartmentService->getApartelsByApartmentId($data['apartment_id']);
            $apartelId = isset ($data['apartel_id']) && (int)$data['apartel_id'] > 0 ? (int)$data['apartel_id'] : 0;

            // Post data
            $error = $messages = '';

            if ($request->isPost()) {
                $data = $request->getPost();
                $form->setInputFilter(new BookingFilter($data, $form->getPostalCodeStatus($data['country'], $options['countris'])));
                $form->setData($data);

                if ($form->isValid()) {
                    $vData         = $form->getData();
                    $resultUserPay = $bookingService->bookingProcess($vData);

                    if ($resultUserPay['status'] != 'success') {
                        return $this->redirect()->toUrl($domain);
                    }

                    return $this->redirect()->toRoute('booking', ['action' => 'thank-you']);
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

                    $error = $messages;
                }
            }

            $checkDiscountUrl = $this->url()->fromRoute('booking', ['action' => 'ajax-check-discount']);

            $this->layout()->setVariable('footerVisibility', 'visible');

            $isAffiliateChooser = false;

            if (isset($_COOKIE['backoffice_user'])) {
                $backofficeUserId = $_COOKIE['backoffice_user'];

                /**
                 * @var UserManager $userManagerDao
                 */
                $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
                $userData       = $userManagerDao->getUserById($backofficeUserId);

                $backofficeUserDepartmentId = $userData->getDepartmentId();

                if (in_array($backofficeUserDepartmentId, TeamService::$teamsThatAllowedToChooseAffiliateForWebsiteReservations)) {
                    $isAffiliateChooser = true;
                }

                $userName = $userData->getFirstname() . ' ' . $userData->getLastname();
            } else {
                $userName = false;
            }

            return new ViewModel([
                'general'            => $result['result'],
                'options'            => $options,
                'bookingForm'        => $form,
                'error'              => $error,
                'apartels'           => $apartels,
                'zipCodeStatusJson'  => json_encode($form->getCountryPostalCodes($options['countris'])),
                'checkDiscountUrl'   => $checkDiscountUrl,
                'ginosikDiscountId'  => PartnersService::GINOSI_EMPLOYEE,
                'backofficeUserName' => $userName,
                'isAffiliateChooser' => $isAffiliateChooser,
                'apartelId'          => $apartelId
            ]);
        } catch (\Exception $e) {
            return $this->redirect()->toUrl($domain);
        }
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function thankYouAction()
    {
        try {
            $reservationSession = Helper::getSessionContainer('booking');

            if (!$reservationSession->offsetExists('reservation') || !$reservationSession->offsetExists('tankyou') || !isset($_SERVER['HTTPS'])) {
                return $this->redirect()->toRoute('home');
            }

            /**
             * @var \DDD\Service\Website\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_website_booking');

            $paymentDetails                              = $bookingService->changePaymentDetailsByGuestCurrency($reservationSession->tankyou['reservation_id']);
            $reservationSession->reservation['payments'] = $paymentDetails;

            $this->layout()->userTrackingInfo = [
                'partner_id' => $reservationSession->tankyou['partner_id'],
            ];

            return new ViewModel([
                'thankYouPageData' => $reservationSession->tankyou,
                'reservation'      => $reservationSession->reservation
            ]);
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('home');
        }
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function updateCcDetailsAction()
    {
        /**
         * @var Request $request
         */
        $request            = $this->getRequest();
        $domain             = '//' . DomainConstants::WS_DOMAIN_NAME;
        $viewSuccessMessage = false;

        try {

            //data for reservation
            $data = $request->getQuery();

            /**
             * @var \DDD\Service\Website\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_website_booking');

            $reservationData = $bookingService->bookingDataByCCPassword($data);
            $form   = new CCUpdate($this->getServiceLocator(), $reservationData);

            if (!$reservationData) {
                return $this->redirect()->toUrl($domain);
            }

            //post data
            $error = $messages = '';
            $form->setInputFilter(new CCUpdateFilter());

            if ($request->isPost()) {
                $filter = $form->getInputFilter();
                $form->setInputFilter($filter);
                $data = $request->getPost();
                $form->setData($data);

                if ($form->isValid()) {
                    $vData      = $form->getData();
                    $updateData = $bookingService->updateCCData($vData, $reservationData);

                    if (!$updateData) {
                        return $this->redirect()->toUrl($domain);
                    }

                    $viewSuccessMessage = true;
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

                    $error = $messages;
                }
            }

            $this->layout()->setVariable('footerVisibility', 'visible');

            return new ViewModel([
                'general'            => $reservationData,
                'ccUpdateForm'       => $form,
                'error'              => $error,
                'viewSuccessMessage' => $viewSuccessMessage
            ]);
        } catch (\Exception $e) {
            return $this->redirect()->toUrl($domain);
        }
    }

    /**
     * @return \Zend\Http\Response|JsonModel
     */
    public function ajaxCheckDiscountAction()
    {
        try {
            $result['status'] = 'success';

            $request = $this->getRequest();

            if (!$request->isXmlHttpRequest()) {
                return $this->redirect()->toRoute('home');
            }

            $data         = $request->getPost();
            $prePartnerId = 0;

            if (isset($_COOKIE['backoffice_user'])) {
                $visitor = new Container('visitor');
                if (isset($visitor->partnerId)) {
                    $prePartnerId = $visitor->partnerId;
                }

                $userDao  = $this->getServiceLocator()->get('dao_user_user_manager');
                $userData = $userDao->getUserById($_COOKIE['backoffice_user']);

                if (($userData->getEmail() != $data['email'] && $userData->getAlt_email() != $data['email'])
                    && ($data['aff_id'] != $visitor->partnerId)
                    && isset($data['aff_id'])
                ) {
                    $visitor->partnerId   = $data['aff_id'];
                    $visitor->partnerName = $data['partner_name'];
                }
                if ($userData->getEmail() == $data['email']
                    || $userData->getAlt_email() == $data['email']
                ) {
                    $data['aff_id']       = PartnersService::GINOSI_EMPLOYEE;
                    $visitor->partnerId   = PartnersService::GINOSI_EMPLOYEE;
                    $visitor->partnerName = PartnersService::GINOSI_EMPLOYEE_NAME;
                } elseif (!empty($data['email'])) {
                    if (isset($visitor->partnerId)
                        && ($visitor->partnerId == PartnersService::GINOSI_EMPLOYEE)
                    ) {
                        $visitor->partnerId    = PartnersService::GINOSI_CONTACT_CENTER;
                        $visitor->partnerName  = PartnersService::GINOSI_PARTNER_STAFF_NAME;
                        $result['partnerName'] = PartnersService::GINOSI_PARTNER_STAFF_NAME;
                        $result['affId']       = PartnersService::GINOSI_CONTACT_CENTER;
                    } elseif (isset($visitor->partnerId)
                        && ($visitor->partnerId != PartnersService::GINOSI_EMPLOYEE)
                    ) {
                        $result['partnerName'] = $visitor->partnerName;
                        $result['affId']       = $visitor->partnerId;
                    }
                }
            }

            /** @var \DDD\Service\Booking\BookingTicket $bookingThicketService */
            $bookingThicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');

            $validData = $bookingThicketService->validateAndCheckDiscountData($data);
            if ($validData['valid'] && ceil($validData['discount_value'])) {
                $discountAmount          = number_format($data['accommodation_price'] * (float)$validData['discount_value'] / 100, 2, '.', '');
                $totalAmountWithDiscount = number_format($data['total_amount'] - $discountAmount, 2, '.', '');

                $result = [
                    'status'                     => 'success',
                    'discount_amount'            => $discountAmount,
                    'total_amount_with_discount' => $totalAmountWithDiscount,
                    'affId'                      => isset($validData['aff_id']) ? $validData['aff_id'] : 0,
                    'partnerName'                => isset($validData['partner_name']) ? $validData['partner_name'] : 0,
                    'pre_partner_id'             => $prePartnerId
                ];
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR;
        }

        return new JsonModel($result);

    }

    public function ajaxCheckGinosikEmailAction()
    {
        try {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR_EMAIL_NOT_FOUND;

            $request = $this->getRequest();

            if (!$request->isXmlHttpRequest()) {
                return $this->redirect()->toRoute('home');
            }

            $email = $request->getPost('email');

            $userService = $this->getServiceLocator()->get('service_user');

            if (isset($_COOKIE['backoffice_user'])) {
                $response = $userService->checkGinosikEmail((int)$_COOKIE['backoffice_user'], $email);
                $visitor  = new Container('visitor');

                if ($response) {
                    $result['status']      = 'success';
                    $result['result']      = TextConstants::SUCCESS_APPROVED;
                    $result['partnerName'] = PartnersService::GINOSI_EMPLOYEE_NAME;

                    $visitor->partnerId   = PartnersService::GINOSI_EMPLOYEE;
                    $visitor->partnerName = PartnersService::GINOSI_EMPLOYEE_NAME;
                } elseif (isset($visitor->partnerId)
                    && ($visitor->partnerId == PartnersService::GINOSI_EMPLOYEE)
                ) {
                    $visitor->partnerId    = PartnersService::GINOSI_CONTACT_CENTER;
                    $visitor->partnerName  = PartnersService::GINOSI_PARTNER_STAFF_NAME;
                    $result['partnerName'] = PartnersService::GINOSI_PARTNER_STAFF_NAME;

                }
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR_EMAIL_NOT_FOUND;
        }

        return new JsonModel($result);
    }
}
