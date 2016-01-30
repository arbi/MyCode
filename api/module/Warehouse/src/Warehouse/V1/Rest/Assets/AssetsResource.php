<?php
namespace Warehouse\V1\Rest\Assets;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

use DDD\Service\Warehouse\Category as CategoryService;

use Application\Entity\Error;
use Application\Service\ApiException;

class AssetsResource extends AbstractResourceListener
{

    protected $serviceLocator;

    public function __construct($service)
    {
        $this->serviceLocator = $service;
    }

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     *
     * @api {post} API_DOMAIN/warehouse/assets New Asset
     * @apiVersion 1.0.0
     * @apiName NewAsset
     * @apiGroup Asset
     *
     * @apiDescription This method is used for creating a new asset item. It returns the newly created asset's properties and data
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiParam {String} uuid              The Universal Unique Identifiers generated in the mobile device to prevent duplicate requests
     * @apiParam {Int} assetType            This is the type indicator for the asset. The possible values are Consumable (1) and Valuable (2)
     * @apiParam {Int} locationEntityId     The identification of the asset location
     * @apiParam {Int} locationEntityType   This is the type indicator for the asset location. The possible values are in /warehouse/configs under locationTypes
     * @apiParam {Int} quantity             This is the quantity indicator for the asset. The possible value for Valuable assets is only 1
     * @apiParam {Int} categoryId           The identification for the asset category
     * @apiParam {Int} status               This is the asset status indicator bit. The possible values for Consumable assets is always 0 and for Valuable assets is the asset type
     * @apiParam {String} barcode           This is the barcode identification of the asset
     * @apiParam {Int} assigneeId           The user identification of assigned user
     * @apiParam {String} name              This is the name for the asset. The possible values are the actual asset name for Valuable and empty string for Consumable assets
     * @apiParam {Int} shipmentStatus       This is the shipment status indicator bit. If set to 1 it indicates new asset from a received order
     * @apiParam {String} comment           The attached user comments
     *
     * @apiParamExample {json} Sample Request:
     *   {
     *       "uuid": "XXX",
     *       "assetType": 1,
     *       "locationEntityId" : 1,
     *       "locationEntityType": 2,
     *       "quantity": 3000,
     *       "categoryId": 1,
     *       "status": 0,
     *       "barcode": "XXX",
     *       "assigneeId": 12,
     *       "name": "Toilet Paper",
     *       "shipmentStatus": 1,
     *       "comment": "This is my comments"
     *   }
     *
     * @apiSuccessExample {json} Consumable Sample Response:
     *     HTTP/1.1 201 Created
     *     {
     *         "id": "7",
     *         "locationEntityId": "1",
     *         "locationEntityType": "3",
     *         "categoryId": "1",
     *         "quantity": "1100"
     *     }
     * @apiSuccessExample {json} Valuable Sample Response:
     *     HTTP/1.1 201 Created
     *     {
     *         "id": "7",
     *         "locationEntityId": "1",
     *         "locationEntityType": "3",
     *         "categoryId": "1",
     *         "status": "4",
     *         "barcode": "XXX",
     *         "assigneeId": "12",
     *         "name": "Toilet Paper"
     *     }
     *
     */
    public function create($data)
    {
        try {
            $assetService   = $this->serviceLocator->get('service_warehouse_asset');
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');

            $isDuplicateRequest  = $requestHandler->checkRequest($data->uuid);

            if ($isDuplicateRequest) {
                throw new ApiException(Error::DUPLICATE_REQUEST_CODE);
            }

            if ($data->assetType == CategoryService::CATEGORY_TYPE_CONSUMABLE) {
                $skuFromDb = $assetService->getSkuIdByName($data->barcode);
                if ($skuFromDb) {
                    if ($data->categoryId != $skuFromDb['asset_category_id']) {
                        throw new ApiException(Error::BAD_REQUEST_CODE);
                    }
                    $skuAlreadyInDbId = $skuFromDb['id'];
                } else {
                    $skuAlreadyInDbId = false;
                }

                $assetId = $assetService->checkIfCategoryLocationIdLocationEntitySkuIsUnique(
                    $data->categoryId,
                    $data->barcode,
                    $data->locationEntityType,
                    $data->locationEntityId,
                    true
                );

                if ($assetId) {
                    $assetService->updateConsumableAsset($data, $assetId);
                } else {
                    $assetId = $assetService->saveNewConsumableAsset($data, $skuAlreadyInDbId);
                }

                $assetDetails  = $assetService->getConsumableBasicInfoById($assetId);

                $result = [
                    'id'                 => $assetDetails->getId(),
                    'locationEntityId'   => $assetDetails->getLocationEntityId(),
                    'locationEntityType' => $assetDetails->getLocationEntityType(),
                    'categoryId'         => $assetDetails->getCategoryId(),
                    'quantity'           => $assetDetails->getQuantity(),
                ];
            } else {
                if (!(int)$data->status) {
                    throw new ApiException(Error::INVALID_VALUABLE_STATUS_CODE);
                }
                $assetId = $assetService->checkIfSerialNumberIsUnique($data->barcode, false, true);
                if ($assetId) {
                    $assetService->updateValuableAsset($data, $assetId);
                } else {
                    $assetId = $assetService->saveNewValuableAsset($data);
                }

                $assetDetails = $assetService->getValuableBasicInfoById($assetId);

                $result = [
                    'id'                 => $assetDetails->getId(),
                    'locationEntityId'   => $assetDetails->getLocationEntityId(),
                    'locationEntityType' => $assetDetails->getLocationEntityType(),
                    'categoryId'         => $assetDetails->getCategoryId(),
                    'status'             => $assetDetails->getStatus(),
                    'barcode'            => $assetDetails->getSerialNumber(),
                    'assigneeId'         => $assetDetails->getAssigneeId(),
                    'name'               => $assetDetails->getName(),
                ];
            }

            if ($result) {
                $requestHandler->setCompleted($data->uuid);
                return $result;
            }
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for individual resources');
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     *
     * @api {get} API_DOMAIN/warehouse/assets/:assets_id Asset Details
     * @apiVersion 1.0.0
     * @apiName AssetDetails
     * @apiGroup Asset
     * @apiDescription This method is used for checking whether an asset with a given barcode exists.
     * The assets_id parameter contains the value of asset barcode
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccessExample {json} Valuable Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "4",
     *         "status": "6",
     *         "categoryId": "2",
     *         "locationEntityId": "33",
     *         "locationEntityType": "22",
     *         "serialNumber": "erterertre",
     *         "locationName": "Chicago Storage",
     *         "categoryName": "Computer",
     *         "firstnameLastUpdated": "App",
     *         "lastnameLastUpdated": "User",
     *         "statusName": "New",
     *         "assetTypeId": "2"
     *     }
     * @apiSuccessExample {json} Consumable Sample Response:
     *    HTTP/1.1 200 OK
     *    {
     *         "id": "1",
     *         "categoryId": "1",
     *         "locationEntityId": "3",
     *         "locationEntityType": "2",
     *         "quantity": "45020",
     *         "description": "This is the Kleenex description",
     *         "locationName": "Chicago Storage",
     *         "skuName": "Kleenex",
     *         "skuId": "3",
     *         "categoryName": "Toilet Paper",
     *         "firstnameLastUpdated": "App",
     *         "lastnameLastUpdated": "User",
     *         "assetTypeId": "1"
     *     }
     *
     */
    public function fetch($id)
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $assetService   = $this->serviceLocator->get('service_warehouse_asset');
            $assetInfo      = $assetService->getAssetInfoByBarcode($id);

            if ($assetInfo) {
                return  $assetInfo;
            }
            throw new ApiException(Error::NOT_FOUND_CODE);
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * @api {get} API_DOMAIN/warehouse/assets Asset List
     * @apiVersion 1.0.0
     * @apiName AssetList
     * @apiGroup Asset
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiDescription This method returns the assets list. The possible values for assetTypeId are Consumable (1) and Valuable (2)
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     [
     *         {
     *             "id": "4",
     *             "name": "Toshiba Laptop",
     *             "categoryId": "2",
     *             "locationEntityId": "3",
     *             "locationEntityType": "2",
     *             "serialNumber": "5dsf4g35d4f3sd4f3sd4f34s",
     *             "categoryName": "Computer",
     *             "statusName": "New",
     *             "asigneeFirstname": "App",
     *             "asigneeLastname": "User",
     *             "locationName": "Chicago Storage",
     *             "assetTypeId": "2"
     *         },
     *         {
     *             "id": "1",
     *             "quantity": "450",
     *             "lastUpdatedById": "234",
     *             "shipmentStatus": "1",
     *             "locationEntityId": "3",
     *             "locationEntityType": "2",
     *             "categoryId": "1",
     *             "skues": [
     *                 "5454534544354",
     *                 "54354354543544",
     *                 "fdssdf4sd54f5ds33"
     *             ],
     *             "categoryName": "Toilet Paper",
     *             "locationName": "Chicago Storage",
     *             "assetTypeId": "1"
     *         }
     *     ]
     **/
    public function fetchAll($params = array())
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $assetService   = $this->serviceLocator->get('service_warehouse_asset');
            $auth           = $this->serviceLocator->get('library_backoffice_auth');

            $userId    = $auth->getIdentity()->id;
            $countryId = $auth->getIdentity()->countryId;
            $cityId    = $auth->getIdentity()->cityId;

            $assets = $assetService->getAssets($userId, $countryId, $cityId);

            return $assets;
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     *
     * @api {patch} API_DOMAIN/warehouse/assets/:assets_id Update Asset
     * @apiVersion 1.0.0
     * @apiName UpdateAsset
     * @apiGroup Asset
     *
     * @apiDescription This method is used for updating an existing asset item. It returns the modified asset's properties and data
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiParam {String} uuid              The Universal Unique Identifiers generated in the mobile device to prevent duplicate requests
     * @apiParam {Int} assetType            This is the type indicator for the asset. The possible values are Consumable (1) and Valuable (2)
     * @apiParam {Int} locationEntityId     The identification of the asset location
     * @apiParam {Int} locationEntityType   This is the type indicator for the asset location. The possible values are in /warehouse/configs under locationTypes
     * @apiParam {Int} quantity             This is the quantity indicator for the asset. The possible value for Valuable assets is only 1
     * @apiParam {Int} categoryId           The identification for the asset category
     * @apiParam {Int} status               This is the asset status indicator bit. The possible values for Consumable assets is always 0 and for Valuable assets is the asset type
     * @apiParam {String} barcode           This is the barcode identification of the asset
     * @apiParam {Int} assigneeId           The user identification of assigned user
     * @apiParam {String} name              This is the name for the asset. The possible values are the actual asset name for Valuable and empty string for Consumable assets
     * @apiParam {Int} shipmentStatus       This is the shipment status indicator bit. If set to 1 it indicates new asset from a received order
     * @apiParam {String} comment           The attached user comments
     *
     * @apiParamExample {json} Sample Request:
     *   {
     *     "uuid": "we354fwe534ffew54",
     *     "assetType": 1,
     *     "locationEntityId" : 1,
     *     "locationEntityType": 2,
     *     "quantity": 3000,
     *     "categoryId": 1,
     *     "status": 0,
     *     "barcode": "s3d54fs3d54f3s",
     *     "assigneeId": "12",
     *     "name": "Toilet Paper",
     *     "shipmentStatus": 1,
     *     "comment": "This is my comments"
     *   }
     * @apiSuccessExample {json} Consumable Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "7",
     *         "locationEntityId": "1",
     *         "locationEntityType": "3",
     *         "categoryId": "1",
     *         "quantity": "1100"
     *     }
     * @apiSuccessExample {json} Valuable Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "7",
     *         "locationEntityId": "1",
     *         "locationEntityType": "3",
     *         "categoryId": "1",
     *         "categoryName": "Computer",
     *         "status": "1",
     *         "statusName": "Working",
     *         "barcode": "XXX",
     *         "assigneeId": "12",
     *         "name": "Toilet Paper"
     *     }
     */
    public function patch($id, $data)
    {
        $result = false;

        $assetService   = $this->serviceLocator->get('service_warehouse_asset');
        $consumableDao  = $this->serviceLocator->get('dao_warehouse_asset_consumable');
        $valuableDao    = $this->serviceLocator->get('dao_warehouse_asset_valuable');
        $requestHandler = $this->serviceLocator->get('Service\RequestHandler');

        try {
            $isDuplicateRequest = $requestHandler->checkRequest($data->uuid);

            if ($isDuplicateRequest) {
                throw new ApiException(Error::DUPLICATE_REQUEST_CODE);
            }

            $assetDetails = false;

            if ($data->assetType == CategoryService::CATEGORY_TYPE_CONSUMABLE) {
                $assetInfo = $consumableDao->getRowByCategoryLocationIdLocationEntity(
                    $data->categoryId,
                    $data->locationEntityType,
                    $data->locationEntityId,
                    false,
                    true
                );

                if (!$assetInfo) {
                    $skuFromDb = $assetService->getSkuIdByName($data->barcode);

                    if ($skuFromDb) {
                        if ($data->categoryId != $skuFromDb['asset_category_id']) {
                            throw new ApiException(Error::BAD_REQUEST_CODE);
                        }
                        $skuAlreadyInDbId = $skuFromDb['id'];
                    } else {
                        $skuAlreadyInDbId = false;
                    }

                    $id           = $assetService->saveNewConsumableAsset($data, $skuAlreadyInDbId);
                    $assetDetails = $assetService->getConsumableBasicInfoById($id);

                    if (!$assetDetails) {
                        throw new ApiException(Error::SERVER_SIDE_PROBLEM_CODE);
                    }

                    $result = [
                        'id'                 => $assetDetails->getId(),
                        'locationEntityId'   => $assetDetails->getLocationEntityId(),
                        'locationEntityType' => $assetDetails->getLocationEntityType(),
                        'categoryId'         => $assetDetails->getCategoryId(),
                        'quantity'           => $assetDetails->getQuantity(),
                    ];
                } else {
                    $assetService->updateConsumableAsset($data, $assetInfo->getId());
                    $assetDetails = $assetService->getConsumableBasicInfoById($assetInfo->getId());

                    if (!$assetDetails) {
                        throw new ApiException(Error::SERVER_SIDE_PROBLEM_CODE);
                    }

                    $result = [
                        'id'                 => $assetDetails->getId(),
                        'locationEntityId'   => $assetDetails->getLocationEntityId(),
                        'locationEntityType' => $assetDetails->getLocationEntityType(),
                        'categoryId'         => $assetDetails->getCategoryId(),
                        'quantity'           => $assetDetails->getQuantity(),
                    ];
                }
            } else {
                if (!$data->status) {
                    throw new ApiException(Error::BAD_REQUEST_CODE);
                }

                $valuableAssetExist = $valuableDao->checkValuableAssetExist($id, true);

                if ($valuableAssetExist) {
                    $id = $valuableAssetExist->getId();
                    $assetService->updateValuableAsset($data, $id);
                    $assetDetails = $assetService->getValuableBasicInfoById($id);
                } else {
                    $id = $assetService->saveNewValuableAsset($data);
                    $assetDetails = $assetService->getValuableBasicInfoById($id);
                }

                if (!$assetDetails) {
                    throw new ApiException(Error::SERVER_SIDE_PROBLEM_CODE);
                }
                $result = [
                    'id'                 => $assetDetails->getId(),
                    'locationEntityId'   => $assetDetails->getLocationEntityId(),
                    'locationEntityType' => $assetDetails->getLocationEntityType(),
                    'categoryId'         => $assetDetails->getCategoryId(),
                    'categoryName'       => $assetDetails->getCategoryName(),
                    'status'             => $assetDetails->getStatus(),
                    'statusName'         => $assetDetails->getStatusName(),
                    'barcode'            => $assetDetails->getSerialNumber(),
                    'assigneeId'         => $assetDetails->getAssigneeId(),
                    'name'               => $assetDetails->getName(),
                ];
            }

            if ($result) {
                $requestHandler->setCompleted($data->uuid);
                return $result;
            }
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for individual resources');
    }
}
