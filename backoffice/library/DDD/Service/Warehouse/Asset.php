<?php

namespace DDD\Service\Warehouse;

use DDD\Dao\Warehouse\Asset\Changes;
use DDD\Dao\Warehouse\Asset\Consumable;
use DDD\Dao\Warehouse\Asset\ConsumableSkusRelation;
use DDD\Dao\Warehouse\Asset\Valuable;
use DDD\Dao\Warehouse\Asset\ValuableStatuses;
use DDD\Dao\Warehouse\SKU;
use DDD\Service\ServiceBase;
use DDD\Service\WHOrder\Order;
use Library\Constants\Roles;
use Zend\Db\Sql\Expression;
use Zend\Http\Request;
use Library\ActionLogger\Logger;
use DDD\Service\Warehouse\Category as CategoryService;
use Zend\View\Model\JsonModel;

class Asset extends ServiceBase
{
    CONST VALUABLE_STATUS_WORKING  = 1;
    CONST VALUABLE_STATUS_BROKEN   = 2;
    CONST VALUABLE_STATUS_LOST     = 3;
    CONST VALUABLE_STATUS_RETIRED  = 4;
    CONST VALUABLE_STATUS_EXPUNGED = 5;
    CONST VALUABLE_STATUS_NEW      = 6;
    CONST VALUABLE_STATUS_REPAIR   = 7;

    CONST ENTITY_TYPE_APARTMENT = 1;
    CONST ENTITY_TYPE_STORAGE   = 2;
    CONST ENTITY_TYPE_OFFICE    = 3;
    CONST ENTITY_TYPE_BUILDING  = 4;

    public static $types = [
        self::ENTITY_TYPE_APARTMENT => 'Apartment',
        self::ENTITY_TYPE_STORAGE   => 'Storage',
        self::ENTITY_TYPE_OFFICE    => 'Office',
        self::ENTITY_TYPE_BUILDING  => 'Building'
    ];

    CONST RUNNING_OUT_YES     = 1;
    CONST RUNNING_OUT_NO      = 2;
    CONST RUNNING_OUT_NOT_SET = 3;

    CONST SHIPMENT_STATUS_OK     = 1;
    CONST SHIPMENT_STATUS_NOT_OK = 0;

    public static $statuses = [
        self::VALUABLE_STATUS_NEW      => 'New',
        self::VALUABLE_STATUS_REPAIR   => 'Repair',
        self::VALUABLE_STATUS_WORKING  => 'Working',
        self::VALUABLE_STATUS_BROKEN   => 'Broken',
        self::VALUABLE_STATUS_LOST     => 'Lost',
        self::VALUABLE_STATUS_RETIRED  => 'Retired',
        self::VALUABLE_STATUS_EXPUNGED => 'Expunged',
    ];

    public static $assetChangeTypes =  [
        Logger::ACTION_ASSET_VALUABLE_STATUS_CHANGED   => 'Status Change',
        Logger::ACTION_ASSET_VALUABLE_ASSIGNEE_CHANGED => 'Assignee Change',
        Logger::ACTION_ASSET_VALUABLE_LOCATION_CHANGED => 'Location Change',
        Logger::ACTION_ASSET_VALUABLE_ADDED_COMMENT    => 'Added Comment',
    ];

    /**
     * @param Request $request
     * @return int
     */
    public function saveNewValuableAsset($request)
    {
        /** @var Valuable $assetValuableDao */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $userId   = $auth->getIdentity()->id;
        $status   = self::VALUABLE_STATUS_NEW;
        $shipment = self::SHIPMENT_STATUS_OK;

        if ($request instanceof \Zend\Http\PhpEnvironment\Request) {
            $location           = $request->getPost('location');
            $locationArray      = explode('_', $location);
            $categoryId         = $request->getPost('category');
            $locationEntityType = $locationArray[0];
            $locationEntityId   = $locationArray[1];
            $serialNumber       = $request->getPost('serialNumber');
            $name               = $request->getPost('name');
            $assigneeId         = $request->getPost('assignee');
            $description        = $request->getPost('description');

        } else {
            $categoryId         = $request->categoryId;
            $locationEntityType = $request->locationEntityId;
            $locationEntityId   = $request->locationEntityType;
            $serialNumber       = $request->barcode;
            $name               = $request->name;
            $assigneeId         = property_exists($request, 'assigneeId') ? $request->assigneeId : null;
            $description        = '';
        }

        $shipmentStatus = $this->applyMatchingOrder($shipment, $categoryId, $locationEntityType, $locationEntityId, 1);

        $assetValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        return $assetValuableDao->saveNewValuableAsset(
            $categoryId,
            $locationEntityType,
            $locationEntityId,
            $serialNumber,
            $name,
            $assigneeId,
            $description,
            $userId,
            $status,
            $shipmentStatus
        );
    }

    public function updateValuableAsset($request, $assetId = false)
    {
        /**
         * @var Valuable $assetValuableDao
         */
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggerService  = $this->getServiceLocator()->get('ActionLogger');
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $userId = $auth->getIdentity()->id;

        if ($request instanceof \Zend\Http\PhpEnvironment\Request) {

            $id                 = $request->getPost('id');
            $location           = $request->getPost('location');
            $locationArray      = explode('_', $location);
            $categoryId         = $request->getPost('category');
            $locationEntityType = $locationArray[0];
            $locationEntityId   = $locationArray[1];
            $serialNumber       = $request->getPost('serialNumber');
            $name               = $request->getPost('name');
            $assigneeId         = $request->getPost('assignee');
            $description        = $request->getPost('description');
            $statusComment      = $request->getPost('statusComment');
            $status             = $request->getPost('status');

        } else {
            $id                 = $assetId;
            $categoryId         = $request->categoryId;
            $locationEntityType = $request->locationEntityType;
            $locationEntityId   = $request->locationEntityId;
            $serialNumber       = $request->barcode;
            $name               = $request->name;
            $assigneeId         = $request->assigneeId;
            $status             = $request->status;
            $statusComment      = $request->comment;
            $description        = '';
        }

        $assetValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');

        $oldAssetInfo = $assetValuableDao->fetchOne(['id' => $id]);

        $assetValuableDao->updateValuableAsset(
            $id,
            $categoryId,
            $locationEntityType,
            $locationEntityId,
            $serialNumber,
            $name,
            $assigneeId,
            $description,
            $userId,
            $status,
            $statusComment
        );

        $userInfo = $userManagerDao->fetchOne(['id' => $userId]);

        if ($oldAssetInfo) {
            // Log logic
            if ($oldAssetInfo->getStatus() != $status) {
                $logMsg = 'Status had changed from <b>' . self::$statuses[$oldAssetInfo->getStatus()] .
                    '</b> to <b>' . self::$statuses[$status] . '</b> for following reason: ' . $statusComment ;

                $loggerService->save(Logger::MODULE_ASSET_VALUABLE, $id, Logger::ACTION_ASSET_VALUABLE_STATUS_CHANGED, $logMsg);
            } elseif (!empty($statusComment)) {
                $loggerService->save(Logger::MODULE_ASSET_VALUABLE, $id, Logger::ACTION_ASSET_VALUABLE_ADDED_COMMENT, $statusComment);
            }

            if (!empty($assigneeId) && $oldAssetInfo->getAssigneeId() != $assigneeId) {
                $oldUserInfo  = $userManagerDao->fetchOne(['id' => $oldAssetInfo->getAssigneeId()], ['firstname', 'lastname']);
                $newUserInfo  = $userManagerDao->fetchOne(['id' => $assigneeId], ['firstname', 'lastname']);

                if ($newUserInfo) {
                    $oldAssigneeName = 'nobody';

                    if ($oldUserInfo) {
                        $oldAssigneeName = $oldUserInfo->getFullName();
                    }

                    $logMsg = 'Assignee had changed from <b>' . $oldAssigneeName .
                        '</b> to <b>' . $newUserInfo->getFullName() . '</b>' ;

                    $loggerService->save(Logger::MODULE_ASSET_VALUABLE, $id, Logger::ACTION_ASSET_VALUABLE_ASSIGNEE_CHANGED, $logMsg);
                }
            }

            if (   ($oldAssetInfo->getLocationEntityId()   != $locationEntityId)
                || ($oldAssetInfo->getLocationEntityType() != $locationEntityType)
            ) {
                $entityInfo    = $this->detectLocationbyType($locationEntityType, $locationEntityId);
                $oldEntityInfo = $this->detectLocationbyType($oldAssetInfo->getLocationEntityType(), $oldAssetInfo->getLocationEntityId());

                $logMsg = 'Location had changed from <b>' .
                    self::$types[$oldAssetInfo->getLocationEntityType()] . ': ' . $oldEntityInfo->getName() .
                    '</b> to <b>' . self::$types[$locationEntityType] . ': ' . $entityInfo->getName() . '</b>.';

                $loggerService->save(Logger::MODULE_ASSET_VALUABLE, $id, Logger::ACTION_ASSET_VALUABLE_LOCATION_CHANGED, $logMsg);
            }
        }
    }

    public function checkIfSerialNumberIsUnique($serialNumber, $assetId = false, $returnId = false)
    {
        /**
         * @var Valuable $assetValuableDao
         */
        $assetValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        $result = $assetValuableDao->getRowBySerialNumber($serialNumber, $assetId);

        if ($returnId) {
            return ($result) ? $result->getId() : false;
        }
        return false === $result;
    }


    public function saveNewConsumableAsset($request, $skuAlreadyInDbId)
    {
        /**
         * @var Consumable $assetConsumableDao
         * @var ConsumableSkusRelation $daoWarehouseAssetConsumableSkusRelation
         * @var Changes $daoWarehouseAssetChanges
         */
        $auth                                    = $this->getServiceLocator()->get('library_backoffice_auth');
        $skuDao                                  = $this->getServiceLocator()->get('dao_warehouse_sku');
        $assetConsumableDao                      = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        $daoWarehouseAssetChanges                = $this->getServiceLocator()->get('dao_warehouse_asset_changes');
        $daoWarehouseAssetConsumableSkusRelation = $this->getServiceLocator()->get('dao_warehouse_asset_consumable_skus_relation');

        try {
            $assetConsumableDao->beginTransaction();
            $userId = $auth->getIdentity()->id;

            if ($request instanceof \Zend\Http\PhpEnvironment\Request) {
                $categoryId         = $request->getPost('category');
                $location           = $request->getPost('location');
                $locationArray      = explode('_', $location);
                $locationEntityType = $locationArray[0];
                $locationEntityId   = $locationArray[1];
                $sku                = $request->getPost('sku');
                $quantity           = $request->getPost('quantity');
                $description        = $request->getPost('description');
                $shipment           = $request->getPost('shipment') ? 1 : 0;
            } else {
                $categoryId         = $request->categoryId;
                $locationEntityId   = $request->locationEntityId;
                $locationEntityType = $request->locationEntityType;
                $sku                = $request->barcode;
                $quantity           = $request->quantity;
                $shipment           = $request->shipmentStatus ? 1 : 0;
                $description        = '';
            }

            $shipmentStatus = $this->applyMatchingOrder($shipment, $categoryId, $locationEntityType, $locationEntityId, $quantity);

            $assetId = $this->checkIfCategoryLocationIdLocationEntityIsUnique(
                $categoryId,
                $locationEntityType,
                $locationEntityId);

            if (FALSE !== $assetId  ) {
                $assetConsumableDao->changeAssetQuantity($assetId, $quantity, $shipmentStatus);
            } else {
                $assetConsumableDao->saveNewConsumableAsset(
                    $categoryId,
                    $locationEntityType,
                    $locationEntityId,
                    $quantity,
                    $description,
                    $userId,
                    $shipmentStatus
                );
                $assetId = $assetConsumableDao->getLastInsertValue();
            }

            if (false === $skuAlreadyInDbId) {
                $skuDao->save(['name' => $sku, 'asset_category_id' => $categoryId]);
                $skuAlreadyInDbId = $skuDao->getLastInsertValue();
            }

            $daoWarehouseAssetConsumableSkusRelation->save([
                'sku_id'   => $skuAlreadyInDbId,
                'asset_id' => $assetId,
            ]);

            $daoWarehouseAssetChanges->logChange(
                $categoryId,
                $userId,
                $locationEntityType,
                $locationEntityId,
                $quantity,
                $shipmentStatus,
                date("Y-m-d H:i:s")
            );

            $assetConsumableDao->commitTransaction();
        }
        catch (\Exception $ex) {
            $assetConsumableDao->rollbackTransaction();
            return false;
        }
        return $assetId;
    }


    public function updateConsumableAsset($request, $id = false)
    {
        /**
         * @var Consumable $assetConsumableDao
         * @var ConsumableSkusRelation $daoWarehouseAssetConsumableSkusRelation
         * @var Changes $daoWarehouseAssetChanges
         * @var Logger $loggerService
         */
        $auth                     = $this->getServiceLocator()->get('library_backoffice_auth');
        $assetConsumableDao       = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        $daoWarehouseAssetChanges = $this->getServiceLocator()->get('dao_warehouse_asset_changes');
        $thresholdDao             = $this->getServiceLocator()->get('dao_warehouse_threshold');
        $loggerService            = $this->getServiceLocator()->get('ActionLogger');
        $userManagerDao           = $this->getServiceLocator()->get('dao_user_user_manager');

        try {
            $assetConsumableDao->beginTransaction();

            $userId    = $auth->getIdentity()->id;
            $threshold = false;

            if ($request instanceof \Zend\Http\PhpEnvironment\Request) {
                $categoryId         = $request->getPost('category');
                $location           = $request->getPost('location');
                $locationArray      = explode('_', $location);
                $locationEntityType = $locationArray[0];
                $locationEntityId   = $locationArray[1];
                $quantity           = $request->getPost('quantity');
                $description        = $request->getPost('description');
                $id                 = $request->getPost('id');
                $threshold          = $request->getPost('threshold');
            }

            $consumableOldInfo = $assetConsumableDao->fetchOne(['id' => $id]);

            if (!$request instanceof \Zend\Http\PhpEnvironment\Request) {
                $locationEntityType = $consumableOldInfo->getLocationEntityType();
                $locationEntityId   = $consumableOldInfo->getLocationEntityId();
                $categoryId         = $request->categoryId;
                $description        = '';
                $quantity           = (int)($consumableOldInfo->getQuantity() + $request->quantity);
            }

            if ($quantity != $consumableOldInfo->getQuantity()) {
                $daoWarehouseAssetChanges->logChange(
                    $categoryId, $userId, $locationEntityType, $locationEntityId,
                    $quantity - $consumableOldInfo->getQuantity(), self::SHIPMENT_STATUS_OK,
                    date("Y-m-d H:i:s")
                );
            }

            $assetConsumableDao->updateConsumableAsset(
                $id,
                $categoryId,
                $quantity,
                $description,
                $userId
            );

            $oldThersholdRes = $thresholdDao->fetchone(
                [
                    'storage_id'        => $consumableOldInfo->getLocationEntityId(),
                    'asset_category_id' => $consumableOldInfo->getCategoryId()
                ]
            );

            if ($threshold) {
                if (!$oldThersholdRes) {
                    $thresholdDao->save(
                        [
                            'threshold'         => $threshold,
                            'storage_id'        => $locationEntityId,
                            'asset_category_id' => $categoryId
                        ]
                    );
                } else {
                    $thresholdDao->save(
                        ['threshold' => $threshold],
                        ['id' => $oldThersholdRes->getId()]
                    );
                }
            }

            $userInfo  = $userManagerDao->fetchOne(['id' => $userId]);

            if ($oldThersholdRes) {
                // Log logic
                if ($oldThersholdRes->getThreshold() != $threshold && $threshold !== false) {
                    $logMsg = $userInfo->getFullName() .
                        ' ' . 'had changed <b>threshold</b> from <b>' . $oldThersholdRes->getThreshold() .
                        '</b> to <b>' . $threshold . '</b>' ;
                    $loggerService->save(Logger::MODULE_ASSET_CONSUMABLE, $id, Logger::ACTION_ASSET_THRESHOLD_CHANGED, $logMsg);

                }

                if ($quantity != $consumableOldInfo->getQuantity()) {
                    $logMsg = $userInfo->getFullName() .
                        ' ' . 'had changed <b>quantity</b> from <b>' . $consumableOldInfo->getQuantity() .
                        '</b> to <b>' . $quantity . '</b>' ;
                    $loggerService->save(Logger::MODULE_ASSET_CONSUMABLE, $id, Logger::ACTION_ASSET_QUANTITY_CHANGED, $logMsg);
                }
            }

            $assetConsumableDao->commitTransaction();
        }
        catch (\Exception $ex) {
            $assetConsumableDao->rollbackTransaction();
            return false;
        }
        return true;
    }

    public function checkIfCategoryLocationIdLocationEntitySkuIsUnique($categoryId, $sku, $locationEntityType, $locationEntityId, $returnInfo = false)
    {
        /**
         * @var Consumable $assetConsumableDao
         */
        $assetConsumableDao = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        $result = $assetConsumableDao->getRowByCategoryLocationIdLocationEntitySku($categoryId, $sku, $locationEntityType, $locationEntityId);

        if ($returnInfo) {
            if ($result) {
                return $result->getId();
            }
            return false;
        }
        return FALSE === $result;
    }

    public function checkIfCategoryLocationIdLocationEntityIsUnique($categoryId, $locationEntityType, $locationEntityId, $assetId = false)
    {
        /**
         * @var Consumable $assetConsumableDao
         */
        $assetConsumableDao = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        $result = $assetConsumableDao->getRowByCategoryLocationIdLocationEntity($categoryId,  $locationEntityType, $locationEntityId, $assetId);
        if (FALSE === $result) {
            return false;
        } else {
            return $result->getId();
        }
    }

    public function getSkuIdByName($sku)
    {
        /**
         * @var SKU $skuDao
         */
        $skuDao = $this->getServiceLocator()->get('dao_warehouse_sku');
        $result = $skuDao->getSkuIdByName($sku);
        return $result;
    }

    public function getValuableAssetsStatusesArray($apiRequest = false)
    {
        /**
         * @var ValuableStatuses $assetsValuableStatusesDao
         */
        $assetsValuableStatusesDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable_status');
        $result = $assetsValuableStatusesDao->fetchAll();
        $resultArray = [];
        if (!$apiRequest) {
            $resultArray = ["0" => '-- All Statuses --'];
        }
        foreach($result as $row) {
            $resultArray[$row->getId()] = $row->getName();
        }
        return $resultArray;
    }

    public function getDatatableDataValuable(
                $iDisplayStart,
                $iDisplayLength,
                $iSortCol_0,
                $sSortDir_0,
                $sSearch,
                $queryParams
            ) {
        /**
         * @var Valuable $assetValuableDao
         */
        $assetValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        $list = $assetValuableDao->getListForSearch($iDisplayStart, $iDisplayLength, $queryParams, $iSortCol_0, $sSortDir_0);
        $filteredArray = [];
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        foreach ($list['result'] as $row) {

            if (strlen($sSearch)) {
                if(
                    FALSE === strpos(strtolower($row->getlocationName()),strtolower($sSearch))
                    &&
                    FALSE === strpos(strtolower($row->getStatusName()),strtolower($sSearch))
                    &&
                    FALSE === strpos(strtolower($row->getFirstName()),strtolower($sSearch))
                    &&
                    FALSE === strpos(strtolower($row->getLastName()),strtolower($sSearch))
                    &&
                    FALSE === strpos(strtolower($row->getStatusName()),strtolower($sSearch))
                    &&
                    FALSE === strpos(strtolower($row->getCategoryName()),strtolower($sSearch))
                    &&
                    FALSE === strpos(strtolower($row->getName()),strtolower($sSearch))
                ) {
                    $list['count'] --;
                    continue;
                }
            }

            switch ($row->getLocationEntityType()) {
                case self::ENTITY_TYPE_APARTMENT:
                    $locationLabel = '<span class="label label-success" title="Apartment">A</span>';
                    break;
                case self::ENTITY_TYPE_OFFICE:
                    $locationLabel = '<span class="label label-info" title="Office">O</span>';
                    break;
                case self::ENTITY_TYPE_STORAGE:
                    $locationLabel = '<span class="label label-primary" title="Storage">S</span>';
                    break;
                case self::ENTITY_TYPE_BUILDING:
                    $locationLabel = '<span class="label label-warning" title="Building">B</span>';
                    break;
            }
            $editUrl = $hasAssetManagementGlobal ?
                '<a class="btn btn-xs btn-primary" target="_blank" href="/warehouse/asset/edit-valuable/' . $row->getId() . '" data-html-content="Edit"></a>'
                : '';
            $dtRow = [
                $row->getName(),
                $row->getCategoryName(),
                $locationLabel . "&nbsp;" . $row->getlocationName(),
                $row->getStatusName(),
                $row->getFirstName() . "&nbsp;" . $row->getLastName(),
                $editUrl
            ];
            $filteredArray[] = $dtRow;
        }

        $result['total'] = $list['count'];
        $result['data'] = $filteredArray;
        return $result;
    }

    public function getDatatableDataConsumable(
        $iDisplayStart,
        $iDisplayLength,
        $iSortCol_0,
        $sSortDir_0,
        $sSearch,
        $queryParams
    ) {
        $assetsConsumableStatusesDao = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        $list = $assetsConsumableStatusesDao->getListForSearch($iDisplayStart, $iDisplayLength, $queryParams, $sSearch, $iSortCol_0, $sSortDir_0);
        $filteredArray = [];
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        foreach ($list['result'] as $row) {
            $locationLabel = '<span class="label label-info" title="Office">O</span>';
            $editUrl = $hasAssetManagementGlobal ?
                '<a class="btn btn-xs btn-primary" target="_blank" href="/warehouse/asset/edit-consumable/' . $row->getId() . '" data-html-content="Edit"></a>'
                : '';
            $dtRow = [
                $row->getCategoryName(),
                $locationLabel . "&nbsp;" . $row->getLocationName(),
                $row->getQuantity(),
                $row->getRunningOut(),
                $row->getThreshold(),
                $editUrl
            ];
            $filteredArray[] = $dtRow;
        }

        $result['total'] = $list['count'];
        $result['data'] = $filteredArray;
        return $result;
    }

    /**
     * @param $id
     * @return \DDD\Domain\Warehouse\Assets\Valuable
     */
    public function getValuableBasicInfoById($id)
    {
        /** @var \DDD\Dao\Warehouse\Asset\Valuable $assetsValuableDao */
        $assetsValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        return $assetsValuableDao->getValuableBasicInfoById($id);
    }

    public function getConsumableBasicInfoById($id)
    {
        /** @var \DDD\Dao\Warehouse\Asset\Consumable $assetsConsumableDao */
        $assetsConsumableDao = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        return $assetsConsumableDao->getConsumableBasicInfoById($id);
    }

    /**
     * @param int $shipment
     * @param int $categoryId
     * @param int $locationEntityType
     * @param int $locationEntityId
     * @param int $quantity
     * @return int
     */
    public function applyMatchingOrder($shipment, $categoryId, $locationEntityType, $locationEntityId, $quantity)
    {
        /** @var \DDD\Dao\WHOrder\Order $orderDao */
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');

        $matchingOrders = $orderDao->getMatchingOrdersForAsset($categoryId, $locationEntityType, $locationEntityId, $quantity);

        if ($matchingOrders->count() && $shipment) {
            $matchingOrder = $matchingOrders->current();
            $orderDao->save([
                'received_date'     => date('Y-m-j H:i:s'),
                'status_shipping'   => ($matchingOrder['remaining_quantity'] == $quantity ? Order::STATUS_RECEIVED : Order::STATUS_PARTIALLY_RECEIVED),
                'received_quantity' => new Expression('received_quantity + ' . $quantity)
            ], [
                'id' => $matchingOrder['id']
            ]);
        }
        // If either matching order exists or the shipment checkbox is checked, then shipment status is not fine
        // If neither of conditions is true, or both of them are, then all fine
        if (boolval($matchingOrders->count()) xor boolval($shipment)) {
            $shipmentStatus = 0;
        } else {
            $shipmentStatus = 1;
        }

        return $shipmentStatus;
    }

    /**
     * @return array
     */
    public function getAssetsAwaitingApproval()
    {
        /** @var \DDD\Dao\Warehouse\Asset\Valuable $assetsValuableDao */
        $assetsValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        /** @var \DDD\Dao\Warehouse\Asset\Changes $assetsConsumChangesDao */
        $assetsConsumChangesDao = $this->getServiceLocator()->get('dao_warehouse_asset_changes');

        $valuables  = $assetsValuableDao->getAssetsAwaitingApproval();
        $consumables = $assetsConsumChangesDao->getAssetsAwaitingApproval();

        return array_merge(iterator_to_array($valuables), iterator_to_array($consumables));
    }

    /**
     * @return int
     */
    public function getAssetsAwaitingApprovalCount()
    {
        /** @var \DDD\Dao\Warehouse\Asset\Valuable $assetsValuableDao */
        $assetsValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        /** @var \DDD\Dao\Warehouse\Asset\Changes $assetsConsumChangesDao */
        $assetsConsumChangesDao = $this->getServiceLocator()->get('dao_warehouse_asset_changes');

        $valuablesCount = $assetsValuableDao->getAssetsAwaitingApprovalCount();
        $dataSet        = $assetsConsumChangesDao->getAssetsAwaitingApproval();
        $count          = 0;

        if ($dataSet && count($dataSet)) {
            /** @var \DDD\Domain\Warehouse\Assets\Consumable | \DDD\Domain\Warehouse\Assets\Valuable $row */
            foreach ($dataSet as $row) {

                /** @var \DDD\Dao\WHOrder\Order $orderDao */
                $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');

                $assetType = 'valuable';

                if (!$row instanceof \DDD\Domain\Warehouse\Assets\Valuable) {
                    $quantity       = $row->getQuantityChange();
                    $assetType      = 'consumable';
                    $assetId        = $row->getAssetId();
                    $entityId       = $row->getId();
                } else {
                    $assetId  = $row->getId();
                    $entityId = $row->getId();
                }

                if ($assetType === 'consumable') {

                    if ($row->getShipmentStatus() == self::SHIPMENT_STATUS_NOT_OK) {
                        $matchingOrders = $orderDao->getMatchingOrdersForConsumableAsset(
                            $row->getCategoryId(),
                            $row->getLocationEntityType(),
                            $row->getLocationEntityId(),
                            $quantity,
                            $row->getShipmentStatus(),
                            $checkOrderExist = 1
                        );

                        // if it did not find any order so will shown in UD
                        if ($matchingOrders->count()) {
                            $matchingOrders = $orderDao->getMatchingOrdersForConsumableAsset(
                                $row->getCategoryId(),
                                $row->getLocationEntityType(),
                                $row->getLocationEntityId(),
                                $quantity,
                                $row->getShipmentStatus(),
                                $checkOrderExist = 0
                            );

                            // Did not find any order which its quantity is less than to this asset
                            if (!$matchingOrders->count()) {
                                continue;
                            } else {
                                $count += $matchingOrders->count();
                            }
                        } else {
                            $count += $matchingOrders->count();
                        }
                    } else {
                        $matchingOrders = $orderDao->getMatchingOrdersForConsumableAsset(
                            $row->getCategoryId(),
                            $row->getLocationEntityType(),
                            $row->getLocationEntityId(),
                            $quantity,
                            $row->getShipmentStatus(),
                            $checkOrderExist = 0
                        );

                        // Did not find any order which its quantity is equal to this asset
                        if (!$matchingOrders->count()) {
                            continue;
                        } else {
                            $count += $matchingOrders->count();
                        }
                    }
                }
            }
        }

        return $count + $valuablesCount;
    }

    /**
     * @param int $assetId
     */
    public function resolveValuable($assetId)
    {
        /** @var \DDD\Dao\Warehouse\Asset\Valuable $assetsValuableDao */
        $assetsValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');

        $assetsValuableDao->save([
            'shipment_status' => self::SHIPMENT_STATUS_OK
        ], [
            'id' => $assetId
        ]);
    }

    /**
     * @param int $entityId
     */
    public function resolveConsumable($entityId)
    {
        /** @var \DDD\Dao\Warehouse\Asset\Changes $assetsConsumableChangesDao */
        $assetsConsumableChangesDao = $this->getServiceLocator()->get('dao_warehouse_asset_changes');

        $assetsConsumableChangesDao->save([
            'shipment_status' => self::SHIPMENT_STATUS_OK
        ], [
            'id' => $entityId
        ]);
    }

    /**
     * @param int $assetId
     * @param int $orderId
     * @param int $quantity
     */
    public function receiveValuable($assetId, $orderId, $quantity)
    {
        /** @var \DDD\Dao\Warehouse\Asset\Valuable $assetsValuableDao */
        $assetsValuableDao = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        /** @var \DDD\Dao\WHOrder\Order $orderDao */
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');

        $assetsValuableDao->save([
            'shipment_status' => self::SHIPMENT_STATUS_OK
        ], [
            'id' => $assetId
        ]);

        $order = $orderDao->fetchOne(['id' => $orderId], ['quantity', 'received_quantity']);

        if ($order && $quantity == $order->getQuantity() - $order->getReceivedQuantity()) {
            $status = Order::STATUS_RECEIVED;
        } else {
            $status = Order::STATUS_PARTIALLY_RECEIVED;
        }
        $orderDao->save([
            'received_quantity' => new Expression('received_quantity + ' . $quantity),
            'received_date' => date('Y-m-j H:i:s'),
            'status_shipment' => $status
        ], [
            'id' => $orderId
        ]);
    }

    /**
     * @param int $entityId
     * @param int $orderId
     * @param int $quantity
     */
    public function receiveConsumable($entityId, $orderId, $quantity)
    {
        /** @var \DDD\Dao\Warehouse\Asset\Consumable $assetsC$assetsConsumableChangesDaoonsumableDao */
        $assetsConsumableChangesDao = $this->getServiceLocator()->get('dao_warehouse_asset_changes');

        /** @var \DDD\Dao\WHOrder\Order $orderDao */
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');

        $assetsConsumableChangesDao->save(
            ['shipment_status' => self::SHIPMENT_STATUS_OK],
            ['id' => $entityId]
        );

        $order = $orderDao->fetchOne(['id' => $orderId], ['quantity', 'received_quantity']);

        $data = [
            'received_quantity' => new Expression('received_quantity + ' . $quantity),
            'received_date'     => date('Y-m-j H:i:s'),
        ];

        if ($order && $quantity == $order->getQuantity() - $order->getReceivedQuantity()) {
            $data['status_shipment'] = Order::STATUS_RECEIVED;
        }

        $orderDao->save($data, ['id' => $orderId]);
    }

    public function detectLocationbyType($typeId, $entityId)
    {
        $entityInfo = [];

        switch ($typeId) {
            case self::ENTITY_TYPE_APARTMENT:
                $apartmentDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');
                $entityInfo   = $apartmentDao->fetchOne(['id' => $entityId]);
                break;
            case self::ENTITY_TYPE_STORAGE:
                $storageDao = $this->getServiceLocator()->get('dao_warehouse_storage');
                $entityInfo = $storageDao->fetchOne(['id' => $entityId]);
                break;
            case self::ENTITY_TYPE_OFFICE:
                $officeDao  = $this->getServiceLocator()->get('dao_office_office_manager');
                $entityInfo = $officeDao->fetchOne(['id' => $entityId]);
                break;
            case self::ENTITY_TYPE_BUILDING:
                $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
                $entityInfo        = $apartmentGroupDao->fetchOne(['id' => $entityId]);
                break;
        }

        return $entityInfo;
    }

    public function getAssets($userId, $countryId, $cityId)
    {
        $assetValuableDao    = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        $assetConsumeDao     = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');

        $assetValueableInfo  = $assetValuableDao->getAssetByUser($countryId);
        $valuableStorageInfo = $assetValuableDao->getStorageAssetsByUser($userId);

        $assetConsumeInfo    = $assetConsumeDao->getConsumeAssetsByUser($userId);

        $assetList           = [];

        foreach ($assetValueableInfo as $row) {
            $asset                = iterator_to_array($row);
            $asset['assetTypeId'] = CategoryService::CATEGORY_TYPE_VALUABLE;
            $assetList[]          = $asset;
        }
        foreach ($valuableStorageInfo as $row) {
            $asset                = iterator_to_array($row);
            $asset['assetTypeId'] = CategoryService::CATEGORY_TYPE_VALUABLE;
            $assetList[]          = $asset;
        }

        foreach ($assetConsumeInfo as $row) {
            $asset                = iterator_to_array($row);
            $skus                 = explode(',', $asset['skues']);
            $asset['skues']       = $skus;
            $asset['assetTypeId'] = CategoryService::CATEGORY_TYPE_CONSUMABLE;
            $assetList[]          = $asset;
        }

        return $assetList;
    }

    public function getAssetInfoByBarcode($id)
    {
        /**
         * @var \DDD\Dao\Warehouse\Asset\Valuable $assetsValuableDao
         * @var \DDD\Dao\Warehouse\Asset\Consumable $assetsConsumableDao
         */
        $assetsValuableDao   = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
        $assetsConsumableDao = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
        $logger              = $this->getServiceLocator()->get('ActionLogger');

        $valuableInfo        = $assetsValuableDao->getAssetBySerialNumber($id);
        $consumableInfo      = $assetsConsumableDao->getConsumableInfoBySku($id);


        $type   = 0;
        $result = [];

        if ($valuableInfo) {
            $type = CategoryService::CATEGORY_TYPE_VALUABLE;
            $result = $valuableInfo ? iterator_to_array($valuableInfo) : false;
            $result['assetTypeId'] = $type;
        } else if ($consumableInfo) {
            $result = $consumableInfo ? iterator_to_array($consumableInfo) : false;
            $type = CategoryService::CATEGORY_TYPE_CONSUMABLE;
            $result['assetTypeId'] = $type;
        }

        return $result;
    }

    public function getValuableAssetHistories($assetId)
    {
        $logger    = $this->getServiceLocator()->get('ActionLogger');
        $histories = $logger->get(Logger::MODULE_ASSET_VALUABLE, $assetId, null, false);

        $historyDataArray = [];
        foreach ($histories as $history) {
            $titleType = '';
            $title     = '';

            switch ($history['action_id']) {
                case $logger::ACTION_ASSET_VALUABLE_STATUS_CHANGED:
                    $titleType = $logger::ACTION_ASSET_VALUABLE_STATUS_CHANGED;
                    $title     = "Status change";
                    break;
                case $logger::ACTION_ASSET_VALUABLE_ASSIGNEE_CHANGED:
                    $titleType = $logger::ACTION_ASSET_VALUABLE_ASSIGNEE_CHANGED;
                    $title     = "Assignee Change";
                    break;
                case $logger::ACTION_ASSET_VALUABLE_LOCATION_CHANGED:
                    $titleType = $logger::ACTION_ASSET_VALUABLE_LOCATION_CHANGED;
                    $title     = "Location Change";
                    break;
                case $logger::ACTION_ASSET_VALUABLE_ADDED_COMMENT:
                    $titleType = $logger::ACTION_ASSET_VALUABLE_ADDED_COMMENT;
                    $title     = "Added Comment";
                    break;
            }

            $historyData['title']      = $title;
            $historyData['titleType']  = $titleType;
            $historyData['date']       = $history['timestamp'];
            $historyData['desription'] = $history['value'];
            $historyData['userName']   = $history['user_name'];
            $historyDataArray[]        = $historyData;
        }

        return $historyDataArray;
    }
}
