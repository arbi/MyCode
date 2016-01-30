<?php

namespace Warehouse\Controller;

use DDD\Service\Apartment\General;
use DDD\Service\ApartmentGroup;
use DDD\Service\Office;
use DDD\Service\Warehouse\Category;
use DDD\Service\Warehouse\Asset as AssetService;
use DDD\Service\Warehouse\Storage;
use DDD\Service\WHOrder\Order as OrderService;

use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\ActionLogger\Logger;

use Warehouse\Form\AssetValuable as AssetValuableForm;
use Warehouse\Form\AssetConsumable as AssetConsumableForm;
use Warehouse\Form\AssetConsumableSearch as AssetConsumableSearchForm;
use Warehouse\Form\AssetValuableSearch as AssetValuableSearchForm;

use Zend\View\Model\JsonModel;

class Asset extends ControllerBase
{

    public function indexAction()
    {
        /**
         * @var Category $categoryService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasAssetManagementModule = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT);
        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementModule && !$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }
        $categoryService   = $this->getServiceLocator()->get('service_warehouse_category');
        $allActiveCategories = $categoryService->getCategories();
        $mapOfCategoryTypes = [];
        $mapOfCategories    = [];
        foreach ($allActiveCategories as $category) {
            $mapOfCategoryTypes[$category->getId()] = $category->getType();
            $mapOfCategories[$category->getId()] = $category->getName();
        }

        return [
            'allActiveCategories' => $allActiveCategories,
            'mapOfCategoryTypes'  => $mapOfCategoryTypes,
            'mapOfCategories'     => $mapOfCategories,
            'hasGlobalRole'       => $hasAssetManagementGlobal
        ];
    }

    public function addAction()
    {
        /**
         * @var Category $categoryService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }
        $categoryService   = $this->getServiceLocator()->get('service_warehouse_category');
        $allActiveCategories = $categoryService->getCategories();
        $mapOfCategoryTypes = [];
        $mapOfCategories    = [];
        foreach ($allActiveCategories as $category) {
            $mapOfCategoryTypes[$category->getId()] = $category->getType();
            $mapOfCategories[$category->getId()] = $category->getName();
        }
        return [
            'allActiveCategories' => $allActiveCategories,
            'mapOfCategoryTypes'  => $mapOfCategoryTypes,
            'mapOfCategories'  => $mapOfCategories
        ];
    }

    public function renderTemplateAction()
    {
        /**
         * @var General $apartmentServiceGeneral
         * @var Storage $storageService
         * @var Office $officeService
         * @var ApartmentGroup\Usages\Building  $buildingService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $categoryType = (int) $request->getPost('categoryType');
            $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
            $partialFile ='warehouse/asset/partial/';

            $apartmentServiceGeneral = $this->getServiceLocator()->get('service_apartment_general');
            $storageService = $this->getServiceLocator()->get('service_warehouse_storage');
            $officeService = $this->getServiceLocator()->get('service_office');
            $buildingService = $this->getServiceLocator()->get('service_apartment_group_usages_building');


            $apartmentsList   = $apartmentServiceGeneral->getApartmentSearch(false);
            $storageList      = $storageService->searchStorageByName(false);
            $officesList      = $officeService->searchOfficeByName(false);
            $buildingList     = $buildingService->getBuildingListForSelectize();

            $locationList = [];

            foreach ($storageList as $storage) {
                array_push($locationList, [
                    'id'    => AssetService::ENTITY_TYPE_STORAGE . '_' . $storage->getId(),
                    'info'  => $storage->getCityName(),
                    'label' => 'storage',
                    'text'  => $storage->getName(),
                    'type'  => AssetService::ENTITY_TYPE_STORAGE
                ]);
            }

            switch ($categoryType) {
                case Category::CATEGORY_TYPE_VALUABLE:
                    $partialFile .= 'valuable';
                    $userService       = $this->getServiceLocator()->get('service_user');
                    $activeUsers       = $userService->getAllActiveUsersArray();

                    foreach ($apartmentsList as $apartment) {
                        array_push($locationList, [
                            'id'    => AssetService::ENTITY_TYPE_APARTMENT . '_' . $apartment['id'],
                            'info'  => $apartment['location_name'],
                            'label' => 'apartment',
                            'text'  => $apartment['name'],
                            'type'  => AssetService::ENTITY_TYPE_APARTMENT
                        ]);
                    }

                    foreach ($officesList as $office) {
                        array_push($locationList, [
                            'id'    => AssetService::ENTITY_TYPE_OFFICE . '_' . $office->getId(),
                            'info'  => $office->getCity(),
                            'label' => 'office',
                            'text'  => $office->getName(),
                            'type'  => AssetService::ENTITY_TYPE_OFFICE
                        ]);
                    }

                    foreach ($buildingList as $building) {
                        array_push($locationList, [
                            'id'    => AssetService::ENTITY_TYPE_BUILDING . '_' . $building['id'],
                            'info'  => $building['country'],
                            'label' => 'building',
                            'text'  => $building['name'],
                            'type'  => AssetService::ENTITY_TYPE_BUILDING
                        ]);
                    }


                    $assetValuableForm = new AssetValuableForm($activeUsers);
                    $result['partial_html'] = $partial($partialFile,['form' => $assetValuableForm]);
                    break;
                case Category::CATEGORY_TYPE_CONSUMABLE:
                    $partialFile .= 'consumable';
                    $assetConsumableForm = new AssetConsumableForm();
                    $result['partial_html'] = $partial($partialFile,['form' => $assetConsumableForm]);
                    break;
            }

            $result['location_list'] = json_encode($locationList);
            $result['status'] = 'success';
            unset($result['msg']);

        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);

    }

    public function renderTemplateSearchAction()
    {
        /**
         * @var General $apartmentServiceGeneral
         * @var Storage $storageService
         * @var Office $officeService
         * @var ApartmentGroup\Usages\Building  $buildingService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $categoryType     = (int) $request->getPost('categoryType');
            $partial          = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
            $partialDirectory ='warehouse/asset/partial/';

            $apartmentServiceGeneral = $this->getServiceLocator()->get('service_apartment_general');
            $storageService          = $this->getServiceLocator()->get('service_warehouse_storage');
            $officeService           = $this->getServiceLocator()->get('service_office');
            $buildingService         = $this->getServiceLocator()->get('service_apartment_group_usages_building');


            $apartmentsList = $apartmentServiceGeneral->getApartmentSearch(false);
            $storageList    = $storageService->searchStorageByName(false);
            $officesList    = $officeService->searchOfficeByName(false);
            $buildingList   = $buildingService->getBuildingListForSelectize();

            $locationList = [];

            foreach ($storageList as $storage) {
                array_push($locationList, [
                    'id'    => AssetService::ENTITY_TYPE_STORAGE . '_' . $storage->getId(),
                    'info'  => $storage->getCityName(),
                    'label' => 'storage',
                    'text'  => $storage->getName(),
                    'type'  => AssetService::ENTITY_TYPE_STORAGE
                ]);
            }

            switch ($categoryType) {
                case Category::CATEGORY_TYPE_VALUABLE:
                    $partialDataTableFile   = $partialDirectory . 'valuable-datatable';
                    $partialFile            = $partialDirectory . 'valuable-search';
                    $userService            = $this->getServiceLocator()->get('service_user');
                    $assetService           = $this->getServiceLocator()->get('service_warehouse_asset');
                    $valuableAssetsStatuses = $assetService->getValuableAssetsStatusesArray();
                    $activeUsers            = $userService->getAllActiveUsersArray();
                    $activeUsers[0]         = '-- All Assignee --';

                    foreach ($apartmentsList as $apartment) {
                        array_push($locationList, [
                            'id'    => AssetService::ENTITY_TYPE_APARTMENT . '_' . $apartment['id'],
                            'info'  => $apartment['location_name'],
                            'label' => 'apartment',
                            'text'  => $apartment['name'],
                            'type'  => AssetService::ENTITY_TYPE_APARTMENT
                        ]);
                    }

                    foreach ($officesList as $office) {
                        array_push($locationList, [
                            'id'    => AssetService::ENTITY_TYPE_OFFICE . '_' . $office->getId(),
                            'info'  => $office->getCity(),
                            'label' => 'office',
                            'text'  => $office->getName(),
                            'type'  => AssetService::ENTITY_TYPE_OFFICE
                        ]);
                    }

                    foreach ($buildingList as $building) {
                        array_push($locationList, [
                            'id'    => AssetService::ENTITY_TYPE_BUILDING . '_' . $building['id'],
                            'info'  => $building['country'],
                            'label' => 'building',
                            'text'  => $building['name'],
                            'type'  => AssetService::ENTITY_TYPE_BUILDING
                        ]);
                    }

                    $assetValuableForm                = new AssetValuableSearchForm($activeUsers, $valuableAssetsStatuses);
                    $result['partial_html']           = $partial($partialFile,['form' => $assetValuableForm]);
                    $result['partial_datatable_html'] = $partial($partialDataTableFile);
                    break;
                case Category::CATEGORY_TYPE_CONSUMABLE:
                    $partialFile                      = $partialDirectory . 'consumable-search';
                    $partialDataTableFile             = $partialDirectory . 'consumable-datatable';
                    $assetConsumableSearchForm        = new AssetConsumableSearchForm();
                    $result['partial_html']           = $partial($partialFile,['form' => $assetConsumableSearchForm]);
                    $result['partial_datatable_html'] = $partial($partialDataTableFile);
                    break;
            }

            $result['location_list'] = json_encode($locationList);
            $result['status']        = 'success';
            unset($result['msg']);

        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);

    }

    public function ajaxAddValuableAction()
    {
        /**
         * @var AssetService $assetService
         */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $assetService = $this->getServiceLocator()->get('service_warehouse_asset');
                if (!$assetService->checkIfSerialNumberIsUnique($request->getPost('serialNumber'))) {
                    $result['msg'] = 'Serial Number should be unique';
                    throw new \Exception();
                }

                $id = $assetService->saveNewValuableAsset($request);
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                $result = [
                    'type'   => 'valuable',
                    'id'     => $id,
                    'status' => 'success',
                    'msg'    => 'OK',
                ];
            } catch (\Exception $ex) {

            }
        } else {

        }

        return new JsonModel($result);
    }

    public function ajaxAddConsumableAction()
    {
        /**
         * @var AssetService $assetService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $assetService       = $this->getServiceLocator()->get('service_warehouse_asset');
                $location           = $request->getPost('location');
                $locationArray      = explode('_', $location);
                $locationEntityType = $locationArray[0];
                $locationEntityId   = $locationArray[1];
                $categoryId         = $request->getPost('category');
                $sku                = $request->getPost('sku');

                $skuFromDb = $assetService->getSkuIdByName($sku);
                if (false !== $skuFromDb) {
                    if ($categoryId != $skuFromDb['asset_category_id']) {
                        $result['msg'] = 'Sku with this name already exists in another category.';
                        throw new \Exception();
                    }
                    $skuAlreadyInDbId = $skuFromDb['id'];
                } else {
                    $skuAlreadyInDbId = false;
                }

                if (!$assetService->checkIfCategoryLocationIdLocationEntitySkuIsUnique(
                        $categoryId,
                        $sku,
                        $locationEntityType,
                        $locationEntityId
                    )
                ) {
                    $result['msg'] = '<ul><li style="list-style-type:none;">Category, Location, Sku combination shall be unique!</li>' .
                    '<li style="list-style-type:none;">The right approach is to increase existing quantity!</li></ul>';
                    throw new \Exception();
                }

                $id = $assetService->saveNewConsumableAsset($request, $skuAlreadyInDbId);

                if (!$id) {
                    $result['msg'] = 'Could not save changes';
                    throw new \Exception();
                }
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                $result = [
                    'type'   => 'consumable',
                    'id'     => $id,
                    'status' => 'success',
                    'msg'    => 'OK',
                ];
            } catch (\Exception $ex) {
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function ajaxSearchValuableAction()
    {
        /**
         * @var AssetService $assetService
         */
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $requestParams = $this->getRequest()->getPost();
            $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

            $result = $assetService->getDatatableDataValuable(
                $requestParams['iDisplayStart'],
                $requestParams['iDisplayLength'],
                $requestParams['iSortCol_0'],
                $requestParams['sSortDir_0'],
                $requestParams['sSearch'],
                [
                    'category'               => $requestParams['category_id'],
                    'location'                  => $requestParams['location'],
                    'status'                    => $requestParams['status'],
                ]
            );

            $result = [
                'iTotalRecords'         => $result['total'],
                'iTotalDisplayRecords'  => $result['total'],
                'iDisplayStart'         => $requestParams['iDisplayStart'],
                'iDisplayLength'        => $requestParams['iDisplayLength'],
                'aaData'                => $result['data']
            ];
        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function ajaxSearchConsumableAction()
    {
        /**
         * @var AssetService $assetService
         */
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $requestParams = $this->getRequest()->getPost();
            $assetService = $this->getServiceLocator()->get('service_warehouse_asset');
            $result = $assetService->getDatatableDataConsumable(
                $requestParams['iDisplayStart'],
                $requestParams['iDisplayLength'],
                $requestParams['iSortCol_0'],
                $requestParams['sSortDir_0'],
                $requestParams['sSearch'],
                [
                    'category'   => $requestParams['category_id'],
                    'location'   => $requestParams['location'],
                    'runningOut' => $requestParams['running_out'],
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
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function editValuableAction()
    {
        /**
         * @var General $apartmentServiceGeneral
         * @var Storage $storageService
         * @var Office $officeService
         * @var ApartmentGroup\Usages\Building  $buildingService
         * @var AssetService $assetService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);

        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }

        $id           = $this->params()->fromRoute('id', 0);
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');
        /** @var \DDD\Domain\Warehouse\Assets\Valuable $basicInfo */
        $basicInfo    = $assetService->getValuableBasicInfoById($id);

        if (false === $basicInfo) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toUrl( '/' );
        }

        $apartmentServiceGeneral = $this->getServiceLocator()->get('service_apartment_general');
        $storageService          = $this->getServiceLocator()->get('service_warehouse_storage');
        $officeService           = $this->getServiceLocator()->get('service_office');
        $buildingService         = $this->getServiceLocator()->get('service_apartment_group_usages_building');
        /** @var \DDD\Service\User $userService */
        $userService             = $this->getServiceLocator()->get('service_user');

        $activeUsers       = $userService->getAllActiveUsersArray(true, $basicInfo->getAssigneeId());

        $apartmentsList   = $apartmentServiceGeneral->getApartmentSearch(false);
        $storageList      = $storageService->searchStorageByName(false);
        $officesList      = $officeService->searchOfficeByName(false);
        $buildingList     = $buildingService->getBuildingListForSelectize();

        $locationList = [];

        foreach ($storageList as $storage) {
            array_push($locationList, [
                'id'    => AssetService::ENTITY_TYPE_STORAGE . '_' . $storage->getId(),
                'info'  => $storage->getCityName(),
                'label' => 'storage',
                'text'  => $storage->getName(),
                'type'  => AssetService::ENTITY_TYPE_STORAGE
            ]);
        }

        foreach ($apartmentsList as $apartment) {
            array_push($locationList, [
                'id'    => AssetService::ENTITY_TYPE_APARTMENT . '_' . $apartment['id'],
                'info'  => $apartment['location_name'],
                'label' => 'apartment',
                'text'  => $apartment['name'],
                'type'  => AssetService::ENTITY_TYPE_APARTMENT
            ]);
        }

        foreach ($officesList as $office) {
            array_push($locationList, [
                'id'    => AssetService::ENTITY_TYPE_OFFICE . '_' . $office->getId(),
                'info'  => $office->getCity(),
                'label' => 'office',
                'text'  => $office->getName(),
                'type'  => AssetService::ENTITY_TYPE_OFFICE
            ]);
        }

        foreach ($buildingList as $building) {
            array_push($locationList, [
                'id'    => AssetService::ENTITY_TYPE_BUILDING . '_' . $building['id'],
                'info'  => $building['country'],
                'label' => 'building',
                'text'  => $building['name'],
                'type'  => AssetService::ENTITY_TYPE_BUILDING
            ]);
        }

        /** @var \DDD\Service\Warehouse\Category $categoryService */
        $categoryService             = $this->getServiceLocator()->get('service_warehouse_category');
        $allActiveValuableCategories = $categoryService->getCategories([Category::CATEGORY_TYPE_VALUABLE], true, $basicInfo->getCategoryId());
        $allActiveCategoriesArray    = [];

        foreach($allActiveValuableCategories as $row) {
           $allActiveCategoriesArray[$row->getId()] = $row->getName();
        }

        $valuableAssetsStatuses = $assetService->getValuableAssetsStatusesArray();
        $assetValuableForm      = new AssetValuableForm($activeUsers, $id, $allActiveCategoriesArray,$valuableAssetsStatuses,$basicInfo );

        /**
         * @var Logger $loggerService
         */
        $loggerService = $this->getServiceLocator()->get('ActionLogger');

        $actionLogs = $loggerService->getDatatableData(Logger::MODULE_ASSET_VALUABLE, $id);

        return [
            'id'           => $id,
            'basicInfo'    => $basicInfo,
            'form'         => $assetValuableForm,
            'location'     => $basicInfo->getLocationEntityType() . '_' . $basicInfo->getLocationEntityId(),
            'locationList' => $locationList,
            'historyData'  => json_encode($actionLogs),
        ];
    }

    public function editSaveValuableAction()
    {
        /**
         * @var AssetService $assetService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $assetService = $this->getServiceLocator()->get('service_warehouse_asset');
                if (!$assetService->checkIfSerialNumberIsUnique($request->getPost('serialNumber'), $request->getPost('id'))) {
                    $result['msg'] = 'Serial Number should be unique';
                    throw new \Exception();
                }
                $assetService->updateValuableAsset($request);
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $result = [
                    'status' => 'success',
                    'msg' => 'OK',
                ];
            } catch (\Exception $ex) {

            }
        } else {

        }

        return new JsonModel($result);
    }


    public function editConsumableAction()
    {
        /**
         * @var General $apartmentServiceGeneral
         * @var Storage $storageService
         * @var Office $officeService
         * @var ApartmentGroup\Usages\Building  $buildingService
         * @var AssetService $assetService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }

        $id = $this->params()->fromRoute('id', 0);
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');
        $basicInfo = $assetService->getConsumableBasicInfoById($id);
        if (FALSE === $basicInfo) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toUrl( '/' );
        }

        $storageService = $this->getServiceLocator()->get('service_warehouse_storage');
        $storageList    = $storageService->searchStorageByName(false);
        $locationList   = [];

        foreach ($storageList as $storage) {
            array_push($locationList, [
                'id'    => AssetService::ENTITY_TYPE_STORAGE . '_' . $storage->getId(),
                'info'  => $storage->getCityName(),
                'label' => 'storage',
                'text'  => $storage->getName(),
                'type'  => AssetService::ENTITY_TYPE_STORAGE
            ]);
        }

        $categoryService               = $this->getServiceLocator()->get('service_warehouse_category');
        $allActiveConsumableCategories = $categoryService->getCategories([Category::CATEGORY_TYPE_CONSUMABLE]);
        $allActiveCategoriesArray      = [];

        foreach($allActiveConsumableCategories as $row) {
            $allActiveCategoriesArray[$row->getId()] = $row->getName();
        }

        $assetConsumableForm = new AssetConsumableForm( $id, $allActiveCategoriesArray, $basicInfo );

        $finalStatuses = OrderService::getIrreversiblyStatuses();

        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $ordersRelated = $orderService->getRelatedOrders($basicInfo->getCategoryId(),
            $basicInfo->getLocationEntityId(),
            $basicInfo->getLocationEntityType(),
            $finalStatuses
        );
        $hasOrderManagementGlobal = $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);

        /**
         * @var Logger $loggerService
         */
        $loggerService = $this->getServiceLocator()->get('ActionLogger');

        $actionLogs = $loggerService->getDatatableData(Logger::MODULE_ASSET_CONSUMABLE, $id);

        return [
            'id'                       => $id,
            'basicInfo'                => $basicInfo,
            'form'                     => $assetConsumableForm,
            'location'                 => $basicInfo->getLocationEntityType() . '_' . $basicInfo->getLocationEntityId(),
            'locationList'             => $locationList,
            'ordersRelated'            => $ordersRelated,
            'hasOrderManagementGlobal' => $hasOrderManagementGlobal,
            'historyData'              => json_encode($actionLogs)
        ];
    }

    public function editSaveConsumableAction()
    {
        /**
         * @var AssetService $assetService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $assetService       = $this->getServiceLocator()->get('service_warehouse_asset');
                $location           = $request->getPost('location');
                $locationArray      = explode('_', $location);
                $locationEntityType = $locationArray[0];
                $locationEntityId   = $locationArray[1];

                if (FALSE !== $assetService->checkIfCategoryLocationIdLocationEntityIsUnique(
                        $request->getPost('category'),
                        $locationEntityType,
                        $locationEntityId,
                        $request->getPost('id')
                    )) {
                    $result['msg'] = '<ul><li>This category already exists in this storage.</li><li>The right approach is to change the quantities</li>';
                    throw new \Exception();
                }
                $assetService->updateConsumableAsset($request);
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $result = [
                    'status' => 'success',
                    'msg'    => 'OK',
                ];
            } catch (\Exception $ex) {

            }
        } else {

        }

        return new JsonModel($result);
    }

    public function resolveValuableAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var \DDD\Service\Warehouse\Asset $assetService */
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }

        $assetId = $this->params()->fromRoute('id', 0);
        $assetService->resolveValuable($assetId);

        return new JsonModel([
            'status' => 'success',
            'msg'    => 'Asset successfully resolved'
        ]);
    }

    public function resolveConsumableAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var \DDD\Service\Warehouse\Asset $assetService */
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }

        $assetId = $this->params()->fromRoute('id', 0);
        $assetService->resolveConsumable($assetId);

        return new JsonModel([
            'status' => 'success',
            'msg'    => 'Asset successfully resolved'
        ]);
    }

    public function receiveValuableAction()
    {
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        /** @var \DDD\Service\Warehouse\Asset $assetService */
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }

        $assetId  = $this->params()->fromRoute('id', 0);
        $orderId  = $request->getPost('orderId');
        $quantity = $request->getPost('quantity');

        $assetService->receiveValuable($assetId, $orderId, $quantity);

        return new JsonModel([
            'status' => 'success',
            'msg' => 'Asset successfully resolved'
        ]);
    }

    public function receiveConsumableAction()
    {
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        /** @var \DDD\Service\Warehouse\Asset $assetService */
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

        $hasAssetManagementGlobal = $auth->hasRole(Roles::ROLE_ASSET_MANAGEMENT_GLOBAL);
        if (!$hasAssetManagementGlobal) {
            return $this->redirect()->toUrl( '/' );
        }

        $assetChangesId = $this->params()->fromRoute('id', 0);
        $orderId        = $request->getPost('orderId');
        $quantity       = $request->getPost('quantity');

        $assetService->receiveConsumable($assetChangesId, $orderId, $quantity);

        return new JsonModel([
            'status' => 'success',
            'msg'    => 'Asset successfully resolved'
        ]);
    }
}
