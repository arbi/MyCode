<?php

namespace UniversalDashboard\Controller;

use DDD\Dao\Booking\Statuses;
use DDD\Service\Finance\Transfer;
use DDD\Service\Recruitment\Applicant;
use DDD\Service\UniversalDashboard\Widget\EvaluationLessEmployees as EvaluationLessEmployeesWidgetService;
use DDD\Service\UniversalDashboard\Main;
use DDD\Service\UniversalDashboard\Widget\NotChargedApartelReservations as NotChargedApartelReservationsService;
use DDD\Service\UniversalDashboard\Widget\OverbookingReservations;
use DDD\Service\UniversalDashboard\Widget\TimeOffRequests as TimeOffRequestsService;
use DDD\Service\UniversalDashboard\Widget\UpcomingEvaluations as UpcomingEvaluationsWidgetService;
use DDD\Dao\Accommodation\Accommodations;
use DDD\Service\User as UserService;
use DDD\Service\WHOrder\Order;
use UniversalDashboard\AbstractUDWidget;
use UniversalDashboard\Widget\AssetsAwaitingApproval;
use UniversalDashboard\Widget\AwaitingTransfer;
use UniversalDashboard\Widget\CashPayments;
use UniversalDashboard\Widget\ItemsToBeDelivered;
use UniversalDashboard\Widget\ItemsToBeOrdered;
use UniversalDashboard\Widget\MyActualPOCount;
use UniversalDashboard\Widget\OrdersCreatedByMe;
use UniversalDashboard\Widget\OrdersToBeRefunded;
use UniversalDashboard\Widget\NotApprovedItems;
use UniversalDashboard\Widget\OrdersToBeShipped;
use UniversalDashboard\Widget\OTAConnectionIssues;
use UniversalDashboard\Widget\PendingBudgets;
use UniversalDashboard\Widget\PendingCancelationReservations;
use UniversalDashboard\Widget\PendingPOItems;
use UniversalDashboard\Widget\PendingTransactions;
use UniversalDashboard\Widget\ReadyToBeSettledPO;
use UniversalDashboard\Widget\NewAssetCategories;

use UniversalDashboard\Widget\UnpaidInvoices;
use Zend\View\Model\JsonModel;

use Library\Constants\TextConstants;
use Library\Constants\Roles;
use Library\ActionLogger\Logger;
use Library\Constants\Objects;
use Library\Controller\ControllerBase;

// new dashboards
use UniversalDashboard\Widget\KINotViewedReservations;
use UniversalDashboard\Widget\NoCollectionReservations;
use UniversalDashboard\Widget\CollectFromCustomerReservations;
use UniversalDashboard\Widget\PayToCustomerReservations;
use UniversalDashboard\Widget\ToBeSettledReservations;
use UniversalDashboard\Widget\CollectFromPartnerReservations;
use UniversalDashboard\Widget\ValidateCCReservations;
use UniversalDashboard\Widget\ApprovedVacationRequest;
use UniversalDashboard\Widget\PeopleEvaluations;
use UniversalDashboard\Widget\ApartmentsInRegistrationProcess;
use UniversalDashboard\Widget\FrontierChargeReviewed;
use UniversalDashboard\Widget\ReservationIssues;
use UniversalDashboard\Widget\SuspendedApartments;
use UniversalDashboard\Widget\PinnedReservations;
use UniversalDashboard\Widget\UnresolvedComments;
use UniversalDashboard\Widget\Applicants;
use UniversalDashboard\Widget\NotChargedApartelReservations as NotChargedApartelReservationsWidget;

// old dashboards
use UniversalDashboard\Widget\PendingReservations;
use UniversalDashboard\Widget\VacationRequest;
use UniversalDashboard\Widget\ExpensesAwaitingApproval;
use UniversalDashboard\Widget\Reviews;
use UniversalDashboard\Widget\TasksActions;
use UniversalDashboard\Widget\LastMinuteBookings;

class UniversalDashboardController extends ControllerBase
{
    const REMINDER = 1;
    const PEOPLE_EVALUATION = 2;

    public function indexAction()
    {
        /**
         * @var $homeService Main
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
        $homeService    = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserID = $auth->getIdentity()->id;

        $widgets = [];

        //Recently Evaluated
        if ($auth->hasDashboard(UserService::DASHBOARD_UNRESOLVED_EVALUATIONS)) {
            /** @var \DDD\Service\User\Evaluations $evaluationsService */
            $evaluationsService = $this->getServiceLocator()->get('service_user_evaluations');
            $count = $evaluationsService->getNotResolvedEvaluations()->count();
            if ($count) {
                $peopleEvaluationsWidget = new PeopleEvaluations();
                $peopleEvaluationsWidget->setTitle('Recently Evaluated');
                $peopleEvaluationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['people_evaluations'] = $peopleEvaluationsWidget;
            }
        }

        //New Applicants
        if ($auth->hasDashboard(UserService::DASHBOARD_NEW_APPLICANTS)) {
            /** @var \DDD\Service\Recruitment\Applicant $applicantsService */
            $applicantsService = $this->getServiceLocator()->get('service_recruitment_applicant');
            $count = $applicantsService->getApplicantCount('', Applicant::APPLICANT_STATUS_NEW);

            if ($count) {
                $applicantsWidget = new Applicants();
                $applicantsWidget->setTitle('New Applicants');
                $applicantsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['applicants'] = $applicantsWidget;
            }
        }

        //Approved Vacations
        if ($auth->hasDashboard(UserService::DASHBOARD_APPROVED_VACATIONS)) {
            /** @var \DDD\Dao\User\VacationRequest $vacationsDao */
            $vacationsDao = $this->getServiceLocator()->get('dao_user_vacation_request');
            $count = $vacationsDao->getApprovedNotResolvedVacations()->count();
            if ($count) {
                $approvedVacationsWidget = new ApprovedVacationRequest();
                $approvedVacationsWidget->setTitle('Approved Time Off Requests');
                $approvedVacationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['approved_vacations'] = $approvedVacationsWidget;
            }
        }

        //Pinned Reservation:
        /**
         * @var $pinnedResWidgetService \DDD\Service\UniversalDashboard\Widget\PinnedReservation
         */
        $pinnedResWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pinned_reservation');
        $count = $pinnedResWidgetService->getAllPinnedReservationsCount($loggedInUserID);

        if ($count) {
            $pinnedReservationWidget = new PinnedReservations();
            $pinnedReservationWidget->setTitle('Pinned Reservations');
            $pinnedReservationWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]['pinned_reservation'] = $pinnedReservationWidget;
        }

        // Reservation Issues
        if ($auth->hasDashboard(UserService::DASHBOARD_RESERVATION_ISSUES)) {
            /**
             * @var $reservationIssuesService \DDD\Service\Booking\ReservationIssues
             */
            $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');
            $count                    = $reservationIssuesService->getAllIssuesAndLessThan9DayFromTodayOrbitzAgodaCount(true);
            $occupancyIssueCount      = $reservationIssuesService->getChangedOccupancyReservationCount();

            $count += $occupancyIssueCount;

            if ($count) {
                $reservationIssuesWidget = new ReservationIssues();
                $reservationIssuesWidget->setTitle('Reservation Issues');
                $reservationIssuesWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['reservation_issues'] = $reservationIssuesWidget;
            }
        }

        // Not Bookable Apartments at Partners
        if ($auth->hasDashboard(UserService::DASHBOARD_OTA_CONNECTION_ISSUES)) {
            /* @var $otaDistributionService \DDD\Service\OTADistribution */
            $otaDistributionService = $this->getServiceLocator()->get('service_ota_distribution');
            $count = $otaDistributionService->getIssueConnectionsCount();

            if ($count) {
                $otaConnectionIssuesWidget = new OTAConnectionIssues();
                $otaConnectionIssuesWidget->setTitle('Not Bookable Apartments at Partners');
                $otaConnectionIssuesWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['ota_connection_issues'] = $otaConnectionIssuesWidget;
            }
        }

        // Pending Cancelations
        if ($auth->hasDashboard(UserService::DASHBOARD_PENDING_CANCELLATION)) {
            /* @var $pendingCancelationWidgetService \DDD\Service\UniversalDashboard\Widget\PendingCancelation */
            $pendingCancelationWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_cancellation');
            $count = $pendingCancelationWidgetService->getPendingCancelationReservationsCount();

            if ($count) {
                $pendingCancelationReservationsWidget = new PendingCancelationReservations();
                $pendingCancelationReservationsWidget->setTitle('Pending Cancellations');
                $pendingCancelationReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DANGER]['pending_cancelation_reservations'] = $pendingCancelationReservationsWidget;
            }
        }

        // Last Minute Reservation Arrivals
        if ($auth->hasDashboard(UserService::DASHBOARD_LAST_MINUTE_BOOKINGS)) {
            $count = $homeService->getLastMinuteReservationCount();

            if ($count) {
                $lastMinuteReservationsWidget = new LastMinuteBookings();
                $lastMinuteReservationsWidget->setTitle('Last Minute Reservation Arrivals');
                $lastMinuteReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DANGER]["widget_last_minute_reservation_arrivals"] = $lastMinuteReservationsWidget;
            }
        }

        // Not Charged Apartel Reservations
        if ($auth->hasDashboard(UserService::DASHBOARD_CHARGE_APARTEL_RESERVATIONS)) {
            /**
             * @var NotChargedApartelReservationsService $notChargedApartelReservationsService
             */
            $notChargedApartelReservationsService = $this->getServiceLocator()->get('service_universal_dashboard_widget_not_charged_apartel_reservations');
            $count = $notChargedApartelReservationsService->getNotChargedApartelReservationsCount();

            if ($count) {
                $notChargedApartelReservationsWidget = new NotChargedApartelReservationsWidget();
                $notChargedApartelReservationsWidget->setTitle('Not Charged Apartel Reservations');
                $notChargedApartelReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DANGER]["widget_not_charges_apartel"] = $notChargedApartelReservationsWidget;
            }
        }

        // Apartments In Registration Process
        if ($auth->hasDashboard(UserService::DASHBOARD_APARTMENTS_IN_REGISTRATION_PROCESS)) {
            /* @var $inRegistrationProcessWidgetService \DDD\Service\UniversalDashboard\Widget\InRegistrationProcess */
            $inRegistrationProcessWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_in_registration_process');
            $count = $inRegistrationProcessWidgetService->getApartmentsInRegistrationProcessCount();

            if ($count) {
                $inRegistrationProcessWidget = new ApartmentsInRegistrationProcess();
                $inRegistrationProcessWidget->setTitle('Apartments in Registration');;
                $inRegistrationProcessWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['apartments_in_registration_process'] = $inRegistrationProcessWidget;
            }
        }

        // Key Instructions Not Viewed
        if ($auth->hasDashboard(UserService::DASHBOARD_KI_NOT_VIEWED)) {
            /* @var $kiNotViewedWidgetService \DDD\Service\UniversalDashboard\Widget\KINotViewed */
            $kiNotViewedWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_ki_not_viewed');
            $count = $kiNotViewedWidgetService->getKINotViewedReservationsCount();
            if ($count) {
                $kiNotViewedReservationsWidget = new KINotViewedReservations();
                $kiNotViewedReservationsWidget->setTitle('Key Instructions Not Viewed');
                $kiNotViewedReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['ki_not_viewed_reservations'] =  $kiNotViewedReservationsWidget;
            }
        }

        // No Collection
        if ($auth->hasDashboard(UserService::DASHBOARD_NO_COLLECTION)) {
            /* @var $noCollectionWidgetService \DDD\Service\UniversalDashboard\Widget\NoCollection */
            $noCollectionWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_no_collection');
            $count = $noCollectionWidgetService->getNoCollectionReservationsCount();

            if ($count) {
                $noCollectionReservationsWidget = new NoCollectionReservations();
                $noCollectionReservationsWidget->setTitle('Not Able to Collect');
                $noCollectionReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['no_collection_reservations'] = $noCollectionReservationsWidget;
            }
        }

        // Colect From Customer
        if ($auth->hasDashboard(UserService::DASHBOARD_COLLECT_FROM_CUSTOMER)) {
            /* @var $collectFromCustomerWidgetService \DDD\Service\UniversalDashboard\Widget\CollectFromCustomer */
            $collectFromCustomerWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_collect_from_customer');
            $count = $collectFromCustomerWidgetService->getCollectFromCustomerReservationsCount();

            if($count) {
                $collectFromCustomerWidget = new CollectFromCustomerReservations();
                $collectFromCustomerWidget->setTitle('Collect From Customer');
                $collectFromCustomerWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['collect_from_customer_reservations'] = $collectFromCustomerWidget;
            }
        }

        // Pay To Customer
        if ($auth->hasDashboard(UserService::DASHBOARD_PAY_TO_CUSTOMER)) {
            /* @var $payToCustomerWidgetService \DDD\Service\UniversalDashboard\Widget\PayToCustomer */
            $payToCustomerWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pay_to_customer');
            $count = $payToCustomerWidgetService ->getPayToCustomerReservationsCount();

            if($count) {
                $payToCustomerWidget = new PayToCustomerReservations();
                $payToCustomerWidget->setTitle('Pay to Customer');
                $payToCustomerWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['pay_to_customer_reservations'] = $payToCustomerWidget;
            }
        }

        // To Be Settled
        if ($auth->hasDashboard(UserService::DASHBOARD_MARK_AS_SETTLED)) {
            /* @var $toBeSettledWidgetService \DDD\Service\UniversalDashboard\Widget\ToBeSettled */
            $toBeSettledWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_to_be_settled');
            $count = $toBeSettledWidgetService->getToBeSettledReservationsCount();

            if ($count) {
                $toBeSettledWidget = new ToBeSettledReservations();
                $toBeSettledWidget->setTitle('Mark as Settled');
                $toBeSettledWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['to_be_settled_reservations'] = $toBeSettledWidget;
            }
        }
        /**
         * @var $pendingTransactionWidgetService \DDD\Service\UniversalDashboard\Widget\PendingTransaction
         */
        $pendingTransactionWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_transaction');

        // Pending Transaction
        if ($auth->hasDashboard(UserService::DASHBOARD_TRANSACTION_PENDING)) {
            $count = $pendingTransactionWidgetService->getPendingTransactionsCount();
            if($count){
                $pendingTransactionWidget = new PendingTransactions();
                $pendingTransactionWidget->setTitle('Pending Transactions');
                $pendingTransactionWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]["pending_pending_widget"] = $pendingTransactionWidget;
            }
        }

        // Cash payments
        if ($auth->hasDashboard(UserService::DASHBOARD_CASH_PAYMENTS)) {
            $count = $pendingTransactionWidgetService->getPendingTransactionsCount('cash');
            if($count) {
                $notVerifiedTransactionsWidget = new CashPayments();
                $notVerifiedTransactionsWidget->setTitle('Cash Payments');
                $notVerifiedTransactionsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['not_verified_transactions'] = $notVerifiedTransactionsWidget;
            }
        }

        // Frontier charge reviewed
        if ($auth->hasDashboard(UserService::DASHBOARD_FRONTIER_CHARGE_REVIEWED)
            && $auth->hasRole(Roles::ROLE_BOOKING_TRANSACTION_VERIFIER)
        ) {
            $count = $pendingTransactionWidgetService->getPendingTransactionsCount('frontier');
            if ($count) {
                $reviewTransactionsWidget = new FrontierChargeReviewed();
                $reviewTransactionsWidget->setTitle('Point of Sale Charges');
                $reviewTransactionsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['reviewed_transaction_widget'] = $reviewTransactionsWidget;
            }
        }

        // Collect From Partner
        if ($auth->hasDashboard(UserService::DASHBOARD_COLLECT_FROM_PARTNER)) {
            /* @var $collectFromPartnerWidgetService \DDD\Service\UniversalDashboard\Widget\CollectFromPartner */
            $collectFromPartnerWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_collect_from_partner');
            $count = $collectFromPartnerWidgetService->getCollectFromPartnerReservationsCount();
            if($count){
                $collectFromPartnerWidget = new CollectFromPartnerReservations();
                $collectFromPartnerWidget->setTitle('Collect from Partner');
                $collectFromPartnerWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['collect_from_partner_reservations'] = $collectFromPartnerWidget;
            }
        }

        // Validate Credit Card
        if ($auth->hasDashboard(UserService::DASHBOARD_VALIDATE_CC)) {
            /* @var $validateCCWidgetService \DDD\Service\UniversalDashboard\Widget\ValidateCC */
            $validateCCWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_validate_cc');
            $count = $validateCCWidgetService->getValidateCCReservationsCount();

            if ($count) {
                $validateCCWidget = new ValidateCCReservations();
                $validateCCWidget->setTitle('Validate Credit Cards');
                $validateCCWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['validate_cc_reservations'] = $validateCCWidget;
            }
        }

        // Awaiting Payment Details
        if ($auth->hasDashboard(UserService::DASHBOARD_AWAITING_PAYMENT_DETAILS)) {
            /**
             * @var $bookingService \DDD\Service\Booking
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');
            $count = $bookingService->getPendingReservationsCount();

            if ($count) {
                $pendingReservationsWidget = new PendingReservations();
                $pendingReservationsWidget->setTitle('Awaiting Payment Details');
                $pendingReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]["pending_reservations"] = $pendingReservationsWidget;
            }
        }

        // New Reviews
        if ($auth->hasDashboard(UserService::DASHBOARD_NEW_REVIEWS)) {
            /**
             * @var $reviewService \DDD\Service\Review
             */
            $reviewService  = $this->getServiceLocator()->get('service_review');
            $count = $reviewService->getPendingReviewsCount();

            if ($count) {
                $reviewsWidget = new Reviews();
                $reviewsWidget->setTitle('New Reviews');
                $reviewsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]["widget_review_service"] = $reviewsWidget;
            }
        }

        // Purchase Orders awaiting approval
        if ($auth->hasDashboard(UserService::DASHBOARD_PO_AWAITING_APPROVAL)) {
            $count = $homeService->getAwaitingApprovalExpenseCount();
            if ($count) {
                $expensesAwaitingApprovalWidget = new ExpensesAwaitingApproval();
                $expensesAwaitingApprovalWidget->setTitle('Purchase Orders Awaiting Approval');
                $expensesAwaitingApprovalWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]["widget_expenses_to_approve"] = $expensesAwaitingApprovalWidget;
            }
        }

        // PO items awaiting transfers (request an advance)
        if ($auth->hasDashboard(UserService::DASHBOARD_AWAITING_TRANSFERS)) {
            $count = $homeService->getAwaitingTransferCount();
            if ($count) {
                $awaitingTransferWidget = new AwaitingTransfer();
                $awaitingTransferWidget->setTitle('Awaiting Transfers');
                $awaitingTransferWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]["widget_awaiting_transfer"] = $awaitingTransferWidget;
            }
        }

        //                          Widgets without permissions                         //

        // Expenses Awaiting Approval
        $count = $homeService->getPendingPOItemsCount($loggedInUserID);
        if ($count) {
            $pendingPOItemsWidget = new PendingPOItems();
            $pendingPOItemsWidget->setTitle('Expenses Awaiting Approval');
            $pendingPOItemsWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_finance_pending_po_items"] = $pendingPOItemsWidget;
        }

        // My Expenses
        $count = $homeService->getNotApprovedItemsCount($loggedInUserID);
        if ($count) {
            $financeNotApprovedItemsWidget = new NotApprovedItems();
            $financeNotApprovedItemsWidget->setTitle('My Expenses');
            $financeNotApprovedItemsWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_finance_not_approved_items"] = $financeNotApprovedItemsWidget;
        }

        // Closed for Review Purchase Orders
        if ($auth->hasDashboard(UserService::DASHBOARD_PO_READY_TO_BE_SETTLED)) {
            $count = $homeService->getReadyToBeSettledPOCount();
            if ($count) {
                $readyToBeSettledPOWidget = new ReadyToBeSettledPO();
                $readyToBeSettledPOWidget->setTitle('Closed for Review Purchase Orders');
                $readyToBeSettledPOWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]["widget_po_ready_to_be_settled"] = $readyToBeSettledPOWidget;
            }
        }

        // My Actual POs
        $count = $homeService->getMyActualPOCount($loggedInUserID);
        if ($count) {
            $handledPOWidget = new MyActualPOCount();
            $handledPOWidget->setTitle('My Actual Purchase Orders');
            $handledPOWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_po_handled"] = $handledPOWidget;
        }

        // Time off requests
        /**
         * @var TimeOffRequestsService $timeOffRequestsWidgetService
         */
        $timeOffRequestsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_time_off_requests');
        $timeOffRequestsCount = $timeOffRequestsWidgetService->getTimeOffRequestsCount($loggedInUserID);

        if ($timeOffRequestsCount) {
            $vacationRequestWidget = new VacationRequest();
            $vacationRequestWidget->setTitle('Time Off Requests');
            $vacationRequestWidget->setCount($timeOffRequestsCount);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_time_off_requests"] = $vacationRequestWidget;
        }

        /**
         * @var \DDD\Service\Task $taskService
         */
        $taskService    = $this->getServiceLocator()->get('service_task');

        // Tasks Unassigned
        /**
         * @var UserService\Main $userMainService
         */

        $count = $taskService->getUDListCount($loggedInUserID, 'team');

        if ($count) {
            $taskWidgetTeam = new TasksActions('team');
            $taskWidgetTeam->setTitle('Unassigned Tasks for my Team(s)');
            $taskWidgetTeam->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widgettasks_for_my_team"] = $taskWidgetTeam;
        }

        // Tasks doing
        $count = $taskService->getUDListCount($loggedInUserID, 'doing');

        if ($count) {
            $taskWidgetDoing = new TasksActions('doing');
            $taskWidgetDoing->setTitle('Tasks I\'m Doing');
            $taskWidgetDoing->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_tasks_doing"] = $taskWidgetDoing;
        }

        // Tasks for my team
        $count = $taskService->getUDListCount($loggedInUserID, 'team_assigned');

        if ($count) {
            $taskWidgetTeamAssigned = new TasksActions('team_assigned');
            $taskWidgetTeamAssigned->setTitle('Tasks Assigned to my Team');
            $taskWidgetTeamAssigned->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_tasks_team_assigned"] = $taskWidgetTeamAssigned;
        }

        // Tasks To Be Verified
        /**
         * @var $taskService \DDD\Service\Task
         */
        $taskService    = $this->getServiceLocator()->get('service_task');
        $count = $taskService->getUDListCount($loggedInUserID, 'verifying');

        if ($count) {
            $taskWidgetVerifying = new TasksActions('verifying');
            $taskWidgetVerifying->setTitle('Tasks I\'m Verifying');
            $taskWidgetVerifying->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_tasks_verifying"] = $taskWidgetVerifying;
        }

        // Tasks created
        $count = $taskService->getUDListCount($loggedInUserID, 'created');

        if ($count) {
            $taskWidgetCreated = new TasksActions('created');
            $taskWidgetCreated->setTitle('Tasks I\'ve Created');
            $taskWidgetCreated->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_tasks_i_created"] = $taskWidgetCreated;
        }

        // Tasks following
        $count = $taskService->getUDListCount($loggedInUserID, 'following');

        if ($count) {
            $taskWidgetFollowing = new TasksActions('following');
            $taskWidgetFollowing->setTitle('Tasks I\'m Following');
            $taskWidgetFollowing->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]["widget_tasks_i_am_following"] = $taskWidgetFollowing;
        }

        // Suspended Apartments
        if ($auth->hasDashboard(UserService::DASHBOARD_SUSPENDED_APARTMENTS)) {
            /* @var $suspendedApartmentsWidgetService \DDD\Service\UniversalDashboard\Widget\SuspendedApartments */
            $suspendedApartmentsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_suspended_apartments');
            $count = $suspendedApartmentsWidgetService->getSuspendedApartmentsCount();

            if ($count) {
                $suspendedApartmentsWidget = new SuspendedApartments();
                $suspendedApartmentsWidget->setTitle('Suspended Apartments');
                $suspendedApartmentsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_WARNING]['suspended_apartments'] = $suspendedApartmentsWidget;
            }
        }

        // Unresolved Comments
        $resolveCommentsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_resolve_comments');
        $count = $resolveCommentsWidgetService->getUnresolvedCommentsCountForUser($loggedInUserID);

        if ($count) {
            $unesolvedCommentsWidget = new UnresolvedComments();
            $unesolvedCommentsWidget->setTitle('Unresolved Comments');
            $unesolvedCommentsWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_WARNING]['unresolved_comments'] = $unesolvedCommentsWidget;
        }

        // Overbooking Reservations
        if ($auth->hasDashboard(UserService::DASHBOARD_OVERBOOKING_RESERVATIONS)) {
            /**
             * @var OverbookingReservations $overbookingReservationsWidgetService
             */
            $overbookingReservationsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_overbooking_reservations');
            $count = $overbookingReservationsWidgetService->getOverbookingReservationsCount();

            if ($count) {
                $overbookingReservationsWidget = new \UniversalDashboard\Widget\OverbookingReservations();
                $overbookingReservationsWidget->setTitle('Overbooking Reservations');
                $overbookingReservationsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DANGER]['overbooking_reservations'] = $overbookingReservationsWidget;
            }
        }

        // Upcoming Evaluations
        /**
         * @var UpcomingEvaluationsWidgetService $upcomingEvaluationsWidgetService
         */
        $upcomingEvaluationsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_upcoming_evaluations');
        $count = $upcomingEvaluationsWidgetService->getUpcomingEvaluationsCount();

        if ($count) {
            $upcomingEvaluationsWidget = new \UniversalDashboard\Widget\UpcomingEvaluations();
            $upcomingEvaluationsWidget->setTitle('Upcoming Evaluations');
            $upcomingEvaluationsWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]['upcoming_evaluations'] = $upcomingEvaluationsWidget;
        }

        // Evaluation Less Employees
        /**
         * @var EvaluationLessEmployeesWidgetService $evaluationLessEmployeesWidgetService
         */
        $evaluationLessEmployeesWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_evaluation_less_employees');
        $count = $evaluationLessEmployeesWidgetService->getEvaluationLessEmployeesCount();

        if ($count) {
            $evaluationLessEmployeesWidget = new \UniversalDashboard\Widget\EvaluationLessEmployees();
            $evaluationLessEmployeesWidget->setTitle('Employees Without Planned Evaluations');
            $evaluationLessEmployeesWidget->setCount($count);
            $widgets[AbstractUDWidget::WIDGET_INFO]['evaluation_less_employees'] = $evaluationLessEmployeesWidget;
        }

        // Pending Transfers
        /**
         * @var Transfer $transferService
         */
        if ($auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL)) {
            $transferService = $this->getServiceLocator()->get('service_finance_transfer');
            $count = $transferService->getPendingTransferCount();

            if ($count) {
                $pendingTransfersWidget = new \UniversalDashboard\Widget\PendingTransfer();
                $pendingTransfersWidget->setTitle('Pending Transfers');
                $pendingTransfersWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]['pending_transfers'] = $pendingTransfersWidget;
            }
        }

        // Pending Budgets
        if ($auth->hasDashboard(UserService::DASHBOARD_PENDING_BUDGET)) {

            /**
             * @var \DDD\Service\Finance\Budget $budgetService
             */
            $budgetService = $this->getServiceLocator()->get('service_finance_budget');
            $count = $budgetService->getPendingBudgetCount();

            if ($count) {
                $pendingBudgetsWidget = new PendingBudgets();
                $pendingBudgetsWidget->setTitle('Pending Budgets');
                $pendingBudgetsWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]["widget_pending_budgets"] = $pendingBudgetsWidget;
            }
        }

        //Assets Awaiting for Approval
        if ($auth->hasDashboard(UserService::DASHBOARD_PENDING_ASSETS)) {
            /** @var \DDD\Service\Warehouse\Asset $assetService */
            $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

            $count = $assetService->getAssetsAwaitingApprovalCount();

            if ($count) {
                $assetsAwaitingApprovalWidget = new AssetsAwaitingApproval();
                $assetsAwaitingApprovalWidget->setTitle('Assets Awaiting for Approval');
                $assetsAwaitingApprovalWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['widget_assets_awaiting_approval'] = $assetsAwaitingApprovalWidget;
            }
        }

        //Items To Be Ordered
        if ($auth->hasDashboard(UserService::DASHBOARD_ITEMS_TO_BE_ORDERED)) {
            /** @var Order $orderService */
            $orderService = $this->getServiceLocator()->get('service_wh_order_order');

            $count = $orderService->getItemsToBeOrderedCount($loggedInUserID);

            if ($count) {
                $itemsToBeOrderedWidget = new ItemsToBeOrdered();
                $itemsToBeOrderedWidget->setTitle('Items Ready to Order');
                $itemsToBeOrderedWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['widget_items_to_ordered'] = $itemsToBeOrderedWidget;
            }
        }

        //Items To Be Delivered
        if ($auth->hasDashboard(UserService::DASHBOARD_ITEMS_TO_BE_DELIVERED)) {
            /** @var Order $orderService */
            $orderService = $this->getServiceLocator()->get('service_wh_order_order');

            $count = $orderService->getItemsToBeDeliveredCount($loggedInUserID);

            if ($count) {
                $itemsToBeDeliveredWidget = new ItemsToBeDelivered();
                $itemsToBeDeliveredWidget->setTitle('Items to be Delivered');
                $itemsToBeDeliveredWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['widget_items_to_delivered'] = $itemsToBeDeliveredWidget;
            }
        }

        //Orders To Be Refunded
        if ($auth->hasDashboard(UserService::DASHBOARD_ORDERS_TO_REFUNDED)) {
            /** @var Order $orderService */
            $orderService = $this->getServiceLocator()->get('service_wh_order_order');

            $count = $orderService->getOrdersToBeRefundedCount($loggedInUserID);

            if ($count) {
                $ordersToBeDeliveredWidget = new OrdersToBeRefunded();
                $ordersToBeDeliveredWidget->setTitle('Orders to be Refunded');
                $ordersToBeDeliveredWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['widget_orders_to_refunded'] = $ordersToBeDeliveredWidget;
            }
        }

        // My Orders
        if ($auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT) || $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL)
            || $auth->hasRole(Roles::ROLE_WH_CREATE_ORDER_FUNCTION)
        ) {
            /** @var Order $orderService */
            $orderService = $this->getServiceLocator()->get('service_wh_order_order');

            $count = $orderService->getOrdersCreatedByMeCount($loggedInUserID);

            if ($count) {
                $ordersToBeDeliveredWidget = new OrdersCreatedByMe();
                $ordersToBeDeliveredWidget->setTitle('My Orders');
                $ordersToBeDeliveredWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['my_orders'] = $ordersToBeDeliveredWidget;
            }
        }

        // Orders to be shipped in last 2 days
        if ($auth->hasDashboard(UserService::DASHBOARD_ORDERS_TO_BE_SHIPPED)) {
            /** @var Order $orderService */
            $orderService = $this->getServiceLocator()->get('service_wh_order_order');
            $count        = $orderService->getOrdersToBeShippedInLastTwoDaysCount();

            if ($count) {
                $ordersToBeShippedWidget = new OrdersToBeShipped();
                $ordersToBeShippedWidget->setTitle('Orders To Be Shipped');
                $ordersToBeShippedWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['orders_to_be_shipped'] = $ordersToBeShippedWidget;
            }
        }

        // New Asset Categories
        if ($auth->hasDashboard(UserService::DASHBOARD_NEW_ASSET_CATEGORIES)) {
            /** @var Order $orderService */
            $categorySevice = $this->getServiceLocator()->get('service_warehouse_category');

            $count = $categorySevice->getNewAssetCategoriesCount();

            if ($count) {
                $newAssetCategoriesWidget = new NewAssetCategories();
                $newAssetCategoriesWidget->setTitle('New Asset Categories');
                $newAssetCategoriesWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_INFO]['new_category'] = $newAssetCategoriesWidget;
            }
        }

        // Unpaid Invoices
        if ($auth->hasDashboard(UserService::DASHBOARD_UNPAID_INVOICES)) {

            /**
             * @var \DDD\Service\Finance\Expense\ExpenseTicket $expenseTicketService
             */
            $expenseTicketService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
            $count = $expenseTicketService->getUnpaidInvoicesCount();
            if ($count) {
                $unpaidInvoicesWidget = new UnpaidInvoices();
                $unpaidInvoicesWidget->setTitle('Unpaid Invoices');
                $unpaidInvoicesWidget->setCount($count);
                $widgets[AbstractUDWidget::WIDGET_DEFAULT]["widget_unpaid_invoices"] = $unpaidInvoicesWidget;
            }
        }


        return ['widgets' => $widgets];
    }


    public function ajaxApplyCancelationAction()
    {
        /**
         * @var \DDD\Service\UniversalDashboard\Widget\PendingCancelation $pendingCancelationWidgetService
         */
        $pendingCancelationWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_cancellation');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_PENDING_CANCELLATION)) {
            return $this->redirect()->toRoute('home');
        }

        $logger = $this->getServiceLocator()->get('ActionLogger');

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $params        = $this->params()->fromPost();
                $resNumber     = $params['res_number'];
                $bookingStatus = $params['booking_status'];
                $bookingId     = $params['booking_id'];

                $resolveResult = $pendingCancelationWidgetService->applyCancelation($resNumber, $bookingStatus);

                if ($resolveResult) {
                    $bookingStatusesDao = new Statuses($this->getServiceLocator(), '\ArrayObject');
                    $bookingStatuses = $bookingStatusesDao->getAllList();

                    $result['status'] = 'success';
                    $statusName = '';

                    foreach ($bookingStatuses as $status) {
                        if ($bookingStatus == $status['id']) {
                            $statusName = $status['name'];
                        }
                    }
                    $result['msg'] = 'Reservation with R# ' . $resNumber . ' marked as ' . $statusName;

                    $logger->save(
                        Logger::MODULE_BOOKING,
                        $bookingId,
                        Logger::ACTION_BOOKING_STATUSES,
                        Objects::getBookingStatusMapping()[$bookingStatus]
                    );

                } else {
                    $result['msg'] = 'Problem during requested operation for R# ' . $resNumber;
                }
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    /**
     * Action for handling process on clicking "Collected" button in "Collect From Partner" widget
     */
    public function ajaxCollectFromPartnerAction()
    {
        /**
         * @var $collectFromPartnerService \DDD\Service\UniversalDashboard\Widget\CollectFromPartner
         */
        $collectFromPartnerService = $this->getServiceLocator()->get('service_universal_dashboard_widget_collect_from_partner');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_COLLECT_FROM_PARTNER)) {
                return $this->redirect()->toRoute('home');
            }

            if ($request->getMethod() === 'POST') {
                $params = $this->params()->fromPost();
                $resNumber = $params['res_number'];

                $result = $collectFromPartnerService->markAsPartnerSettled($resNumber);
            } else {
                $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
            }
        } catch (\Exception $e) {
            // don nothing
        }

        return new JsonModel($result);
    }

    public function ajaxMarkSettledAction()
    {
        /**
         * @var \DDD\Service\UniversalDashboard\Widget\ToBeSettled $markAsSettledService
         */
        $markAsSettledService = $this->getServiceLocator()->get('service_universal_dashboard_widget_to_be_settled');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_MARK_AS_SETTLED)) {
                return $this->redirect()->toRoute('home');
            }

            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $resNumber = $request->getPost('res_number');

                $markAsSettledResult = $markAsSettledService->markAsSettled($resNumber);

                if ($markAsSettledResult) {
                    $result['status'] = 'success';
                    $result['msg'] = "R# {$resNumber} marked as \"Settled\"";
                } else {
                    $result['status'] = 'error';
                    $result['msg'] = "Problem during requested operation for R# {$resNumber}";
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxResolveLastMinuteReservationAction()
    {
        /**
         * @var Main $homeService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_LAST_MINUTE_BOOKINGS)) {
            return $this->redirect()->toRoute('home');
        }

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id = $this->params()->fromPost('id');
                $responsDb = $homeService->resolveLastMinuteReservation($id);

                if ($responsDb) {
                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::UNIVERSAL_DASHBOARD_RESERVATION_RESOLVED;
                }
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxApproveReviewAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $apartmentReviewService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $apartmentReviewService = $this->getServiceLocator()->get('service_apartment_review');
        $request = $this->getRequest();
        $result = [
            'result' => [],
            'status' => 'success',
            'msg' => TextConstants::UNIVERSAL_DASHBOARD_TIME_OFF_REQUEST_RESPONSE,
        ];

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_NEW_REVIEWS)) {
            return $this->redirect()->toRoute('home');
        }

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $reviewId = (int)$request->getPost('id');
                $status = $request->getPost('value');
                $apartmentId = (int)$request->getPost('apartment_id');

                $response = $apartmentReviewService->updateReview($reviewId, $status, $apartmentId);

                if ($response){
                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::SUCCESS_UPDATE;
                } else {
                    $result['status'] = 'error';
                    $result['msg'] = TextConstants::SERVER_ERROR;
                }
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    /**
     * @todo: check usage!
     */
    public function ajaxMarkPaidAction()
    {
        /**
         * @var Main $service
         */
        $service = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => 'Reservation marked as paid to affiliate.',
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id = (int)$request->getPost('id');
                $auth = $auth->getIdentity();

                $service->markPaid($id, $auth);
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxVacationRequestAction()
    {
        /**
         * @var TimeOffRequestsService $timeOffRequestsWidgetService
         */
        $timeOffRequestsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_time_off_requests');
        $request = $this->getRequest();
        $result = [
            'result' => [],
            'status' => 'success',
            'msg'    => TextConstants::UNIVERSAL_DASHBOARD_TIME_OFF_REQUEST_RESPONSE
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $vacationRequestId = intval($request->getPost('id'));
                $vacationRequestStatus = intval($request->getPost('value'));

                $response = $timeOffRequestsWidgetService->vacationRequestUpdate(
                    $vacationRequestId,
                    $vacationRequestStatus
                );

                if (!$response) {
                    $result['status'] = 'error';
                    $result['msg']    = TextConstants::SERVER_ERROR;
                } else {
                    $result['result'] = $vacationRequestStatus;
                }
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    /**
     * Action for handling process on clicking Reviewed
     *
     * @todo: check usage!
     */
    public function ajaxResolveCommentAction()
    {
        /**
         * @var \DDD\Dao\ActionLogs\LogsTeam $logsTeamDao
         */
        $request = $this->getRequest();
        $logsTeamDao = $this->getServiceLocator()->get('dao_action_logs_logs_team');
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id = (int)$request->getPost('id', 0);
                $logsTeamDao->deleteWhere(['id' => $id]);

                $result['status'] = 'success';
                $result['msg']    = TextConstants::SUCCESS_RESOLVED;
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function ajaxResolveVacationRequestAction()
    {
        /**
         * @var \DDD\Dao\User\VacationRequest $vacationsDao
         */
        $vacationsDao = $this->getServiceLocator()->get('dao_user_vacation_request');
        $request = $this->getRequest();

        $result = [
            'result' => [],
            'status' => 'success',
            'msg' => TextConstants::UNIVERSAL_DASHBOARD_RESOLVE_REQUEST_RESPONSE,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id = (int)$request->getPost('id');
                $vacationsDao->resolveVacationRequest($id);
            } else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxArchiveOrderAction()
    {
        /**
         * @var \DDD\Service\WHOrder\Order $orderService
         */

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {

            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $orderId = $request->getPost('order_id', 0);

                $response = $orderService->archiveOrder($orderId);
                if ($response) {
                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::SUCCESS_ARCHIVE;
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxResolveUnpaidItemAction()
    {
        /**
         * @var \DDD\Service\Finance\Expense\ExpenseTicket $expenseTicketService
         */

        $expenseTicketService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if (!$auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL)) {
                return $this->redirect()->toRoute('home');
            }

            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $expenseItemId = $request->getPost('expense_item_id', 0);

                $response = $expenseTicketService->resolveUnpaidItem($expenseItemId);
                if ($response) {
                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::SUCCESS_ARCHIVE;
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxMarkReceivedOrderAction()
    {
        /**
         * @var \DDD\Service\WHOrder\Order $orderService
         */

        $orderService = $this->getServiceLocator()->get('service_wh_order_order');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $orderId = $request->getPost('order_id', 0);

                $response = $orderService->markReceivedOrder($orderId);
                if ($response) {
                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::SUCCESS_ARCHIVE;
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function getProductGeneralDao($domain = 'DDD\Domain\Accommodation\Accommodations')
    {
        return new Accommodations($this->getServiceLocator(), $domain);
    }
}
