<?php

namespace Library\ActionLogger;

use Library\Constants\TextConstants;
use Library\Constants\Constants;

class Logger extends LoggerEngine
{
    const POSITIVE  = 1;
    const NEGATIVE  = 0;

    // Actions
    const ACTION_BLOB     = 1;
    const ACTION_COMMENT  = 2;

    const ACTION_APPROVED = 3;
    const ACTION_PAID     = 4;
    const ACTION_VERIFIED = 5;
    const ACTION_VIEWED   = 6;
    const ACTION_BLOCKED  = 7;
    const ACTION_STARTED  = 8;
    const ACTION_DONE     = 9;
    const ACTION_CANCELED = 10;
    const ACTION_NEW      = 11;

    const ACTION_PRIORITY                   = 12;
    const ACTION_BUDGET_SET                 = 13;
    const ACTION_TASK_DATE_SET              = 14;
    const ACTION_FINANCE_BOOKING_STATE      = 15;
    const ACTION_BOOKING_STATUSES           = 16;

    /**
     * This constant is written just to reserve action_id 17 that used for moved res column to history migration
     */
    const ACTION_BOOKING_MIGRATION_MOVED_RES= 17;

    const ACTION_NO_COLLECTION              = 18;
    const ACTION_PARTNER_SETTLED            = 19;
    const ACTION_RESERVATION_SETTLED        = 20;
    const ACTION_BOOKING_CC_STATUS          = 21;
    const ACTION_APARTEL_ID                 = 22;
    const ACTION_OVERBOOKING_STATUS_CHANGE  = 23;
    const ACTION_KI_VIEWED                  = 24;
    const ACTION_BOOKING_EMAIL              = 25;
    const ACTION_CHECK_IN                   = 26;
    const ACTION_BLACK_LIST                 = 27;
    const ACTION_REQUEST_PAYMENT_DETAILS    = 28;
    const ACTION_HK_ACTIONS                 = 29;
    const ACTION_LAST_MINUTE_RESOLVED       = 31;
    const ACTION_HK_COMMENT_READ            = 33;
    const ACTION_PAID_TO_AFFILIATE          = 34;
    const ACTION_PROVIDE_PARKING            = 35;

    const ACTION_NEW_CC_CHANNEL_RESERVATION_SYSTEM          = 200;
    const ACTION_NEW_CC_FROM_CHANNEL_MODIFICATION_SYSTEM    = 201;
    const ACTION_NEW_CC_FROM_WEBSITE_GUEST                  = 202;
    const ACTION_NEW_CC_FROM_WEBSITE_EMPLOYEE               = 203;
    const ACTION_NEW_CC_FROM_WEBSITE_RESERVATION_GUEST      = 204;
    const ACTION_NEW_CC_FROM_WEBSITE_RESERVATION_EMPLOYEE   = 205;
    const ACTION_NEW_CC_FROM_FRONTIER_DASHBOARD_EMPLOYEE    = 206;

    const ACTION_RESERVATION_MOVE           = 38;
    const ACTION_APARTMENT_STATUS           = 39;
    const ACTION_APARTMENT_NAME             = 40;
    const ACTION_APARTMENT_DESCRIPTION      = 41;
    const ACTION_APARTMENT_GROUPS_NAME           = 42;
    const ACTION_APARTMENT_GROUPS_APARTMENT_LIST = 43;
    const ACTION_APARTMENT_GROUPS_USAGE          = 44;
    const ACTION_PARTNER_NAME               = 45;
    const ACTION_PARTNER_STATUS             = 46;
    const ACTION_LOCATION_NAME              = 47;
    const ACTION_LOCATION_INFORMATION       = 49;
    const ACTION_LOCATION_SEARCHABLE        = 50;
    const ACTION_OCUPANCY_CHANGE            = 51;
    const ACTION_REMOVE_DECLINED_CARD       = 52;
    const ACTION_BlACK_LIST                 = 53;
    const ACTION_CC                         = 54;
    const ACTION_BOOKING_PARTNER_COMMISSION = 55;
    const ACTION_BOOKING_MODIFY             = 56;

    const ACTION_HANDLED                    = 60;
    const ACTION_SERVICE_IS_RENDERED        = 61;
    const ACTION_SETTLED                    = 62;
    const ACTION_CLOSED                     = 63;
    const ACTION_RESUBMITTED                = 64;
    const ACTION_REVOKED                    = 65;

    const ACTION_BOOKING_UPLOAD_DOCS        = 66;
    const ACTION_BOOKING_DELETE_DOCS        = 67;
    const ACTION_TASK_TEAM                  = 68;
    const ACTION_TASK_STAFF_REMOVE          = 69;
    const ACTION_TASK_STAFF_ADD             = 70;
    const ACTION_TASK_STATUS_CHANGED        = 71;
    const ACTION_TASK_SUBTASK               = 172;
    const ACTION_CC_STATUS_CHANGED          = 72;
    const ACTION_RESERVATION_EMAIL_RECEIPT  = 73;
    const ACTION_PARTNER_DISCOUNT           = 74;
    const ACTION_TASK_SYSTYEM_GENERATED     = 75;

    const ACTION_TASK_STAFF_CHANGE          = 76;

    const ACTION_BLOB_TRANSFORMATION        = 99;
    const ACTION_TRANSACTIONS               = 101;
    const ACTION_HOUSEKEEPING_COMMENT       = 102;

    const ACTION_USER_VACATION              = 110;
    const ACTION_DUPLICATE_CANCELLATION     = 111;
    const ACTION_CCCA_VERIFIED              = 112;
    const ACTION_RESERVATION_LOCKED         = 113;
    const ACTION_RESERVATION_UNSETTLED      = 120;
    const ACTION_RESERVATION_CCCA_FORM_GENERATED_AND_SENT = 121;

    const ACTION_TASK_AUTO_VERIFY           = 5000;

    const ACTION_USER_DOCUMENT              = 122;

    CONST ACTION_HR_ACCOUNT_MANAGEMENT      = 132;

    const ACTION_BOOKING_EMAIL_EX       = 123;

    const ACTION_APARTMENT_CALENDAR_AVAILABILITY  = 130;
    const ACTION_APARTMENT_INVENTORY_AVAILABILITY = 131;


    // credit card vault actions
    const ACTION_REQUEST_CC_COMPLETE_DATA = 140;

    const ACTION_WH_ORDER_STATUS_CHANGED = 150;
    const ACTION_WH_ORDER_CREATED        = 151;
    const ACTION_WH_ORDER_RE_ORDERED     = 152;

    // Assets
    const ACTION_ASSET_THRESHOLD_CHANGED = 153;
    const ACTION_ASSET_QUANTITY_CHANGED  = 154;

    const ACTION_ASSET_VALUABLE_STATUS_CHANGED   = 155;
    const ACTION_ASSET_VALUABLE_ASSIGNEE_CHANGED = 156;
    const ACTION_ASSET_VALUABLE_LOCATION_CHANGED = 157;
    const ACTION_ASSET_VALUABLE_ADDED_COMMENT    = 158;

    // Apartels
    const ACTION_APARTEL_STATUS    = 170;
    const ACTION_APARTEL_MAXIMIZED = 171;
    const ACTION_CHANGE_STATUS     = 173;

    const ACTION_VERIFY_TRANSACTION = 207;
    const ACTION_VOID_TRANSACTION   = 208;

    // Asset Category
    const ACTION_MERGE_CATEGORY = 209;

    // Modules
    const MODULE_BOOKING      = 1;
    const MODULE_HOUSEKEEPING = 2;
    const MODULE_EXPENSE      = 3;
    const MODULE_TASK         = 4;
    const MODULE_USER         = 5;

    const MODULE_APARTMENT_GENERAL    = 10;
    const MODULE_APARTMENT_DETAILS    = 11;
    const MODULE_APARTMENT_LOCATION   = 12;
    const MODULE_APARTMENT_MEDIA      = 14;
    const MODULE_APARTMENT_DOCUMENTS  = 15;
    const MODULE_APARTMENT_RATES      = 16;
    const MODULE_APARTMENT_CALENDAR   = 17;
    const MODULE_APARTMENT_INVENTORY  = 18;
    const MODULE_APARTMENT_CONNECTION = 19;
    const MODULE_APARTMENT_REVIEW     = 20;

    const MODULE_APARTMENT_GROUPS = 21;

    const MODULE_PARTNERS         = 22;

    const MODULE_LOCATIONS        = 23;

    const MODULE_WH_ORDER         = 24;

    const MODULE_ASSET_CONSUMABLE = 25;
    const MODULE_ASSET_VALUABLE   = 26;

    const MODULE_APARTEL          = 27;
    const MODULE_ESPM             = 28;

    const MODULE_MONEY_ACCOUNT    = 29;

    const MODULE_ASSET_CATEGORY   = 30;

    // Output style. Default - Raw
	const OUTPUT_RAW     = 1;
	const OUTPUT_HTML    = 2;
	const OUTPUT_BOOKING = 3;
	const OUTPUT_USER    = 4;

    // Basic Action: priority
    const VALUE_PRIORITY_NORMAL    = 1;
    const VALUE_PRIORITY_HIGH      = 2;
    const VALUE_PRIORITY_IMPORTANT = 3;
    const VALUE_PRIORITY_CRITICAL  = 4;

    // Basic Action: finance booking state
    const VALUE_EXPECTED  = 0;
    const VALUE_CHECK_IN  = 1;
    const VALUE_CHECK_OUT = 2;
    const VALUE_NO_SHOW   = 4;

    // Basic Action: booking statuses
    const VALUE_BOOKED               = 1;
    const VALUE_CANCELED_MOVED       = 2;
    const VALUE_CANCELED_BY_CUSTOMER = 3;
    const VALUE_CANCELED_BY_GINOSI   = 4;
    const VALUE_CANCELED_INVALID     = 5;
    const VALUE_CANCELED_TEST        = 6;
    const VALUE_CANCELED_FRAUDULANT  = 7;
    const VALUE_CANCELLED_NO_SHOW    = 8;
    const VALUE_CANCELED_UNKNOWN     = 100;
    const VALUE_CANCELED_EXCEPTION   = 10;
    const VALUE_CANCELED_UNWANTED   = 11;

    // Basic Action: booking cc status
    const VALUE_CC_UNKNOWN = 1;
    const VALUE_CC_VALID   = 2;
    const VALUE_CC_INVALID = 3;

    // Basic Action: booking email
    const VALUE_EMAIL_GINOSI_RESERVATION = 1;
    const VALUE_EMAIL_GUEST_RESERVATION  = 2;
    const VALUE_EMAIL_KI                 = 3;
    const VALUE_EMAIL_REVIEW_REQUEST     = 4;

    // Basic Action: hk actions
    const VALUE_CLEANED     = 1;
    const VALUE_CHECKED_OUT = 2;
    const VALUE_INSPECTED   = 3;

    const VALUE_APARTMENT_STATUS_SANDBOX              = 1;
    const VALUE_APARTMENT_STATUS_REGISTRATION         = 2;
    const VALUE_APARTMENT_STATUS_REVIEW               = 3;
    const VALUE_APARTMENT_STATUS_LIVEANDSELLING       = 5;
    const VALUE_APARTMENT_STATUS_SUSPENDED            = 8;
    const VALUE_APARTMENT_STATUS_DISABLED             = 9;
    const VALUE_APARTMENT_STATUS_SELLINGNOTSEARCHABLE = 10;
    const VALUE_APARTMENT_STATUS_LIVEINUNIT           = 11;

    /**
	 * Save Action Log in database.
	 *
	 * @param int $moduleId Booking, Housekeeping, Expense or Task
	 * @param int $identityId booking_id, expense_id or task_id respectively
	 * @param int $actionId One of available actions
	 * @param int|string $value Action's value respectively
	 * @param int|null $userId
	 * @param string|null $timestamp
	 *
	 * @return bool|int
	 */
	public function save($moduleId, $identityId, $actionId, $value = Logger::POSITIVE, $userId = null, $timestamp = null)
    {
		try {
            /**
             * @var \DDD\Dao\ActionLogs\ActionLogs $actionLogsDao
             */
            $actionLogsDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

            $displayValue     = $this->getValueDependsOnAction($actionId, $value);

			$lastInsertId = $actionLogsDao->save([
                'module_id'   => $moduleId,
                'identity_id' => $identityId,
                'timestamp'   => is_null($timestamp) ? $this->getTime(date('Y-m-d H:i:s')) : $timestamp,
                'user_id'     => is_null($userId) ? $this->getUserId() : $userId,
                'action_id'   => $actionId,
                'value'       => $displayValue,
            ]);
		} catch (\Exception $ex) {
			$this->setException($ex);

			return false;
		}

		return $lastInsertId;
	}

	/**
	 * Get Action Log for exact ticket.
	 *
	 * @param int $moduleId Booking, Housekeeping, Expense or Task
	 * @param int $identityId booking_id, expense_id or task_id respectively
     * @param int $actionId get by exact action id or all by default
	 *
	 * @return bool|string
	 */
	public function get($moduleId, $identityId, $actionId = null, $draw = true)
    {
		try {
            /**
             * @var \DDD\Dao\ActionLogs\ActionLogs $actionLogsDao
             */
            $actionLogsDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

			$displayLog       = $actionLogsDao->getByTicket($moduleId, $identityId, $actionId);

            if ($draw) {
    			$displayLog = $this->drawLogs($displayLog);
            }
		} catch (\Exception $ex) {
			$this->setException($ex);

			return false;
		}

		return $displayLog;
	}

    public function getDatatableData($moduleId, $identityId, $highlight = 0, $userId = null)
    {
        /**
         * @var \DDD\Dao\ActionLogs\ActionLogs $actionLoggingDao
         */
        $actionLoggingDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');
        $actionLogTeamDao = $this->getServiceLocator()->get('dao_action_logs_logs_team');
        $teamDao          = $this->getServiceLocator()->get('dao_team_team');
        $logData          = [];

        $actions = $actionLoggingDao->getByTicket($moduleId, $identityId);

        if ($actions->count()) {
            $userTeams      = null;
            $userTeamsArray = [];

            if (!is_null($userId)) {
                $userTeams = $teamDao->getUserTeams($userId);

                foreach ($userTeams as $userTeam) {
                    array_push($userTeamsArray, $userTeam->getId());
                }
            }

            foreach ($actions as $log) {
                $rowClass = '';
                $comment = $log['value'];

                if ($log['action_id'] == Logger::ACTION_COMMENT || $log['action_id'] == Logger::ACTION_HOUSEKEEPING_COMMENT) {
                    $rowClass = "info";
                }

                if ($log['is_system']) {
                    $rowClass = "warning";
                }

                if ($log['id'] == $highlight) {
                    $rowClass = "danger";
                }

                if ($moduleId == Logger::MODULE_BOOKING) {
                    if (
                        isset($userTeamsArray[0])
                        && ($log['action_id'] == Logger::ACTION_COMMENT || $log['action_id'] == Logger::ACTION_HOUSEKEEPING_COMMENT)
                    ) {
                        $teamInfo = $actionLogTeamDao->fetchOne(['action_log_id' => $log['id']]);

                        if ($teamInfo) {
                            if (in_array($teamInfo['team_id'], $userTeamsArray)) {
                                $resolveBtn = '<a href="javascript:void(0)" id="unresolved-comment-' .
                                    $teamInfo['id'] . '" class="btn btn-xs btn-success pull-right" onclick="resolveComment(this)">Resolve</a>';
                                $comment = $log['value'] . ' ' . $resolveBtn;
                            }
                        }
                    }
                }

                $row = [
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($log['timestamp'])),
                    $log['user_name'],
                    $comment,
                    "DT_RowClass" => $rowClass
                ];

                if ($moduleId == Logger::MODULE_BOOKING) {
                    switch ($log['action_id']) {
                        case Logger::ACTION_COMMENT:
                            $row[3] = '<span
                                class="text-danger glyphicon glyphicon-eye-close btn-send-frontier"
                                data-content="This comment is <b>not</b> visible to Frontier. Click to make it visible"
                                data-container="body" data-toggle="popover" data-animation="true"
                                id="btn-send-frontier-' . $log['id'] . '"
                                data-id="' . $log['id'] . '"></span>';
                            break;
                        case Logger::ACTION_HOUSEKEEPING_COMMENT:
                            $row[3] = '<span
                                class="text-success glyphicon glyphicon-eye-open"
                                data-content="This comment is visible to Frontier."
                                data-container="body" data-toggle="popover" data-animation="true"></span>';
                            break;
                        default:
                            $row[3] = '';
                            break;
                    }
                }

                array_push($logData, $row);
            }
        }

        return $logData;
    }
}
