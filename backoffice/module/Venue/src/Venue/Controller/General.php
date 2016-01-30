<?php

namespace Venue\Controller;

use DDD\Service\Venue\Venue;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Venue\Form\InputFilter\VenueFilter;
use Venue\Form\SearchVenueForm;
use Venue\Form\VenueForm;
use Venue\Form\VenueItemsForm;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use DDD\Service\Venue\Charges as VenueCharges;

/**
 * Class General
 *
 * @package Venue\Controller
 * @author  Harut Grigoryan
 */
class General extends ControllerBase
{
    /**
     * Venues list
     *
     * @return array
     */
    public function indexAction()
    {
        // get search form
        $searchForm = $this->getSearchForm();
        $searchForm->prepare();

        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $isVenueManager = $auth->hasRole(Roles::ROLE_VENUE_MANAGER);

        return [
            'searchForm'     => $searchForm,
            'ajaxSourceUrl'  => $this->url()->fromRoute('venue', ['action' => 'ajax-get-venue-list']),
            'isVenueManager' => $isVenueManager
        ];
    }

    /**
     * Edit Venue
     *
     * @return array
     */
    public function editAction()
    {
        $venueId = $this->params()->fromRoute('id', 0);
        $form    = $this->getForm();

        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $isVenueManager     = $auth->hasRole(Roles::ROLE_VENUE_MANAGER);
        $isChargeManager    = $auth->hasRole(Roles::ROLE_VENUE_CHARGE_MANAGER);

        if ($venueId) {
            /**
             * @var \DDD\Service\Venue\Venue $venueService
             */
            $venueService = $this->getServiceLocator()->get('service_venue_venue');
            $venue = $venueService->getVenueById($venueId);

            if (!$venue) {
                $this->redirect()->toRoute('venue', ['action' => 'index']);
            }

            $form->get('currencyId')->setAttribute('disabled', true);
            $form->bind($venue);

            $chargesData = $this->prepareVenueCharges($venueId);

            $itemsForm = $this->getItemsForm();
        } else {
            $venue = null;
            $chargesData = [];
            $itemsForm = null;
        }

        $form->prepare();

        /**
         * @var \DDD\Dao\Venue\Items $itemsDao
         */
        $itemsDao = $this->getServiceLocator()->get('dao_venue_items');
        $itemsData = $itemsDao->getItemsByVenueId($venueId);

        return [
            'form'              => $form,
            'venueId'           => $venueId,
            'venue'             => $venue,
            'chargesData'       => json_encode($chargesData),
            'itemsForm'         => $itemsForm,
            'venueItems'        => $itemsData,
            'isVenueManager'    => $isVenueManager,
            'isChargeManager'   => $isChargeManager
        ];
    }

    /**
     * Delete Venue
     *
     * @return array
     */
    public function deleteAction()
    {
        $venueId = $this->params()->fromRoute('id', 0);

        if ($venueId) {
            /**
             * @var \DDD\Dao\Venue\Venue $venueDao
             */
            $venueDao = $this->getServiceLocator()->get('dao_venue_venue');
            $venueDao->save([
                'status' => Venue::VENUE_STATUS_INACTIVE
            ], [
                'id' => $venueId
            ]);

            Helper::setFlashMessage(["success" => "Successfully Deactivated."]);
        }

        $this->redirect()->toRoute('venue', ['action' => 'index']);
    }

    /**
     * Get Venue list for datatable
     *
     * @return JsonModel
     */
    public function ajaxGetVenueListAction()
    {
        /**
         * @var \DDD\Service\Venue\Venue $venueService
         * @var \DDD\Service\User
         */
        $venueService   = $this->getServiceLocator()->get('service_venue_venue');
        $userService    = $this->getServiceLocator()->get('service_user');

        $userListById  = $userService->getPeopleListById();
        $countriesById = $userService->getUserCountriesById();

        // get query parameters and reservations data
        $venues = $venueService->getVenuesByParams([
            'acceptOrders' => $this->params()->fromQuery('acceptOrders', 0),
            'cityId'       => $this->params()->fromQuery('cityId', 0),
            'managerId'    => $this->params()->fromQuery('managerId', 0),
            'cashierId'    => $this->params()->fromQuery('cashierId', 0),
        ]);

        $data = [];
        foreach ($venues as $key => $venue) {
            if ($venue->getAcceptOrders() == Venue::VENUE_ACCEPT_ORDERS_ON) {
                $data[$key][] = '<span class="label label-success">On</span>';
            } elseif ($venue->getAcceptOrders() == Venue::VENUE_ACCEPT_ORDERS_OFF) {
                $data[$key][] = '<span class="label label-danger">Off</span>';
            } else {
                $data[$key][] = '<span class="label label-primary">N/A</span>';
            }

            $data[$key][] = $venue->getName();
            $data[$key][] = $countriesById[$venue->getCityId()]->getCity();

            $data[$key][] = (isset($userListById[$venue->getManagerId()]))
                ? $userListById[$venue->getManagerId()]['firstname'] . ' ' . $userListById[$venue->getManagerId()]['lastname']
                : '';
            $data[$key][] = (isset($userListById[$venue->getCashierId()]))
                ? $userListById[$venue->getCashierId()]['firstname'] . ' ' . $userListById[$venue->getCashierId()]['lastname']
                : '';

            // special for datatable edit link
            $data[$key][] = $venue->getId();
        }

        return new JsonModel(
            [
                'iTotalRecords' => sizeof($data),
                "aaData"        => $data
            ]
        );
    }

    /**
     * Save Venue
     *
     * @return JsonModel
     */
    public function ajaxSaveAction()
    {
        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $postData = $request->getPost();

            try {
                $form        = $this->getForm($postData->get('id'));
                $inputFilter = new VenueFilter();

                $form->setInputFilter($inputFilter->getInputFilter());

                if ($postData->get('id')) {
                    $form->getInputFilter()->remove('currencyId');
                }

                $form->setData($postData);
                $form->prepare();

                if ($form->isValid()) {
                    $validData = $form->getData();
                    $where = [];
                    $data  = [
                        'name'              => $validData['name'],
                        'city_id'           => $validData['cityId'],
                        'threshold_price'   => $validData['thresholdPrice'],
                        'discount_price'    => $validData['discountPrice'],
                        'perday_max_price'  => $validData['perdayMaxPrice'],
                        'perday_min_price'  => $validData['perdayMinPrice'],
                        'status'            => $validData['status'],
                        'accept_orders'     => $validData['acceptOrders'],
                        'manager_id'        => $validData['managerId'],
                        'cashier_id'        => $validData['cashierId'],
                        'account_id'        => $validData['account_id'],
                    ];

                    if ($postData->get('id')) {
                        $where['id']           = $postData->get('id');
                    } else {
                        $data['creation_date']  = date('Y-m-d H:i:s');
                        $data['status']         = Venue::VENUE_STATUS_ACTIVE;
                        $data['currency_id']    = $validData['currencyId'];
                        $data['type']          = $validData['type'];
                    }

                    /**
                     * @var \DDD\Dao\Venue\Venue $venueDao;
                     */
                    $venueDao       = $this->getServiceLocator()->get('dao_venue_venue');
                    $lastInsertedId = $venueDao->save($data, $where);

                    if ($postData->get('id')) {
                        $lastInsertedId = $postData->get('id');
                    }

                    Helper::setFlashMessage(["success" => "Successfully updated."]);

                    return new JsonModel([
                        "status"  => "success",
                        "msg" => "Successfully updated.",
                        "url"     => $this->url()->fromRoute('venue', ['action' => 'edit', 'id' => $lastInsertedId])
                    ]);
                } else {
                    $result['msg'] = $this->parseFormMessage($form);
                }
            } catch (\Exception $e) {
                $result['msg'] = $e->getMessage();
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function ajaxGetSuppliersAction()
    {
        /**
         * @var \DDD\Service\Finance\TransactionAccount $accountService
         */
        $request = $this->getRequest();
        $accountService = $this->getServiceLocator()->get('service_finance_transaction_account');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $accounts = $accountService->getAccountsByAutocomplete($request->getPost('q'));

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $accounts,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     * Get Venue Form
     *
     * @return bool|VenueForm
     */
    private function getForm()
    {
        /**
         * @var \DDD\Service\User
         * @var \DDD\Service\Currency\Currency
         * @var \DDD\Dao\User\UserManager
         */
        $userService     = $this->getServiceLocator()->get('service_user');
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $userManagerDao  = $this->getServiceLocator()->get('dao_user_user_manager');

        $userList   = $userService->getPeopleList();
        $currencies = $currencyService->getSimpleCurrencyList();
        $cities     = $userManagerDao->getUsersCountries();

        return new VenueForm($name = 'venue-form', $userList, $currencies, $cities);
    }

    /**
     * Get Venue Form
     *
     * @return bool|VenueForm
     */
    private function getSearchForm()
    {
        /**
         * @var \DDD\Service\User
         * @var \DDD\Service\Currency\Currency
         * @var \DDD\Dao\User\UserManager $userManagerDao
         */
        $userService     = $this->getServiceLocator()->get('service_user');
        $userManagerDao  = $this->getServiceLocator()->get('dao_user_user_manager');

        $userList   = $userService->getPeopleList();
        $cities     = $userManagerDao->getUsersCountries();

        return new SearchVenueForm($name = 'search_venue', $userList, $cities);
    }

    private function prepareVenueCharges($venueId = 0)
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Dao\Venue\Charges $venueChargesService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $venueChargesService = $this->getServiceLocator()->get('dao_venue_charges');

        $venueCharges = $venueChargesService->getChargesByVenueId($venueId, false);

        $isVenueManager     = $auth->hasRole(Roles::ROLE_VENUE_MANAGER);
        $isChargeManager    = $auth->hasRole(Roles::ROLE_VENUE_CHARGE_MANAGER);

        $chargesData = [];
        foreach ($venueCharges as $charge) {
            /**
             * @var \DDD\Domain\Venue\Charges $charge
             * @var \DDD\Dao\User\UserManager $userDao
             */
            $userDao = $this->getServiceLocator()->get('dao_user_user_manager');

            $creatorData = $userDao->getUserById($charge->getCreatorId(), true, [
                'firstname', 'lastname'
            ]);

            $chargedUserData = $userDao->getUserById($charge->getChargedUserId(), true, [
                'firstname', 'lastname'
            ]);

            switch ($charge->getStatus()) {
                case VenueCharges::CHARGE_STATUS_NEW:
                    $status = '<span class="label label-success">'
                        . VenueCharges::getChargeStatuses()[$charge->getStatus()]
                        . '</span>';
                    break;
                case VenueCharges::CHARGE_STATUS_TRANSFERRED:
                    $status = '<span class="label label-primary">'
                        . VenueCharges::getChargeStatuses()[$charge->getStatus()]
                        . '</span>';
                    break;
                default:
                    $status = '<span class="label label-default">'
                        . VenueCharges::getChargeStatuses()[$charge->getStatus()]
                        . '</span>';
            }

            switch ($charge->getOrderStatus()) {
                case VenueCharges::ORDER_STATUS_NEW:
                    $orderStatus = '<span class="label label-success">'
                        . VenueCharges::getChargeOrderStatuses()[$charge->getOrderStatus()]
                        . '</span>';
                    break;
                case VenueCharges::ORDER_STATUS_PROCESSING:
                    $orderStatus = '<span class="label label-dark-green">'
                        . VenueCharges::getChargeOrderStatuses()[$charge->getOrderStatus()]
                        . '</span>';
                    break;
                case VenueCharges::ORDER_STATUS_DONE:
                    $orderStatus = '<span class="label label-info">'
                        . VenueCharges::getChargeOrderStatuses()[$charge->getOrderStatus()]
                        . '</span>';
                    break;
                case VenueCharges::ORDER_STATUS_VERIFIED:
                    $orderStatus = '<span class="label label-primary">'
                        . VenueCharges::getChargeOrderStatuses()[$charge->getOrderStatus()]
                        . '</span>';
                    break;
                default:
                    $orderStatus = '<span class="label label-default">'
                        . VenueCharges::getChargeOrderStatuses()[$charge->getOrderStatus()]
                        . '</span>';
            }

            $editButton = ($isVenueManager || $isChargeManager)
                ? '<a href="'
                . $this->url()->fromRoute('venue-charges', ['action' => 'edit', 'id' => $charge->getId()])
                . '" class="btn btn-xs btn-primary" target="_blank">Edit</a>'
                : '';

            array_push($chargesData, [
                $status,
                $orderStatus,
                $creatorData->getFirstName() . ' ' . $creatorData->getLastName(),
                $chargedUserData->getFirstName() . ' ' . $chargedUserData->getLastName(),
                Helper::truncateNotBreakingHtmlTags($charge->getDescription(), 200),
                $charge->getAmount(),
                $editButton
            ]);
        }

        return $chargesData;
    }

    private function getItemsForm()
    {
        return new VenueItemsForm();
    }

    /**
     * @param \Zend\Form\Form $form
     * @return bool|string
     */
    private function parseFormMessage($form)
    {
        if (is_array($form->getMessages())) {
            $errorMessage = '';

            foreach ($form->getMessages() as $title => $values) {
                $formElement = $form->getElements();
                $errorMessage .= $formElement[$title]->getLabel() . PHP_EOL;

                if (is_array($values)) {
                    foreach ($values as $value) {
                        $errorMessage .= '<li>' . $value . '</li>';
                    }
                } else {
                    $errorMessage .= $values;
                }
            }

            return $errorMessage;
        } elseif(is_string($form->getMessages())) {
            return $form->getMessages();
        } else {
            return false;
        }
    }
}
