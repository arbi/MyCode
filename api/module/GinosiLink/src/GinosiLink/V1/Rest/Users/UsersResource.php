<?php
namespace GinosiLink\V1\Rest\Users;

use Library\Utility\Helper;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

class UsersResource extends AbstractResourceListener
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
     * @api {get} API_DOMAIN/ginosi-link/users/:users_id User Details
     * @apiVersion 1.0.0
     * @apiName userDetails
     * @apiGroup User
     * @apiDescription This method return user details data for GinosiLink.
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "id": "12",
     *          "firstname": "App",
     *          "lastname": "User",
     *          "phone": "37455555555",
     *          "email": "app.user@ginosi.com",
     *          "avatar": "https://images.ginosi.com/profile/387/1447849917_0_150.png",
     *          "department": "Engineering",
     *          "manager": "Tigran Petrosyan"
     *      }
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $userManagerDao = new \DDD\Dao\User\UserManager($this->serviceLocator, 'ArrayObject');

            $user = $userManagerDao->getUserDataById($id);

            $user['manager'] = $user['manager_firstname'] . ' ' . $user['manager_lastname'];
            unset($user['manager_firstname']);
            unset($user['manager_lastname']);

            $user['avatar'] = 'https:' . Helper::getUserAvatar($user, 'big');

            return $user;
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * @api {get} API_DOMAIN/ginosi-link/users User List
     * @apiVersion 1.0.0
     * @apiName GetUser
     * @apiGroup User
     *
     * @apiDescription This method returns the list of all users
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Int}    id         The unique identification of the user
     * @apiSuccess {String} firstname  The first name of the user
     * @apiSuccess {String} lastname   The last name of the user
     * @apiSuccess {String} avatar     The profile picture of the user
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     [
     *         {
     *             "id": "12",
     *             "firstname": "App",
     *             "lastname": "User",
     *             "avatar": "https://images.ginosi.com/profile/12/1439802421_0_150.png"
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
            $usersInfo = $userManagerDao->getUserByCountry($countryId, [
                'id',
                'firstname',
                'lastname',
                'avatar'
            ]);

            $usersInfoArray = [];
            foreach ($usersInfo as $userInfo) {
                $userInfo['avatar'] = 'https:' . Helper::getUserAvatar($userInfo, 'big');
                $usersInfoArray[]   = $userInfo;
            }

            return $usersInfoArray;
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
