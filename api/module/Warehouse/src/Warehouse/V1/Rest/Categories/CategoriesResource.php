<?php
namespace Warehouse\V1\Rest\Categories;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

use Application\Entity\Error;
use Application\Service\ApiException;

class CategoriesResource extends AbstractResourceListener
{
    private $serviceLocator;

    public function __construct($service)
    {
        $this->serviceLocator = $service;
    }

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     * @api {post} API_DOMAIN/warehouse/categories New Category
     * @apiVersion 1.0.0
     * @apiName NewCategory
     * @apiGroup Category
     *
     * @apiDescription This method is used for creating a new category
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiParam {String} uuid  The Universal Unique Identifiers generated in the mobile device to prevent duplicate requests
     * @apiParam {Int} type The category type. The possible values are in /warehouse/configs under assetTypes
     * @apiParam {String} name  The category name
     *
     * @apiParamExample {json} Sample Request:
     *   {
     *       "uuid": "XXX",
     *       "type": 1,
     *       "name": "Toilet Paper"
     *   }
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "1",
     *         "name": "Toilet Paper",
     *         "type": "1"
     *     }
     */
    public function create($data)
    {
        try {

            $requestHandler  = $this->serviceLocator->get('Service\RequestHandler');
            $categoryService = $this->serviceLocator->get('service_warehouse_category');

            /**
             * @var \DDD\Dao\Warehouse\Category $categoryDao
             */
            $categoryDao = $this->serviceLocator->get('dao_warehouse_category');

            $isDuplicateRequest = $requestHandler->checkRequest($data->uuid);

            if ($isDuplicateRequest) {
                throw new ApiException(Error::DUPLICATE_REQUEST_CODE);
            }

            $categoryData = [
              'name' => $data->name,
              'type' => $data->type,
            ];

            $duplicateCategory = $categoryDao->checkCategoryExist($data->name, $data->type);

            if ($duplicateCategory) {
                throw new ApiException(Error::DUPLICATE_CATEGORY_CODE);
            }

            $categoryData['id'] = $categoryService->saveCategory($categoryData);

            $requestHandler->setCompleted($data->uuid);
            return $categoryData;
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
     */
    public function fetch($id)
    {

        return new ApiProblem(405, 'The GET method has not been defined for individual resources');
    }

    /**
     * @api {get} API_DOMAIN/warehouse/categories Category List
     * @apiVersion 1.0.0
     * @apiName GetCategories
     * @apiGroup Category
     *
     * @apiDescription This method returns all category list
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Int} id The category identification
     * @apiSuccess {String} name  The category name
     * @apiSuccess {Int} type  The category type. The possible values are Consumable (1) and Valuable (2)
     * @apiSuccess {String[]} skues  This is the list of SKUes for the given category
     * @apiSuccess {String[]} aliases This is the list of aliases for the given category
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "1",
     *         "name": "Toilet Paper",
     *         "type": "1",
     *         "skues": [
     *             "4ds3f4sd3fde4",
     *             "sa53d4sa534d4"
     *         ],
     *         "aliases": [
     *             "Kleenex",
     *             "Royale",
     *             "Delica"
     *         ]
     *     }
     */
    public function fetchAll($params = [])
    {
        try {
            $requestHandler  = $this->serviceLocator->get('Service\RequestHandler');
            $categoryService = $this->serviceLocator->get('service_warehouse_category');
            $categoryList    = $categoryService->getCategoriesList();

            return $categoryList;
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
