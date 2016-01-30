<?php

namespace Application\Controller;

use Zend\View\Model\JsonModel;
use Zend\Validator\File\IsImage;
use Zend\Mvc\Controller\AbstractRestfulController;

use FileManager\Constant\DirectoryStructure;

use Library\Upload\Files;
use Library\Utility\Helper;

use Application\Entity\Error;
use Application\Service\ApiException;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use DDD\Service\Task as TaskService;

class AttachmentsController extends AbstractRestfulController
{
    /**
     * @api {post} API_DOMAIN/file/attachments Attachment
     * @apiVersion 1.0.0
     * @apiName UploadImage
     * @apiGroup Attachments
     *
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiDescription This method is used for uploading attachments to a module
     *
     * @apiParam {Object} attachemnt This is the byte array representation of the file
     * @apiParam {Int} attachmentType This is the type of attachment. Possible values are in /warehouse/configs under attachmentTypes
     * @apiParam {Int} entityId This is the entity identification for the given module
     * @apiParam {Int} moduleId This is the module identification. Possible values are in /warehouse/configs under moduleTypes
     *
     * @apiSuccess {String} Status Success
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "Status": "Success"
     *     }
     */
    public function attachmentsAction()
    {
        try {
            $httpResponseFactory = $this->getServiceLocator()->get('Service\HttpResponseFactory');

            $file           = $this->getRequest()->getFiles();
            $attachmentType = $this->getRequest()->getPost('attachmentType', 0);
            $entityId       = $this->getRequest()->getPost('entityId', 0);
            $moduleId       = $this->getRequest()->getPost('moduleId', 0);

            if (isset($file['attachment']) && $attachmentType && $entityId && $moduleId) {

                $file = $file['attachment'];

                if (empty($file)) {
                    throw new ApiException(Error::FILE_NOT_FOUND_CODE);
                }

                switch ($attachmentType) {
                    case Files::FILE_TYPE_ALL:
                        $validator = new IsImage();
                        if (!$validator->isValid($file)) {
                            throw new ApiException(Error::FILE_TYPE_NOT_TRUE_CODE);
                        }
                        break;
                    case Files::FILE_TYPE_IMAGE:
                        $validator = new IsImage();

                        if (!$validator->isValid($file)) {
                            throw new ApiException(Error::FILE_TYPE_NOT_TRUE_CODE);
                        }
                        break;
                    case Files::FILE_TYPE_DOCUMENT:
                        throw new ApiException(Error::FILE_TYPE_NOT_TRUE_CODE);
                        break;
                }

                switch ($moduleId) {
                    case TaskService::ENTITY_TYPE_INCIDENT:
                        $response = $this->uploadIncidentPhoto($entityId, $file);
                        if ($response) {
                            return new JsonModel(['Status' => 'Success']);
                        }

                        break;
                    default:
                        throw new ApiException(Error::MODULE_NOT_FOUND_CODE);
                }

                throw new ApiException(Error::FILE_TYPE_NOT_TRUE_CODE);
            }

            throw new ApiException(Error::SERVER_SIDE_PROBLEM_CODE);

        } catch (\Exception $e) {
            return new ApiProblemResponse(ApiException::handleException($e));
        }
    }

    private function uploadIncidentPhoto($entityId, $file)
    {
        try {
            $attachmentsDao = $this->getServiceLocator()->get('dao_task_attachments');
            $taskDao        = new \DDD\Dao\Task\Task($this->getServiceLocator(), 'ArrayObject');
            $taskDetails    = $taskDao->fetchOne(['id' => $entityId]);

            if ($taskDetails) {
                $uploadFolder = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_TASK_ATTACHMENTS
                    . date('Y/m/d', strtotime($taskDetails['creation_date'])) . '/' . $entityId . '/';

                $destination = $uploadFolder . pathinfo($file['name'], PATHINFO_FILENAME) .
                    '_' . round(microtime(true) * 1000) . '.' .
                    pathinfo($file['name'], PATHINFO_EXTENSION);

                $response = Files::moveFile($file['tmp_name'], $destination);
                if ($response) {
                    $attachmentsDao->save([
                        'task_id' => $entityId,
                        'file' => pathinfo($destination, PATHINFO_BASENAME)
                    ]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // public function profileAction()
    // {
    //     try {
    //         $userManagerDao      = $this->getServiceLocator()->get('dao_user_user_manager');
    //         $httpResponseFactory = $this->getServiceLocator()->get('Service\HttpResponseFactory');
    //
    //         $userId = $this->params()->fromRoute('user_id', 0);
    //         /**
    //          * @var \FileManager\Service\GenericDownloader $genericDownloader
    //          */
    //         $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');
    //
    //         if (!$userId) {
    //             throw new ApiException(Error::USER_NOT_FOUND_CODE);
    //         }
    //
    //         $userInfo = $userManagerDao->getUserById($userId);
    //         $avatar = Helper::getUserAvatar($userInfo, 'small', true);
    //
    //         if (!$avatar) {
    //             throw new ApiException(Error::FILE_NOT_FOUND_CODE);
    //         }
    //
    //         $genericDownloader->setFileSystemMode($genericDownloader::FS_MODE_IMAGES, true);
    //         $genericDownloader->downloadAttachment($avatar);
    //
    //         if ($genericDownloader->hasError()) {
    //             throw new ApiException(Error::FILE_NOT_FOUND_CODE);
    //             $error = $httpResponseFactory->getHttpContentBody(Error::FILE_NOT_FOUND_CODE);
    //             return new ApiProblemResponse(new ApiProblem(Error::NOT_FOUND_CODE, $error));
    //         }
    //         return true;
    //     } catch(\Exception $e) {
    //         // throw new ApiException(Error::SERVER_SIDE_PROBLEM_CODE);
    //         return new ApiProblemResponse(ApiException::handleException($e));
    //     }
    // }
}
