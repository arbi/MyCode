<?php

namespace Venue\Controller;

use Library\Controller\ControllerBase;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Zend\View\Model\JsonModel;


class Items extends ControllerBase
{
    public function ajaxSaveAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $this->params()->fromPost();

            $venueId = (isset($postData['venue_id'])) ? $postData['venue_id'] : 0;

            /**
             * @var \DDD\Dao\Venue\Venue $venueDao
             */
            $venueDao = $this->getServiceLocator()->get('dao_venue_venue');
            $venueData = $venueDao->getVenueById($venueId);

            if ($venueData === false) {
                throw new \Exception('It is impossible to create a charge for a non-existent venue');
            }

            /**
             * @var \DDD\Service\Venue\Items $itemsService
             */
            $itemsService = $this->getServiceLocator()->get('service_venue_items');
            $saveResult = $itemsService->saveItems($venueId, $postData);

            if ($saveResult) {
                Helper::setFlashMessage([
                    'success' => TextConstants::SUCCESS_UPDATE,
                ]);

                $result = [
                    'status'    => 'success',
                    'msg'       => TextConstants::SUCCESS_UPDATE,
                    'url'       => $this->url()->fromRoute('venue',
                        [
                            'action' => 'edit',
                            'id' => $venueId
                        ]) . '#items'
                ];
            }
        } catch (\Exception $e) {
            $this->gr2logException($e);

            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }
}