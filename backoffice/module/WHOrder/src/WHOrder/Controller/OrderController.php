<?php

namespace WHOrder\Controller;

use DDD\Service\Team\Usages\Procurement;
use DDD\Service\User;
use DDD\Service\WHOrder\Order;
use Finance\Form\ExpenseTicket;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Constants;
use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\ActionLogger\Logger;

use WHOrder\Form\InputFilter\OrderNewFilter;
use WHOrder\Form\OrderForm;
use WHOrder\Form\InputFilter\OrderFilter;

use DDD\Service\WHOrder\Order as OrderService;
use DDD\Service\Warehouse\Category as AssetsCategoryService;

use WHOrder\Form\OrderNewForm;
use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;


class OrderController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $authService
         * @var \DDD\Service\WHOrder\Order $orderService
         * @var \DDD\Service\User $userService
         * @var \DDD\Dao\User\UserManager $userDao
         */
        $authService  = $this->getServiceLocator()->get('library_backoffice_auth');
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $userService  = $this->getServiceLocator()->get('service_user');
        $userDao      = $this->getServiceLocator()->get('dao_user_user_manager');

        $statusShipping = $orderService->getStatusShippingForSelectize(OrderService::getStatusesShipping());

        // check roles
        $hasRole       = $authService->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT);
        $hasGlobalRole = $authService->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);

        if ($hasRole && !$hasGlobalRole) {
            $userId  = $authService->getIdentity()->id;
            $users[] = $userDao->findUserById($userId);
        } else {
            $users = iterator_to_array($userService->getUsersList(false, true));
        }

        // get by default available statuses for search
        $defaultStatuses = [];
        foreach ($statusShipping as $key => $status) {
            if ($status['id'] != Order::STATUS_CANCELED &&
                $status['id'] != Order::STATUS_RETURNED &&
                $status['id'] != Order::STATUS_RECEIVED)
            {
                $defaultStatuses[] = $status['id'];
            }
        }

        return [
            'users'           => $users,
            'statuses'        => OrderService::getStatusesByText(),
            'statusShipping'  => json_encode($statusShipping),
            'defaultStatuses' => $defaultStatuses,
            'hasRole'         => $hasRole,
            'hasGlobalRole'   => $hasGlobalRole
        ];
    }

    public function ajaxSearchOrdersAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $requestParams = $this->getRequest()->getPost();

            /**
             * @var \DDD\Service\WHOrder\Order $orderService
             */
            $orderService = $this->getServiceLocator()->get('service_wh_order_order');
            $result = $orderService->getDatatableData(
                $requestParams['iDisplayStart'],
                $requestParams['iDisplayLength'],
                $requestParams['iSortCol_0'],
                $requestParams['sSortDir_0'],
                $requestParams['sSearch'],
                [
                    'status'                   => $requestParams['status'],
                    'created_by'               => $requestParams['users'],
                    'status_shipping'          => $requestParams['status_shipping'],
                    'category_id'              => $requestParams['category'],
                    'location'                 => $requestParams['location'],
                    'supplier_id'              => $requestParams['supplier'],
                    'estimated_date_start'     => $requestParams['estimated_date_start'],
                    'estimated_date_end'       => $requestParams['estimated_date_end'],
                    'order_date'               => $requestParams['order_date'],
                    'order_title'              => $requestParams['name'],
                    'supplier_tracking_number' => $requestParams['stn'],
                    'received_date'            => $requestParams['received_date'],
                    'received_quantity'        => $requestParams['received_quantity'],
                ]
            );

            $result = [
                'iTotalRecords'        => $result['total'],
                'iTotalDisplayRecords' => $result['total'],
                'iDisplayStart'        => $requestParams['iDisplayStart'],
                'iDisplayLength'       => $requestParams['iDisplayLength'],
                'aaData'               => $result['data']
            ];
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'msg'    => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function editAction()
    {
        /**
         * @var Procurement $teamUsageProcurementService
         * @var \DDD\Service\WHOrder\Order $orderService
         * @var BackofficeAuthenticationService $authenticationService
         * @var \DDD\Service\MoneyAccount $moneyAccountService
         * @var \DDD\Dao\Finance\Expense\Expenses $poDao
         * @var User $usersService
         * @var Logger $loggerService
         */
        $teamUsageProcurementService = $this->getServiceLocator()->get('service_team_usages_procurement');
        $orderService                = $this->getServiceLocator()->get('service_wh_order_order');
        $authenticationService       = $this->getServiceLocator()->get('library_backoffice_auth');
        $moneyAccountService         = $this->getServiceLocator()->get('service_money_account');
        $usersService                = $this->getServiceLocator()->get('service_user');
        $poDao                       = $this->getServiceLocator()->get('dao_finance_expense_expenses');

        $request = $this->getRequest();
        $orderId = $this->params()->fromRoute('order_id', 0);

        $orderData   = $actionLogs = $orderOptions = [];
        $orderStatus = $orderService::STATUS_ORDER_NEW;

        $procurementTeams         = $teamUsageProcurementService->getTeamsByUsage();
        $hasPORol                 = $authenticationService->hasRole(Roles::ROLE_EXPENSE_MANAGEMENT);
        $hasOrderGlobalManagerRol = $authenticationService->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
        $userId                   = $authenticationService->getIdentity()->id;

        $moneyAccountList = $moneyAccountService->getUserMoneyAccountListByPosession($userId, $moneyAccountService::OPERATION_ADD_TRANSACTION);

        // edit mode
        if ($orderId) {
            $orderData = $orderService->getOrder($orderId);

            if (!$orderData) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('orders');
            }

            // check permission
            if (!$hasOrderGlobalManagerRol && $userId != $orderData->getCreatorId() && !$orderService->isYourTeamOrder($orderId, $userId)) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_VIEW_PERMISSION]);
                $this->redirect()->toRoute('orders');
            }
        }

        $orderOptions       = $orderService->getOptions($orderData);
        $orderTargetDetails = $orderOptions['orderTargetDetails'];
        $supplierDetails    = $orderOptions['supplierDetails'];
        $currencyList       = $orderOptions['currencyList'];
        $statusShipping     = $orderOptions['statusShipping'];

        $peopleList        = $usersService->getPeopleListWithManagersForSelect();
        $budgetHoldersList = $usersService->getBudgetHolderList();
        $itemForm          = new ExpenseTicket($budgetHoldersList, $peopleList, \Library\Finance\Process\Expense\Helper::TYPE_ORDER_EXPENSE);

        $form = new OrderForm($orderData, $orderTargetDetails, $supplierDetails, $currencyList);
        $form->setInputFilter(new OrderFilter());
        $form->prepare();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $saveOrder = $orderService->saveOrder($postData, $orderId);
                if ($saveOrder['status'] == 'success') {
                    Helper::setFlashMessage(['success' => $orderId ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
                    $this->redirect()->toRoute('orders/edit', ['controller' => 'warehouse_order', 'action' => 'edit', 'order_id' => $saveOrder['id']]);
                } else {
                    Helper::setFlashMessage(['error' => $saveOrder['msg']]);
                    $this->redirect()->toRoute('orders');
                }
            }
        } else {
            // edit mode
            if ($orderId) {
                if ($orderData) {
                    $orderStatus = $orderData->getStatus();
                    if (isset($orderService::getStatusesByText()[$orderStatus]) ) {
                        $orderStatusText = $orderService::getStatusesByText()[$orderStatus];
                    }

                    if (isset($orderOptions['objectData'])) {
                        $form->populateValues($orderOptions['objectData']);
                    }

                    $poId = $orderData->getPoId();
                    if (!is_null($orderData->getPoItemId())) {
                        /**
                         * @var \DDD\Service\Finance\Expense\ExpenseTicket $financeService
                         */
                        $financeService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
                        $poItemAttachment = $financeService->getItemAttachment($poId);
                    }

                    $created = $orderData->getUser() . ' ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($orderData->getDateCreated()));;
                    $viewRejectedButton = $orderStatus != OrderService::STATUS_ORDER_APPROVED && $orderData->getStatusShipping() == OrderService::STATUS_TO_BE_ORDERED;

                    $loggerService = $this->getServiceLocator()->get('ActionLogger');
                    $actionLogs = $loggerService->getDatatableData(Logger::MODULE_WH_ORDER, $orderId);
                } else {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    $this->redirect()->toRoute('orders');
                }
            }
        }

        // user managed POs
        $poDao->setEntity(new \ArrayObject());
        $userPOList = $poDao->getManagerPOs($userId);

        return new ViewModel([
            'form'                  => $form,
            'itemForm'              => $itemForm,
            'statusShipping'        => $statusShipping,
            'orderId'               => $orderId,
            'orderShippingStatuses' => OrderService::getStatusesShipping(),
            'historyData'           => json_encode($actionLogs),
            'orderStatus'           => $orderStatus,
            'isRejected'            => $orderStatus == OrderService::STATUS_ORDER_REJECTED,
            'orderStatusText'       => isset($orderStatusText) ? $orderStatusText : '',
            'hasPORol'              => $hasPORol,
            'poId'                  => isset($poId) ? $poId : 0,
            'created'               => isset($created) ? $created : '',
            'viewRejectedButton'    => isset($viewRejectedButton) && $viewRejectedButton ? true : false,
            'orderData'             => $orderData,
            'procurementTeams'      => $procurementTeams,
            'moneyAccountList'      => $moneyAccountList,
            'userPOList'            => $userPOList,
            'currentStatusShipping' => $orderData ? $orderData->getStatusShipping() : 0,
            'poItemAttachment'      => isset($poItemAttachment) ? $poItemAttachment : false
         ]);
    }

    /**
     * @return JsonModel
     */
    public function ajaxChangeManagerAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $this->getRequest()->getPost();

            if (is_numeric($postData['order_id']) && $postData['order_id'] > 0) {
                /**
                 * @var \DDD\Service\WHOrder\Order $orderService
                 */
                $orderService = $this->getServiceLocator()->get('service_wh_order_order');

                $saveResult = $orderService->saveOrder(
                    ['team_id' => $postData['team_id']],
                    $postData['order_id'],
                    false
                );

                if ($saveResult['status'] == 'success') {
                    $result = [
                        'status'    => 'success',
                        'msg'       => TextConstants::SUCCESS_UPDATE
                    ];
                }
            }
        } catch (\Exception $e) {
            $result['msg'] = $result['msg'] . PHP_EOL . $e->getMessage();
        }

        return new JsonModel($result);
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function addAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $authService
         * @var \DDD\Service\WHOrder\Order $orderService
         */
        $authService = $this->getServiceLocator()->get('library_backoffice_auth');
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request = $this->getRequest();

        $loggedInUserFullName = $authService->getIdentity()->firstname . ' ' . $authService->getIdentity()->lastname;

        $form = new OrderNewForm();
        $form->setInputFilter(new OrderNewFilter());
        $form->prepare();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $saveOrder = $orderService->saveOrderMultiple($postData);
                Helper::setFlashMessage([$saveOrder['status'] => $saveOrder['msg']]);
                return $this->redirect()->toRoute('universal-dashboard');
            } else {
                Helper::setFlashMessage(['error' => 'Entered parameters are not valid']);
            }
        }

        return new ViewModel([
            'form' => $form,
            'loggedInUserFullName' => $loggedInUserFullName
        ]);
    }


    public function rejectAction()
    {
        /**
         * @var \DDD\Service\WHOrder\Order $orderService
         * @var BackofficeAuthenticationService $authenticationService
         * @var \DDD\Dao\WHOrder\Order $orderDao
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $orderId = $this->params()->fromRoute('order_id', 0);
        $hasOrderGlobalManagerRol = $authenticationService->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
        $userId = $authenticationService->getIdentity()->id;
        $orderData = $orderDao->forRejectOrder($orderId);

        // check permission
        if (!$orderData ||
            (!$hasOrderGlobalManagerRol &&  $userId != $orderData['creator_id'] && !$orderService->isYourTeamOrder($orderId, $userId))) {
            Helper::setFlashMessage(['error' => TextConstants::NO_PERMISSION]);
            $this->redirect()->toRoute('orders');
            return;
        }

        if ($orderId > 0) {
            $orderService->reject($orderId);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_REJECTED]);
        } else {
            Helper::setFlashMessage(['error' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('orders');
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetOrderLocationsAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Category $assetsCategoryService
         * @var \DDD\Dao\Warehouse\Storage $storageDao
         * @var \DDD\Dao\Apartment\General $apartmentDao
         * @var \DDD\Dao\Office\OfficeManager $officeDao
         */
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $query = $this->getRequest()->getPost('query');

            $result      = [];
            $storageDao  = $this->getServiceLocator()->get('dao_warehouse_storage');
            $storageList = $storageDao->searchStorageByName($query, true);

            if ($storageList) {
                foreach ($storageList as $storage) {
                    $result[] = [
                        'id'    => OrderService::ORDER_LOCATION_TYPE_STORAGE . '_' . $storage->getId(),
                        'info'  => $storage->getCityName(),
                        'label' => 'storage',
                        'text'  => $storage->getName(),
                        'type'  => OrderService::ORDER_LOCATION_TYPE_STORAGE
                    ];
                }
            }

        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetOrderCategoriesAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Category $assetsCategoryService
         */
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $assetsCategoryService = $this->serviceLocator->get('service_warehouse_category');
            $assetsCategoriesData = $assetsCategoryService->getCategories([
                AssetsCategoryService::CATEGORY_TYPE_CONSUMABLE,
                AssetsCategoryService::CATEGORY_TYPE_VALUABLE,
            ]);

            $result = [];
            foreach ($assetsCategoriesData as $category) {
                $result[] = [
                    'id'        => $category->getId(),
                    'title'     => $category->getName(),
                    'type'      => AssetsCategoryService::$categoryTypes[$category->getType()],
                    'type_id'   => $category->getType(),
                ];
            }

            if (!empty($result)) {
                usort($result, function($a, $b) {
                    return $a['type_id'] - $b['type_id'];
                });
            }
        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetOrderSuppliersAction()
    {
        /**
         * @var \DDD\Dao\Finance\Supplier $supplierDao
         */
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $query = $this->getRequest()->getPost('query');

            $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');

            $suppliersData = $supplierDao->getAllSuppliers($query, true, true);

            $result = [];
            foreach ($suppliersData as $supplier) {
                $result[] = [
                    'id'    => $supplier['id'],
                    'name'  => $supplier['name'],
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxCreatePoItemAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\WHOrder\Order $orderService
         */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request = $this->getRequest();
        $isValid = true;
        $output = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                if ($request->getPost('order_id')) {
                    $isAttachPoRequest = $request->getPost('is_attach_po', 0);

                    if (!$isAttachPoRequest) {
                        $saveOrder = $orderService->saveOrder($request->getPost(), $request->getPost('order_id'));
                        if (!$saveOrder['id'] || ($saveOrder['status'] != 'success')) {
                            $isValid = false;
                        }
                    }

                    if ($isValid) {
                        $itemOutput = $orderService->createPOItem($request->getPost());
                        if ($itemOutput !== false) {
                            $output = $itemOutput;
                        }
                    } else {
                        $output['msg'] = TextConstants::BAD_REQUEST;
                    }
                }
            } else {
                $output['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $ex) {
            // do nothing
        }
        Helper::setFlashMessage([$output['status'] => $output['msg']]);
        return new JsonModel($output);
    }

    /**
     * Request money for order
     *
     * @return JsonModel
     */
    public function ajaxRequestAdvanceAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\WHOrder\Order $orderService
         */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request = $this->getRequest();
        $output = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {

                if ($request->getPost('order_id') &&
                    $request->getPost('money_account_name') &&
                    $request->getPost('money_amount') &&
                    $request->getPost('currency_iso'))
                {
                    $output = $orderService->setRequestMoney($request->getPost('order_id'),
                        $request->getPost('money_account_name'),
                        $request->getPost('money_amount'),
                        $request->getPost('currency_iso'));

                }
            } else {
                $output['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        Helper::setFlashMessage([$output['status'] => $output['msg']]);
        return new JsonModel($output);
    }

    public function ajaxCreateItemTransactionAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\WHOrder\Order $orderService
         */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $moneyTransactionAccountId = $orderService->createPOItemTransaction($request->getPost()->data, $request->getFiles());
                if ($moneyTransactionAccountId) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_CREATED,
                        'moneyTransactionAccountId' => $moneyTransactionAccountId
                    ];
                }
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxCreateRefundPoItemAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\WHOrder\Order $orderService
         */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request      = $this->getRequest();
        $output       = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];
        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                if ($request->getPost('orderId')) {
                    $itemOutput = $orderService->createRefundPOItem($request->getPost());
                    if ($itemOutput !== false) {
                        if (!$itemOutput['hasTransaction']) {
                        }

                        $output = $itemOutput;
                    }
                }
            } else {
                $output['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($output);
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetItemAccountDetailsAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\WHOrder\Order $orderService
         */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request      = $this->getRequest();
        $output       = [
            'status' => 'success',
            'msg'    => '',
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                if ($request->getPost('orderId')) {
                    $itemOutput = $orderService->getItemDetails($request->getPost());

                    $output = $itemOutput;
                }
            } else {
                $output       = [
                    'status' => 'error',
                    'msg'    => TextConstants::SERVER_ERROR,
                ];
            }
        } catch (\Exception $ex) {
            $output       = [
                'status' => 'error',
                'msg'    => TextConstants::SERVER_ERROR,
            ];
        }
        return new JsonModel($output);
    }
}
