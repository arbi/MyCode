<?php

namespace DDD\Service\Booking;

use DDD\Service\ServiceBase;
use DDD\Service\Booking;
use Library\Constants\Constants;
use Zend\Validator\EmailAddress;

use Library\ReservationIssues\Detector\EmailAddressIssues;

class ReservationIssues extends ServiceBase
{
    const ISSUE_EMAIL_IS_MISSING                = 1;
    const ISSUE_CCN_IS_MISSING                  = 3;
    const ISSUE_CCN_IS_INVALID                  = 4;
    const ISSUE_CCN_IS_TEST                     = 5;
    const ISSUE_CCN_HOLDER_NAME_IS_MISSING      = 6;
    const ISSUE_CCN_DATE_HAS_EXPIRED            = 7;
    const ISSUE_CCN_EXPIRATION_DATE_IS_MISSING  = 8;
    const ISSUE_CCN_DATE_WILL_BE_EXPIRED        = 9;
    const ISSUE_APARTMENT_OCUPANCY_REDUCED      = 12;


    const CC_LIST_ALL           = 'ALL';
    const CC_LIST_MODE_SETTLED  = 'SETTLED';
    const CC_LIST_MODE_PARTNER  = 'PARTNER';
    const CC_LIST_FOR_CLEAR     = 'CC_CLEAR';

    const ENTITY_TYPE_APARTMENT = 1;

    /**
     *
     * @param int $reservationId
     * @param int $issueTypeId
     * @return boolean
     */
    public function createIssue($reservationId, $issueTypeId, $entityType = null, $entityId = null)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             */
            $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

            if (!($this->getReservationIssues($reservationId, $issueTypeId))) {
                $reservationIssuesDao->save([
                    'reservation_id'    => $reservationId,
                    'issue_type_id'     => $issueTypeId,
                    'date_of_detection' => date('Y-m-d H:i:s'),
                    'entity_type'       => $entityType,
                    'entity_id'         => $entityId,
                ]);
            }
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     *
     * @param int $id
     * @return boolean
     */
    public function deleteIssue($id)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             */
            $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

            if ($this->getIssue($id)) {
                $reservationIssuesDao->delete([
                    'id' => $id
                ]);
                return TRUE;
            }

            return FALSE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    public function resolveReservationIssueByType($resId, $type = self::ISSUE_CCN_IS_MISSING)
    {
        /**
         * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
         */
        $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

        $reservationIssuesDao->delete([
            'reservation_id' => $resId,
            'issue_type_id'  => $type
        ]);
    }

    /**
     *
     * @param int $id
     * @param boolean $forceResolve
     * @return boolean
     */
    public function resolveIssue($id, $forceResolve = FALSE)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             * @var \DDD\Dao\Booking\Booking $bookingDao
             */
            $reservationIssuesDao   = $this->getServiceLocator()->get('dao_booking_reservation_issues');
            $bookingDao             = $this->getServiceLocator()->get('dao_booking_booking');

            $bookingDao->setEntity(new \ArrayObject());

            $issueData = $reservationIssuesDao->getIssueById($id);

            $reservationData = $bookingDao->fetchOne(['id' => $issueData->getReservationId()], ['guest_email']);

            if(!$reservationData)
                return false;

            switch ($issueData->getIssueTypeId()) {
                case EmailAddressIssues::ISSUE_TYPE_MISSING:
                    if ($forceResolve || $this->validateBookerEmail($reservationData['guest_email'])) {
                        $this->deleteIssue($id);
                        return TRUE;
                    }
                    break;
                case EmailAddressIssues::ISSUE_TYPE_INVALID:
                    if ($forceResolve || $this->validateBookerEmail($reservationData['guest_email'])) {
                        $this->deleteIssue($id);
                        return TRUE;
                    }
                    break;
                case EmailAddressIssues::ISSUE_TYPE_TEMPORARY:
                    if ($forceResolve || $this->validateBookerEmail($reservationData['guest_email'])) {
                        $this->deleteIssue($id);
                        return TRUE;
                    }
                    break;
                case self::ISSUE_CCN_IS_MISSING:
                case self::ISSUE_CCN_EXPIRATION_DATE_IS_MISSING:
//                default :
//                    $this->deleteIssue($id);
//                    return TRUE;
            }

            return FALSE;
        } catch (\Exception $e) {
            //return $e->getMessage();
            return FALSE;
        }
    }

    /**
     *
     * @param int $reservationId
     */
    public function resolveReservationAllIssues(
            $reservationId,
            $forceResolve = FALSE,
            $issuesList = [])
    {
        $reservationIssues = $this->getReservationIssues($reservationId);

        foreach ($reservationIssues as $issue) {
            if (empty($issuesList)) {
                $this->resolveIssue($issue->getId(), $forceResolve);
            } elseif (in_array($issue->getIssueTypeId(), $issuesList)) {
                $this->resolveIssue($issue->getId(), $forceResolve);
            }
        }
    }

    /**
     *
     * @param int $id
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getIssue($id)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             */
            $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

            return $reservationIssuesDao->getIssueById($id);

        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     *
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getAllIssues($filter = false)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             */
            $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

            return $reservationIssuesDao->getAllIssues($filter);

        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @return int
     */
    public function getAllIssuesCount($filter = false)
    {
        /**
         * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
         */
        $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

        return $reservationIssuesDao->getAllIssuesCount($filter);
    }

    /**
     * @param bool|false $filter
     * @return bool
     */
    public function getAllIssuesAndLessThan9DayFromTodayOrbitzAgoda($filter = false)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             */
            $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

            return $reservationIssuesDao->getAllIssuesAndLessThan9DayFromTodayOrbitz($filter);

        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @return int
     */
    public function getAllIssuesAndLessThan9DayFromTodayOrbitzAgodaCount($filter = false)
    {
        /**
         * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
         */
        $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

        return $reservationIssuesDao->getAllIssuesAndLessThan9DayFromTodayOrbitzCount($filter);
    }

    /**
     *
     * @return Array
     */
    public function getIssuesGrouppedByReservationId()
    {
        try {
            $issues = $this->getAllIssues();
            $issuesByReservation = [];
            foreach ($issues as $issue) {
                $issuesByReservation[$issue->getReservationId()][] = [
                    'id' => $issue->getId(),
                    'reservation_number' => $issue->getReservationNumber(),
                    'issue_type' => $issue->getIssueTypeId(),
                    'issue_title' => $issue->getTitle(),
                    'date_of_detection' => $issue->getDateOfDetection(),
                ];
            }

            return $issuesByReservation;

        } catch (Exception $ex) {
            return FALSE;
        }
    }

    /**
     * @param $reservationId
     * @param bool|FALSE $type
     * @return bool|\DDD\Domain\Booking\ReservationIssues
     */
    public function getReservationIssues($reservationId, $type = FALSE)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\ReservationIssues $reservationIssuesDao
             */
            $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

            return $reservationIssuesDao->getIssuesByReservationId($reservationId, $type);

        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param int $reservationId
     * @param array $data ['cc_not_provided', 'cc_date']
     * @return bool
     */
    public function checkReservationIssues($reservationId, array $data = [])
    {
        try {
            /**
             * @var \DDD\Dao\Booking\Booking $bookingDao
             */
            $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
            $bookingDao->setEntity(new \ArrayObject());

            if (in_array($reservationId, $this->getIgnoredApartmentsList())) {
                return FALSE;
            }

            $reservationData = $bookingDao->fetchOne(['id' => $reservationId], [
                'payment_settled',
                'funds_confirmed',
                'model',
                'status',
                'guest_email',
                'date_to',
            ]);

            if (!$reservationData) {
                return FALSE;
            }

            if ($reservationData['payment_settled'] == 1) {
                $this->resolveReservationAllIssues($reservationId, TRUE);
                return FALSE;
            }

            $validateCC = TRUE;
            if ($reservationData['funds_confirmed'] != BookingTicket::CC_STATUS_UNKNOWN) {
                $resolvableIssuesList = $this->getCCIssuesList(self::CC_LIST_MODE_SETTLED);
                $this->resolveReservationAllIssues($reservationId, TRUE, $resolvableIssuesList);
                $validateCC = FALSE;
            }

            if ($reservationData['model'] == BookingTicket::MODEL_PARTNER) {
                $resolvableIssuesList = $this->getCCIssuesList(self::CC_LIST_MODE_PARTNER);
                $this->resolveReservationAllIssues($reservationId, TRUE, $resolvableIssuesList);
                $validateCC = FALSE;
            }

            $emailIssueIsValid = $this->validateBookerEmail($reservationData['guest_email'], $reservationId);
            $reservationIssues = $this->getReservationIssues($reservationId);

            foreach ($reservationIssues as $issue) {
                if ($emailIssueIsValid && in_array($issue->getIssueTypeId(), $this->getEmailIssuesList())) {
                    $this->resolveIssue($issue->getId());
                }
            }

            $exclusionStatuses = [
                Booking::BOOKING_STATUS_BOOKED,
                Booking::BOOKING_STATUS_CANCELLED_PENDING
            ];

            if (!in_array($reservationData['status'], $exclusionStatuses)) {
                $resolvableIssuesList = $this->getCCIssuesList();
                $this->resolveReservationAllIssues($reservationId, TRUE, $resolvableIssuesList);
                $validateCC = FALSE;
            }

            if (!in_array($reservationData['status'], $exclusionStatuses)) {
                return FALSE;
            }

            if ($validateCC && !empty($data)) {
                if (isset($data['clear_old_cc_issue']) && $data['clear_old_cc_issue'] == true) {
                    $resolvableIssuesList = $this->getCCIssuesList(self::CC_LIST_FOR_CLEAR);
                    $this->resolveReservationAllIssues($reservationId, false, $resolvableIssuesList);
                }

                if (isset($data['cc_provided']) && $data['cc_provided'] == false) {
                    $this->createIssue($reservationId, self::ISSUE_CCN_IS_MISSING);
                } elseif(isset($data['cc_date']) && $data['cc_date']) {
                    $this->validateBookerCCExpirationDateRelativelyDayOfDeparture(
                        $data['cc_date'],
                        $reservationData['date_to'],
                        $reservationId
                    );
                }
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * @param $email
     * @param bool|FALSE $reservationId
     * @return bool
     */
    public function validateBookerEmail($email, $reservationId = FALSE)
    {
        try {
            $emailDetector = new EmailAddressIssues($reservationId);

            $emailDetector->setEmail($email);
            $emailDetector->detectIssues();

            if ($detectedIssues = $emailDetector->getIssues()) {
                foreach ($detectedIssues as $issue) {
                    $reservationCurrentIssues = $this->getReservationIssues($reservationId);

                    if ($reservationCurrentIssues->count()) {
                        foreach ($reservationCurrentIssues as $currentIssue) {
                            if (in_array($currentIssue->getIssueTypeId(), $this->getEmailIssuesList())
                                &&
                                $issue != $currentIssue->getIssueTypeId()) {

                                    // Resolve old issue
                                    $this->resolveIssue($currentIssue->getId(), TRUE);
                            }
                        }

                    }

                    // Create issue
                     $this->createIssue($reservationId, $issue);

                    break;
                }

                return FALSE;
            }

            return TRUE;
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param string $ccn
     * @param int $reservationId
     * @return boolean
     */
    public function validateBookerCreditCard($ccn, $reservationId = FALSE, $createIssue = FALSE)
    {
        try {
            $result = TRUE;
            $issueType = NULL;

            if (empty($ccn)) {
                $result = FALSE;
                $issueType = self::ISSUE_CCN_IS_MISSING;
            } elseif ($this->checkTestCreditCard($ccn)) {
                $result = FALSE;
                $issueType = self::ISSUE_CCN_IS_TEST;
            }

            if ($reservationId) {
                $missingCCIssue = $this->getReservationIssues($reservationId, self::ISSUE_CCN_IS_MISSING);
            }

            if (!$result && $reservationId) {
                if ($issueType !== self::ISSUE_CCN_IS_MISSING && $missingCCIssue) {
                    $this->resolveIssue($missingCCIssue->getId());
                }
                if ($createIssue) {
                    $this->createIssue($reservationId, $issueType);
                }
            }

            return $result;
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param string $holderName
     * @param int $reservationId
     * @return boolean
     */
    public function validateBookerCCHolderName($holderName, $reservationId = FALSE)
    {
        try {
            $result = TRUE;
            $issueType = NULL;

            if (empty($holderName)) {
                $result = FALSE;
                $issueType = self::ISSUE_CCN_HOLDER_NAME_IS_MISSING;
            }

            if (!$result && $reservationId) {
                $this->createIssue($reservationId, $issueType);
            }

            return $result;
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param string $ccExpirationDate date('Y-m')
     * @param int $reservationId
     * @return boolean
     */
    public function validateBookerCCExpirationDate(
            $ccExpirationDate,
            $reservationId = FALSE)
    {
        try {
            $result = TRUE;
            $issueType = NULL;

            if (empty($ccExpirationDate)) {
                $result = FALSE;
                $issueType = self::ISSUE_CCN_EXPIRATION_DATE_IS_MISSING;
            } else {
                $today = new \DateTime(date('Y-m'));
                $ccExpirationDate = new \DateTime($ccExpirationDate);
                $intervalToday   = $today->diff($ccExpirationDate);

                if ($intervalToday->format('%r%a') < -30) {
                    $result = FALSE;
                    $issueType = self::ISSUE_CCN_DATE_HAS_EXPIRED;
                }
            }

            if (!$result && $reservationId) {
                $this->createIssue($reservationId, $issueType);
            }

            return $result;
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param string $ccExpirationDate date('Y-m')
     * @param string $departureDate - date('Y-m-d')
     * @param int $reservationId
     * @return boolean
     */
    public function validateBookerCCExpirationDateRelativelyDayOfDeparture(
            $ccExpirationDate,
            $departureDate = FALSE,
            $reservationId = FALSE)
    {
        try {
            $result = TRUE;
            $issueType = NULL;

            if (!empty($ccExpirationDate) && $departureDate) {
                $ccExpirationDate = new \DateTime($ccExpirationDate);
                $ticketDepatrureDate = new \DateTime($departureDate);
                $intervalDepartureDay   = $ticketDepatrureDate->diff($ccExpirationDate);

                if ($intervalDepartureDay->format('%r%a') < -30) {
                    $result = FALSE;
                    $issueType = self::ISSUE_CCN_DATE_WILL_BE_EXPIRED;
                }
            }

            if (!$result && $reservationId) {
                $this->createIssue($reservationId, $issueType);
            }

            return $result;
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param string $ccn
     * @return boolean
     */
    private function checkTestCreditCard($ccn)
    {
        try {
            $testCreditCardNumbers = [
                '378282246310005',
                '371449635398431',
                '378734493671000',
                '5610591081018250',
                '30569309025904',
                '38520000023237',
                '6011111111111117',
                '6011000990139424',
                '3530111333300000',
                '3566002020360505',
                '5555555555554444',
                '5105105105105100',
                '4111111111111111',
                '4012888888881881',
                '4222222222222',
                '76009244561',
                '5019717010103742',
                '6331101999990016'
            ];

            if (in_array($ccn, $testCreditCardNumbers)) {
                return TRUE;
            }

            return FALSE;
        } catch (\Exception $e) {

        }
    }

    /**
     * @return array
     */
    private function getIgnoredApartmentsList()
    {
        return [
            Constants::TEST_APARTMENT_1,
            Constants::TEST_APARTMENT_2
        ];
    }

    private function getEmailIssuesList()
    {
        $emailIssues = [
            EmailAddressIssues::ISSUE_TYPE_MISSING,
            EmailAddressIssues::ISSUE_TYPE_INVALID,
            EmailAddressIssues::ISSUE_TYPE_TEMPORARY
        ];

        return $emailIssues;
    }


    private function getCCIssuesList($mode = self::CC_LIST_ALL)
    {
        switch ($mode) {
            case self::CC_LIST_ALL:
                $result = [
                    self::ISSUE_CCN_DATE_HAS_EXPIRED,
                    self::ISSUE_CCN_DATE_WILL_BE_EXPIRED,
                    self::ISSUE_CCN_HOLDER_NAME_IS_MISSING,
                    self::ISSUE_CCN_IS_INVALID,
                    self::ISSUE_CCN_IS_MISSING,
                    self::ISSUE_CCN_IS_TEST,
                    self::ISSUE_CCN_EXPIRATION_DATE_IS_MISSING,
                ];
                break;

            case self::CC_LIST_MODE_SETTLED:
                $result = [
                    self::ISSUE_CCN_DATE_HAS_EXPIRED,
                    self::ISSUE_CCN_DATE_WILL_BE_EXPIRED,
                    self::ISSUE_CCN_HOLDER_NAME_IS_MISSING,
                    self::ISSUE_CCN_IS_INVALID,
                    self::ISSUE_CCN_IS_MISSING,
                    self::ISSUE_CCN_IS_TEST,
                ];
                break;

            case self::CC_LIST_MODE_PARTNER:
                $result = [
                    self::ISSUE_CCN_DATE_HAS_EXPIRED,
                    self::ISSUE_CCN_DATE_WILL_BE_EXPIRED,
                    self::ISSUE_CCN_HOLDER_NAME_IS_MISSING,
                    self::ISSUE_CCN_IS_INVALID,
                    self::ISSUE_CCN_IS_MISSING,
                    self::ISSUE_CCN_IS_TEST,
                    self::ISSUE_CCN_EXPIRATION_DATE_IS_MISSING,
                ];
                break;
            case self::CC_LIST_FOR_CLEAR:
                $result = [
                    self::ISSUE_CCN_IS_MISSING,
                    self::ISSUE_CCN_EXPIRATION_DATE_IS_MISSING,
                ];
                break;
        }

        return $result;
    }

    public function issueForOccupanyReservation($apartmentId)
    {
        $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');

        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
        $reservation    = $reservationDao->getGreaterOccupancyRes($apartmentId);

        $reservationIssuesDao->delete(
            [
                'entity_type' => self::ENTITY_TYPE_APARTMENT,
                'entity_id'   => $apartmentId
            ]
        );

        foreach ($reservation as $reservation) {
            $this->createIssue(
                $reservation['id'],
                self::ISSUE_APARTMENT_OCUPANCY_REDUCED,
                self::ENTITY_TYPE_APARTMENT,
                $apartmentId
            );
        }
    }

    public function getChangedOccupancyReservation()
    {
        $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');
        return $reservationIssuesDao->getGreaterOccupancyResIssues();
    }

    public function getChangedOccupancyReservationCount()
    {
        $reservationIssuesDao = $this->getServiceLocator()->get('dao_booking_reservation_issues');
        return $reservationIssuesDao->getGreaterOccupancyResIssuesCount();
    }
}
