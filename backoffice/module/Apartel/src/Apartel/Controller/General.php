<?php

namespace Apartel\Controller;

use Apartel\Controller\Base as ApartelBaseController;
use Apartel\Form\General as GeneralForm;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class General extends ApartelBaseController
{
    public function indexAction()
    {
        try {
            /**
             * @var \DDD\Service\Apartel\General $apartelGeneralService
             * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
             * @var \DDD\Dao\Partners\Partners $partnerDao
             */

            if (!$this->apartelId) {
                throw new \Exception('Cannot found Apartel');
            }

            $apartelGeneralService = $this->getServiceLocator()->get('service_apartel_general');
            $apartelDetailsDao  = $this->getServiceLocator()->get('dao_apartel_details');
            $partnerDao  = $this->getServiceLocator()->get('dao_partners_partners');

            $generalViewData = $apartelGeneralService->getGeneralViewData($this->apartelId);

            $form = new GeneralForm($this->apartelId);
            $form->prepare();

            $apartelGeneralData = $generalViewData['apartelGeneralData'];
            $apartelCurrentData = $apartelDetailsDao->getApartelDetailsById($this->apartelId);

            $form->populateValues([
                'id'                        => $this->apartelId,
                'status'                    => $apartelGeneralData['status'],
                'default_availability'      => $apartelCurrentData->getDefaultAvailability()
            ]);

            // get fiscal list
            $fiscalList = $apartelGeneralService->getApartelFiscals($this->apartelId);

            // get partner list
            $partnerList = $partnerDao->getActivePartners();

            return new ViewModel([
                'apartelId'             => $this->apartelId,
                'apartelName'           => $apartelCurrentData->getName(),
                'form'                  => $form,
                'scoreLastTwoYears'     => $generalViewData['scoreLastTwoYears'],
                'scoreLastThreeMont'    => $generalViewData['scoreLastThreeMont'],
                'reviews'               => $generalViewData['reviews'],
                'fiscalList'            => $fiscalList,
                'partnerList'           => $partnerList,
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
                 * @var \DDD\Service\Apartel\General $apartelGeneralService
                 */
                $apartelGeneralService = $this->getServiceLocator()->get('service_apartel_general');
                $saveResult = $apartelGeneralService->saveApartel($postData, $postFile->toArray());

                if ($saveResult) {
                    $result = [
                        'status'    => 'success',
                        'msg'       => TextConstants::APARTEL_DETAILS_SAVED_SUCCESSFULLY
                    ];
                }
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function ajaxSaveFiscalAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Apartel\General $apartelGeneralService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $data = $request->getPost();
                $apartelGeneralService = $this->getServiceLocator()->get('service_apartel_general');
                $apartelGeneralService->saveFiscal($data->toArray());
                $result['status'] = 'success';
                Helper::setFlashMessage(['success' => $data['fiscal_id'] ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function deleteFiscalAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Apartel\General $apartelGeneralService
         */
        $apartelId = (int)$this->params()->fromRoute('apartel_id', 0);
        $fiscalId = (int)$this->params()->fromRoute('fiscal_id', 0);

        if ($fiscalId && $apartelId) {
            $apartelGeneralService = $this->getServiceLocator()->get('service_apartel_general');
            $apartelGeneralService->deleteFiscal($fiscalId);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
        } else {
            Helper::setFlashMessage(['error' => TextConstants::BAD_REQUEST]);
        }

        return $this->redirect()->toRoute('apartel/general', ['apartel_id' => $apartelId]);
    }
}
