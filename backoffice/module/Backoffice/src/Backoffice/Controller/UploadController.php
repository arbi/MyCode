<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UploadController
 *
 * @author developer
 */

namespace Backoffice\Controller;

//use DDD\Service\Upload;
use Library\Controller\ControllerBase;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Zend\View\Model\JsonModel;

class UploadController extends ControllerBase {

    public function avatarAction()
    {
        try
        {
            /**
             * @var \DDD\Service\Upload $uploadService
             */
            $uploadService = $this->getServiceLocator()->get('service_upload');

            $request       = $this->getRequest();

            // check request method
            if ($request->getMethod() !== 'POST')
                throw new \Exception(TextConstants::AJAX_NO_POST_ERROR);

            // take avatar file and user id from POST
            $avatarFile = $request->getFiles();
            $profileId  = $request->getPost('userId');

            // send resoult to service
            $newAvatar = $uploadService->updateAvatar((int)$profileId, [$avatarFile['file']]);

            if (   is_array($newAvatar)
                && $newAvatar['status'] === 'error'
            ) {
                return new JsonModel($newAvatar);
            }

            $session = Helper::getSession('Zend_Auth');
            $session['storage']->avatar = $newAvatar;

            $result['status'] = 'success';
            $result['msg']    = TextConstants::SUCCESS_UPDATE;
            $result['src']    = $profileId.'/'.$newAvatar;

            return new JsonModel($result);

        } catch (Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    public function ajaxUploadPartnerLogoAction()
    {
        try
        {
            /**
             * @var \DDD\Service\Upload $uploadService
             */
            $uploadService = $this->getServiceLocator()->get('service_upload');

            $request = $this->getRequest();

            // check request method
            if($request->getMethod() !== 'POST')
                throw new \Exception(TextConstants::AJAX_NO_POST_ERROR);

            // take avatar file and user id from POST
            $logoFile = $request->getFiles();

            // send resoult to service
            $tempName = $uploadService->saveToTemp(array($logoFile['file']));

            if(isset($tempName['status']) AND $tempName['status'] == 'error'){
                return new JsonModel($tempName);
            } else {
                $result['status'] = 'success';
                $result['msg'] = TextConstants::SUCCESS_UPDATE;
                $result['src'] = $tempName;

                return new JsonModel($result);
            }

        } catch (Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}

?>
