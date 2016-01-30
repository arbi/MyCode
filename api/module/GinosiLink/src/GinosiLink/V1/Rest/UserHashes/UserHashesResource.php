<?php
namespace GinosiLink\V1\Rest\UserHashes;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use Application\Entity\Error;
use Application\Service\ApiException;

class UserHashesResource extends AbstractResourceListener
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
     * @api {post} API_DOMAIN/ginosi-link/user-hashes New Hash
     * @apiVersion 1.0.0
     * @apiName  NewHash
     * @apiGroup User
     *
     * @apiDescription This method is used for creating a new hash code in user devices. It returns the newly created user device data
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiParam {String} uuid   The Universal Unique Identifiers generated in the mobile device to prevent duplicate requests
     * @apiParam {Int}    userId This is the type indicator for the user
     * @apiParam {String} hash   This is the hash code
     *
     * @apiParamExample {json} Sample Request:
     *   {
     *       "uuid": "XXX",
     *       "userId": 1,
     *       "hash" : "XXX",
     *   }
     *
     * @apiSuccessExample {json} Hash Code Response:
     *     HTTP/1.1 201 Created
     *     {
     *         "id": 1,
     *         "user_id": 1,
     *         "hash" : "XXX",
     *         "date_added" : "2015-11-20 15:44:32",
     *     }
     *
     * @param mixed $data
     * @return array|int|ApiProblem
     * @throws ApiException
     */
    public function create($data)
    {
        try {
            $requestHandler     = $this->serviceLocator->get('Service\RequestHandler');
            $isDuplicateRequest = $requestHandler->checkRequest($data->uuid);

            if ($isDuplicateRequest) {
                throw new ApiException(Error::DUPLICATE_REQUEST_CODE);
            }

            $userDeviceDao = new \DDD\Dao\User\Devices($this->serviceLocator, 'ArrayObject');
            $duplicate = $userDeviceDao->getByUserIdAndHash($data->userId, $data->hash);

            if (!$duplicate) {
                $result = $userDeviceDao->saveDeviceHash($data->userId, $data->hash);

                if ($result) {
                    $requestHandler->setCompleted($data->uuid);
                    $result = $userDeviceDao->getById($result['id']);

                    return $result;
                }
            } else {
                throw new ApiException(Error::DUPLICATE_USER_HASH);
            }
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
    }

    /**
     * Delete resource
     *
     * @api {delete} API_DOMAIN/ginosi-link/user-hashes/:user_hashes_id Unlink Hash
     * @apiVersion 1.0.0
     * @apiName  UnlinkHash
     * @apiGroup User
     *
     * @apiDescription This method is used for unlink hash code in user devices.
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiParam {String} user_hashes_id      This is a user device identification number
     *
     * @apiSuccessExample {json} Hash Code Response:
     *     HTTP/1.1 204 Deleted
     *
     * @param mixed $id
     * @return array|ApiException
     */
    public function delete($id)
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $userDeviceDao  = new \DDD\Dao\User\Devices($this->serviceLocator);
            $result         = $userDeviceDao->unlinkDeviceById($id);

            if (!$result) {
                throw new ApiException(Error::NOT_FOUND_CODE);
            }

            return true;
        } catch (\Exception $e) {
            return $requestHandler->handleException($e);
        }
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
     * @api {get} API_DOMAIN/ginosi-link/user-hashes/:user_hash Get User by Hash
     * @apiVersion 1.0.0
     * @apiName  getUserDeviceByHash
     * @apiGroup User
     * @apiDescription This method return user device data for GinosiLink. The user_hash parameter contains the value of user hash.
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Int}  id      User Device identification number
     * @apiSuccess {Int}  user_id User Id
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "id": "1",
     *          "user_id": "12"
     *     }
     *
     * @param mixed $id
     * @return \DDD\Domain\User\Devices[]
     */
    public function fetch($id)
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $userDeviceDao  = new \DDD\Dao\User\Devices($this->serviceLocator, 'ArrayObject');
            $result         = $userDeviceDao->getUserIdByHash($id);

            if ($result) {
                return $result;
            }
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
        return new ApiProblem(405, 'The GET method has not been defined for collections');
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
