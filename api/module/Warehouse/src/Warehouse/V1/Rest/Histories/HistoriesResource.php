<?php
namespace Warehouse\V1\Rest\Histories;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

use Application\Entity\Error;
use Application\Service\ApiException;

class HistoriesResource extends AbstractResourceListener
{
    protected $serviceLocator;
    protected $mapper;

    public function __construct($service, $mapper)
    {
        $this->serviceLocator = $service;
        $this->mapper = $mapper;
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
     * @api {get} API_DOMAIN/warehouse/assets/:assets_id/histories Asset History
     * @apiVersion 1.0.0
     * @apiName GetHistory
     * @apiGroup Asset
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiDescription This method returns a Valuable asset history list
     *
     * @apiSuccess {String} title       This is the asset modification title
     * @apiSuccess {Int} titleType      This is the asset modification type. The possible values are in /warehouse/configs under assetChangeTypes
     * @apiSuccess {String} username    The username of the user who added the comment
     * @apiSuccess {String} description The description text about the action
     * @apiSuccess {String} date        The date and time when the comment was added
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "_links": {
     *             "self": {
     *                 "href": "https://API_DOMAIN/warehouse/assets/1/histories?page=1"
     *             },
     *            "first": {
     *                "href": "https://API_DOMAIN/warehouse/assets/1/histories"
     *            },
     *            "last": {
     *                "href": "https://API_DOMAIN/warehouse/assets/1/histories?page=2"
     *            },
     *            "next": {
     *                "href": "https://API_DOMAIN/warehouse/assets/1/histories?page=2"
     *            }
     *         },
     *         "_embedded": {
     *             "histories": [
     *                 {
     *                     "title": "Status Changes",
     *                     "titleType": 155,
     *                     "username": "app.user@ginosi.com",
     *                     "description": "We changed this item because it was broken",
     *                     "date": "2015-11-11 12:12:12"
     *                 }
     *             ]
     *         },
     *         "page_count": 2,
     *         "page_size": 25,
     *         "total_items": 43,
     *         "page": 1
     *     }
     */
    public function fetchAll($params = array())
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $request        = $this->serviceLocator->get('request');
            $router         = $this->serviceLocator->get('router');
            $serialNumber   = $router->match($request)->getParam("assets_id");

            $assetService   = $this->serviceLocator->get('service_warehouse_asset');
            $assetInfo      = $assetService->getAssetInfoByBarcode($serialNumber);

            if (!$assetInfo) {
                throw new ApiException(Error::ASSET_NOT_FOUND_CODE);
            }

            return $this->mapper->fetchAll($assetInfo['id']);
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
