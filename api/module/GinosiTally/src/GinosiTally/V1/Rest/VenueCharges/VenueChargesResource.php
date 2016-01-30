<?php
namespace GinosiTally\V1\Rest\VenueCharges;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

class VenueChargesResource extends AbstractResourceListener
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
     * @api {get} API_DOMAIN/ginosi-tally/venue-charges/:venue_id Venue Charges
     * @apiVersion 1.0.0
     * @apiName  venueCharges
     * @apiGroup Venue
     * @apiDescription This method return Venue Charges and Items.
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccess {Int} id The unique identification of the user
     * @apiSuccess {Datetime} date_created_server  The date of server request
     * @apiSuccess {Datetime} date_created_client  The date of client request
     * @apiSuccess {Int}      user_id              The user identification
     * @apiSuccess {Array}    items                The items list
     * @apiSuccess {Int}      item_id              The item identification
     * @apiSuccess {Int}      item_name            The item name
     * @apiSuccess {Int}      item_quantity        The item quantity
     * @apiSuccess {Int}      item_price           The price for single item
     * @apiSuccess {String}   currency_code        The venue currency code
     * @apiSuccess {Int}      currency_id          The venue currency identification
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "1": {
     *               "id": "1",
     *               "date_created_server": "2015-11-20 09:49:23",
     *               "date_created_client": "2015-11-20 09:49:23",
     *               "user_id": "306",
     *               "items": []
     *           },
     *           "2": {
     *                   "id": "2",
     *                   "date_created_server": "2015-11-20 09:51:57",
     *                   "date_created_client": "2015-11-20 09:51:57",
     *                   "user_id": "387",
     *                   "items": [
     *                       {
     *                           "item_id": "5",
     *                           "item_name": "salat",
     *                           "item_quantity": "1",
     *                           "item_price": "200.00",
     *                           "currency_code": "GBP",
     *                           "currency_id": "53"
     *                       },
     *                       {
     *                           "item_id": "6",
     *                           "item_name": "borsh",
     *                           "item_quantity": "1",
     *                           "item_price": "1000.00",
     *                           "currency_code": "GBP",
     *                           "currency_id": "53"
     *                       }
     *                   ]
     *           },
     *      }
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        try {
            $requestHandler = $this->serviceLocator->get('Service\RequestHandler');
            $venueChargeDao = new \DDD\Dao\Venue\Charges($this->serviceLocator);

            return $venueChargeDao->getNewChargeItemsByVenueId($id);
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
