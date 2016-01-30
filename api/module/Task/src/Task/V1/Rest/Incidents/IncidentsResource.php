<?php
namespace Task\V1\Rest\Incidents;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use Zend\View\Model\JsonModel;

use DDD\Service\Warehouse\Asset as AssetService;
use DDD\Service\Task as TaskService;

use Library\Upload\Files;
use Library\Constants\DomainConstants;

use Application\Entity\Error;
use Application\Service\ApiException;

class IncidentsResource extends AbstractResourceListener
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
     * @api {post} API_DOMAIN/task/incidents New Incident
     * @apiVersion 1.0.0
     * @apiName NewIncident
     * @apiGroup Incident
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiDescription This method is used for creating an incident report
     *
     * @apiParam {String} uuid             The Universal Unique Identifiers generated in the mobile device to prevent duplicate requests
     * @apiParam {Int} locationEntityType  This is the type for the location. The possible values are in /warehouse/configs under locationTypes
     * @apiParam {Int} locationEntityId    The identification of the asset location
     * @apiParam {String} description      This is the description text about the incident
     *
     * @apiSuccess {Int} entityId  The newly created incident identification
     * @apiSuccess {Int} moduleId  The newly created incident type identification
     * @apiSuccess {Int} attachmentType The valid attachment file type for this incident report
     * @apiSuccess {Object} _links  This is used to add attachment(s) to the incident report
     *
     * @apiParamExample {json} Sample Request:
     *   {
     *       "uuid": "XXX",
     *       "locationEntityType": 1,
     *       "locationEntityId" : 42,
     *       "description": "The storage door was broken"
     *   }
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 201 Created
     *     {
     *         "entityId": 33444,
     *         "moduleId": 1,
     *         "attachmentType": 2,
     *         "_links": {
     *             "attachment": "API_DOMAIN/file/attachment"
     *         }
     *     }
     *
     */
    public function create($data)
    {
       /**
        * @var \DDD\Service\Location $cityService
        */
        $cityService            = $this->serviceLocator->get('service_location');
        $taskService            = $this->serviceLocator->get('service_task');
        $taskAttachDao          = $this->serviceLocator->get('dao_task_attachments');
        $auth                   = $this->serviceLocator->get('library_backoffice_auth');
        $httpResponseFactory    = $this->serviceLocator->get('Service\HttpResponseFactory');
        $requestHandler         = $this->serviceLocator->get('Service\RequestHandler');
        $apartmentGroupItemsDao = $this->serviceLocator->get('dao_apartment_group_apartment_group_items');
        $apartmentsDao          = $this->serviceLocator->get('dao_accommodation_accommodations');
        $officeDao              = $this->serviceLocator->get('dao_office_office_manager');
        $storageDao             = $this->serviceLocator->get('dao_warehouse_storage');

        try {
            $isDuplicateRequest = $requestHandler->checkRequest($data->uuid);

            if ($isDuplicateRequest) {
                throw new ApiException(Error::DUPLICATE_REQUEST_CODE);
            }

            $apartmentId = null;
            $buildingId  = null;
            $cityId      = null;
            $name        = '';

            switch ($data->locationEntityType) {
                case AssetService::ENTITY_TYPE_BUILDING:
                    $buildingDetails = $apartmentGroupItemsDao->getCityByBuidling($data->locationEntityId);
                    $cityId          = $buildingDetails['city_id'];
                    $buildingId      = $data->locationEntityId;
                    $name            = $buildingDetails['name'];
                    break;
                case AssetService::ENTITY_TYPE_APARTMENT:
                    $apartmentDetails = $apartmentsDao->getApartmentRawData($data->locationEntityId);
                    $cityId           = $apartmentDetails['city_id'];
                    $name             = $apartmentDetails['name'];
                    break;
                case AssetService::ENTITY_TYPE_STORAGE:
                    $storageDetails = $storageDao->getStorageData($data->locationEntityId);
                    $cityId         = $storageDetails['city'];
                    $name           = $storageDetails['name'];
                    break;
                case AssetService::ENTITY_TYPE_OFFICE:
                    $officeDetails = $officeDao->getOfficeDetailsById($data->locationEntityId, true);
                    $cityId         = $storageDetails['city_id'];
                    $name           = $storageDetails['name'];
                    break;
            }

            if (is_null($cityId)) {
                throw new ApiException(Error::LOCATION_NOT_FOUND_CODE);
            }

            $currentDateCity             = $cityService->getCurrentDateCity($cityId);
            $ninetySixHoursLaterDateCity = $cityService->getIncrementedDateCity($cityId, 96);

            $creator = $auth->getIdentity()->id;

            $taskId = $taskService->createReportIncidentTask(
                null,
                $apartmentId,
                $buildingId,
                $currentDateCity,
                $ninetySixHoursLaterDateCity,
                "Incident report on ({$name})", //title
                $creator,
                $data->description,
                false, //$gemId,
                false, //taskId
                false
            );

            if ($taskId) {
                $requestHandler->setCompleted($data->uuid);
                return [
                    'entityId'       => (int)$taskId,
                    'moduleId'       => TaskService::ENTITY_TYPE_INCIDENT,
                    'attachmentType' => Files::FILE_TYPE_IMAGE,
                    '_links'  => [
                        'attachment' => 'https://' . DomainConstants::API_DOMAIN_NAME . '/file/attachment',
                    ]
                ];
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
