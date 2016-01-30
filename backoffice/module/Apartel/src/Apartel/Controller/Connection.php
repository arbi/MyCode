<?php

namespace Apartel\Controller;

use Apartel\Controller\Base as ApartelBaseController;
use Apartel\Form\Connection as ApartelForm;
use Library\ChannelManager\Testing\ConnectionTest;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Library\ChannelManager\ChannelManager as Chm;
use Zend\View\Model\ViewModel;

use DDD\Service\Apartel\OTADistribution;
use Library\OTACrawler\OTACrawler;
use Library\OTACrawler\Product\Apartelle;

/**
 * Class Connection
 * @package Apartel\Controller
 */
class Connection extends ApartelBaseController
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Apartel\Connection $connectionService
         * @var \DDD\Service\Cubilis\Connection $cubilisConnectionService
         * @var \DDD\Service\Apartel\Type $typeService
         * @var OTADistribution $apartelOTAService
         */
        $connectionService = $this->getServiceLocator()->get('service_apartel_connection');
        $cubilisConnectionService = $this->getServiceLocator()->get('service_cubilis_connection');
        $typeService = $this->getServiceLocator()->get('service_apartel_type');

        // set Connection From
        $cubilisDetails = $connectionService->getCubilisConnectionDetails($this->apartelId);
        $form = new ApartelForm();
        $form->prepare();
        $form->populateValues($cubilisDetails);

        // syn Ginosi Type/Rate width Cubilis
        $ginosiTypeRateList = $typeService->getApartelTypesWithRates($this->apartelId);
        $cubilisTypeRateList = $cubilisConnectionService->getCubilisTypes($this->apartelId, $ginosiTypeRateList, true);

        // OTA connection
        $apartelOTAService = $this->getServiceLocator()->get('service_apartel_ota_distribution');
        $apartelOTAList = $apartelOTAService->getOTAList($this->apartelId);
        $partnerList = $apartelOTAService->getPartnerList();

		return [
            'form' => $form,
            'cubilisDetails' => $cubilisDetails,
            'ginosiTypeRateList' => $ginosiTypeRateList,
            'cubilisTypeRateList' => $cubilisTypeRateList,
            'apartelOTAList' => $apartelOTAList,
            'partnerList' => $partnerList,
            'OTAStatus' => Objects::getOTADistributionStatusList(),
        ];
	}

    public function saveAction()
    {
        /**
         * @var Request $request
         */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Service\Apartel\Connection $connectionService
                 */
                $connectionService = $this->getServiceLocator()->get('service_apartel_connection');

                $params['cubilis_id']       = $request->getPost('cubilis_id');
                $params['cubilis_username'] = $request->getPost('cubilis_username');
                $params['cubilis_password'] = $request->getPost('cubilis_password');
                $params['prepare']          = $request->getPost('prepare', 0);
                $params['rollback']         = $request->getPost('rollback', 0);

                $connectionData = $connectionService->saveCubilisConnection($this->apartelId, $params);

                $msg    = $connectionData['msg'];
                $status = $connectionData['status'];

                Helper::setFlashMessage([$status => $msg]);

                $result = [
                    'status' => $status,
                    'msg'    => $msg,
                ];
            } else {
                throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }

    public function testPullReservationsAction()
    {
        /** @var $request Request */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $connectionTest = new ConnectionTest($this->getServiceLocator());
                $result = $connectionTest->testPullReservation($this->apartelId, true);
            } else {
                throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }

    public function testUpdateAvailabilityAction()
    {
        /** @var $request Request */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $connectionTest = new ConnectionTest($this->getServiceLocator());
                $result = $connectionTest->testUpdateAvailability($this->apartelId, true);
            } else {
                throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }

    public function testFetchListAction()
    {
        /** @var $request Request */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $connectionTest = new ConnectionTest($this->getServiceLocator());
                $result = $connectionTest->testFetchList($this->apartelId, true);
            } else {
                throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }

        return new JsonModel($result);

    }

    public function connectAction() {
        /**
         * @var Request $request
         */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Service\Apartel\Connection $connectionService
                 */
                $connectionService = $this->getServiceLocator()->get('service_apartel_connection');
                $data = $request->getPost('data');
                $data['connect'] = $request->getPost('connect');
                $connectionData = $connectionService->connectCubilis($this->apartelId, $data);

                $msg = $connectionData['msg'];
                $status = $connectionData['status'];
                Helper::setFlashMessage([$status => $msg]);
                $result = [
                    'status' => $status,
                    'msg' => $msg,
                ];
            } else {
                throw new \Exception(TextConstants::ERROR_BAD_REQUEST);
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }

    public function linkAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Apartel\Type $typeService
         * @var \Library\ChannelManager\ChannelManager $channelManager
         */

        $request = $this->getRequest();
        try {
            if ($request->isPost()) {
                $channelManager = $this->getServiceLocator()->get('channel_manager');
                $typeService = $this->getServiceLocator()->get('service_apartel_type');
                $data = (array)$request->getPost();
                // link to ginosi db
                $linkData = $typeService->linkTypeRate($data, $this->apartelId);
                Helper::setFlashMessage([$linkData['status'] => $linkData['msg']]);
            } else {
                throw new \Exception(TextConstants::BAD_REQUEST);
            }
        } catch (\Exception $ex) {
            Helper::setFlashMessage(['error' => $ex->getMessage()]);
        }

        return $this->redirect()->toRoute('apartel/connection', ['apartment_id' => $this->apartelId], [], true);
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function ajaxSaveOtaAction()
    {
        /**
         * @var Request $request
         * @var OTADistribution $service
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_ADD,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $data = $request->getPost();
                $service  = $this->getServiceLocator()->get('service_apartel_ota_distribution');
                $otaId = $service->saveOTA($data, $this->apartelId);

                if ($otaId) {
                    try {
                        $apartel = new Apartelle($this->apartelId);
                        $crawler = new OTACrawler($apartel, [$otaId]);
                        $crawler->setServiceLocator($this->getServiceLocator());
                        $crawler->update();
                    } catch (\Exception $ex) {
                        $parserError = $ex->getMessage();
                    }

                    Helper::setFlashMessage(
                        ['success' => TextConstants::SUCCESS_ADD . (isset($parserError) ? ' but with Parser Error' : '')]
                    );
                } else {
                    $result['status'] = 'error';
                    $result['msg']    = TextConstants::INSERT_PROBLEM;
                }
            }
        } catch (\Exception $e) {
            $result['msg'] .= ' but with Parser Error';
        }

        return new JsonModel($result);
    }

    public function removeOtaAction()
    {
        $optId      = (int)$this->params()->fromRoute('ota_id', 0);
        $service    = $this->getServiceLocator()->get('service_apartel_ota_distribution');
        $removeable = $service->removeOTA($optId);

        if ($removeable) {
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        return $this->redirect()->toRoute('apartel/connection', ['apartment_id' => $this->apartelId], [], true);
    }

    public function ajaxCheckOtaAction()
    {
        /**
         * @var Request $request
         */
        $request = $this->getRequest();
        $apartelId = $this->apartelId;
        $otaId = $this->params()->fromRoute('ota_id');

        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $apartel = new Apartelle($apartelId);
                $crawler   = new OTACrawler($apartel, [$otaId]);
                $crawler->setServiceLocator($this->getServiceLocator());
                $crawler->update();

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $result = [
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_UPDATE,
                ];
            } catch (\Exception $ex) {
                $result['msg'] = $ex->getMessage();
            }
        }

        return new JsonModel($result);
    }
}
