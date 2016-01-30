<?php

namespace Library\ReservationIssues\Detector;

use Library\ReservationIssues\ReservationIssueDetectorBase;
use Zend\Validator\EmailAddress;

/**
 * Class EmailAddressIssues
 * @package Library\ReservationIssues\Detector
 */
class EmailAddressIssues extends ReservationIssueDetectorBase
{
    protected $email;
    protected $issue = FALSE;
    protected $issueType;

    protected $temporaryEmails = [
        'reservations@ginosi.com',
        'resteam@ginosi.com'
    ];

    protected $missingEmails = [
        'no-email@ginosi.com',
        'noemail@ginosi.com',
        'na@ginosi.com'
    ];

    const MISSING     = 'emailAddressMissing';
    const INVALID     = 'emailAddressInvalid';
    const TEMPORARY   = 'emailAddressTemporary';

    const ISSUE_TYPE_MISSING = 1;
    const ISSUE_TYPE_INVALID = 2;
    const ISSUE_TYPE_TEMPORARY = 10;

    /**
     * Returns true if and only if issue detector algorithm found any, and
     * getIssues() will return an array of issues.
     *
     * @return boolean
     */
    public function detectIssues()
    {
        try {
            $emailValidator = new EmailAddress(['domain' => TRUE]);

            if (empty($this->email)) {
                $this->issueType = self::ISSUE_TYPE_MISSING;
            } elseif (!$emailValidator->isValid($this->email)) {
                $this->issueType = self::ISSUE_TYPE_INVALID;
            } elseif($this->checkTemporaryEmail()) {
                $this->issueType = self::ISSUE_TYPE_TEMPORARY;
            } elseif($this->checkMissingEmail()) {
                $this->issueType = self::ISSUE_TYPE_MISSING;
            }

            if (!empty($this->issueType)) {
                $this->issue = TRUE;
                $this->addIssue($this->issueType);
            }

            return $this->issue;
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param string $email
     * @return boolean
     */
    public function setEmail($email = NULL)
    {
        if (!empty($email)) {
            $this->email = $email;
        }

        return $this->email;
    }

    /**
     * @return bool
     */
    private function checkTemporaryEmail()
    {
        if (in_array($this->email, $this->temporaryEmails)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function checkMissingEmail()
    {
        if (in_array($this->email, $this->missingEmails)) {
            return true;
        }

        return false;
    }
}