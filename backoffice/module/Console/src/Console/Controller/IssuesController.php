<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;

/**
 * Class IssuesController
 * @package Console\Controller
 */
class IssuesController extends ConsoleBase
{
    /**
     * @private
     * @var boolean|int
     */
    private $id = FALSE;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');

        if ($this->getRequest()->getParam('id')) {
            $this->id = (int)$this->getRequest()->getParam('id');
        }

        switch ($action) {
            case 'show':
                $this->showAction();
                break;
            case 'detect':
                $this->detectAction();
                break;
            case 'force-resolve':
                $this->forceResolveAction();
                break;
            default :
                $this->helpAction();
        }
    }

    /**
     * Show detected issues by Reservation Id or All
     */
    public function showAction()
    {
        try {
            /**
             * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
             */
            $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

            if ($this->id) {
                $issues = $reservationIssuesService->getReservationIssues($this->id);

                echo PHP_EOL . 'Reservation Id: ' . $this->id . PHP_EOL;

                if (count($issues)) {
                    foreach ($issues as $issue) {
                        echo "    Issue Id: " . $issue->getId() . PHP_EOL;
                        echo "    # \e[1;33m" . $issue->getReservationNumber() . "\e[0m" . PHP_EOL;
                        echo "    ! \e[0;36m" . $issue->getTitle() . "\e[0m" . PHP_EOL;
                        echo '    @ ' . $issue->getDateOfDetection() . PHP_EOL;
                        echo PHP_EOL;
                    }
                } else {
                    echo '    has no issues' . PHP_EOL . PHP_EOL;
                }

            } else {
                $allIssues = $reservationIssuesService->getIssuesGrouppedByReservationId();

                foreach ($allIssues as $reservationId => $issues) {
                    echo PHP_EOL . 'Reservation Id: ' . $reservationId . PHP_EOL;
                    foreach ($issues as $issues) {
                        echo "    Issue Id: " . $issues['id'] . PHP_EOL;
                        echo "    # \e[1;33m" . $issues['reservation_number'] . "\e[0m" . PHP_EOL;
                        echo "    ! \e[0;36m" . $issues['issue_title'] . "\e[0m" . PHP_EOL;
                        echo "    @ " . $issues['date_of_detection'] . PHP_EOL;
                        echo PHP_EOL;
                    }
                }
            }

            echo "\e[2;33m  -=#=-  Done  -=#=-\e[0m".PHP_EOL.PHP_EOL;

        } catch (\Exception $e) {

        }
    }

    /**
     * Detect issues by Reservation Id
     */
    public function detectAction()
    {
        try {
            echo PHP_EOL;

            if ($this->id) {
                /**
                 * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
                 */
                $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

                $reservationIssuesService->checkReservationIssues($this->id);

                $this->showAction();
            } else {
                echo "You should use the command parameters to select reservation by id (--id=12345)";
                echo PHP_EOL;
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * Force resolving all detected issues by Reservation Id
     */
    public function forceResolveAction()
    {
        try {
            echo PHP_EOL;

            if ($this->id) {
                /**
                 * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
                 */
                $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

                $reservationIssuesService->resolveReservationAllIssues($this->id, TRUE);

                $this->showAction();

                echo "\e[2;33m  -=#=-  Issues Resolved  -=#=-\e[0m".PHP_EOL.PHP_EOL;
            } else {
                echo "You should use the command parameters to select reservation by id (--id=12345)";
                echo PHP_EOL;
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * Help
     */
    public function helpAction()
    {
echo <<<USAGE

 \e[0;37m----------------------------------------------------------\e[0m
 \e[2;37m          ✡  Ginosi Backoffice Console (GBC)  ✡          \e[0m
 \e[0;37m----------------------------------------------------------\e[0m

 \e[0;37mReservation Issues:\e[0m

    \e[1;33missues help\e[0m                    \e[2;33m- show this help\e[0m

    \e[1;33missues show\e[0m                    \e[2;33m- show all detected issues (--id=BID|otherwise all)\e[0m
    \e[1;33missues detect\e[0m                  \e[2;33m- detect issue for selected thicket --id=BID\e[0m
    \e[1;33missues force-resolve\e[0m           \e[2;33m- remove all issues for selected thicket --id=BID\e[0m


USAGE;
    }
}
