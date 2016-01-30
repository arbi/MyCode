<?php
namespace Common\V1\Rest\Locations;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use ZF\ApiProblem\ApiProblemResponse;

use Application\Entity\Error;
use Application\Service\ApiException;

class LocationsResource extends AbstractResourceListener
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
     * @api {get} API_DOMAIN/locations Location List
     * @apiVersion 1.0.0
     * @apiName LocationList
     * @apiGroup Location
     *
     * @apiDescription This method returns the list of all locations for the authenticated user
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "apartments": [
     *             {
     *                 "id": "42",
     *                 "name": "Test Apartment n1",
     *                 "address": "104 Andranik st",
     *                 "buildingId": "1",
     *                 "cityId": "6",
     *                 "locationType": "1"
     *             }
     *         ],
     *         "buildings": [
     *             {
     *                 "id": "1",
     *                 "name": "Test Apartment Group",
     *                 "countryId": "2",
     *                 "locationType": "4"
     *             }
     *         ],
     *         "storages": [
     *             {
     *                 "id": "3",
     *                 "name": "Chicago Storage",
     *                 "locationType": "2"
     *             }
     *         ],
     *         "offices": [
     *             {
     *                 "id": "1",
     *                 "name": "Yerevan Office",
     *                 "description": "Head office of Ginosi located in Yerevan city",
     *                 "address": "K. Ulnetsi 31",
     *                 "countryId": "2",
     *                 "cityId": "6",
     *                 "locationType": "3"
     *             }
     *         ]
     *     }
     */
    public function fetchAll($params = array())
    {
        try {
            $requestHandler  = $this->serviceLocator->get('Service\RequestHandler');
            $locationService = $this->serviceLocator->get('service_location');

            $locations = $locationService->getAllTypesOfLocations();

            // if (!$locations) {
            //     throw new ApiException(Error::SERVER_SIDE_PROBLEM_CODE);
            // }
            return $locations;
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
