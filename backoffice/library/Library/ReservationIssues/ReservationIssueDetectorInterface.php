<?php

namespace Library\ReservationIssues;

/**
 * Interface ReservationIssueDetectorInterface
 * @package Library\ReservationIssues
 *
 * @author Tigran Petrosyan
 */
interface ReservationIssueDetectorInterface
{
    /**
     * Returns true if and only if issue detector algorithm found any, and
     * getIssues() will return an array of issues.
     *
     * @param  mixed $object
     * @return bool
     */
    public function detectIssues();

    /**
     * Returns an array of issues which have descriptions that explain why the most recent detectIssues()
     * call returned true. The array keys are issue identifiers,
     * and the array values are the ReservationIssues objects with corresponding human-readable description strings.
     *
     * If detectIssues() was never called or if the most recent detectIssues() call
     * returned false, then this method returns an empty array.
     *
     * @return array
     */
    public function getIssues();
}
