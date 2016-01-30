<?php
namespace Console\Controller;

use DDD\Dao\Accommodation\Accommodations;
use Library\Controller\ConsoleBase;
use Library\Constants\Roles;
use DDD\Service\Notifications as NotificationService;

class ApartmentController extends ConsoleBase
{
    private $apartmentId = false;


    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', false);

        if ($this->getRequest()->getParam('id')) {
            $this->apartmentId = $this->getRequest()->getParam('id');
        }

        switch ($action) {
            case 'check-performance': $this->checkPerformanceAction();
                break;
            case 'documents-after-sixty-days-expiring': $this->documentsAfterSixtyDaysExpiring();
                break;
            case 'correct-apartment-reviews': $this->correctApartmentReviewsAction();
                break;
            default :
                echo '- type true parameter ( check-performance | documents-after-sixty-days-expiring | correct-apartment-reviews)'.PHP_EOL;
                return false;
        }
    }

    public function documentsAfterSixtyDaysExpiring()
    {
        /**
         * @var \DDD\Service\Notifications $notificationsService
         */
        $notificationsService = $this->getServiceLocator()->get('service_notifications');

        $documentsService = $this->getServiceLocator()->get('service_document_document');
        $list = $documentsService->getAfter60DaysExpiringApartmentDocumentsWithInvolvedManagersList();

        $sender = NotificationService::$apartmentDocumentsManagement;
        $viewedDate = date('Y-m-d');


        foreach ($list as $item) {

            $url = '/document/edit/' . $item['documentId'];
            $notificationMessage =
                'Document type ' . $item['documentTypeName'] . ' of ' . $item['apartmentName'] . ' will be expire on ' . $item['validTo'];

            $notificationData = [
                'recipient'   => $item['managerId'],
                'sender'      => $sender,
                'sender_id'   => $item['apartmentId'],
                'message'     => $notificationMessage,
                'url'         => $url,
                'show_date'   => $viewedDate
            ];

            $notificationsService->createNotification(
                $notificationData
            );

        }

        echo PHP_EOL .
            "\e[1;32mAll apartments documents expiring after 60 days were checked:" .
            " Cron job is done successfully. \e[0m" . PHP_EOL;
    }

    public function checkPerformanceAction()
    {
        /**
         * @var \DDD\Service\Notifications $notificationsService
         * @var Accommodations $apartmentGeneralDao
         * @var \DDD\Service\Apartment\Statistics $apartmentStatisticsService
         */
        $notificationsService       = $this->getServiceLocator()->get('service_notifications');
        $apartmentGeneralDao        = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $apartmentStatisticsService = $this->getServiceLocator()->get('service_apartment_statistics');

        $apartments = $apartmentGeneralDao->getApartmentsForPerformanceCalculation();

        $sender = NotificationService::$apartmentsPerformance;

        $notificationMessage = '';

        foreach ($apartments as $apartment) {

            $this->outputMessage("Checking performance for apartment \e[1;34m" . $apartment->getApartmentName() . " \e[0m");

            $url = '/apartment/' . $apartment->getApartmentId() . '/statistics';

            $monthlyProfit = $apartmentStatisticsService->getMonthlyProfit($apartment->getApartmentId());

            //Unset months with no statistics (in case of new apartments)
            foreach($monthlyProfit as $pid => $tempProfit) {
                if($tempProfit == 0) {
                    unset($monthlyProfit[$pid]);
                } else {
                    break;
                }
            }

            //Skip new apartments
            if ($monthlyProfit & count($monthlyProfit) >= 4) {
                $currentMonth     = date('Y-m'). '-15';
                $previousMonth    = strtotime($currentMonth.'-1 month');
                $previousMonthKey = date('M', $previousMonth).'_'.date('Y', $previousMonth);

                $profit = $monthlyProfit[$previousMonthKey];

                if ($profit < 0) {
                    $consoleMessage = "\e[1;34m".$apartment->getApartmentName()."\e[0m".
                       " apartment performance for " .
                        date('F Y', $previousMonth) . " is negative (".
                        round($profit,2)." ".$apartment->getCurrencyCode().")";

                    $this->outputMessage($consoleMessage);

                    $notificationMessage .=
                        '<a href="/apartment/' .
                        $apartment->getApartmentId() . '/statistics" target="_blank"><b>' .
                        $apartment->getApartmentName() .'</b></a>' .
                        ' performance for ' .
                        date('F Y', $previousMonth) . ' is negative ('.
                        round($profit,2).' '.$apartment->getCurrencyCode().')<br>'
                        .PHP_EOL;
                }
            }
        }

        if (strlen($notificationMessage) > 0) {
            $notificationsService->deleteSenderAllNotifications($sender);

            $involvedUsers = $this->selectInvolvedUsersList();

            $notificationData = [
                'recipient'   => $involvedUsers,
                'sender'      => $sender,
                'message'     => $notificationMessage,
            ];

            $notificationsService->createNotification(
                $notificationData
            );
        }

        echo PHP_EOL .
            "\e[1;32mAll selling apartments performance checked:" .
            " Cron job is done successfully. \e[0m" . PHP_EOL;
    }


    public function correctApartmentReviewsAction()
    {

        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
        $allActiveApartments = $apartmentService->getAllApartmentsIdsAndTimezones();
        foreach ($allActiveApartments as $activeApartment) {
            /**
             * @var \DDD\Service\Accommodations $accommodationsService
             */
            $accommodationsService = $this->getServiceLocator()->get('service_accommodations');

            $accommodationsService->updateProductReviewScore($activeApartment['id']);

            $this->outputMessage("Review Score for apartment \e[1;34m"  . $activeApartment['name'] ." \e[0m is corrected.");
        }

        echo PHP_EOL .
            "\e[1;32mAll apartments reviews have benn corrected:" .
            " Cron job is done successfully. \e[0m" . PHP_EOL;
    }

    protected function selectInvolvedUsersList()
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $userManagerDao = $userService->getUserGropusDao();

        $usersList = $userManagerDao
            ->getUsersByGroupId(Roles::ROLE_APARTMENT_PERFORMANCE);

        if ($usersList === NULL) {
            return false;
        }

        foreach ($usersList as $user) {
            $list[] = $user->getUserId();
        }

        return $list;
    }
}
