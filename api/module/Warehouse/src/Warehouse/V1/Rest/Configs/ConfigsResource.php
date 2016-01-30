<?php
namespace Warehouse\V1\Rest\Configs;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

use DDD\Service\Warehouse\Category as CategoryService;
use DDD\Service\Warehouse\Asset as AssetService;
use DDD\Service\Task as TaskService;

use Library\Upload\Files;

use Application\Entity\Error;
use Application\Service\ApiException;

class ConfigsResource extends AbstractResourceListener
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
     */
    public function create($data)
    {
        return new ApiProblem(405, 'The POST method has not been defined');
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
     */
    public function fetch($id)
    {
        return new ApiProblem(405, 'The GET method has not been defined for individual resources');
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     *
     * @api {get} API_DOMAIN/warehouse/configs Configs List
     * @apiVersion 1.0.0
     * @apiName GetConfigs
     * @apiGroup Config
     *
     * @apiDescription This method returns the list of all warehouse configurations
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Int} assets The update interval for updating assets list. The value is in seconds
     * @apiSuccess {Int} locations The update interval for updating locations list. The value is in seconds
     * @apiSuccess {Int} users The update interval for updating users list. The value is in seconds
     * @apiSuccess {Int} categories The update interval for updating category list. The value is in seconds
     * @apiSuccess {Int} configs The update interval for updating configurations. The value is in seconds
     * @apiSuccess {Int} version The current API version
     * @apiSuccess {Int} requestTTL The value indicating how long should a request stay in the mobile
     * @apiSuccess {Object} assetTypes All possible values for asset types
     * @apiSuccess {Object} assetValuableStatuses All possible values for Valuable asset statuses
     * @apiSuccess {Object} locationTypes All possible values for location types
     * @apiSuccess {Object} assetChangeTypes All possible values for asset change types
     * @apiSuccess {Object} attachmentTypes All possible values for attachment types
     * @apiSuccess {Object} moduleTypes All possible values for module types
     * @apiSuccess {Object} imageConfigs The maximum width and height for images. Anything larger is compressed on the client to match this criteria.
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "updateIntervals": {
     *             "assets": 86400,
     *             "locations": 86400,
     *             "users": 86400,
     *             "categories": 86400,
     *             "configs": 86400
     *         },
     *         "requestTTL": 604800,
     *         "version": 1,
     *         "assetTypes": {
     *              "1": "Consumable",
     *              "2": "Valuable"
     *         },
     *         "assetValuableStatuses": {
     *             "1": "Working",
     *             "2": "Broken",
     *             "3": "Lost",
     *             "4": "Retired",
     *             "5": "Expunged",
     *             "6": "New",
     *             "7": "Repair"
     *         },
     *         "locationTypes": {
     *             "1": "Apartment",
     *             "2": "Storage",
     *             "3": "Office",
     *             "4": "Building"
     *         },
     *         "assetChangeTypes" : {
     *             "155": "Status Change",
     *             "156": "Assignee Change",
     *             "157": "Location Change",
     *             "158": "Added Comment"
     *         },
     *         "attachmentTypes": {
     *             "1": "All",
     *             "2": "Image",
     *             "3": "Document"
     *         },
     *         "moduleTypes": {
     *             "1": "Incident"
     *         },
     *         "imageConfigs": {
     *             "maxWidth": 1024,
     *             "maxHeight": 1024
     *         }
     *     }
     */
    public function fetchAll($params = array())
    {
        try {
            $config         = $this->serviceLocator->get('config');
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $assetService   = $this->serviceLocator->get('service_warehouse_asset');

            $result = [];

            if (isset($config['warehouse'])) {
                $result['updateIntervals']['assets']     = $config['warehouse']['assets'];
                $result['updateIntervals']['locations']  = $config['warehouse']['locations'];
                $result['updateIntervals']['users']      = $config['warehouse']['users'];
                $result['updateIntervals']['categories'] = $config['warehouse']['categories'];
                $result['updateIntervals']['configs']    = $config['warehouse']['updateTime'];
                $result['requestTTL']                    = $config['warehouse']['apiExpired'];
                $result['version']                       = $config['warehouse']['version'];
            }

            $result['assetTypes']            = CategoryService::$categoryTypes;
            $assetStatuses                   = $assetService->getValuableAssetsStatusesArray(true);
            $result['assetValuableStatuses'] = [];

            if ($assetStatuses && count($assetStatuses)) {
                $result['assetValuableStatuses'] = $assetStatuses;
            }

            $result['locationTypes']    = AssetService::$types;
            $result['assetChangeTypes'] = AssetService::$assetChangeTypes;
            $result['attachmentTypes']  = Files::getAttachmentTypes();
            $result['moduleTypes']      = TaskService::getModuleTypes();
            $result['imageConfigs']     = $config['imageConfigs'];

            return $result;

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
     */
    public function patch($id, $data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for individual resources');
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
