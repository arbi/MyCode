<?php
namespace Common\V1\Rest\Users;

use Library\Utility\Helper;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

class UsersResource extends AbstractResourceListener
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
     * @api {get} API_DOMAIN/users User List
     * @apiVersion 1.0.0
     * @apiName GetUser
     * @apiGroup User
     *
     * @apiDescription This method returns the list of all users
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Int} id The unique identification of the user
     * @apiSuccess {String} firstname  The first name of the user
     * @apiSuccess {String} lastname  The last name of the user
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     [
     *         {
     *             "id": "12",
     *             "firstname": "App",
     *             "lastname": "User",
     *         }
     *     ]
     *
     * @param array $params
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAll($params = array())
    {
        try {
            $auth           = $this->serviceLocator->get('library_backoffice_auth');
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $userManagerDao = new \DDD\Dao\User\UserManager($this->serviceLocator, 'ArrayObject');

            $countryId = $auth->getIdentity()->countryId;
            $usersInfo = $userManagerDao->getUserByCountry($countryId);

            return $usersInfo;
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
