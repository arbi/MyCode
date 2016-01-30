<?php
namespace Console\Controller;

use DDD\Dao\Accommodation\Accommodations;
use Library\Controller\ConsoleBase;
use Library\Constants\Roles;
use Library\Constants\EmailAliases;
use Library\Constants\DomainConstants;
use DDD\Service\Notifications as NotificationService;

class ApartmentGroupController extends ConsoleBase
{
    private $apartmentGroupId = false;


    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', false);

        if ($this->getRequest()->getParam('id')) {
            $this->apartmentGroupId = $this->getRequest()->getParam('id');
        }

        switch ($action) {
            case 'check-performance':
                $this->checkPerformanceAction();
                break;
            default :
                echo '- type true parameter ( check-performance | send-email)' . PHP_EOL;
                return false;
        }
    }

    public function checkPerformanceAction()
    {
        /**
         * @var \DDD\Service\Notifications $notificationsService
         * @var $apartmentDao Accommodations
         * @var \DDD\Service\Apartment\Statistics $apartmentStatisticsService
         */
        $notificationsService       = $this->getServiceLocator()->get('service_notifications');
        $apartmentDao               = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $apartmentStatisticsService = $this->getServiceLocator()->get('service_apartment_statistics');

        $apartments = $apartmentDao->getPerformanceGroupsSellingApartments();

        $sender = NotificationService::$groupPerformance;
        $involvedUsers = $this->selectInvolvedUsersList();
        $viewedDate = date('Y-m-d');

        $previousPerformanceGroupId = 0;
        $performanceGroupProfit = 0;

        $apartmentsArray = iterator_to_array($apartments);

        foreach ($apartmentsArray as $counter => $apartment) {

            if ($previousPerformanceGroupId != $apartment->getPerformanceGroupId()) {
                $this->outputMessage("\e[1;34mChecking performance for group " . $apartment->getPerformanceGroupName() . " \e[0m");

                $performanceGroupProfit = 0;
                $previousPerformanceGroupId = $apartment->getPerformanceGroupId();
            }

            $this->outputMessage("   -> Calculating performance for apartment \e[1;33m" . $apartment->getApartmentName() . " \e[0m");

            // calculating profit
            $monthlyProfit = $apartmentStatisticsService->getMonthlyProfit($apartment->getApartmentId());

            foreach ($monthlyProfit as $pid => $tempProfit) {
                if ($tempProfit == 0) {
                    unset($monthlyProfit[$pid]);
                } else {
                    break;
                }
            }

            if ($monthlyProfit & count($monthlyProfit) >= 4) {

                $currentMonth = date('Y-m') . '-15';  // Get mid of this month
                $previousMonth = strtotime($currentMonth . '-1 month'); // mid of previous month
                $previousMonthKey = date('M', $previousMonth) . '_' . date('Y', $previousMonth);
                $profit = $monthlyProfit[$previousMonthKey];

                $performanceGroupProfit += $profit;
            }

            $currencyCode = $apartment->getCurrencyCode();

            $sendNotification = false;
            if (isset($apartmentsArray[$counter + 1])) {
                if ($apartmentsArray[$counter + 1]->getPerformanceGroupId() != $apartment->getPerformanceGroupId()) {
                    $sendNotification = true;
                }
            } else {
                $sendNotification = true;
            }

            if ($sendNotification) {

                if ($performanceGroupProfit < 0) {
                    $url = '/concierge/edit/' . $apartment->getPerformanceGroupId();
                    $notificationsService->deleteSenderNotification(
                        $apartment->getPerformanceGroupId(),
                        $sender
                    );

                    $consoleMessage =
                        $apartment->getPerformanceGroupName() . ' performance for ' .
                        date('F Y', $previousMonth) . ' is negative (' .
                        round($performanceGroupProfit, 2) . ' ' . $currencyCode . ')';

                    $notificationMessage =
                        '<a href="/concierge/edit/' .
                        $apartment->getPerformanceGroupId() . '"><b>' .
                        $apartment->getPerformanceGroupName() . '</b></a>' .
                        ' performance for ' .
                        date('F Y', $previousMonth) . ' is negative (' .
                        round($performanceGroupProfit, 2) . ' ' . $currencyCode . ')';

                    $notificationData = [
                        'recipient' => $involvedUsers,
                        'sender' => $sender,
                        'sender_id' => $apartment->getPerformanceGroupId(),
                        'message' => $notificationMessage,
                        'url' => $url,
                        'viewed_date' => $viewedDate
                    ];

                    $notificationsService->createNotification(
                        $notificationData
                    );

                    $this->outputMessage($consoleMessage);
                }
            }
        }

        echo PHP_EOL .
            "\e[1;32mAll groups performance checked:" .
            " Cron job is done successfully. \e[0m" . PHP_EOL;
    }

    protected function selectInvolvedUsersList()
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $userManagerDao = $userService->getUserGropusDao();

        $usersList = $userManagerDao->getUsersByGroupId(Roles::ROLE_APARTMENT_PERFORMANCE);
        $list = [];

        if (is_null($usersList)) {
            return false;
        }

        foreach ($usersList as $user) {
            array_push($list, $user->getUserId());
        }

        return $list;
    }
}
