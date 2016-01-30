<?php

namespace Library\ReservationIssues;

use DDD\Domain\Booking\ReservationIssues;

/**
 * Class ReservationIssueDetectorBase
 * @package Library\ReservationIssues
 */
abstract class ReservationIssueDetectorBase implements ReservationIssueDetectorInterface
{
    /**
     * @var int
     */
    protected $reservationId;

    /**
     * @var ReservationIssues[]
     */
    protected $detectedIssues = [];

    /**
     * @param int $reservationId
     * @param string $reservationNumber
     */
    public function __construct($reservationId)
    {
        $this->reservationId = $reservationId;
    }

    /**
     * Returns an array of detected issues.
     *
     * @return array
     */
    public function getIssues()
    {
        return $this->detectedIssues;
    }

    /**
     *
     * @param int $issueType
     * @return Array
     */
    public function addIssue($issueType)
    {
        $this->detectedIssues = array_merge(
            $this->detectedIssues,
            [
                $this->reservationId => $issueType
            ]
        );

        return $this->detectedIssues;
    }

    /**
     * @param string $issueKey
     * @param int $id
     * @param int $typeId
     * @param string $date
     * @param string $title
     * @param string $description
     */
    protected function issue($issueKey, $id, $typeId, $date, $title, $description)
    {
        $issue = new ReservationIssues();

        $issue->setId($id);
        $issue->setIssueTypeId($typeId);
        $issue->setReservationId($this->reservationId);
        $issue->setReservationNumber($this->reservationNumber);
        $issue->setDateOfDetection($date);
        $issue->setTitle($title);
        $issue->setDescription($description);

        $this->detectedIssues[$issueKey] = $issue;
    }
}