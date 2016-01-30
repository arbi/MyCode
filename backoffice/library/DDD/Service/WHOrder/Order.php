<?php

namespace DDD\Service\WHOrder;

use DDD\Dao\Finance\Expense\ExpenseItem;
use DDD\Service\Finance\Expense\ExpenseTicket;
use DDD\Service\ServiceBase;
use DDD\Service\Team\Team;
use DDD\Service\Team\Usages\Procurement;
use DDD\Service\Finance\Expense\ExpenseCosts;
use DDD\Dao\Finance\MoneyAccount;
use Library\ActionLogger\Logger;
use Library\Constants\DbTables;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Constants\Constants;
use DDD\Service\Warehouse\Category as AssetsCategory;
use Zend\Stdlib\ParametersInterface;
use Zend\Db\Sql\Where;

class Order extends ServiceBase
{
    const ORDER_LOCATION_TYPE_APARTMENT = 1;
    const ORDER_LOCATION_TYPE_STORAGE   = 2;
    const ORDER_LOCATION_TYPE_OFFICE    = 3;
    const ORDER_LOCATION_TYPE_BUILDING  = 4;

    const ORDER_QUANTITY_TYPE_PIECE   = 1;
    const ORDER_QUANTITY_TYPE_PACK    = 2;
    const ORDER_QUANTITY_TYPE_PALETTE = 3;

    const STATUS_ORDER_NEW = 0;
    const STATUS_ORDER_AWAITING_APPROVAL = 1;
    const STATUS_ORDER_APPROVED = 2;
    const STATUS_ORDER_REJECTED = 3;

    const STATUS_TO_BE_ORDERED      = 1;
    const STATUS_ORDERED            = 2;
    const STATUS_CANCELED           = 3;
    const STATUS_SHIPPED            = 4;
    const STATUS_DELIVERED          = 5;
    const STATUS_RECEIVED           = 6;
    const STATUS_PARTIALLY_RECEIVED = 10;
    const STATUS_ISSUE              = 7;
    const STATUS_RETURNED           = 8;
    const STATUS_REFUNDED           = 9;

    const STATUS_IS_ARCHIVE         = 1;

    protected $orderDao = false;

    /**
     * @return array
     */
    static function getStatusesByText()
    {
        return [
            self::STATUS_ORDER_NEW               => ['New', 'label-primary'],
            self::STATUS_ORDER_AWAITING_APPROVAL => ['Awaiting Approval', 'label-warning'],
            self::STATUS_ORDER_APPROVED          => ['Approved', 'label-success'],
            self::STATUS_ORDER_REJECTED          => ['Rejected', 'label-danger'],
        ];
    }

    public static function getLabelForTargetType($targetType)
    {
        switch ($targetType)
        {
            case self::ORDER_LOCATION_TYPE_APARTMENT:
                $label = '<span class="label label-success" title="Apartment">Apartment</span> ';
                break;
            case self::ORDER_LOCATION_TYPE_STORAGE:
                $label = '<span class="label label-primary" title="Storage">Storage</span> ';
                break;
            case self::ORDER_LOCATION_TYPE_OFFICE:
                $label = '<span class="label label-info" title="Office">Office</span> ';
                break;
            case self::ORDER_LOCATION_TYPE_BUILDING:
                $label = '<span class="label label-warning" title="Building">Building</span> ';
                break;
        }
        return $label;
    }

    /**
     * @return array
     */
    static function getStatusesShipping()
    {
        return [
            self::STATUS_TO_BE_ORDERED      => 'Ready to Order',
            self::STATUS_ORDERED            => 'Ordered',
            self::STATUS_CANCELED           => 'Canceled',
            self::STATUS_SHIPPED            => 'Shipped',
            self::STATUS_DELIVERED          => 'Delivered',
            self::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
            self::STATUS_RECEIVED           => 'Received',
            self::STATUS_ISSUE              => 'Issue',
            self::STATUS_RETURNED           => 'Returned',
            self::STATUS_REFUNDED           => 'Refunded',
        ];
    }

    /**
     * @return array
     */
    static function getShortShippingStatuses()
    {
        return [
            self::STATUS_TO_BE_ORDERED      => 'RTO',
            self::STATUS_ORDERED            => 'ORD',
            self::STATUS_CANCELED           => 'CXL',
            self::STATUS_SHIPPED            => 'SHP',
            self::STATUS_DELIVERED          => 'DLD',
            self::STATUS_RECEIVED           => 'RCD',
            self::STATUS_PARTIALLY_RECEIVED => 'PRD',
            self::STATUS_ISSUE              => 'ISS',
            self::STATUS_RETURNED           => 'RTD',
            self::STATUS_REFUNDED           => 'RFD',
        ];
    }

    /**
     * @return array
     */
    static function getStatusesColor()
    {
        return [
            self::STATUS_TO_BE_ORDERED      => 'success',
            self::STATUS_ORDERED            => 'success',
            self::STATUS_SHIPPED            => 'info',
            self::STATUS_DELIVERED          => 'info',
            self::STATUS_PARTIALLY_RECEIVED => 'info',
            self::STATUS_RECEIVED           => 'info',
            self::STATUS_CANCELED           => 'danger',
            self::STATUS_ISSUE              => 'warning',
            self::STATUS_RETURNED           => 'warning',
            self::STATUS_REFUNDED           => 'warning',
        ];
    }

    /**
     * @return array
     */
    static function getIrreversiblyStatuses()
    {
        return [
            self::STATUS_CANCELED,
            self::STATUS_RECEIVED,
            self::STATUS_REFUNDED
        ];
    }

    /**
     * @param int $statusId
     * @return bool
     */
    public function isIrreversiblyStatus($statusId)
    {
        if (in_array($statusId, self::getIrreversiblyStatuses())) {
            return true;
        }

        return false;
    }

    /**
     * @param $orderData \DDD\Domain\WHOrder\Order|[]
     * @return array
     */
    public function getOptions($orderData)
    {
        $orderTargetDetails = $supplierDetails = $objectData = [];

        /**
         * @var \DDD\Service\Currency\Currency $currencyService
         */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');

        $currencyList = $currencyService->getActiveCurrencies();

        if (!empty($orderData) && in_array($orderData->getStatus(), [self::STATUS_ORDER_APPROVED, self::STATUS_ORDER_REJECTED])) {
            $statusShippingList  = self::getStatusesShipping();
            $orderShippingStatus = $orderData->getStatusShipping();

            if ($orderShippingStatus != self::STATUS_TO_BE_ORDERED && $orderShippingStatus != self::STATUS_CANCELED) {
                unset($statusShippingList[self::STATUS_CANCELED]);
            }

            if ($orderShippingStatus == self::STATUS_TO_BE_ORDERED) {
                $statusShippingList = [
                    self::STATUS_TO_BE_ORDERED => self::getStatusesShipping()[self::STATUS_TO_BE_ORDERED],
                    self::STATUS_ORDERED       => self::getStatusesShipping()[self::STATUS_ORDERED],
                    self::STATUS_CANCELED      => self::getStatusesShipping()[self::STATUS_CANCELED],
                ];
            }

            if ($orderShippingStatus == self::STATUS_CANCELED) {
                $statusShippingList = [
                    self::STATUS_ORDERED  => self::getStatusesShipping()[self::STATUS_ORDERED],
                    self::STATUS_CANCELED => self::getStatusesShipping()[self::STATUS_CANCELED],
                ];
            }



        } else {
            $statusShippingList = [self::STATUS_TO_BE_ORDERED => self::getStatusesShipping()[self::STATUS_TO_BE_ORDERED]];
            $orderShippingStatus = false;
        }

        $statusShipping = $this->getStatusShippingForSelectize($statusShippingList, $orderShippingStatus);

        if (!empty($orderData)) {
            // for populate form
            $objectData = [];
            $objectData['title']                         = $orderData->getTitle();
            $objectData['price']                         = $orderData->getPrice();
            $objectData['currency']                      = $orderData->getCurrencyId();
            $objectData['team_id']                       = $orderData->getTeamId();
            $objectData['quantity']                      = $orderData->getQuantity();
            $objectData['quantity_type']                 = $orderData->getQuantityType();
            $objectData['supplier_tracking_number']      = $orderData->getSupplierTrackingNumber();
            $objectData['supplier_transaction_id']       = $orderData->getSupplierTransactionId();
            $objectData['url']                           = $orderData->getUrl();
            $objectData['description']                   = $orderData->getDescription();
            $objectData['received_date']                 = $orderData->getReceivedDate() ? date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($orderData->getReceivedDate())) : '';
            $objectData['received_quantity']             = $orderData->getReceivedQuantity();
            $objectData['order_date']                    = $orderData->getOrderDate() ? date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($orderData->getOrderDate())) : '';
            $objectData['tracking_url']                  = $orderData->getTrackingUrl();
            $objectData['estimated_delivery_date_range'] = (!empty($orderData->getEstimatedDateStart()))
                ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($orderData->getEstimatedDateStart())) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($orderData->getEstimatedDateEnd())) : '';

            $orderTargetDetails = [
                'id' => $orderData->getTargetType() . '_' . $orderData->getTargetId(),
                'type' => $orderData->getTargetType()
            ];

            switch ($orderData->getTargetType()) {
                case self::ORDER_LOCATION_TYPE_APARTMENT:
                    /**
                     * @var \DDD\Dao\Apartment\General $apartmentDao
                     */
                    $apartmentDao = $this->getServiceLocator()->get('dao_apartment_general');
                    $apartmentData = $apartmentDao->getApartmentGeneralInfo($orderData->getTargetId());

                    $orderTargetDetails = array_merge($orderTargetDetails, [
                        'info' => $apartmentData['city_name'],
                        'label' => 'apartment',
                        'text' => $apartmentData['name'],
                    ]);
                    break;
                case self::ORDER_LOCATION_TYPE_OFFICE:
                    /**
                     * @var \DDD\Dao\Office\OfficeManager $officeDao
                     */
                    $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');
                    $officeData = $officeDao->getOfficeDetailsById($orderData->getTargetId(), false);

                    $orderTargetDetails = array_merge($orderTargetDetails, [
                        'info' => $officeData['city_name'],
                        'label' => 'office',
                        'text' => $officeData['name']
                    ]);
                    break;
                case self::ORDER_LOCATION_TYPE_STORAGE:
                    /**
                     * @var \DDD\Dao\Warehouse\Storage $storageDao
                     */
                    $storageDao = $this->getServiceLocator()->get('dao_warehouse_storage');
                    $storageData = $storageDao->getStorageDetails($orderData->getTargetId(), false);

                    $orderTargetDetails = array_merge($orderTargetDetails, [
                        'info' => $storageData['city_name'],
                        'label' => 'storage',
                        'text' => $storageData['name']
                    ]);
                    break;
                case self::ORDER_LOCATION_TYPE_BUILDING:
                    $buildingDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
                    $building = $buildingDao->fetchOne(['id' => $orderData->getTargetId()], ['name']);

                    $orderTargetDetails = array_merge($orderTargetDetails, [
                        'info' => '',
                        'label' => 'building',
                        'text' => $building->getName()
                    ]);
                    break;
            }

            $supplierDetails = [];
            if ($orderData->getSupplierId()) {
                /**
                 * @var \DDD\Dao\Finance\Supplier $supplierDao
                 */
                $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');
                $supplierData = $supplierDao->getSupplierById($orderData->getSupplierId());

                $supplierDetails = [
                    'id'   => $orderData->getSupplierId(),
                    'name' => $supplierData->getName()
                ];
            }
        }



        return [
            'orderTargetDetails' => $orderTargetDetails,
            'supplierDetails' => $supplierDetails,
            'objectData' => $objectData,
            'currencyList' => $currencyList,
            'statusShipping' => json_encode($statusShipping),
        ];
    }

    /**
     * @param $statusShippingList
     * @return array
     */
    public function getStatusShippingForSelectize($statusShippingList, $excludeStatus = false)
    {
        $irreversiblyStatuses = self::getIrreversiblyStatuses();
        $statusShipping = [];
        foreach ($statusShippingList as $id => $title) {
            if ($excludeStatus !== false && $excludeStatus != self::STATUS_TO_BE_ORDERED) {
                if ($id == self::STATUS_TO_BE_ORDERED) {
                    continue;
                }
            }

            $statusShipping[] = [
                'id'                => $id,
                'title'             => $title,
                'irreversibility'   => (in_array($id, $irreversiblyStatuses)) ? true : false
            ];
        }
        return $statusShipping;
    }

    /**
     * @param int $id
     * @return \DDD\Domain\WHOrder\Order|[]
     */
    public function getOrder($id)
    {
        try {
            $orderDao = $this->getOrderDao();
            $orderData = $orderDao->getOrderById($id);
            if ($orderData) {
                return $orderData;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get Order data by id', [
                'id' => $id
            ]);
        }

        return [];
    }

    /**
     * @param $data
     * @return array
     */
    public function saveOrderMultiple($data)
    {
        $data = (array)$data;
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_ADD,
        ];

        if (
            (!isset($data['title_template']) || !isset($data['quantity_template']) || !isset($data['quantity_type_template']))
            &&
            (!isset($data['quantity']) ||
             !isset($data['quantity_type']) ||
             !isset($data['title']) ||
             !is_array($data['quantity']) ||
             !is_array($data['title']) ||
             !is_array($data['quantity_type']))
        ) {
            return [
                'status' => 'error',
                'msg'    => 'Wrong parameters',
            ];
        }

        if ($data['title_template'] != '' && $data['quantity_template'] != '' && is_numeric($data['quantity_template']) && $data['quantity_type_template'] != '') {
            if (!isset($data['quantity'])) {
                $data['quantity']      = [];
                $data['quantity_type'] = [];
                $data['title']         = [];
                $data['url']           = [];
            }

            array_push($data['quantity'], $data['quantity_template']);
            array_push($data['quantity_type'], $data['quantity_type_template']);
            array_push($data['url'], $data['url_template']);
            array_push($data['title'], $data['title_template']);
        }

        foreach ($data['quantity'] as $key => $quantity) {
            $orderResult = $this->saveOrder([
                'title'           => htmlspecialchars($data['title'][$key]),
                'url'             => htmlspecialchars($data['url'][$key]),
                'quantity'        => htmlspecialchars($quantity),
                'quantity_type'   => htmlspecialchars($data['quantity_type'][$key]),
                'location_target' => $data['location_target'],
                'description'     => $data['description'],
            ], 0, false);

            if ($orderResult['status'] != 'success') {
                $result = [
                    'status' => 'warning',
                    'msg'    => 'Some of the items had errors, so they have not been saved.',
                ];
            }
        }

        return $result;
    }

    /**
     * @param $data
     * @param $orderId
     * @param boolean $traverse
     * @return int
     */
    public function saveOrder($data, $orderId, $traverse = true)
    {
        $orderDao = $this->getOrderDao();
        if ($traverse) {
            $data = array_filter(iterator_to_array($data));
        }

        $oldData = $orderDao->fetchOne(['id' => $orderId]);

        if (isset($data['title'])) {
            $params['title'] = $data['title'];
        }

        if (isset($data['price'])&& !($oldData && $oldData->getStatus() != Order::STATUS_ORDER_NEW)) {
            $params['price'] = $data['price'];
        }

        if (isset($data['currency'])) {
            $params['currency_id'] = $data['currency'];
        }

        if (isset($data['asset_category_id'])) {
            $params['asset_category_id'] = $data['asset_category_id'];
        }

        if (isset($data['location_target']) && $data['location_target']) {
            $orderTarget = explode('_', $data['location_target']);
            if (isset($data['asset_category_id']) && $data['asset_category_id'] &&
                !$this->checkAssetCategoryAndLocationTargetCompliance($data['asset_category_id'], $orderTarget[0])) {
                return [
                    'status' => 'error',
                    'msg' => TextConstants::WAREHOUSE_ORDER_CATEGORY_DOES_NOT_CORRESPOND_WITH_LOCATION
                ];
            }
            $params['target_type'] = $orderTarget[0];
            $params['target_id'] = $orderTarget[1];
        }

        if (isset($data['quantity'])) {
            $params['quantity'] = $data['quantity'];
        }

        if (isset($data['quantity_type'])) {
            $params['quantity_type'] = $data['quantity_type'];
        }

        if (isset($data['supplier_id'])) {
            $params['supplier_id'] = $data['supplier_id'];
            if ($orderId) {
                $orderExpenseItemId = $orderDao->fetchOne(['id' => $orderId], ['po_item_id'])->getPoItemId();
                if (!is_null($orderExpenseItemId) && $orderExpenseItemId) {
                    $transactionAccountsDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
                    $transactionAccountId = $transactionAccountsDao->fetchOne(
                    //4-supplier
                        ['holder_id' => $data['supplier_id'], 'type' => 4],
                        ['id']
                    )->getId();
                    if (is_null($transactionAccountId)) {
                        return false;
                    }
                    $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
                    $expenseItemDao->save(['account_id' => $transactionAccountId], ['id' => $orderExpenseItemId]);
                }

            }
        } else {
            $params['supplier_id'] = null;
            if ($orderId) {
                $orderExpenseItemId = $orderDao->fetchOne(['id' => $orderId], ['po_item_id'])->getPoItemId();
                if (!is_null($orderExpenseItemId) && $orderExpenseItemId) {
                    $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
                    $expenseItemDao->save(['account_id' => null], ['id' => $orderExpenseItemId]);
                }
            }
        }

        if (isset($data['supplier_tracking_number'])) {
            $params['supplier_tracking_number'] = $data['supplier_tracking_number'];
        }

        if (isset($data['supplier_transaction_id'])) {
            $params['supplier_transaction_id'] = $data['supplier_transaction_id'];
        }

        if (isset($data['supplier_transaction_id'])) {
            $params['url'] = $data['url'];
        }

        if (isset($data['estimated_delivery_date_range']) && $data['estimated_delivery_date_range']) {
            $dataRange = explode(' - ', $data['estimated_delivery_date_range']);
            $params['estimated_date_start'] = date("Y-m-d H:i:s", strtotime($dataRange[0]));
            $params['estimated_date_end'] = date("Y-m-d H:i:s", strtotime($dataRange[1]));
        }

        if (isset($data['description'])) {
            $params['description'] = $data['description'];
        }

        if (isset($data['received_date'])) {
            $params['received_date'] = date('Y-m-j H:i:s', strtotime($data['received_date']));
        }

        if (isset($data['received_quantity'])) {
            $params['received_quantity'] = $data['received_quantity'];
        }

        if (isset($data['order_date'])) {
            $params['order_date'] = date('Y-m-j H:i:s', strtotime($data['order_date']));
        }

        if (isset($data['tracking_url'])) {
            $params['tracking_url'] = $data['tracking_url'];
        }

        try {
            if ($orderId) {
                if (isset($data['status_shipping']) && $data['status_shipping'] != $oldData->getStatusShipping()) {
                    $params['status_shipping'] = $data['status_shipping'];

                    /**
                     * @var Logger $loggerService
                     */
                    $loggerService = $this->getServiceLocator()->get('ActionLogger');

                    $loggerService->save(
                        Logger::MODULE_WH_ORDER,
                        $orderId,
                        Logger::ACTION_WH_ORDER_STATUS_CHANGED,
                        'Order Status changed from the "'
                        . Order::getStatusesShipping()[$oldData->getStatusShipping()]
                        . '" to "' . Order::getStatusesShipping()[$data['status_shipping']] . '"'
                    );
                }

                if (isset($data['team_id'])) {
                    $params['team_id'] = $data['team_id'];
                }

                $orderDao->save($params, ['id' => $orderId]);
            } else {
                $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                $params['creator_id'] = $auth->getIdentity()->id;
                $params['date_created'] = date('Y-m-d');
                $params['status_shipping'] = self::STATUS_TO_BE_ORDERED;
                $params['team_id'] = Team::TEAM_PROCUREMENT;
                $orderId = $orderDao->save($params);
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR,
            ];
        }

        return [
            'status' => 'success',
            'id' => $orderId,
        ];
    }

    /**
     * @param int $assetCategoryId
     * @param int $locationTargetType
     * @return bool
     */
    private function checkAssetCategoryAndLocationTargetCompliance($assetCategoryId, $locationTargetType)
    {
        /**
         * @var \DDD\Service\Warehouse\Category $assetsCategoryService
         */
        $assetsCategoryService = $this->getServiceLocator()->get('service_warehouse_category');
        $assetCategoryData = $assetsCategoryService->getCategoryData($assetCategoryId);

        if ((int)$assetCategoryData['type'] === AssetsCategory::CATEGORY_TYPE_CONSUMABLE
            && (int)$locationTargetType !== self::ORDER_LOCATION_TYPE_STORAGE
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param string $like
     * @param array $filters
     * @return array|bool
     */
    public function getDatatableData($offset, $limit, $sortCol, $sortDir, $like = '', $filters = [])
    {
        try {
            $orderDao = $this->getOrderDao();

            if (!empty($filters['status_shipping'])) {
                $filters['status_shipping'] = explode(',', $filters['status_shipping']);
            }

            /**
             * @var \Library\Authentication\BackofficeAuthenticationService $authService
             * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
             */
            $authService  = $this->getServiceLocator()->get('library_backoffice_auth');
            if (!$authService->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL)) {
                $userId = $authService->getIdentity()->id;
                $query = DbTables::TBL_WM_ORDERS . '.creator_id = ' .  $userId;

                /**
                 * @var Procurement $teamUsageProcurementService
                 */
                $teamUsageProcurementService = $this->getServiceLocator()->get('service_team_usages_procurement');

                $userProcurementTeams = $teamUsageProcurementService->getUserProcurementTeams($userId);

                $userProcurementTeamsIds = [];
                foreach ($userProcurementTeams as $userProcurementTeam) {
                    $userProcurementTeamsIds[] = $userProcurementTeam->getId();
                }

                if (!empty($userProcurementTeamsIds)) {
                    $ids    = implode(",", $userProcurementTeamsIds);
                    $query .= ' OR ' . DbTables::TBL_WM_ORDERS . '.team_id in (' .$ids. ')';
                }
                $filters['users_or_teams'] = $query;

            }

            $result = $orderDao->getAllOrders($offset, $limit, $sortCol, $sortDir, $like, $filters);

            $ordersList         = $result['orders_list'];
            $ordersTotalCount   = $result['total_count'];

            $data = [];
            if ($ordersList->count()) {
                /**
                 * @var \DDD\Dao\Apartment\General $apartmentDao
                 * @var \DDD\Dao\Warehouse\Storage $storageDao
                 * @var \DDD\Dao\Office\OfficeManager $officeDao
                 * @var \DDD\Dao\Finance\Supplier $supplierDao
                 * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $buildingDao
                 */
                $apartmentDao   = $this->getServiceLocator()->get('dao_apartment_general');
                $storageDao     = $this->getServiceLocator()->get('dao_warehouse_storage');
                $officeDao      = $this->getServiceLocator()->get('dao_office_office_manager');
                $supplierDao    = $this->getServiceLocator()->get('dao_finance_supplier');
                $buildingDao    = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

                $assetsCategoryColors = [
                    AssetsCategory::CATEGORY_TYPE_CONSUMABLE    => 'success',
                    AssetsCategory::CATEGORY_TYPE_VALUABLE      => 'primary'
                ];

                foreach ($ordersList as $order) {
                    $location = $category = '';
                    switch ($order['target_type']) {
                        case self::ORDER_LOCATION_TYPE_APARTMENT;
                            $apartmentData = $apartmentDao->getApartmentGeneralInfo($order['target_id']);

                            $location = '<span class="label label-success" title="Apartment">A</span> '
                                . ' ' . $apartmentData['name'];
                            break;
                        case self::ORDER_LOCATION_TYPE_STORAGE:
                            $storageData = $storageDao->getStorageDetails($order['target_id'], false);

                            $location = '<span class="label label-primary" title="Storage">S</span> '
                                . ' ' . $storageData['name'];
                            break;
                        case self::ORDER_LOCATION_TYPE_OFFICE:
                            $officeData = $officeDao->getOfficeDetailsById($order['target_id'], false);

                            $location = '<span class="label label-info" title="Office">O</span> '
                                . ' ' . $officeData['name'];
                            break;
                        case self::ORDER_LOCATION_TYPE_BUILDING:
                            $buildingData = $buildingDao->fetchOne(['id' => $order['target_id']], ['name']);

                            $location = '<span class="label label-warning" title="Building">B</span> '
                                . ' ' . $buildingData->getName();
                            break;
                    }

                    $shippingStatus = '<span class="label label-' . self::getStatusesColor()[$order['status_shipping']] . '"'
                        . ' title="' . self::getStatusesShipping()[$order['status_shipping']] . '">'
                        . self::getShortShippingStatuses()[$order['status_shipping']] . '</span>';

                    if ($order['category_type']) {
                        $category = '<span class="label label-'. $assetsCategoryColors[$order['category_type']] . '"'
                            . ' title="' . AssetsCategory::$categoryTypes[$order['category_type']] . '">'
                            . ' ' . substr(AssetsCategory::$categoryTypes[$order['category_type']], 0, 1)
                            . '</span> ' . $order['category_name'];
                    }

                    $urlBtn = '';
                    if (!empty($order['url'])) {
                        if ((stristr($order['url'], 'http://') || stristr($order['url'], 'https://')) === false) {
                            $order['url'] = 'http://' . $order['url'];
                        }
                        $urlBtn = '<a href="' . $order['url'] . '" target="_blank" class="btn btn-xs btn-info pull-left"><i class="glyphicon glyphicon-globe"></i> <span class="hidden-sm hidden-xs">URL</span></a>';
                    }

                    $editBtn = '<a href="/orders/edit/' . $order['id']
                        . '" target="_blank" class="btn btn-xs btn-primary pull-left">Edit</a>';
                    $status = Order::getStatusesByText()[$order['status']];
                    $data[] = [
                        '<span class="label ' . $status[1] . '">' . $status[0] . '</span>',
                        $shippingStatus,
                        $order['title'],
                        $category,
                        $location,
                        (!empty($order['estimated_date_start']))
                            ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($order['estimated_date_start'])) : '',
                        (!empty($order['estimated_date_end']))
                            ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($order['estimated_date_end'])) : '',
                        $urlBtn,
                        $editBtn
                    ];
                }

            }

            return [
                'data'  => $data,
                'total' => $ordersTotalCount
            ];

        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get Orders from datatable', [
                'offset'      => $offset,
                'limit'       => $limit,
                'sort_column' => $sortCol,
                'sort_dir'    => $sortDir,
                'like'        => $like
            ]);
        }

        return false;
    }

    /**
     * @param  $data
     * @return bool
     */
    public function createPOItemTransaction($data, $files)
    {
        /**
         * @var ExpenseTicket $financeService
         * @var \DDD\Domain\WHOrder\Order $order
         */
        $financeService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $orderDao = $this->getOrderDao();
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        $order = $orderDao->fetchOne(['id' => $data['order_id']], ['po_item_id']);

        if ($order && $order->getPoItemId()) {
            try {
                $expenseItemDao->beginTransaction();

                $poItem = $expenseItemDao->fetchOne(['id' => $order->getPoItemId()], ['account_id', 'expense_id']);

                if ($poItem && $data['supplier_id'] && $poItem['expense_id']) {
                    $transactionAccountsDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
                    $transactionAccountId = $transactionAccountsDao->fetchOne(
                    //4-supplier
                        ['holder_id' => $data['supplier_id'], 'type' => 4],
                        ['id']
                    )->getId();
                    if (is_null($transactionAccountId)) {
                        throw new \Exception();
                    }
                    $moneyAccountDao = new MoneyAccount($this->getServiceLocator(), '\ArrayObject');
                    $moneyAccountCurrencyId = $moneyAccountDao->getCurrencyId($data['money_account_id']);
                    $newItemAmountInOldItemCurrency = $financeService->recalculateTicketBalance($order->getPoItemId(), $data['amount'], $moneyAccountCurrencyId);

                    if ($newItemAmountInOldItemCurrency === false) {
                        $newItemAmountInOldItemCurrency = $data['amount'];
                    }

                    $expenseItemDao->save(
                        [
                            'account_id' => $transactionAccountId,
                            'amount' => $newItemAmountInOldItemCurrency,
                        ],
                        ['id' => $order->getPoItemId()]
                    );
                    $transactionData = [
                        'poId' => $poItem['expense_id'],
                        'itemId' => $order->getPoItemId(),
                        'accountId' => $transactionAccountId,
                        'moneyAccount' => $data['money_account_id'],
                        'transactionDate' => date('Y-m-d', strtotime($data['transaction_date'])),
                        'amount' => $data['amount'],
                    ];

                    $orderDao->save(['price' => $data['amount'], 'currency_id' => $moneyAccountCurrencyId],['id' => $data['order_id']]);
                    $financeService->makeTransaction($transactionData);

                    //attachment processing
                    if ($files->count()) {
                        $errorMessages = $financeService->saveItemFile($files, ['itemId' => $order->getPoItemId()]);
                        if (count($errorMessages)) {
                            throw new \Exception('Cannot save item attachment. ' . print_r($errorMessages, true));
                        }
                        $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
                        $attachmentInfo = $expenseItemAttachmentsDao->getAttachmentsToRemove($order->getPoItemId());
                        $financeService->moveTmpItem($attachmentInfo->getExpenseId(), $order->getPoItemId(), $attachmentInfo->getDateCreated(), $attachmentInfo->getFilename());
                    }

                    $expenseItemDao->commitTransaction();

                    return $moneyAccountCurrencyId;
                }
            } catch (\Exception $e) {
                $expenseItemDao->rollbackTransaction();
                return false;
            }

        }
        return false;
    }

    /**
     * @param ParametersInterface $data
     * @return bool
     */
    public function createPOItem(ParametersInterface $data)
    {
        /**
         * @var ExpenseTicket $financeService
         */
        $financeService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $orderDao       = $this->getOrderDao();

        $orderId    = $data->get('order_id', 0);
        $poId       = $data->get('po_id', 0);
        $currencyId = $data->get('currency', '');

        $orderData = $orderDao->getDataForPOItem($orderId);

        if (!$orderData) {
            return [
                'status' => 'error',
                'msg'    => 'Order with mentioned Id does not exist',
            ];
        }

        if ($poId) {
            $poCheckResult = $financeService->checkPoForOrderItem($poId, $data->get('price'), $currencyId);

            if ($poCheckResult['status'] != 'success') {
                return $poCheckResult;
            }
        }

        $user         = $orderData['user'];
        $createdDate  = date(Constants::GLOBAL_DATE_FORMAT, strtotime($orderData['date_created']));
        $name         = $orderData['title'];
        $category     = $orderData['asset_category_name'];
        $locationName = $orderData['location_name'];
        $quantity     = $orderData['quantity'];
        $description  = $orderData['description'] ? 'Purpose : ' . $orderData['description'] : '';
        $comment      = "{$user} on {$createdDate} created an order with following parameters\nName : {$name}\nCategory : {$category}\nLocation : {$locationName}\nQuantity : $quantity\n$description";

        $itemData = [
            'comment'    => $comment,
            'amount'     => $orderData['price'],
            'currencyId' => $currencyId,
            'accountId'  => $orderData['account_id'],
            'type'       => \Library\Finance\Process\Expense\Helper::TYPE_ORDER_EXPENSE,
        ];

        if ($poId) {
            $itemData['poId']                  = $poId;
            $itemData['accountReference']      = $data->get('account_reference');
            $itemData['costCenters']           = $data->get('cost_centers');
            $itemData['subCategoryId']         = $data->get('sub_category_id');
            $itemData['type']                  = $data->get('type');
            $itemData['period']                = $data->get('period');
            $itemData['doAnExceptionForOrder'] = true;

            if ($data->get('supplier_type') == 1) {
                $itemData['accountId'] = $data->get('supplier_id');
            }
        } else {
            $itemData['creatorId'] = $orderData['creator_id'];
        }

        try {
            $param = [];
            if ($financeService->saveItem($itemData, false, $params)) {
                $orderDao->update([
                    'po_item_id' => $params['itemId'],
                    'status' => ($poId) ? self::STATUS_ORDER_APPROVED : self::STATUS_ORDER_AWAITING_APPROVAL,
                ], ['id' => $orderId]);

                return [
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_ADD,
                ];
            }
        } catch (\Exception $ex) {
            return false;
        }

        return false;
    }

    /**
     * @param ParametersInterface $data
     * @return bool
     */
    public function createRefundPOItem(ParametersInterface $data)
    {
        /**
         * @var ExpenseTicket $financeService
         */
        $financeService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $expenseItemDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item');
        $orderDao       = $this->getOrderDao();

        $orderId      = $data->get('orderId', 0);
        $poId         = $data->get('poId', 0);
        $refundAmount = $data->get('refundAmount', 0);
        $orderAmount  = $data->get('orderAmount', 0);

        $orderData = $orderDao->getDataForPOItem($orderId);

        if (!$orderData || !$orderData['po_item_id'] || !$refundAmount || !$orderAmount) {
            return [
                'status' => 'error',
                'msg'    => 'Order with mentioned Id does not exist',
            ];
        }

        $expenseItemDetail = $expenseItemDao->getRawItemData($orderData['po_item_id']);

        if (!$expenseItemDetail) {
            return false;
        }

        $officeSectionDao = $this->getServiceLocator()->get('dao_office_office_section');
        $apartmentDao     = $this->getServiceLocator()->get('dao_apartment_general');

        if (isset($expenseItemDetail['costs'])) {
            $costs = explode(',', $expenseItemDetail['costs']);
            $costList = [];

            foreach ($costs as $cost) {
                $costDetail = explode('_', $cost);

                $currencyId = false;

                if ($costDetail[1] == ExpenseCosts::TYPE_APARTMENT) {
                    $currencyId = $apartmentDao->fetchOne(['id' => $costDetail[0]], ['currency_id'])['currency_id'];
                } elseif($costDetail[1] == ExpenseCosts::TYPE_OFFICE_SECTION) {
                    $currencyId = $officeSectionDao->getSectionById($costDetail[0])['currency_id'];
                }

                if ($currencyId) {
                    $costList[] = [
                        'id'         => $costDetail[0],
                        'type'       => $costDetail[1],
                        'currencyId' => $currencyId
                    ];
                }
            }
        }

        if (!$poId) { return false; }

        $itemData = [
            'refundAmount'          => $refundAmount,
            'orderAmount'           => $orderAmount,
            'currencyId'            => $expenseItemDetail['currency_id'],
            'accountId'             => $expenseItemDetail['account_id'],
            'poId'                  => $poId,
            'accountReference'      => $expenseItemDetail['account_reference'],
            'costCenters'           => $costList,
            'subCategoryId'         => $expenseItemDetail['sub_category_id'],
            'type'                  => $expenseItemDetail['type'],
            'doAnExceptionForOrder' => true,
            'isRefund'              => ExpenseTicket::IS_REFUND,
            'transactionId'         => $expenseItemDetail['transaction_id'],
        ];

        try {
            $financeService->createRefundedOrder($itemData, $params);

            return [
                'status' => 'success',
                'msg'    => TextConstants::SUCCESS_ADD,
                'hasTransaction' => $params['hasTransaction']
            ];

        } catch (\Exception $ex) {
            return false;
        }

        return false;
    }

    public function getItemDetails(ParametersInterface $data)
    {
        $orderDao              = $this->getOrderDao();
        $expenseTransactionDao = $this->getServiceLocator()->get('dao_finance_transaction_expense_transactions');
        $expenseItemDao        = $this->getServiceLocator()->get('dao_finance_expense_expense_item');

        $orderId   = $data->get('orderId', 0);
        $orderData = $orderDao->getDataForPOItem($orderId);

        if (!$orderData || !$orderData['po_item_id']) {
            return [
                'status' => 'error',
                'msg'    => 'Order with mentioned Id does not exist',
            ];
        }

        $accountFrom       = '';
        $accountTo         = '';
        $hasNotTransaction = 1;

        $expenseItemDetail = $expenseItemDao->getRawItemData($orderData['po_item_id']);
        if (!$expenseItemDetail || !$expenseItemDetail['transaction_id']) {
            return [
                'accountFrom'       => $accountFrom,
                'accountTo'         => $accountTo,
                'hasNotTransaction' => $hasNotTransaction,
            ];
        }

        $where = new Where();
        $where->equalTo('ga_expense_transaction.id', $expenseItemDetail['transaction_id']);
        $transactionDetails = $expenseTransactionDao->getTransactions($where);

        if ($transactionDetails) {
            foreach ($transactionDetails as $row) {
                $accountFrom = $row['account_to'];
                $accountTo   = $row['account_from'];
            }

            $hasNotTransaction = 0;
        }
        return [
            'accountFrom'       => $accountFrom,
            'accountTo'         => $accountTo,
            'hasNotTransaction' => $hasNotTransaction,
        ];
    }

    /**
     * Duplicate Item PO for money request
     *
     * @param $orderId
     * @param $moneyAccountName
     * @param $price
     * @param $currencyIsoCode
     * @return array
     */
    public function setRequestMoney($orderId, $moneyAccountName, $price, $currencyIsoCode)
    {
        /**
         * @var \DDD\Dao\Finance\Expense\ExpenseItem $expenseItem
         */
        $expenseItemDao  = $this->getServiceLocator()->get('dao_finance_expense_expense_item');
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $user            = $this->getServiceLocator()->get('library_backoffice_auth');
        $orderDao        = $this->getOrderDao();
        $order           = $orderDao->getOrderById($orderId);

        $currencyId = $currencyService->getCurrencyIDByIsoCode($currencyIsoCode);

        if (!$order) {
            return [
                'status' => 'error',
                'msg'    => 'Order Id does not exist',
            ];
        }

        $poItemId    = $order->getPoItemId();
        $expenseItem = iterator_to_array($expenseItemDao->fetchOne(['id' => $poItemId]));

        unset($expenseItem['id']);
        unset($expenseItem['expense_id']);

        $expenseItem['date_created'] = date('Y-m-d h:i:s');
        $expenseItem['creator_id']   = $user->getIdentity()->id;
        $expenseItem['amount']       = $price;
        $expenseItem['currency_id']  = $currencyId;
        $expenseItem['creator_id']   = $user->getIdentity()->id;
        $expenseItem['comment']      = $expenseItem['comment'] . ' Prefered Money Account - ' . strip_tags($moneyAccountName);

        try {
            $expenseItemDao->beginTransaction();

            $lastInsertedId = $expenseItemDao->save($expenseItem);
            $orderDao->save(['po_ra_item_id' => $lastInsertedId], ['id' => $orderId]);
            $expenseItemDao->commitTransaction();

            if ($lastInsertedId > 0) {
                return [
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_ADD,
                ];
            }

        } catch (\Exception $ex) {
            $expenseItemDao->rollbackTransaction();
        }

        return [
            'status' => 'error',
            'msg'    => 'Expense Item does not created',
        ];
    }

    /**
     * @param $orderId
     * @param $userId
     * @return array|\ArrayObject|bool
     */
    public function isYourTeamOrder($orderId, $userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->isYourTeamOrder($orderId, $userId);
    }

    /**
     * @param $orderId
     * @return int
     */
    public function reject($orderId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->update(
            [
                'status' => self::STATUS_ORDER_REJECTED,
                'status_shipping' => self::STATUS_CANCELED
            ],
            [
                'id' => $orderId
            ]
        );
    }

    /**
     * @param $orderId
     * @return int
     */
    public function archiveOrder($orderId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->update(['is_archive' => self::STATUS_IS_ARCHIVE], ['id' => $orderId]);
    }

    /**
     * @param $orderId
     * @return int
     */
    public function markReceivedOrder($orderId)
    {
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');
        return $orderDao->update(['status_shipping' => self::STATUS_RECEIVED], ['id' => $orderId]);
    }

    /**
     * @param $categoryId
     * @param $locationEntityId
     * @param $locationEntityType
     * @param array $finalStatuses
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRelatedOrders($categoryId, $locationEntityId, $locationEntityType, $finalStatuses = [])
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getRelatedOrders($categoryId, $locationEntityId, $locationEntityType, $finalStatuses) ;
    }

    /**
     * @param $userId
     * @return int
     */
    public function getItemsToBeOrderedCount($userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getItemsToBeOrderedCount($userId);
    }

    /**
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet | \DDD\Domain\WHOrder\Order []
     */
    public function getItemsToBeOrdered($userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getItemsToBeOrdered($userId);
    }

    /**
     * @param $userId
     * @return int
     */
    public function getItemsToBeDeliveredCount($userId)
    {
        $dateTimeAfter2days    = new \DateTime('now +2days');
        $dateTimeAfter2days    = $dateTimeAfter2days->format('Y-m-d');
        $orderDao = $this->getOrderDao();
        return $orderDao->getItemsToBeDeliveredCount($dateTimeAfter2days, $userId);
    }

    /**
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsToBeDelivered($userId)
    {
        $dateTimeAfter2days    = new \DateTime('now +2days');
        $dateTimeAfter2days    = $dateTimeAfter2days->format('Y-m-d');
        $orderDao = $this->getOrderDao();
        return $orderDao->getItemsToBeDelivered($dateTimeAfter2days, $userId);
    }

    /**
     * @param $userId
     * @return int
     */
    public function getOrdersToBeRefundedCount($userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getOrdersToBeRefundedCount($userId);
    }

    /**
     * @param $userId
     * @return int
     */
    public function getOrdersCreatedByMeCount($userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getOrdersCreatedByMeCount($userId);
    }

    /**
     * @return int
     */
    public function getOrdersToBeShippedInLastTwoDaysCount()
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getOrdersToBeShippedInLastTwoDaysCount();
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getOrdersToBeShippedInLastTwoDays()
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getOrdersToBeShippedInLastTwoDays();
    }

    /**
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getOrdersToBeRefunded($userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getOrdersToBeRefunded($userId);
    }

    /**
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet | \DDD\Domain\WHOrder\Order []
     */
    public function getOrdersCreatedByMe($userId)
    {
        $orderDao = $this->getOrderDao();
        return $orderDao->getOrdersCreatedByMe($userId);
    }

    /**
     * @return \DDD\Dao\WHOrder\Order
     */
    private function getOrderDao()
    {
        if ($this->orderDao) {
            return $this->orderDao;
        }

        return $this->getServiceLocator()->get('dao_wh_order_order');
    }

}
