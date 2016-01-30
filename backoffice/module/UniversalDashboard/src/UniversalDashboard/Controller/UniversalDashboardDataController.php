<?php

namespace UniversalDashboard\Controller;

use DDD\Service\Finance\Budget;
use DDD\Service\Finance\Transfer;
use DDD\Service\Partners;
use DDD\Service\Recruitment\Applicant;
use DDD\Service\UniversalDashboard\Main;
use DDD\Domain\Booking\CustomerService;
use DDD\Service\UniversalDashboard\Widget\EvaluationLessEmployees;
use DDD\Service\UniversalDashboard\Widget\NotChargedApartelReservations as NotChargedApartelReservationsService;
use DDD\Service\UniversalDashboard\Widget\OverbookingReservations;
use DDD\Service\UniversalDashboard\Widget\TimeOffRequests as TimeOffRequestsService;
use DDD\Service\UniversalDashboard\Widget\UpcomingEvaluations as UpcomingEvaluationsService;
use DDD\Service\Warehouse\Category;
use DDD\Service\WHOrder\Order;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use Library\Constants\Objects;
use Library\Constants\DomainConstants;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Constants\Constants;
use DDD\Service\Task;
use DDD\Service\User as UserService;
use DDD\Service\Booking;
use DDD\Service\Apartment\Review;
use DDD\Dao\Booking\Statuses;
use DDD\Domain\Review\ReviewBase;
use DDD\Service\User\Vacation as VacationService;
use DDD\Service\Warehouse\Asset as AssetService;

use Library\Finance\Base\Account;
use Library\Finance\Process\Expense\Helper;
use Zend\View\Model\JsonModel;

class UniversalDashboardDataController extends ControllerBase
{
    const REMINDER = 1;

    public function getPeopleEvaluationsAction()
    {
        /** @var \DDD\Service\User\Evaluations $evaluationsService */
        $evaluationsService = $this->getServiceLocator()->get('service_user_evaluations');
        $evaluations = $evaluationsService->getNotResolvedEvaluations();
        $preparedData = [];

        if ($evaluations && $evaluations->count()) {

            foreach ($evaluations as $evaluation) {
                $description = strip_tags($evaluation->getDescription());
                if (strlen($description) > 75) {
                    $description = substr($description, 0, 75) . '...';
                }

                array_push($preparedData, [
                    '<a href="/user-evaluation/view/' . $evaluation->getId() . '">' .
                        $evaluation->getEmployeeFullName() .
                        '</a>  has been evaluated by ' . $evaluation->getCreatorFullName() . '. "'.
                        $description . '"',
                    '<a data-id="'. $evaluation->getId() . '" href="javascript:void(0)" onclick="resolveEvaluation(this)" class="btn btn-xs btn-success">Resolve</a>'
                ]);
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getApprovedVacationsAction()
    {
        /** @var \DDD\Dao\User\VacationRequest $vacationsDao */
        $vacationsDao = $this->getServiceLocator()->get('dao_user_vacation_request');
        $vacations = $vacationsDao->getApprovedNotResolvedVacations();
        $vacationDao = $this->getServiceLocator()->get('dao_user_vacation_days');

        $preparedData = [];
        if ($vacations && $vacations->count()) {
            foreach ($vacations as $vacation) {
                $vacationTypes = Objects::getVacationType();
                $remainColumn  = $vacation->getVacation_days();

                if ($vacation->getType() == VacationService::VACATION_TYPE_SICK) {

                    $takenSickDays = 0;
                    $sickDays      = $vacationDao->getSickDays($vacation->getUser_id());
                    if ($sickDays) {
                        foreach ($sickDays as $sickDay) {
                            $takenSickDays += abs($sickDay['total_number']);
                        }
                    }

                    if ($vacation->getSickDays() != -1) {
                        $remainColumn = $vacation->getSickDays() - $takenSickDays;
                    } else {
                        $remainColumn = '';
                    }

                }
                $actionsColumn = '
                    <a href="/profile/' . $vacation->getUser_id() . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>
					<a href="javascript:void(0)" class="btn btn-xs btn-success" onclick="resolveVacation(' . $vacation->getId() . ')" id="vac_resolve_' . $vacation->getId() . '"> Resolve </a>';

                array_push($preparedData, [
                    $vacation->getFullName(),
                    $vacation->getType() ? $vacationTypes[$vacation->getType()] : '',
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($vacation->getFrom())) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($vacation->getTo())),
                    $vacation->getTotal_number(),
                    round($remainColumn, 2),
                    $actionsColumn,
                ]);
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getNewApplicantsAction()
    {

        /** @var \DDD\Service\Recruitment\Applicant $applicantsService */
        $applicantsService = $this->getServiceLocator()->get('service_recruitment_applicant');

        $applicants = $applicantsService->getApplicantList(0, 0, 1, 'ASC', '', Applicant::APPLICANT_STATUS_NEW);

        if ($applicants && count($applicants)) {
            $preparedData = [];

            /** @var \DDD\Domain\Recruitment\Applicant\Applicant $applicant */
            foreach ($applicants as $applicant) {
                array_push($preparedData, [
                    "0" => $applicant->getFullName(),
                    "1" => $applicant->getPosition(),
                    "2" => $applicant->getJobCity(),
                    "3" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($applicant->getDateApplied())),
                    "4" => '<a href="/recruitment/applicants/edit/' . $applicant->getId() .'" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>'
                ]);
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getOtaConnectionIssuesAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_OTA_CONNECTION_ISSUES)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $otaDistributionService \DDD\Service\OTADistribution */
        $otaDistributionService = $this->getServiceLocator()->get('service_ota_distribution');
        $dataSet = $otaDistributionService->getIssueConnections();

        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                if ($row['product'] == 'apartment') {
                    $url = "/apartment/{$row['identity_id']}/channel-connection";
                } else {
                    $url = "/concierge/edit/{$row['identity_id']}";
                }

                array_push($preparedData, [
                    "0" => $row['partner_name'],
                    "1" => $row['name'],
                    "2" => $row['city_name'],
                    "3" => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date_edited'])),
                    "4" => $row['reference'],
                    "5" => '<a href="' . $url . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>',
                ]);
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getApartmentsInRegistrationProcessAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_APARTMENTS_IN_REGISTRATION_PROCESS)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $inRegistrationProcessWidgetService \DDD\Service\UniversalDashboard\Widget\InRegistrationProcess */
        $inRegistrationProcessWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_in_registration_process');
        $dataSet = $inRegistrationProcessWidgetService->getApartmentsInRegistrationProcess();

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn = '
                    <a href="/apartment/' . $row['id'] . '/general" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';

                $preparedData[] = array(
                    $row['name'],
                    $row['city'],
                    $row['status'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['create_date'])),
                    $actionsColumn
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getCashPaymentsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_CASH_PAYMENTS)) {
            return $this->redirect()->toRoute('home');
        }

        /* @var $pendingTransactionWidgetService \DDD\Service\UniversalDashboard\Widget\PendingTransaction */
        $pendingTransactionWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_transaction');
        $dataSet = $pendingTransactionWidgetService->getPendingTransactions('cash');
        $preparedData = [];
        $statusList = Booking\BankTransaction::$transactionStatus;

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $strStatuses = '';
                $transactionId = $row['id'];
                $reservationId = $row['reservation_id'];
                foreach ($statusList as $key => $item) {
                    if ($key == Booking\BankTransaction::BANK_TRANSACTION_STATUS_PENDING) {
                        continue;
                    }
                    $strStatuses .= "<li><a href='javascript:void(0)' onClick='changePendingTransaction(this, {$key}, {$transactionId}, {$reservationId})'>{$item}</a></li>";
                }

                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $row['res_number'] . '#financial_details" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown">
                            Select Status <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu"> ' . $strStatuses . '</ul>
					</div>';

                $preparedData[] = [
                    "0" => $row['res_number'],
                    "1" => $row['acc_name'],
                    "2" => $row['guest'],
                    "3" => $row['user'],
                    "4" => $row['acc_amount'] . ' ' . $row['symbol'],
                    "5" => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date'])),
                    "6" => $actionsColumn,
                ];
            }
        }

        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getPendingTransactionsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_TRANSACTION_PENDING)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $pendingTransactionWidgetService \DDD\Service\UniversalDashboard\Widget\PendingTransaction */
        $pendingTransactionWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_transaction');
        $dataSet = $pendingTransactionWidgetService->getPendingTransactions();
        $preparedData = [];
        $statusList = Booking\BankTransaction::$transactionStatus;
        $chargebackList = [
            Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE => Objects::getChargeType()[Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE],
            Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD => Objects::getChargeType()[Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD],
            Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER => Objects::getChargeType()[Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER],
        ];
        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $strStatuses = $strType = $actionsColumn = $disabled = '';
                $transactionId = $row['id'];
                $reservationId = $row['reservation_id'];
                $actionsColumn = '<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $row['res_number'] . '#financial_details" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';

                if ($row['status'] == Booking\BankTransaction::BANK_TRANSACTION_STATUS_PENDING
                    && $row['type'] == Booking\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER) {
                    $disabled = 'disabled';

                    foreach ($chargebackList as $key => $item) {
                        $strType .= '<li><a href="javascript:void(0)"  onClick="changePendingTransactionType(this, ' . $key . ')">' . $item . '</a></li>';
                    }

                    $actionsColumn .= '
                        <div class="btn-group margin-right-10 chb-transaction-type">
                            <button type="button" class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="exist-transaction-type-button">Change Type</span> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">' . $strType . '</ul>
                        </div>';
                }

                foreach ($statusList as $key => $item) {
                    if ($key == Booking\BankTransaction::BANK_TRANSACTION_STATUS_PENDING) {
                        continue;
                    }
                    $strStatuses .= "<li><a href='javascript:void(0)' onClick='changePendingTransaction(this, {$key}, {$transactionId}, {$reservationId})'>{$item}</a></li>";
                }

                $actionsColumn .= '
                    <div class="btn-group">
                        <button type="button" class="btn btn-xs btn-success dropdown-toggle exist-transaction-status-button" data-toggle="dropdown" ' . $disabled . '>
                            Select Status <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu"> ' . $strStatuses . '</ul>
					</div>';

                $preparedData[] = [
                    "0" => $row['res_number'],
                    "1" => $row['acc_name'],
                    "2" => $row['guest'],
                    "3" => Objects::getChargeTypeById($row['type']),
                    "4" => $row['acc_amount'] . ' ' . $row['symbol'],
                    "5" => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date'])),
                    "6" => $actionsColumn,
                ];
            }

        }

        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getFrontierChargeReviewedAction()
    {
        /**
         * @var \DDD\Service\UniversalDashboard\Widget\PendingTransaction $pendingTransactionWidgetService
         */
        $pendingTransactionWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_transaction');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_FRONTIER_CHARGE_REVIEWED, Roles::ROLE_BOOKING_TRANSACTION_VERIFIER)) {
            return $this->redirect()->toRoute('home');
        };

        $dataSet = $pendingTransactionWidgetService->getPendingTransactions('frontier');
        $preparedData = [];
        $statusList = Booking\BankTransaction::$transactionStatus;

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $strStatuses = '';
                $transactionId = $row['id'];
                $reservationId = $row['reservation_id'];
                foreach ($statusList as $key => $item) {
                    if ($key == Booking\BankTransaction::BANK_TRANSACTION_STATUS_PENDING) {
                        continue;
                    }
                    $strStatuses .= "<li><a href='javascript:void(0)' onClick='changePendingTransaction(this, {$key}, {$transactionId}, {$reservationId})'>{$item}</a></li>";
                }

                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $row['res_number'] . '#financial_details" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown">
                            Select Status <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu"> ' . $strStatuses . '</ul>
					</div>';

                $preparedData[] = [
                    "0" => $row['res_number'],
                    "1" => $row['acc_name'],
                    "2" => $row['guest'],
                    "3" => Objects::getChargeTypeById($row['type']),
                    "4" => $row['acc_amount'] . ' ' . $row['symbol'],
                    "5" => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date'])),
                    "6" => $actionsColumn,
                ];
            }

        }

        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getCollectFromPartnerReservationsAction()
    {
        /**
         * @var \DDD\Service\UniversalDashboard\Widget\CollectFromPartner $collectFromPartnerWidgetService
         */
        $collectFromPartnerWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_collect_from_partner');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_COLLECT_FROM_PARTNER)) {
            return $this->redirect()->toRoute('home');
        };

        $dataSet = $collectFromPartnerWidgetService->getCollectFromPartnerReservations();

        if ($dataSet && count($dataSet)) {
            $preparedData = [];

            foreach ($dataSet as $row) {
                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                array_push($preparedData, [
                    "0" => $row->getReservationNumber(),
                    "1" => Booking::$bookingStatuses[$row->getStatus()],
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getBookingDate())),
                    "3" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDepartureDate())),
                    "4" => $row->getApartmentName(),
                    "5" => $row->getPartnerName(),
                    "6" => $row->getPartnerBalance() . ' ' . $row->getSymbol(),
                    "7" => $actionsColumn,
                ]);
            }

            return new JsonModel([
                "aaData" => $preparedData,
            ]);
        }

        return new JsonModel([
            "aaData" => [],
        ]);
    }

    public function getCollectFromCustomerReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_COLLECT_FROM_CUSTOMER)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $collectFromCustomerWidgetService \DDD\Service\UniversalDashboard\Widget\CollectFromCustomer */
        $collectFromCustomerWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_collect_from_customer');

        $dataSet = $collectFromCustomerWidgetService ->getCollectFromCustomerReservations();

        /* @var $row \DDD\Domain\UniversalDashboard\Widget\CollectFromCustomer */
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn =
                    '<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                $pendingTransactions = $row->getPendingTransactionsAmount()
                    ? '<span class="text-danger glyphicon glyphicon-warning-sign"
                    data-content="This reservation has pending transactions with total amount of ' .
                    $row->getPendingTransactionsAmount() . ' ' . $row->getSymbol() . '"
                    data-container="body"
                    data-toggle="popover"
                    title="Pending Transactions"
                    data-placement="top",
                    data-animation="true"
                ></span>&nbsp;' : '';

                $tableRow = [
                    "0" => $row->getReservationNumber(),
                    "1" => Booking::$bookingStatuses[$row->getStatus()],
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getArrivalDate())),
                    "3" => $row->getApartmentName(),
                    "4" => $row->getGuestFullName(),
                    "5" => ($row->isCreditCardValid()) ?
                            '<div class="text-center">' .
                            '<span class="label label-success">Valid</span></div>' :
                            '<div class="text-center">' .
                            '<span class="label label-danger">Invalid</span></div>'
                ];

                switch ($row->isWaitingForCCDetails()) {
                    case '1':
                        $tableRow["6"] =
                            '<div class="text-center">' .
                            '<span class="label label-danger">yes</span></div>';
                        break;
                    case '0':
                    case '2':
                        $tableRow["6"] =
                            '<div class="text-center">' .
                            '<span class="label label-info">no</span></div>';
                        break;
                    default:
                        $tableRow["6"] =
                            '<div class="text-center"><span class="label ' .
                            'label-default">INVALID VALUE</span></div>';
                }

                $tableRow["7"] = $pendingTransactions . $row->getGuestBalance() . ' ' . $row->getSymbol();
                $tableRow["8"] = $row->getLastAgentFullName();
                $tableRow["9"] = $actionsColumn;

                array_push($preparedData, $tableRow);
            }
            return new JsonModel([
                "aaData" => $preparedData,
            ]);
        }

        return new JsonModel([
            "aaData" => [],
        ]);
    }

    public function getPayToCustomerReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_PAY_TO_CUSTOMER)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $payToCustomerWidgetService \DDD\Service\UniversalDashboard\Widget\PayToCustomer */
        $payToCustomerWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pay_to_customer');

        $dataSet = $payToCustomerWidgetService ->getPayToCustomerReservations();

        /* @var $row \DDD\Domain\UniversalDashboard\Widget\CollectFromCustomer */
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn =
                    '<a href="http://' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                $tableRow = [
                    "0" => $row->getReservationNumber(),
                    "1" => Booking::$bookingStatuses[$row->getStatus()],
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getArrivalDate())),
                    "3" => $row->getApartmentName(),
                    "4" => $row->getGuestFullName(),
                    "5" => ($row->isCreditCardValid()) ?
                            '<div class="text-center">' .
                            '<span class="label label-success">Valid</span></div>' :
                            '<div class="text-center">' .
                            '<span class="label label-danger">Invalid</span></div>'
                ];

                switch ($row->isWaitingForCCDetails()) {
                    case '1':
                        $tableRow["6"] =
                            '<div class="text-center">' .
                            '<span class="label label-danger">yes</span></div>';
                        break;
                    case '0':
                    case '2':
                        $tableRow["6"] =
                            '<div class="text-center">' .
                            '<span class="label label-success">no</span></div>';
                        break;
                    default:
                        $tableRow["6"] =
                            '<div class="text-center"><span class="label ' .
                            'label-default">INVALID VALUE</span></div>';
                }
                $tableRow["7"] = $row->getGuestBalance() . ' ' . $row->getSymbol();
                $tableRow["8"] = $row->getLastAgentFullName();
                $tableRow["9"] = $actionsColumn;

                array_push($preparedData, $tableRow);
            }
            return new JsonModel([
                "aaData" => $preparedData,
            ]);
        }

        return new JsonModel([
            "aaData" => [],
        ]);
    }

    public function getExpensesToApproveAction()
    {
        /**
         * @var Main $homeService
         */
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $dataSet = $homeService->getAwaitingApprovalExpenses();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn = '
					<a href="/finance/purchase-order/ticket/' . $row['id'] .
                    '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';
                $validity = '';
                if (!is_null($row['expected_completion_date_start']) && !is_null($row['expected_completion_date_end'])) {
                    $validity = date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['expected_completion_date_start'])) . ' - ' .
                        date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['expected_completion_date_end']));
                }
                array_push($preparedData, [
                    $row['creator'],
                    $row['manager'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['date_created'])),
                    $validity,
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['purpose'] . '"></span></div>',
                    $row['limit'] . ' ' . $row['symbol'],
                    $actionsColumn,
                ]);
            }
        }

        return new JsonModel([
            "aaData" => $preparedData,
        ]);
    }

    public function getAwaitingTransferAction()
    {
        /**
         * @var Main $homeService
         */
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $dataSet = $homeService->getAwaitingTransfer();
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $viewUrl = $this->url()->fromRoute('finance/item/edit', ['id' => $row['id']]);
                $completeUrl = $this->url()->fromRoute('finance/item/complete', ['id' => $row['id']]);
                $supplier = '<span class="label label-primary">' . Account::getAccountNameById($row['account_type']) . '</span> ' . $row['account_name'];
                $actionsColumn = '<a href="' . $viewUrl . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';
                $actionsColumn .= ' <a href="' . $completeUrl . '" class="btn btn-xs btn-success complete-item">Complete</a>';

                array_push($preparedData, [
                    $row['creator'],
                    $row['manager'],
                    $supplier,
                    $row['date_created'],
                    $row['amount'],
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['comment'] . '"></span></div>',
                    $actionsColumn,
                ]);
            }
        }

        return new JsonModel([
            "aaData" => $preparedData,
        ]);
    }

    public function getNotApprovedItemsAction()
    {
        /**
         * @var Main $homeService
         */
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $dataSet = $homeService->getNotApprovedItems($auth->getIdentity()->id);
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $poUrl = $this->url()->fromRoute('finance/item/edit', ['id' => $row['id']]);
                $actionsColumn = "<a href='{$poUrl}' class='btn btn-xs btn-primary' target='_blank' data-html-content='View'></a>";
                $statusClass = $row['status'] == Helper::ITEM_STATUS_PENDING ? 'warning' : 'danger';

                array_push($preparedData, [
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date_created'])),
                    $row['amount'],
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['comment'] . '"></span></div>',
                    Helper::getTypeById($row['type']),
                    '<span class="label label-' . $statusClass . '">' . Helper::getItemStatusById($row['status']) . '</span>',
                    $actionsColumn,
                ]);
            }
        }

        return new JsonModel([
            'aaData' => $preparedData,
        ]);
    }

    public function getUnpaidInvoicesAction()
    {
        /**
         * @var \DDD\Service\Finance\Expense\ExpenseTicket $expenseTicketService
         */
        $expenseTicketService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasRolePoAndTransferManagerGlobal = $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL);
        $dataSet = $expenseTicketService->getUnpaidInvoices();
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $poUrl = $this->url()->fromRoute('finance/item/edit', ['id' => $row['id']]);
                $actionsColumn = "<a href='{$poUrl}' class='btn btn-xs btn-primary' target='_blank' data-html-content='View'></a>";
                $statusClass = $row['status'] == Helper::ITEM_STATUS_PENDING ? 'warning' : 'danger';
                $resolveButton =  ($hasRolePoAndTransferManagerGlobal) ?'<a href="javascript:void(0)"' .
                    ' onClick="resolveUnpaidItem(this, ' . $row['id'] . ', event)" class="btn btn-xs btn-success text-center margin-left-5">Resolve</a>' : '';
                $actionsColumn .= $resolveButton;
                array_push($preparedData, [
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date_created'])),
                    $row['amount'],
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['comment'] . '"></span></div>',
                    Helper::getTypeById($row['type']),
                    '<span class="label label-' . $statusClass . '">' . Helper::getItemStatusById($row['status']) . '</span>',
                    $actionsColumn,
                ]);
            }
        }

        return new JsonModel([
            'aaData' => $preparedData,
        ]);
    }

    public function getPendingPOItemsAction()
    {
        /**
         * @var Main $homeService
         */
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $dataSet = $homeService->getPendingPOItems($auth->getIdentity()->id);
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $poUrl = $this->url()->fromRoute('finance/item/edit', ['id' => $row['id']]);
                $actionsColumn = "<a href='{$poUrl}' class='btn btn-xs btn-primary' target='_blank' data-html-content='View'></a>";

                array_push($preparedData, [
                    $row['creator'],
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date_created'])),
                    $row['amount'],
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['comment'] . '"></span></div>',
                    Helper::getTypeById($row['type']),
                    $actionsColumn,
                ]);
            }
        }

        return new JsonModel([
            'aaData' => $preparedData,
        ]);
    }

    public function getMyActualPOAction()
    {
        /**
         * @var Main $homeService
         */
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $dataSet = $homeService->getMyActualPO($auth->getIdentity()->id);
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $poUrl = $this->url()->fromRoute('finance/purchase-order/edit', ['id' => $row['id']]);
                $actionsColumn = "<a href='{$poUrl}' class='btn btn-xs btn-primary' target='_blank' data-html-content='View'></a>";
                $validity = '';
                if (!is_null($row['expected_completion_date_start']) && !is_null($row['expected_completion_date_end'])) {
                    $validity = date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['expected_completion_date_start'])) . ' - ' .
                        date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['expected_completion_date_end']));
                }
                array_push($preparedData, [
                    $validity,
                    $row['title'],
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['purpose'] . '"></span></div>',
                    $row['ticket_balance'],
                    $row['ticket_limit'],
                    $actionsColumn
                ]);
            }
        }

        return new JsonModel([
            'aaData' => $preparedData,
        ]);
    }

    public function getReadyToBeSettledPOAction()
    {
        /**
         * @var Main $homeService
         */
        $homeService = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $dataSet = $homeService->getReadyToBeSettledPO();
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $poUrl = $this->url()->fromRoute('finance/purchase-order/edit', ['id' => $row['id']]);
                $actionsColumn = "<a href='{$poUrl}' class='btn btn-xs btn-primary' target='_blank' data-html-content='View'></a>";

                array_push($preparedData, [
                    $row['manager'],
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row['date_created'])),
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $row['purpose'] . '"></span></div>',
                    $row['ticket_balance'],
                    $actionsColumn,
                ]);
            }
        }

        return new JsonModel([
            'aaData' => $preparedData,
        ]);
    }

    public function getKiNotViewedReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_KI_NOT_VIEWED)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $kiNotViewedWidgetService \DDD\Service\UniversalDashboard\Widget\KINotViewed */
        $kiNotViewedWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_ki_not_viewed');
        $dataSet = $kiNotViewedWidgetService->getKINotViewedReservations();

        $preparedData = array();

        if ($dataSet && count($dataSet)) {

            foreach ($dataSet as $row) {
                /* @var $row \DDD\Domain\UniversalDashboard\Widget\KINotViewed */
                $actionsColumn = '<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $row->getReservationNumber() . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';

                $rowClass = '';
                if ($row->getArrivalDate() == date('Y-m-d')) {
                    $rowClass = 'info';
                }

                $preparedData[] = array(
                    "0" => $row->getReservationNumber(),
                    "1" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getArrivalDate())),
                    "2" => $row->getApartmentName(),
                    "3" => $row->getGuestFullName(). ($row->getGuestCountry() ? ' (' . $row->getGuestCountry() . ')' : ''),
                    "4" => $row->getLastAgentFullName(),
                    "5" => $actionsColumn,
                    "DT_RowClass" => $rowClass
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getLastMinuteBookingsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_LAST_MINUTE_BOOKINGS)) {
            return $this->redirect()->toRoute('home');
        };

        /**
         * @var $homeService \DDD\Service\UniversalDashboard
         * @var $dataSet CustomerService[]
         */
        $homeService    = $this->getServiceLocator()->get('service_universal_dashboard_main');
        $dataSet = $homeService->getLastMinuteReservation();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getResNumber() .
                    '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>' .
                    ' <a id="' . $row->getId() . '" href="javascript:void(0)" onClick="resolveLastMinuteReservations(this)"' .
                    ' class="btn btn-xs btn-success">Resolve</a>';

                $preparedData[] = array(
                    "0" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDateFrom())),
                    "1" => $row->getApartmentName(),
                    "2" => $row->getAccCityName(),
                    "3" => $row->getGuestFirstName() . ' ' . $row->getGuestLastName() .
                        ' <i class="glyphicon glyphicon-phone-alt"></i> ' . $row->getPhone(),
                    "4" => $row->getPAX(),
                    "5" => $row->getGuest_balance() . ' ' . $row->getSymbol(),
                    "6" => $actionsColumn
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getNoCollectionReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_NO_COLLECTION)) {
            return $this->redirect()->toRoute('home');
        };

        /** @var $row \DDD\Domain\UniversalDashboard\Widget\NoCollection */
        /* @var $noCollectionWidgetService \DDD\Service\UniversalDashboard\Widget\NoCollection */
        $noCollectionWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_no_collection');

        $dataSet = $noCollectionWidgetService->getNoCollectionReservations();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn =
                    '<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                $rowClass = '';

                if ($row->getArrivalDate() == date('Y-m-d')) {
                    $rowClass = 'warning';
                } elseif (  strtotime($row->getArrivalDate())
                    < strtotime(date('Y-m-d'))
                ) {
                    $rowClass = 'danger';
                }

                $preparedData[] = [
                    "0" => $row->getReservationNumber(),
                    "1" => Booking::$bookingStatuses[$row->getStatus()],
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getArrivalDate())),
                    "3" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDepartureDate())),
                    "4" => $row->getApartmentName(),
                    "5" => $row->getGuestFullName(),
                    "6" => ($row->isCreditCardValid()) ?
                            '<div class="text-center"><span class="label ' .
                            'label-success">Valid</span></div>' :
                            '<div class="text-center"><span class="label ' .
                            'label-danger">Invalid</span></div>',
                    "7" => $row->getGuestBalance() . ' ' . $row->getSymbol(),
                    "8" => $row->getLastAgentFullName(),
                    "9" => $actionsColumn,
                    "DT_RowClass" => $rowClass
                ];
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getPendingCancellationReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_PENDING_CANCELLATION)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $row \DDD\Domain\UniversalDashboard\Widget\PendingCancelation */
        /* @var $pendingCancelationWidgetService \DDD\Service\UniversalDashboard\Widget\PendingCancelation */
        $pendingCancelationWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pending_cancellation');
        $dataSet = $pendingCancelationWidgetService->getPendingCancelationReservations();

        $bookingStatusesDao = new Statuses($this->getServiceLocator(),'\ArrayObject');

        $bookingStatuses = $bookingStatusesDao->getAllList();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            $dontShow = [
                Booking::BOOKING_STATUS_BOOKED,
                Booking::BOOKING_STATUS_CANCELLED_PENDING,
                Booking::BOOKING_STATUS_CANCELLED_MOVED,
                Booking::BOOKING_STATUS_CANCELLED_TEST_BOOKING,
            ];

            foreach ($dataSet as $row) {
                $strStatuses = '';

                foreach ($bookingStatuses as $item) {
                    if (in_array($item['id'], $dontShow)) {
                        continue;
                    }

                    $strStatuses .=
                        "<li><a href='javascript:void(0)' " .
                        "onClick='applyCancellation(this)' data-booking-status=" .
                        "'{$item['id']}' data-booking-id=".$row->getId().">{$item['name']}</a></li>";
                }

                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getResNumber() .
                    '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>
                    <div class="btn-group" id="' . $row->getResNumber() .'">
						<button type="button" class="btn btn-xs btn-success ' .
                    'dropdown-toggle" data-toggle="dropdown">
                        Select Type <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
            ' . $strStatuses . '
						</ul>
					</div>
				';

                $preparedData[] = [
                    "0" => $row->getResNumber(),
                    "1" => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($row->getCancelationDate())),
                    "2" => $row->getAffName(),
                    "3" => $row->getPartnerRef(),
                    "4" => $row->getApartmentName(),
                    "5" => $row->getApartel(),
                    "6" => $row->getGuestBalance() . ' ' . $row->getSymbol(),
                    "7" => $actionsColumn,
                ];
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getPendingReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_AWAITING_PAYMENT_DETAILS)) {
            return $this->redirect()->toRoute('home');
        };

        /**
         * @var $row \DDD\Domain\UniversalDashboard\Widget\AwaitingPaymentDetails
         * @var $bookingService \DDD\Service\Booking
         */
        $bookingService = $this->getServiceLocator()->get('service_booking');
        $dataSet = $bookingService->getPendingReservations();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn =
                    '<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                $rowClass  = '';
                $datetime1 = date_create($row->getArrivalDate());
                $datetime2 = date_create('now');
                $interval  = date_diff($datetime1, $datetime2);

                $interval->format('%R%a days');

                if (   ($interval->format('%a') <= 5)
                    && ($interval->format('%R') == '+')
                ) {
                    $rowClass .= 'danger';
                }

                if ($rowClass != '') {
                    $rowClass .= ' text-bold';
                }

                $preparedData[] = [
                    "0" => $row->getReservationNumber(),
                    "1" => Booking::$bookingStatuses[$row->getStatus()],
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getArrivalDate())),
                    "3" => $row->getApartmentName(),
                    "4" => $row->getGuestFullName(),
                    "5" => $row->getGuestBalance() . ' ' . $row->getAccSymbol(),
                    "6" => $row->getLastAgentFullName(),
                    "7" => $actionsColumn,
                    "DT_RowClass" => $rowClass
                ];
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getPinnedReservationsAction()
    {
        /**
         * @var $pinnedResWidgetService \DDD\Service\UniversalDashboard\Widget\PinnedReservation
         */
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $pinnedResWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_pinned_reservation');

        $preparedData = array();
        $loggedInUserID = $auth->getIdentity()->id;
        $dataSet = $pinnedResWidgetService->getAllPinnedReservation($loggedInUserID);

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $bookingDao = new \DDD\Dao\Booking\Booking(
                    $this->getServiceLocator(), 'DDD\Domain\UniversalDashboard\Widget\PinnedReservation'
                );
                /** @var \DDD\Domain\UniversalDashboard\Widget\PinnedReservation $resInfo */
                $resInfo = $bookingDao
                    ->searchReservationByResNum($row->getResNum());

                $actionsColumn =
                    "<a href='javascript:void(0)' class='btn " .
                    "btn-xs btn-success' onclick='unpin(\"" .
                    $row->getResNum() . "\", " . $loggedInUserID .")' id=" .
                    $row->getResNum() . ">Unpin</a>
					<a href='//". DomainConstants::BO_DOMAIN_NAME .
                    "/booking/edit/" . $row->getResNum() .
                    "' class='btn btn-xs btn-primary' target='_blank' data-html-content='View'></a>";

                $preparedData[] = [
                    "0" => $resInfo->getResNum(),
                    "1" => $resInfo->getGuestFirstName() . ' ' . $resInfo->getGuestLastName(),
                    "2" => $resInfo->getApartmentName(),
                    "3" => $actionsColumn
                ];
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getReviewsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_NEW_REVIEWS)) {
            return $this->redirect()->toRoute('home');
        };

        /*
         * @var $reviewService \DDD\Service\Review
         */
        $reviewService  = $this->getServiceLocator()->get('service_review');
        $apartmentReview = $this->getServiceLocator()->get('service_apartment_review');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $dataSet = $reviewService->getPendingReviews();
        $isBookingManager = $auth->hasRole(Roles::ROLE_BOOKING_MANAGEMENT);
        $preparedData = array();
        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                /**
                 * @var ReviewBase $row
                 */
                $reviewID           = $row->getId();
                $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
                $reviewScore    = $apartmentGeneralDao->getReviewScore($row->getApartmentId())['score'];
                $productReviewsLink = '<div><a href="/apartment/' . $row->getApartmentId() . '/review" target="_blank">' . $row->getApartmentName() . '</a><div>';
                $bookingTicket      = '<div><a href="/booking/edit/' . $row->getResNumber() . '" target="_blank">' . $row->getResNumber() . '</a></div>';

                $actionsColumn = '
					<div id="review_app_' . $reviewID . '">
						<a id="review_approve_3_' . $reviewID . '" onclick="reviewStatus(' . $reviewID . ', ' . Review::REVIEW_STATUS_APPROVED . ', ' . $row->getApartmentId() . ')" href="javascript:void(0)" class="btn btn-xs btn-success">Approve</a>
						<a id="review_reject_2_' . $reviewID . '" onclick="reviewStatus(' . $reviewID . ', ' . Review::REVIEW_STATUS_REJECTED . ', ' . $row->getApartmentId() . ')" href="javascript:void(0)" class="btn btn-xs btn-danger">Reject</a>
					</div>
				';

                $preparedValues = array();
                if ($isBookingManager) {
                    $productReviewsLink .= $bookingTicket;
                }
                $preparedValues[] = $productReviewsLink;
                $preparedValues[] = $row->getScore();
                $preparedValues[] = $reviewScore;
                $preparedValues[] = $row->getLiked();
                $preparedValues[] = $row->getDislike();
                $preparedValues[] = $apartmentReview->getReviewListByReviewId($reviewID, $row->getApartmentId());
                $preparedValues[] = $actionsColumn;

                $preparedData[] = $preparedValues;
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    /**
     * Get Reservation issues
     * Show issue if reservation date_from less than 9 days from today and type is "Email is Missing"
     *
     * @return \Zend\Http\Response|JsonModel
     */
    public function getReservationIssuesAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_RESERVATION_ISSUES)) {
            return $this->redirect()->toRoute('home');
        };

        /**
         * @var $reservationIssuesService \DDD\Service\Booking\ReservationIssues
         */

        $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');
        $dataSet                  = $reservationIssuesService->getAllIssuesAndLessThan9DayFromTodayOrbitzAgoda(true);
        $preparedData             = [];
        $occupancyIssuesData      = [];

        if ($dataSet && count($dataSet)) {
            $i = 0;
            foreach ($dataSet as $row) {
                /* @var \DDD\Domain\Booking\ReservationIssues $row*/
                $preparedData[$i] = [
                    "0" => $row->getReservationNumber(),
                    "1" => $row->getPartnerRef(),
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDateOfDetection())),
                    "3" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDateFrom())),
                    "4" => $row->getPartnerName(),
                    "5" => $row->getTitle(),
                    "6" => '<div style="white-space: nowrap;">' .
                                '<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $row->getReservationNumber() . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a> '.
                            '</div>'
                ];

                if ($row->getIssueTypeId() == Booking\ReservationIssues::ISSUE_EMAIL_IS_MISSING && $row->getPartnerId() == Partners::PARTNER_ORBITZ) {
                    $preparedData[$i]["5"] = 'Check the email from Orbitz for PDF';
                }

                if ($row->getIssueTypeId() == Booking\ReservationIssues::ISSUE_EMAIL_IS_MISSING && $row->getPartnerId() == Partners::PARTNER_AGODA) {
                    $preparedData[$i]["5"] = 'Check the confirmation email from Agoda';
                }
                $i++;
            }
        }

        $otherDataSet = $reservationIssuesService->getChangedOccupancyReservation();

        if (count($otherDataSet)) {
            foreach ($otherDataSet as $row) {
                /* @var \DDD\Domain\Booking\ReservationIssues $row*/
                $preparedData[$i] = [
                    "0" => $row->getReservationNumber(),
                    "1" => $row->getPartnerRef(),
                    "2" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDateOfDetection())),
                    "3" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDateFrom())),
                    "4" => $row->getPartnerName(),
                    "5" => $row->getTitle(),
                    "6" => '<div style="white-space: nowrap;">' .
                                '<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $row->getReservationNumber() . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a> '.
                            '</div>'
                ];

                $i++;
            }
        }

        return new JsonModel(["aaData" => $preparedData]);
    }

    public function getSuspendedApartmentsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_SUSPENDED_APARTMENTS)) {
            return $this->redirect()->toRoute('home');
        };

        /* @var $suspendedApartmentsWidgetService \DDD\Service\UniversalDashboard\Widget\SuspendedApartments */
        $suspendedApartmentsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_suspended_apartments');
        $dataSet = $suspendedApartmentsWidgetService->getSuspendedApartments();
        $preparedData = array();

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                /* @var $row \DDD\Domain\UniversalDashboard\Widget\SuspendedApartments */
                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME . '/apartment/' . $row->getApartmentId() . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>
				';

                $preparedData[] = array(
                    "0" => $row->getApartmentName(),
                    "1" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDateCreated())),
                    "2" => $row->getCountry(),
                    "3" => $row->getCity(),
                    "4" => $row->getAddress(),
                    "5" => $actionsColumn
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getTasksAction()
    {
        /**
         * @todo: Split this method to 5 separate methods on one per every type: "doing', "verifying", "created", "team"
         */

        /**
         * @var $taskService \DDD\Service\Task
         * @var $dataSet \DDD\Domain\Task\Task[]
         */
        $taskService    = $this->getServiceLocator()->get('service_task');
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $type = $this->params()->fromQuery('type', 'doing');
        $loggedInUserID = $auth->getIdentity()->id;

        $dataSet = $taskService->getUDList($loggedInUserID, $type);
        $preparedData = [];

        if ($dataSet->count()) {
            foreach ($dataSet as $row) {
                $rowClass = $location = '';
                if (strtotime($row->getEndDate()) <= strtotime(date('Y-m-j 23:59')) && $row->getTask_status() < Task::STATUS_DONE) {
                    $rowClass = 'danger';
                }

                if ($row->getProperty_name()) {
                    $location = $row->getProperty_name() . ' (' . $row->getUnit_number() . ')';
                } elseif ($row->getBuildingName()) {
                    $location = $row->getBuildingName();
                }

                $actions = '<a href="/task/edit/' . $row->getId() . '" class="btn btn-xs btn-primary hidden-xs" target="_blank" data-html-content="View"></a>';
                $actions .= '<a href="/task/edit/' . $row->getId() . '" class="btn btn-xs btn-primary visible-xs-block" target="_blank"><span class="glyphicon glyphicon-chevron-right"></span></a>';

                if ($type == 'verifying') {
                    $actions .= ' <a href="/task/edit/'.$row->getId().'" class="btn btn-xs btn-success btn-task-verify hidden-xs" data-task-id="' . $row->getId() . '">Verify</a>';
                }

                $statusTitle = Task::getTaskStatus()[$row->getTask_status()];
                $statusLabelClass = Task::getTaskStatusLabelClass($row->getTask_status());
                $statusTitleFirstCharacter = substr($statusTitle,0,1);

                $statusHtml = '<label class="task-label label ' . $statusLabelClass . '" title = "' . $statusTitle . '">'
                              . $statusTitleFirstCharacter . '</label>';
                $preparedData[] = [
                    "0" => Task::getTaskPriorityLabeled()[$row->getPriority()],
                    "1" => $statusHtml,
                    "2" => $row->getCreation_date() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getCreation_date())) : '',
                    "3" => (strlen($row->getTitle()) > 20) ? substr($row->getTitle(), 0, 20) .'...' : $row->getTitle(),
                    "4" => $row->getResponsibleName(),
                    "5" => $location,
                    "6" => $row->getTaskType(),
                    "7" => $row->getEndDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getEndDate())) : '',
                    "8" => $row->getTeamName(),
                    "9" => $actions,
                    "DT_RowClass" => $rowClass,
                ];
            }

            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getToBeSettledReservationsAction()
    {
        /**
         * @var \DDD\Service\UniversalDashboard\Widget\ToBeSettled $toBeSettledWidgetService
         * @var \DDD\Domain\UniversalDashboard\Widget\ToBeSettled $row
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_MARK_AS_SETTLED)) {
            return $this->redirect()->toRoute('home');
        };

        $toBeSettledWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_to_be_settled');
        $dataSet = $toBeSettledWidgetService->getToBeSettledReservations();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a> <a id="' .
                    $row->getReservationNumber() . '" href="javascript:void(0)" onClick="markAsSettled(this)" ' .
                    'class="btn btn-xs btn-success">Settle</a>';

                $rowClass = '';
                if ($row->isNoCollection()) {
                    $rowClass = 'damage';
                }

                array_push($preparedData, [
                    '0' => $row->getReservationNumber(),
                    '1' => Booking::$bookingStatuses[$row->getStatus()],
                    '2' => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getDepartureDate())),
                    '3' => $row->getApartmentName(),
                    '4' => $row->getGuestFullName(),
                    '5' => $row->getGuestBalance() . ' ' . $row->getSymbol(),
                    '6' => $row->getPartnerBalance() . ' ' . $row->getSymbol(),
                    '7' => $actionsColumn,
                    'DT_RowClass' => $rowClass,
                ]);
            }

            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getVacationRequestsAction()
    {
        /**
         * @var TimeOffRequestsService $timeOffRequestsWidgetService
         * @var BackofficeAuthenticationService $authenticationService
         */
        $timeOffRequestsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_time_off_requests');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $vacationDao = $this->getServiceLocator()->get('dao_user_vacation_days');

        $loggedInUserID = $authenticationService->getIdentity()->id;
        $dataSet = $timeOffRequestsWidgetService->getTimeOffRequests($loggedInUserID);
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $remainColumn  = $row->getVacation_days();
                $purposeColumn = '<span class="glyphicon glyphicon-comment" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="' . $row->getComment() . '"></span>';

                $actionsColumn = '
					<a href="javascript:void(0)" class="btn btn-xs btn-success" onclick="vacationRequest(' . $row->getId() . ',1)" id="vac_accept_' . $row->getId() . '">Approve</a>
					<a href="javascript:void(0)" class="btn btn-xs btn-danger" onclick="vacationRequest(' . $row->getId() . ',3)" id="vac_reject_' . $row->getId() . '">Reject</a>
				';

                if ($row->getType() == VacationService::VACATION_TYPE_SICK) {

                    $takenSickDays = 0;
                    $sickDays      = $vacationDao->getSickDays($row->getUser_id());
                    if ($sickDays) {
                        foreach ($sickDays as $sickDay) {
                            $takenSickDays += abs($sickDay['total_number']);
                        }
                    }

                    if ($row->getSickDays() != -1) {
                        $remainColumn = $row->getSickDays() - $takenSickDays;
                    } else {
                        $remainColumn = '';
                    }

                }

                array_push($preparedData, [
                    '<a href="/profile/index/' . $row->getUser_id() . '" target="_blank">' . $row->getFirstName() . ' ' . $row->getLastName() . '</a>',
                    (isset(Objects::getVacationType()[$row->getType()]) ? Objects::getVacationType()[$row->getType()] : 'None'),
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getFrom())) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getTo())),
                    $row->getTotal_number(),
                    round($remainColumn, 2),
                    $purposeColumn,
                    $actionsColumn,
                ]);
            }

            return new JsonModel([
                "aaData" => $preparedData,
            ]);
        } else {
            return new JsonModel([
                "aaData" => [],
            ]);
        }
    }

    public function getValidateCcReservationsAction()
    {
        /**
         * @var \DDD\Domain\UniversalDashboard\Widget\ValidateCC $row
         * @var \DDD\Service\UniversalDashboard\Widget\ValidateCC $validateCCWidgetService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $validateCCWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_validate_cc');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_VALIDATE_CC)) {
            return $this->redirect()->toRoute('home');
        };

        $dataSet = $validateCCWidgetService->getValidateCCReservations();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $rowClass = '';
                $actionsColumn =
                    '<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row->getReservationNumber() .
                    '#financial_details" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                if ($row->getArrivalDate() == date('Y-m-d')) {
                    $rowClass = 'warning';
                } elseif (strtotime($row->getArrivalDate()) < strtotime(date('Y-m-d'))) {
                    $rowClass = 'danger';
                }

                array_push($preparedData, [
                    '0' => $row->getReservationNumber(),
                    '1' => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getBookingDate())),
                    '2' => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getArrivalDate())),
                    '3' => $row->getApartmentName(),
                    '4' => $row->getGuestFullName(),
                    '5' => $row->getGuestBalance() . ' ' . $row->getSymbol(),
                    '6' => $row->getLastAgentFullName(),
                    '7' => $actionsColumn,
                    'DT_RowClass' => $rowClass,
                ]);
            }
            return new JsonModel([
                'aaData' => $preparedData
            ]);
        } else {
            return new JsonModel([
                'aaData' => []
            ]);
        }
    }

    public function getUnresolvedCommentsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $resolveCommentsWidgetService = $this->getServiceLocator()->get('service_universal_dashboard_widget_resolve_comments');

        $dataSet = $resolveCommentsWidgetService->getUnresolvedCommentsForUser($auth->getIdentity()->id);

        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            foreach ($dataSet as $row) {
                $actionsColumn =
                    '<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $row['res_number'] .
                    '?highlightLog=' . $row['action_log_id'] . '#history" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>  ' .
                    '<a href="javascript:void(0)" id="unresolved-comment-' . $row['id'] . '" class="btn btn-xs btn-success" onClick="resolveComment(this)">Resolve</a>';

                $preparedData[] = [
                    "0" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['date'])),
                    "1" => $row['res_number'],
                    "2" => $row['view_message'],
                    "3" => $actionsColumn
                ];
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    /**
     * @return JsonModel
     *
     * @author Tigran Petrosyan
     */
    public function getNotChargedApartelReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_CHARGE_APARTEL_RESERVATIONS)) {
            return $this->redirect()->toRoute('home');
        };

        /**
         * @var NotChargedApartelReservationsService $notChargedApartelReservationsService
         */
        $notChargedApartelReservationsService = $this->getServiceLocator()->get('service_universal_dashboard_widget_not_charged_apartel_reservations');
        $notChargedApartelReservations = $notChargedApartelReservationsService->getNotChargedApartelReservations();
        $preparedData = [];

        if ($notChargedApartelReservations->count()) {
            foreach ($notChargedApartelReservations as $reservation) {
                $actionsColumn  =
                    '<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $reservation->getReservationNumber().
                    '" class="btn btn-xs btn-primary" ' .
                    'target="_blank" data-html-content="View"></a>';

                $preparedData[] = [
                    $reservation->getReservationNumber(),
                    $reservation->getApartelName(),
                    $reservation->getApartmentName(),
                    $reservation->getGuestFullName(),
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getCheckInDate())),
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getCheckOutDate())),
                    $actionsColumn
                ];
            }

            return new JsonModel(['aaData' => $preparedData]);
        } else {
            return new JsonModel(['aaData' => []]);
        }
    }

    /**
     * @return \Zend\Http\Response|JsonModel
     *
     * @author Tigran Petrosyan
     */
    public function getOverbookingReservationsAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_OVERBOOKING_RESERVATIONS)) {
            return $this->redirect()->toRoute('home');
        };

        /**
         * @var OverbookingReservations $overbookingReservationsService
         */
        $overbookingReservationsService = $this->getServiceLocator()->get('service_universal_dashboard_widget_overbooking_reservations');
        $reservations = $overbookingReservationsService->getOverbookingReservations();
        $preparedData = [];

        if ($reservations && count($reservations)) {
            foreach ($reservations as $reservation) {
                $actionsColumn = '
					<a href="//' . DomainConstants::BO_DOMAIN_NAME .
                    '/booking/edit/' . $reservation->getReservationNumber() .
                    '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';

                $preparedData[] = array(
                    "0" => $reservation->getReservationNumber(),
                    "1" => $reservation->getCityName(),
                    "2" => $reservation->getApartmentName(),
                    "3" => $reservation->getGuestFullName(),
                    "4" => date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getArrivalDate())),
                    "5" => $actionsColumn
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    /**
     * @return JsonModel
     *
     * @author Tigran Petrosyan
     */
    public function getUpcomingEvaluationsAction()
    {
        /** @var UpcomingEvaluationsService $upcomingEvaluationsService */
        $upcomingEvaluationsService = $this->getServiceLocator()->get('service_universal_dashboard_widget_upcoming_evaluations');
        $upcomingEvaluations = $upcomingEvaluationsService->getUpcomingEvaluations();
        $preparedData = [];

        if ($upcomingEvaluations && count($upcomingEvaluations)) {
            foreach ($upcomingEvaluations as $evaluation) {
                $evaluateActionURL = $this->url()->fromRoute(
                    'evaluation/edit',
                    [
                        'user_id' => $evaluation->getEmployeeId(),
                        'evaluation_id' => $evaluation->getNextPlannedEvaluationId()
                    ]
                );

//                $evaluateActionURL = '//' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $evaluation->getNextPlannedEvaluationId();

                $actionsColumn = '
					<a href="' . $evaluateActionURL . '" class="btn btn-xs btn-primary" target="_blank">Evaluate</a>' .
                    ' <a id="' . $evaluation->getNextPlannedEvaluationId() . '" href="javascript:void(0)"' .
                    ' onClick="cancelPlannedEvaluation(this)" class="btn btn-xs btn-danger">Cancel</a>';

                $preparedData[] = array(
                    "0" => $evaluation->getCreatorFullName(),
                    "1" => $evaluation->getEmployeeFullName(),
                    "2" => $evaluation->getDatePlanned() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($evaluation->getDatePlanned())) : '',
                    "3" => $actionsColumn
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }

    }

    /**
     * @return JsonModel
     *
     * @author Tigran Petrosyan
     */
    public function getEvaluationLessEmployeesAction()
    {
        /**
         * @var EvaluationLessEmployees $evaluationLessEmployeesService
         */
        $evaluationLessEmployeesService = $this->getServiceLocator()->get('service_universal_dashboard_widget_evaluation_less_employees');
        $evaluationLessEmployees = $evaluationLessEmployeesService->getEvaluationLessEmployees();
        $preparedData = [];

        if ($evaluationLessEmployees && count($evaluationLessEmployees)) {
            foreach ($evaluationLessEmployees as $evaluationLessEmployee) {

                $actionsColumn = '<a href="/user/edit/' . $evaluationLessEmployee->getId() . '#evaluations" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';

                $preparedData[] = array(
                    "0" => $evaluationLessEmployee->getFullName(),
                    "1" => $actionsColumn
                );
            }
            return new JsonModel([
                "aaData" => $preparedData
            ]);
        } else {
            return new JsonModel([
                "aaData" => []
            ]);
        }
    }

    public function getPendingTransfersAction()
    {
        /**
         * @var Transfer $transferService
         */
        $transferService = $this->getServiceLocator()->get('service_finance_transfer');
        $pendingTransfers = $transferService->getPendingTransfers();
        $pendingTransferList = [];

        if ($pendingTransfers->count()) {
            foreach ($pendingTransfers as $pendingTransfer) {
                $url = $this->url()->fromRoute('finance/transfer') . '?id=' . $pendingTransfer['id'];
                $cancelUrl = $this->url()->fromRoute('finance/transfer/cancel', ['id' => $pendingTransfer['id']]);
                $continue = "<a href='{$url}' class='btn btn-xs btn-primary' target='_blank'>Continue</a>";
                $cancel = "<a href='{$cancelUrl}' class='btn btn-xs btn-danger cancel-pending-transfer'>Cancel</a>";
                $action = $continue . ' ' . $cancel;

                array_push($pendingTransferList, [
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($pendingTransfer['date_created'])),
                    $pendingTransfer['account_from'],
                    $pendingTransfer['account_to'],
                    $pendingTransfer['description'],
                    $action
                ]);
            }
        }

        return new JsonModel([
            "aaData" => $pendingTransferList,
        ]);
    }

    public function getPendingBudgetsAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $budgetService
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_PENDING_BUDGET)) {
            return $this->redirect()->toRoute('home');
        };

        $budgetService = $this->getServiceLocator()->get('service_finance_budget');
        $dataSet = $budgetService->getPendingBudgets();
        $preparedData = [];
        $actionsColumn = '';
        if ($dataSet && count($dataSet)) {
            $isGlobalManager = $auth->hasRole(Roles::ROLE_BUDGET_MANAGER_GLOBAL);
            foreach ($dataSet as $row) {

                if ($isGlobalManager) {
                    $approve = '
                    <div class="btn-group" style="white-space:nowrap" data-status="' . Budget::BUDGET_STATUS_APPROVED . '">
                        <a class="btn btn-xs btn-success"
                            onClick="changeBudgetStatus(' . $row['id'] . ', ' . Budget::BUDGET_STATUS_APPROVED. ', this, event)">Approve</a>
                        <button type="button" class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onClick="changeBudgetStatus(' . $row['id'] . ', ' . Budget::BUDGET_STATUS_REJECTED. ', this, event)">Reject</a>
                            </li>
                        </ul>
                    </div>';

                    $actionsColumn = '
					<a href="/finance/budget/edit/' . $row['id'] .
                        '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>' . $approve;
                }

                $preparedData[] = [
                    $row['user_name'],
                    $row['name'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['from'])) . ' - ' .
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($row['to'])),
                    $row['amount'] . Budget::BUDGET_CURRENCY,
                    $actionsColumn
                ];
            }
        }
        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getAssetsAwaitingApprovalAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $budgetService
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $router = $this->getEvent()->getRouter();

        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_PENDING_ASSETS)) {
            return $this->redirect()->toRoute('home');
        };

        /** @var \DDD\Service\Warehouse\Asset $assetService */
        $assetService = $this->getServiceLocator()->get('service_warehouse_asset');

        $dataSet = $assetService->getAssetsAwaitingApproval();
        $preparedData = [];

        if ($dataSet && count($dataSet)) {
            /** @var \DDD\Domain\Warehouse\Assets\Consumable | \DDD\Domain\Warehouse\Assets\Valuable $row */
            foreach ($dataSet as $row) {

                /** @var \DDD\Dao\WHOrder\Order $orderDao */
                $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');

                $orders    = '<span class ="label label-danger">No matching orders found.</span>';
                $quantity  = 1;
                $assetType = 'valuable';

                if (!$row instanceof \DDD\Domain\Warehouse\Assets\Valuable) {
                    $quantity       = $row->getQuantityChange();
                    $assetType      = 'consumable';
                    $assetId        = $row->getAssetId();
                    $entityId       = $row->getId();
                } else {
                    $assetId  = $row->getId();
                    $entityId = $row->getId();
                }

                if ($assetType === 'consumable') {

                    if ($row->getShipmentStatus() == AssetService::SHIPMENT_STATUS_NOT_OK) {
                        $matchingOrders = $orderDao->getMatchingOrdersForConsumableAsset(
                            $row->getCategoryId(),
                            $row->getLocationEntityType(),
                            $row->getLocationEntityId(),
                            $quantity,
                            $row->getShipmentStatus(),
                            $checkOrderExist = 1
                        );

                        // if it did not find any order so will shown in UD
                        if ($matchingOrders->count()) {
                            $matchingOrders = $orderDao->getMatchingOrdersForConsumableAsset(
                                $row->getCategoryId(),
                                $row->getLocationEntityType(),
                                $row->getLocationEntityId(),
                                $quantity,
                                $row->getShipmentStatus(),
                                $checkOrderExist = 0
                            );

                            // Did not find any order which its quantity is less than to this asset
                            if (!$matchingOrders->count()) {
                                continue;
                            }
                        }
                    } else {
                        $matchingOrders = $orderDao->getMatchingOrdersForConsumableAsset(
                            $row->getCategoryId(),
                            $row->getLocationEntityType(),
                            $row->getLocationEntityId(),
                            $quantity,
                            $row->getShipmentStatus(),
                            $checkOrderExist = 0
                        );

                        // Did not find any order which its quantity is equal to this asset
                        if (!$matchingOrders->count()) {
                            continue;
                        }
                    }
                } else {
                    $matchingOrders = $orderDao->getMatchingOrdersForAsset(
                        $row->getCategoryId(),
                        $row->getLocationEntityType(),
                        $row->getLocationEntityId(),
                        $quantity
                    );
                }

                $actions = '<a
                    class="btn btn-xs btn-danger asset-resolve"
                    data-target="/warehouse/asset/resolve-' . $assetType . '/' . $entityId . '"
                    > Resolve </a>';

                if ($matchingOrders->count()) {
                    $orders = '<input type="hidden" class="asset-quantity" value="' . $quantity . '">';
                    $orders .= '<select class="asset-matching-orders form-control">';
                    foreach ($matchingOrders as $order) {
                        $orders .= '<option value="' . $order['id'] . '">' .
                            $order['title'] . ', ' . Order::getShortShippingStatuses()[$order['status']] . '</span>, ' .
                            $order['quantity'] . '/' . $order['remaining_quantity'] .
                            '</option>';
                    }

                    $actions = '<a
                        class="btn btn-xs btn-success asset-received"
                        data-target="/warehouse/asset/receive-' . $assetType . '/' . $entityId . '"
                        > Received </a> ' . $actions;
                }

                $url = $router->assemble(['action' => 'edit-consumable', 'id' => $assetId], ['name' => 'warehouse/asset']);

                if ($assetType === 'valuable') {
                    $url = $router->assemble(['action' => 'edit-valuable', 'id' => $assetId], ['name' => 'warehouse/asset']);
                }

                $actions .= ' <a
                    class="btn btn-xs btn-primary"
                    href="'. $url .'"
                    target="_blank"> View </a>';

                $preparedData[] = [
                    $row->getLocationName(),
                    $row->getCategoryName(),
                    $row->getLastUpdaterFullName(),
                    $orders,
                    $quantity,
                    $actions
                ];
            }
        }
        return new JsonModel(["aaData" => $preparedData]);
    }

    public function getItemsToBeOrderedAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_ITEMS_TO_BE_ORDERED)) {
            return $this->redirect()->toRoute('home');
        };
        /** @var Order $orderService */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');

        $dataSet = $orderService->getItemsToBeOrdered($auth->getIdentity()->id);
        $preparedData = [];
        $actionsColumn = '';
        if ($dataSet && count($dataSet)) {
            $isOrderManager = $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT) || $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
            foreach ($dataSet as $row) {

                if ($isOrderManager) {
                    $actionsColumn = '
					<a href="/orders/edit/' . $row->getId() .
                        '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';
                }
                $status = Order::getStatusesByText()[$row->getStatus()];
                $preparedData[] = [
                    '<span class="label ' . $status[1] . '">' . $status[0] . '</span>',
                    $row->getTitle(),
                    $row->getAssetCategoryName(),
                    $row->getQuantity(),
                    Order::getLabelForTargetType($row->getTargetType()) . $row->getLocationName(),
                    $row->getOrderDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getOrderDate())) : '',
                    $actionsColumn
                ];
            }
        }
        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getItemsToBeDeliveredAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_ITEMS_TO_BE_DELIVERED)) {
            return $this->redirect()->toRoute('home');
        };
        /** @var Order $orderService */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');

        $dataSet = $orderService->getItemsToBeDelivered($auth->getIdentity()->id);
        $preparedData = [];
        $actionsColumn = '';
        if ($dataSet && count($dataSet)) {
            $isOrderManager = $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT) || $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
            foreach ($dataSet as $row) {

                if ($isOrderManager) {
                    $actionsColumn = '
					<a href="/orders/edit/' . $row->getId() .
                        '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';
                }
                $status = Order::getStatusesByText()[$row->getStatus()];
                $classStatusShippingColor = Order::getStatusesColor()[$row->getStatusShipping()];
                $statusShipping = Order::getStatusesShipping()[$row->getStatusShipping()];
                $preparedData[] = [
                    '<span class="label ' . $status[1] . '">' . $status[0] . '</span>',
                    $row->getTitle(),
                    '<span class="label label-' . $classStatusShippingColor  . '">' .$statusShipping . '</span>',
                    $row->getQuantity(),
                    $row->getAssetCategoryName(),
                    Order::getLabelForTargetType($row->getTargetType()) . $row->getLocationName(),
                    $row->getOrderDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getOrderDate())) : '',
                    $row->getEstimatedDateStart() && $row->getEstimatedDateEnd() ? (date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getEstimatedDateStart())) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getEstimatedDateEnd()))) : '',
                    $row->getSupplierName(),
                    ($row->getTrackingUrl()) ? '<a href="' . $row->getTrackingUrl() . '" class="btn btn-xs btn-success" target="_blank">Track</a>' : '',
                    $actionsColumn
                ];
            }
        }
        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getOrdersToBeRefundedAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_ORDERS_TO_REFUNDED)) {
            return $this->redirect()->toRoute('home');
        };
        /** @var Order $orderService */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');

        $dataSet = $orderService->getOrdersToBeRefunded($auth->getIdentity()->id);
        $preparedData = [];
        $actionsColumn = '';
        if ($dataSet && count($dataSet)) {
            $isOrderManager = $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT) || $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
            foreach ($dataSet as $row) {

                if ($isOrderManager) {
                    $actionsColumn = '
					<a href="/orders/edit/' . $row->getId() .
                        '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';
                }
                $status = Order::getStatusesByText()[$row->getStatus()];
                $classStatusShippingColor = Order::getStatusesColor()[$row->getStatusShipping()];
                $statusShipping = Order::getStatusesShipping()[$row->getStatusShipping()];
                $preparedData[] = [
                    '<span class="label ' . $status[1] . '">' . $status[0] . '</span>',
                    '<span class="label label-' . $classStatusShippingColor . '">' . $statusShipping . '</span>',
                    $row->getQuantity(),
                    $row->getAssetCategoryName(),
                    Order::getLabelForTargetType($row->getTargetType()) . $row->getLocationName(),
                    $row->getOrderDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getOrderDate())) : '',
                    $row->getSupplierName(),
                    $row->getSupplierTransactionId(),
                    $actionsColumn
                ];
            }
        }
        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getOrdersToBeShippedAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        if (!$auth->checkUniversalDashboardPermission(UserService::DASHBOARD_ORDERS_TO_BE_SHIPPED)) {
            return $this->redirect()->toRoute('home');
        };
        /** @var Order $orderService */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');

        $dataSet = $orderService->getOrdersToBeShippedInLastTwoDays();
        $preparedData = [];
        $actionsColumn = '';
        if ($dataSet) {
            $isOrderManager = $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT) || $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
            foreach ($dataSet as $row) {
                /* @var \DDD\Domain\WHOrder\Order $row */
                if ($isOrderManager) {
                    $actionsColumn = '
					<a href="/orders/edit/' . $row->getId() .
                        '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View">View</a>';
                }
                $archiveColumn = '<a href="javascript:void(0)"' .
                    ' onClick="archiveOrders(this, ' . $row->getId() . ', event)" class="btn btn-xs btn-primary">Archive</a>';

                $status = Order::getStatusesByText()[$row->getStatus()];

                if ($row->getAssetCategoryType() == Category::CATEGORY_TYPE_CONSUMABLE) {
                    $categoryLabel = '<span class="label label-success">' . Category::$categoryTypes[$row->getAssetCategoryType()] . '</span> ' . $row->getAssetCategoryName();
                } else {
                    $categoryLabel = '<span class="label label-primary">' . Category::$categoryTypes[$row->getAssetCategoryType()] . '</span> ' . $row->getAssetCategoryName();
                }

                $preparedData[] = [
                    '<span class="label ' . $status[1] . '">' . $status[0] . '</span>',
                    $row->getTitle(),
                    $categoryLabel,
                    $row->getQuantity(),
                    Order::getLabelForTargetType($row->getTargetType()) . $row->getLocationName(),
                    $row->getOrderDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getOrderDate())) : '',
                    $archiveColumn,
                    $actionsColumn
                ];
            }
        }

        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getOrdersCreatedByMeAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var Order $orderService */
        $orderService = $this->getServiceLocator()->get('service_wh_order_order');

        $dataSet = $orderService->getOrdersCreatedByMe($auth->getIdentity()->id);
        $preparedData = [];
        $actionsColumn = '';
        if ($dataSet && count($dataSet)) {
            $isOrderManager = $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT) || $auth->hasRole(Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL);
            foreach ($dataSet as $row) {
                $receivedColumn = "&nbsp;";

                if ($isOrderManager) {
                    $actionsColumn = '
					<a href="/orders/edit/' . $row->getId() .
                        '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>';
                }

                $archiveColumn = '<a href="javascript:void(0)"' .
                    ' onClick="archiveOrders(this, ' . $row->getId() . ', event)" class="btn btn-xs btn-primary">Archive</a>';
                if (
                    $row->getStatus() == Order::STATUS_ORDER_APPROVED
                    &&
                    (
                        $row->getStatusShipping() == Order::STATUS_ORDERED
                        ||
                        $row->getStatusShipping() == Order::STATUS_SHIPPED
                        ||
                        $row->getStatusShipping() == Order::STATUS_DELIVERED
                        ||
                        $row->getStatusShipping() == Order::STATUS_PARTIALLY_RECEIVED
                        ||
                        $row->getStatusShipping() == Order::STATUS_ISSUE
                    )
                ) {
                    $receivedColumn = '<a href="javascript:void(0)"' .
                        ' onClick="markAsReceivedOrders(this, ' . $row->getId() . ', event)" class="btn btn-xs btn-success">Mark as Received</a>';
                }
                $status = Order::getStatusesByText()[$row->getStatus()];
                $classStatusShippingColor = Order::getStatusesColor()[$row->getStatusShipping()];
                $statusShipping = Order::getStatusesShipping()[$row->getStatusShipping()];
                $preparedData[] = [
                    $row->getTitle(),
                    '<span class="label ' . $status[1] . '">' . $status[0] . '</span>',
                    '<span class="label label-' . $classStatusShippingColor . '">' . $statusShipping . '</span>',
                    $row->getQuantity(),
                    Order::getLabelForTargetType($row->getTargetType()) . $row->getLocationName(),
                    $row->getOrderDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getOrderDate())) : '',
                    ($row->getEstimatedDateStart() && $row->getEstimatedDateEnd()) ? (date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getEstimatedDateStart())) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($row->getEstimatedDateEnd()))) : '',
                    ($row->getTrackingUrl()) ? '<a href="' . $row->getTrackingUrl() . '" class="btn btn-xs btn-success" target="_blank">Track</a>' : '',
                    $receivedColumn,
                    $archiveColumn,
                    $actionsColumn
                ];
            }
        }
        return new JsonModel([
            "aaData" => $preparedData
        ]);
    }

    public function getNewAssetCategoriesAction()
    {
        $categoryservice = $this->getServiceLocator()->get('service_warehouse_category');
        $categories      = $categoryservice->getNewCategoriesList();
        $preparedData    = [];

        foreach ($categories as $row) {
            $type = '<span class="label label-success">Consumable</span>';

            if ($row['type_id'] == Category::CATEGORY_TYPE_VALUABLE) {
                $type = '<span class="label label-primary">Valuable</span>';
            }

            $preparedData[] = [
                $type,
                $row['name'],
                $row['creator_name'],
                '<a class="btn btn-xs btn-success archive-category" data-target="/warehouse/category/archive-category/' . $row['id'] . '" > Archive </a>',
                '<a href="/warehouse/category/edit/' . $row['id'] . '" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>'
            ];
        }

        return new JsonModel(["aaData" => $preparedData]);
    }

    private function getButtonClass($classType)
    {
        switch ($classType) {
            case 'primary':
                return 'btn btn-xs btn-primary';
            case 'success':
                return 'btn btn-xs btn-success success-notification';
            case 'danger':
                return 'btn btn-xs btn-danger';
            case 'info':
                return 'btn btn-xs btn-info';
            case 'warning':
                return 'btn btn-xs btn-warning';
            case 'link':
                return 'btn btn-xs btn-link';
            default :
                return 'btn btn-xs btn-default';
        }
    }
}
