<?php

namespace Apartel\Controller;

use Apartel\Controller\Base as ApartelBaseController;
use Apartel\Form\Content as ContentForm;
use Library\Constants\TextConstants;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class Content extends ApartelBaseController
{
    public function indexAction()
    {
        try {

            if (!$this->apartelId) {
                throw new \Exception('Cannot found Apartel');
            }

            $form = new ContentForm($this->apartelId);
            $form->prepare();

            /**
             * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
             * @var \DDD\Dao\Textline\Apartment $productTextlineDao
             */
            $apartelDetailsDao  = $this->getServiceLocator()->get('dao_apartel_details');
            $productTextlineDao = $this->getServiceLocator()->get('dao_textline_apartment');

            $apartelCurrentData = $apartelDetailsDao->getApartelDetailsById($this->apartelId);

            $apartelContentTextline = $productTextlineDao->getProductTextline($apartelCurrentData->getContentTextlineId());
            $apartelMotoTextline    = $productTextlineDao->getProductTextline($apartelCurrentData->getMotoTextlineId());
            $apartelMetaTextline    = $productTextlineDao->getProductTextline($apartelCurrentData->getMetaDescriptionTextlineId());

            $form->populateValues([
                'id'                        => $this->apartelId,
                'content_textline'          => $apartelContentTextline['en'],
                'moto_textline'             => $apartelMotoTextline['en'],
                'meta_description_textline' => $apartelMetaTextline['en'],
                'default_availability'      => $apartelCurrentData->getDefaultAvailability()
            ]);


            return new ViewModel([
                'apartelId'             => $this->apartelId,
                'apartelName'           => $apartelCurrentData->getName(),
                'form'                  => $form,
                'bgImage'               => $apartelCurrentData->getBgImage()
            ]);
        } catch (\Exception $e) {
            return $this->redirect()->toUrl('/');
        }
	}

    public function saveAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $this->getRequest()->getPost();
            $postFile = $this->getRequest()->getFiles();

            if (is_numeric($postData['id']) && $postData['id'] > 0) {
                /**
                 * @var \DDD\Service\Apartel\Content $apartelContentService
                 */
                $apartelContentService = $this->getServiceLocator()->get('service_apartel_content');
                $saveResult = $apartelContentService->saveApartel($postData, $postFile->toArray());

                if ($saveResult) {
                    $result = [
                        'status'    => 'success',
                        'msg'       => TextConstants::APARTEL_DETAILS_SAVED_SUCCESSFULLY
                    ];

                    if (is_string($saveResult)) {
                        $result = array_merge($result, ['img' => $saveResult]);
                    }

                } else {
                    $result = [
                        'status'    => 'error',
                        'msg'       => TextConstants::APARTEL_DETAILS_NOT_SAVED
                    ];
                }
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }
}
