<?php
namespace GinosiTally\V1\Rest\UserPins;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

class UserPinsResource extends AbstractResourceListener
{
    /**
     * @var
     */
    protected $serviceLocator;

    /**
     * @param $service
     */
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
     * @api {get} API_DOMAIN/ginosi-tally/user/:user_id/pin/:pin User Pins
     * @apiVersion 1.0.0
     * @apiName  UserPins
     * @apiGroup User
     * @apiDescription This method check user pin code and return status.
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Boolean}  status               Response status
     * @apiSuccess {String}   message              Response message
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "status":  true,
     *         "message": "Success"
     *     }
     *
     * @param  int $pin
     * @return ApiProblem|array
     */
    public function fetch($pin)
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $request        = $this->serviceLocator->get('request');
            $userId         = $this->serviceLocator->get('router')->match($request)->getParam("user_id");
            $pin            = $this->serviceLocator->get('router')->match($request)->getParam("pin");
            $userManagerDao = new \DDD\Dao\User\UserManager($this->serviceLocator, 'ArrayObject');

            $user = $userManagerDao->getUserByIdAndPin($userId, $pin);
            if (!$user) {
                return new ApiProblem(404, 'Pin code is incorrect');
            }

            return [
                'status'  => true,
                'message' => 'Success'
            ];
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($params = array())
    {
        return new ApiProblem(405, 'The GET method has not been defined for individual resources');
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
