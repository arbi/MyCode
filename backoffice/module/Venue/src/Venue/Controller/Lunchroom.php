<?php

namespace Venue\Controller;


use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Utility\Helper;


class Lunchroom extends ControllerBase
{

    public function indexAction()
    {
        /**
         * @var \DDD\Service\Venue\Venue $venueService
         */
        $venueService = $this->getServiceLocator()->get('service_venue_venue');
        $lunchrooms   = $venueService->getLunchroomsForLoggedInUser();
        if ($lunchrooms == false) {
            return $this->redirect()->toUrl('/');
        }
        return new ViewModel(
            ['lunchrooms' => $lunchrooms]
        );
    }

    public function ajaxGetItemsAction()
    {
        /**
         * @var \DDD\Service\Venue\Items $itemsService
         */
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {

            try {
                $itemsService = $this->getServiceLocator()->get('service_venue_items');
                $post = $request->getPost();
                $items = $itemsService->getItemsByVenueId($post['venue_id']);
                $result = [
                    'status' => 'success',
                    'items'   => $items
                ];
            } catch (\Exception $ex) {

            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function ajaxOrderItemsAction()
    {

        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {

            try {
                /**
                 * @var \DDD\Service\Venue\Charges $venueChargeService
                 */
                $venueChargeService = $this->getServiceLocator()->get('service_venue_charges');
                $post = $request->getPost();
                if ($venueChargeService->createChargeForLunchroom(iterator_to_array($post))) {
                    Helper::setFlashMessage(["success" => "Your order is accepted"]);
                    $result = [
                        'status' => 'success',
                    ];
                }

            } catch (\Exception $ex) {

            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

}
