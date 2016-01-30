<?php

namespace Backoffice\Controller;

use Library\Controller\ControllerBase;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use Library\Constants\Constants;
use Library\Utility\Helper;
use DDD\Service\Notifications as NotificationsService;
use Backoffice\Form\SearchNotificationForm;

class NotificationController extends ControllerBase
{
    public function indexAction()
    {
        $allNotificationSenders = NotificationsService::getAllSendersForSelect();
        $searchForm = new SearchNotificationForm($allNotificationSenders);

        return new ViewModel([
            'search_form' => $searchForm,
        ]);
    }

    public function getNotificationsJsonAction()
    {
        /**
         * @var \DDD\Service\Notifications $notificationsService
         */
        $notificationsService = $this->getServiceLocator()->get('service_notifications');

        $requestParams = $this->params()->fromQuery();
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $userId = $auth->getIdentity()->id;

        $notifications = $notificationsService->getUserNotifications($userId, $requestParams, true);
        $result = [];
        $currentPage = ($requestParams['start'] / $requestParams['length']) + 1;

        $notificationsResult = $notifications['result'];
        $count = $notifications['total'];

        foreach ($notificationsResult as $notification) {
            $isActive = $notification->isActive();

            if ($isActive) {
                $secondActionName = 'Archive';
                $secondActionClass = 'btn-success archive-not';
            } else {
                $secondActionName = 'Delete';
                $secondActionClass = 'btn-danger delete-not';
            }

            $actionView = '<a href="' . $notification->getUrl() . '" class="btn btn-xs btn-primary btn-notification-link" target="_blank">View</a>';
            $actionSecond = '<a data-id="' . $notification->getId() .'" href="#" class="btn btn-xs  ' . $secondActionClass . '" target="_blank">' . $secondActionName . '</a>';

            array_push($result, [
                '0' => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($notification->getShowDate())),
                '1' => $notification->getSender(),
                '2' => $notification->getMessage(),
                '3' => $actionSecond . $actionView,
                'DT_RowClass' => $notification->getType(),
            ]);
        }

        return new JsonModel([
            "aaData" => $result,
            'iTotalRecords' => $count,
            'iTotalDisplayRecords' => $count,
            'iDisplayStart' => ($currentPage - 1) * (integer)$requestParams['start'],
            'iDisplayLength' => (integer)$requestParams['length'],
        ]);
    }

    public function pullAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                return $this->redirect()->toRoute('home');
            }

            /**
             * @var \DDD\Service\Notifications $notificationsService
             */
            $notificationsService = $this->getServiceLocator()->get('service_notifications');

            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $userId = $auth->getIdentity()->id;

            $notifications = $notificationsService->getUserNotifications($userId, false, false);
            $notifications = $notifications['result'];

            $notificationData = [];

            foreach($notifications as $notification) {
                array_push($notificationData, [
                    'message' => Helper::truncateNotBreakingHtmlTags($notification->getMessage()),
                    'url'     => !is_null($notification->getUrl()) ? $notification->getUrl() : '',
                    'type'    => $notification->getType(),
                    'sender'  => $notification->getSender(),
                    'id'      => $notification->getId()
                ]);
            }

            return new JsonModel([
                'status' => 'success',
                'data' => $notificationData,
            ]);

        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'success',
                'msg' => TextConstants::ERROR,
            ]);
        }
    }

    public function archiveAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                return $this->redirect()->toRoute('home');
            }

            /**
             * @var \DDD\Service\Notifications $notificationsService
             */
            $notificationsService = $this->getServiceLocator()->get('service_notifications');

            $request = $this->getRequest();
            $id = (int)$request->getPost('id');

            $notificationData = $notificationsService->isNotificationBelongsToUser($id);

            if ($id > 0 && $notificationData) {
                $notificationsService->archiveNotification($id);

                return new JsonModel([
                    'status' => 'success',
                    'msg'    => TextConstants::NOTIFICATION_ARCHIVED_OK
                ]);
            } else {
                return new JsonModel([
                    'status' => 'error',
                    'msg'    => TextConstants::NOTIFICATION_INVALID_ID
                ]);
            }
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'success',
                'msg'    => TextConstants::ERROR
            ]);
        }
    }

    public function deleteAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                return $this->redirect()->toRoute('home');
            }

            /**
             * @var \DDD\Service\Notifications $notificationsService
             */
            $notificationsService = $this->getServiceLocator()->get('service_notifications');

            $request = $this->getRequest();
            $id   = (int)$request->getPost('id');

            $notificationData = $notificationsService->isNotificationBelongsToUser($id);

            if ($id && $notificationData) {
                $notificationsService->deleteNotification($id);

                return new JsonModel([
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_DELETE
                ]);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel([
            'status' => 'success',
            'msg'    => TextConstants::ERROR
        ]);
    }
}
