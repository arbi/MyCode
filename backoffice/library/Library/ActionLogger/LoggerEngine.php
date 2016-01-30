<?php

namespace Library\ActionLogger;

use DDD\Dao\ActionLogs\ActionLogs;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\Reservation\ChargeAuthorization;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LoggerEngine
{
	protected $sm;
	protected $ex;
	protected $userId = null;
	protected $outputFormat = Logger::OUTPUT_RAW;

	/**
	 * @param ServiceLocatorInterface $sm
	 * @throws \InvalidArgumentException
	 */
	public function __construct($sm)
    {
		if ($sm instanceof ServiceLocatorInterface) {
			$this->sm = $sm;
		} else {
			throw new \InvalidArgumentException('Argument should be an instance of ServiceLocatorInterface.');
		}
	}

	/**
	 * @param \ArrayObject|\ArrayObject[] $logs
	 *
	 * @return string
	 */
	protected function drawLogs($logs)
    {
		$displayLog = '';

		switch ($this->outputFormat) {
			case Logger::OUTPUT_RAW:
				$displayLog = $this->drawRaw($logs);
				break;
			case Logger::OUTPUT_HTML:
				$displayLog = $this->drawHTML($logs);
				break;
            case Logger::OUTPUT_BOOKING:
            case Logger::OUTPUT_USER:
                $displayLog = $this->drawHTML($logs, true);
                break;
		}

		return $displayLog;
	}

	/**
	 * @param \ArrayObject|\ArrayObject[] $logs
	 * @return string
	 */
	protected function drawRaw($logs)
    {
		$displayLog = '';

		if ($logs && $logs->count()) {
			foreach ($logs as $log) {
				if ($log['action_id'] == Logger::ACTION_BLOB) {
					$displayLog .= trim($log['value']) . PHP_EOL . PHP_EOL;
				} else {
					$displayLog .= $this->concatFields($log);
				}
			}
		}

		return $displayLog;
	}

	/**
	 * @param \ArrayObject|\ArrayObject[] $logs
	 * @param bool $amsterdamSuffix
	 *
	 * @return string
	 */
	protected function drawHTML($logs, $amsterdamSuffix = false)
    {
		$displayLog = '';

		if ($logs && $logs->count()) {
			foreach ($logs as $log) {
				if ($log['action_id'] == Logger::ACTION_BLOB) {
					$displayLog .= $this->blobBeautifier($log['value']);
				} else {
					$displayLog .= $this->concatFieldsHTML($log, $amsterdamSuffix);
				}
			}
		}

		return $displayLog;
	}

	public function setOutputFormat($format = Logger::OUTPUT_RAW)
    {
		$this->outputFormat = $format;

		return $this;
	}

	protected static function blobBeautifier($data)
    {
		return preg_replace(
            '/----(.*)----/U','<span class="commentBeautifier">----$1----</span>',
            preg_replace(
                "/Comment\s*----\s*\\\n\s*\\\n/U","Comment ----\n",
                trim($data)
            )
        );
	}

	/**
	 * @param \ArrayObject $log
	 *
	 * @return string
	 */
	protected function concatFields($log)
    {
		$drawLogRecord = '';

		if ($log['action_id'] == Logger::ACTION_COMMENT) {
			$drawLogRecord .= (
				'---- ' . $this->getTime($log['timestamp']) . ' || ' . $log['user_name'] . ' || Comment ----' . PHP_EOL . trim($log['value']) . PHP_EOL . PHP_EOL
			);
		} else {
			$drawLogRecord .= (
				'---- ' . $this->getTime($log['timestamp']) . ' || ' . $log['user_name'] . ' || ' . trim($log['value']) . ' ----' . PHP_EOL . PHP_EOL
			);
		}

		return $drawLogRecord;
	}

	/**
	 * @param \ArrayObject $log
	 * @param bool $amsterdamSuffix
	 *
	 * @return string
	 */
	protected function concatFieldsHTML($log, $amsterdamSuffix)
    {
        $drawLogRecord = (
            '<blockquote class="comment-blockquote"><p>' . trim($log['value']) . '</p><footer>'  . $this->getTime($log['timestamp'], $amsterdamSuffix) . ' || ' . $log['user_name'] . '</footer></blockquote>'
        );

		return $drawLogRecord;
	}

	/**
	 * @param int $actionId
	 * @param string $value
	 * @return string
	 */
	protected function getValueDependsOnAction($actionId, $value)
    {
		switch ($actionId) {
			case Logger::ACTION_BLOB:
			case Logger::ACTION_COMMENT:
            case Logger::ACTION_USER_DOCUMENT:
            case Logger::ACTION_HR_ACCOUNT_MANAGEMENT:
            case Logger::ACTION_BOOKING_EMAIL_EX:
            case Logger::ACTION_RESERVATION_EMAIL_RECEIPT:
				break;
			case Logger::ACTION_APPROVED:
				$value = $value ? 'Status >> Approved' : 'Status >> Rejected';
				break;
			case Logger::ACTION_PAID:
				$value = $value ? 'Status >> Paid' : 'Status >> Unpaid';
				break;
			case Logger::ACTION_VERIFIED:
				$value = $value ? 'Status >> Verified' : 'Status >> Unverified';
				break;
            case Logger::ACTION_CLOSED:
                $value = $value ? 'Status >> Closed' : 'Status >> Opened';
                break;
            case Logger::ACTION_RESUBMITTED:
                $value = 'Status >> Awaiting Approval';
                break;
            case Logger::ACTION_BLOCKED:
                $value = $value ? 'Status >> Blocked' : 'Status >> Unblocked';
                break;
            case Logger::ACTION_CANCELED:
                $value = $value ? 'Status >> Canceled' : 'Status >> Not Canceled';
                break;
            case Logger::ACTION_DONE:
                $value = $value ? 'Status >> Done' : 'Status >> Not Done';
                break;
            case Logger::ACTION_NEW:
                $value = $value ? 'Status >> New' : 'Status >> Old';
                break;
            case Logger::ACTION_STARTED:
                $value = $value ? 'Status >> Started' : 'Status >> Ended';
                break;
            case Logger::ACTION_VIEWED:
                $value = $value ? 'Status >> Viewed' : 'Status >> Not Viewed';
                break;
            case Logger::ACTION_TASK_TEAM:
                $value = 'Changed Team from ' . $value;
                break;
            case Logger::ACTION_TASK_STAFF_REMOVE:
                $value = 'Removed ' . $value[1] . ' from ' . $value[0];
                break;
            case Logger::ACTION_TASK_STAFF_ADD:
                $value = 'Added ' . $value[1] . ' as a ' . $value[0];
                break;
            case Logger::ACTION_TASK_STAFF_CHANGE:
                $value = 'Changed ' . $value[0] .' from "' . $value[1] .'" to "' . $value[2] .'"';
                break;
            case Logger::ACTION_PRIORITY:
                switch ($value) {
                    case Logger::VALUE_PRIORITY_NORMAL:
                        $value = 'Priority >> Normal';
                        break;
                    case Logger::VALUE_PRIORITY_HIGH:
                        $value = 'Priority >> High';
                        break;
                    case Logger::VALUE_PRIORITY_IMPORTANT:
                        $value = 'Priority >> Important';
                        break;
                    case Logger::VALUE_PRIORITY_CRITICAL:
                        $value = 'Priority >> Critical';
                        break;
                }

                break;
            case Logger::ACTION_DUPLICATE_CANCELLATION:
                $value = 'Received a cancellation from cubilis on already cancelled reservation' . $value;
                break;
            case Logger::ACTION_BUDGET_SET:
                $value = 'Set Budged ' . $value;
                break;
            case Logger::ACTION_TASK_DATE_SET:
                $value = 'Set Action/Due Date ' . $value;
                break;
            case Logger::VALUE_CHECK_IN:
            case Logger::ACTION_CHECK_IN:
            case Logger::ACTION_FINANCE_BOOKING_STATE:
                switch ($value) {
                    case Logger::VALUE_CHECK_IN:
                        $value = 'Checked-in';
                        break;
                    case Logger::VALUE_CHECK_OUT:
                        $value = 'Checked-out';
                        break;
                    case Logger::VALUE_EXPECTED:
                        $value = 'Expected';
                        break;
                    case Logger::VALUE_NO_SHOW:
                        $value = 'No Show';
                        break;
                }
                break;
            case Logger::ACTION_BOOKING_STATUSES:
                switch ($value) {
                    case Logger::VALUE_BOOKED:
                        $value = 'Booked';
                        break;
                    case Logger::VALUE_CANCELED_MOVED:
                        $value = 'Canceled (Moved)';
                        break;
                    case Logger::VALUE_CANCELED_BY_CUSTOMER:
                        $value = 'Canceled by Customer';
                        break;
                    case Logger::VALUE_CANCELED_BY_GINOSI:
                        $value = 'Canceled by Ginosi';
                        break;
                    case Logger::VALUE_CANCELED_INVALID:
                        $value = 'Canceled (Invalid)';
                        break;
                    case Logger::VALUE_CANCELED_TEST:
                        $value = 'Canceled (Test Booking)';
                        break;
                    case Logger::VALUE_CANCELED_FRAUDULANT:
                        $value = 'Canceled (Fraudulent)';
                        break;
                    case Logger::VALUE_CANCELED_UNWANTED:
                        $value = 'Canceled (Unwanted)';
                        break;
                    case Logger::VALUE_CANCELLED_NO_SHOW:
                        $value = 'Canceled (No Show)';
                        break;
                    case Logger::VALUE_CANCELED_UNKNOWN:
                        $value = 'Cancelled (Pending)';
                        break;
                    case Logger::VALUE_CANCELED_EXCEPTION:
                        $value = 'Cancelled by Exception';
                        break;
                }
                break;
            case Logger::ACTION_NO_COLLECTION:
                $value = $value ? 'No Collection Set' : 'No Collection Unset';
                break;
            case Logger::ACTION_PARTNER_SETTLED:
                $value = $value ? 'Partner Settled' : 'Partner Unsettled';
                break;
            case Logger::ACTION_RESERVATION_SETTLED:
                $value = $value ? 'Reservation Customer Settled' : 'Reservation Customer Unsettled';
                break;
            case Logger::ACTION_CCCA_VERIFIED:
                $value = $value ? 'CCCA Verification checked' : 'CCCA Verification unchecked';
                break;
            case Logger::ACTION_RESERVATION_LOCKED:
                $value = $value ? 'Reservation Locked' : 'Reservation Unlocked';
                break;
            case Logger::ACTION_BOOKING_CC_STATUS:
                switch ($value) {
                    case Logger::VALUE_CC_UNKNOWN:
                        $value = 'Marked reservation with Unknown Credit';
                        break;
                    case Logger::VALUE_CC_INVALID:
                        $value = 'Marked reservation with Invalid Credit';
                        break;
                    case Logger::VALUE_CC_VALID:
                        $value = 'Marked reservation with Valid Credit';
                        break;
                }

                break;
            case Logger::ACTION_APARTEL_ID:
                switch ($value) {
                    case -1:
                        $value = 'Unknown Apartel';
                        break;
                    case 0:
                        $value = 'Non Apartel';
                        break;
                    default:
                        $apartmentGroupDao = $this
                            ->sm
                            ->get('dao_apartment_group_apartment_group');

                        $apartel = $apartmentGroupDao
                            ->fetchOne(['id' => $value]);
                        $value = $apartel->getName() . ' (Apartel)';
                }
                break;

            case Logger::ACTION_OVERBOOKING_STATUS_CHANGE:
                $value = 'Overbooking status was changed to "' . BookingTicket::$overbookingOptions[$value] . '"';
                break;
            case Logger::ACTION_KI_VIEWED:
                $value = $value ? 'Key Instructions Viewed' : 'Key Instructions Not Viewed';
                break;
            case Logger::ACTION_BOOKING_EMAIL:
                switch ($value) {
                    case Logger::VALUE_EMAIL_GINOSI_RESERVATION:
                        $value = 'Ginosi Reservation mail has been sent';
                        break;
                    case Logger::VALUE_EMAIL_GUEST_RESERVATION:
                        $value = 'Guest Reservation mail has been sent';
                        break;
                    case Logger::VALUE_EMAIL_KI:
                        $value = 'Guest Key Instructions mail has been sent';
                        break;
                    case Logger::VALUE_EMAIL_REVIEW_REQUEST:
                        $value = 'Guest Review Request mail has been sent';
                        break;
                }

                break;
            case Logger::ACTION_BLACK_LIST:
                $value = $value ? 'Add to Black List' : 'Remove from Black List';
                break;
            case Logger::ACTION_REQUEST_PAYMENT_DETAILS:
                $value = $value ? 'Sent a request for new payment details to the customer' : 'Closed the link for new payment details';
                break;
            case Logger::ACTION_RESERVATION_CCCA_FORM_GENERATED_AND_SENT:
                switch ($value) {
                    case ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED:
                        $value = 'Sent a request to sign CCCA form to the customer';
                        break;
                }

                break;
            case Logger::ACTION_HK_ACTIONS:
                switch ($value) {
                    case Logger::VALUE_CLEANED:
                        $value = 'Cleaned';
                        break;
                    case Logger::VALUE_CHECKED_OUT:
                        $value = 'Checked-out';
                        break;
                    case Logger::VALUE_INSPECTED:
                        $value = 'Inspected';
                        break;
                }

                break;
            case Logger::ACTION_LAST_MINUTE_RESOLVED:
                $value = 'Last Minute Arrival Resolved';
                break;
            case Logger::ACTION_HK_COMMENT_READ:
                $value = 'Marked Housekeeping Comment as Read';
                break;
            case Logger::ACTION_PAID_TO_AFFILIATE:
                $value = $value ? 'Paid to Affiliate Checked' : 'Paid to Affiliate Unchecked';
                break;
            case Logger::ACTION_PROVIDE_PARKING:
                break;

            // New Credit Card actions
            case Logger::ACTION_NEW_CC_CHANNEL_RESERVATION_SYSTEM:
                $value = "New CC #{$value} received from Cubilis";
                break;
            case Logger::ACTION_NEW_CC_FROM_CHANNEL_MODIFICATION_SYSTEM:
                $value = "New CC #{$value} received from Cubilis during modification";
                break;
            case Logger::ACTION_NEW_CC_FROM_WEBSITE_GUEST:
                $value = "New CC #{$value} was provided via Website (Update Payment Details Page) by the guest";
                break;
            case Logger::ACTION_NEW_CC_FROM_WEBSITE_EMPLOYEE:
                $value = "New CC #{$value} was provided via Website (Update Payment Details Page) by employee";
                break;
            case Logger::ACTION_NEW_CC_FROM_WEBSITE_RESERVATION_GUEST:
                $value = "New CC #{$value} was provided via Website during reservation by the guest";
                break;
            case Logger::ACTION_NEW_CC_FROM_WEBSITE_RESERVATION_EMPLOYEE:
                $value = "New CC #{$value} was provided via Website during reservation by employee";
                break;
            case Logger::ACTION_NEW_CC_FROM_FRONTIER_DASHBOARD_EMPLOYEE:
                $value = "New CC #{$value} received from Frontier Dashboard";
                break;

            case Logger::VALUE_CANCELED_UNKNOWN:
                $value = 'Cancel Unknown';
                break;
            case Logger::ACTION_APARTMENT_STATUS:
                switch ($value) {
                    case Logger::VALUE_APARTMENT_STATUS_SANDBOX;
                        $value = 'Apartment status changed to "Sandbox"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_REGISTRATION;
                        $value = 'Apartment status changed to "Registration"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_REVIEW;
                        $value = 'Apartment status changed to "Review"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_LIVEANDSELLING;
                        $value = 'Apartment status changed to "Live and Selling"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_SUSPENDED;
                        $value = 'Apartment status changed to "Suspended"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_DISABLED;
                        $value = 'Apartment status changed to "Disabled"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_SELLINGNOTSEARCHABLE;
                        $value = 'Apartment status changed to "Selling not Searchable"';
                        break;
                    case Logger::VALUE_APARTMENT_STATUS_LIVEINUNIT;
                        $value = 'Apartment status changed to "Live-in Unit"';
                        break;
                }
                break;
            case Logger::ACTION_HANDLED:
                $value = 'Finance Status >> Handled';
                break;
            case Logger::ACTION_SERVICE_IS_RENDERED:
                $value = 'Finance Status >> Closed for Review';
                break;
            case Logger::ACTION_SETTLED:
                $value = $value ? 'Finance Status >> Settled' : 'Finance Status >> Unsettled';
                break;
            case Logger::ACTION_OCUPANCY_CHANGE:
                $value = "Occupancy was changed from {$value[0]} to {$value[1]}";
                break;
            case Logger::ACTION_VERIFY_TRANSACTION:
                $value = $value ? 'Transaction Verified' : 'Transaction Unverified';
                break;
            case Logger::ACTION_VOID_TRANSACTION:
                $value = 'Transaction Voided';
                break;
		}

		return $value;
	}

	/**
	 * @param \Exception $ex
	 */
	protected function setException(\Exception $ex)
    {
		$this->ex = $ex;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage()
    {
		if ($this->ex instanceof \Exception) {
			return $this->ex->getMessage();
		}

		return '';
	}

	/**
	 * @return int
	 */
	protected function getUserId()
    {
		/**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         */
		if (is_null($this->userId)) {
            if ($this->sm->has('library_backoffice_auth')) {
                $auth = $this->sm->get('library_backoffice_auth');

                if ($auth->getIdentity() && $auth->getIdentity()->id) {
                    $this->userId = $auth->getIdentity()->id;
                } else {
                    $this->userId = 277; // System user
                }
            } else {
                $this->userId = 277; // System user
            }
		}

		return $this->userId;
	}

	/**
	 * @return ServiceLocatorAwareInterface
	 */
	protected function getServiceLocator()
    {
		return $this->sm;
	}

	/**
     * @param string $timestamp
     * @param bool $amsterdamSuffix
	 * @return string
	 */
	protected function getTime($timestamp, $amsterdamSuffix = false)
    {
		return $timestamp . ($amsterdamSuffix ? ' (Amsterdam Time)' : '');
	}
}
