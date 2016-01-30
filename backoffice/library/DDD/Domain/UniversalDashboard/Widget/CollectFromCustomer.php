<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Collect From Customer" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class CollectFromCustomer {
    /**
     * Unique reservation number
     * @var string
     */
    protected $resNumber;

    /**
     * Reservation status
     * @var int
     */
    protected $status;

    /**
     * Arrival date
     * @access protected
     * @var string
     */
    protected $arrivalDate;

    /**
     * Apartment name
     * @var string
     */
    protected $apartmentName;

    /**
     * Guest first name
     * @var string
     */
    protected $guestFirstName;

    /**
     * Guest last name
     * @var string
     */
    protected $guestLastName;

    /**
	 * Shows wether the provided credit card is valid or not
     * @var boolean
     */
    protected $isValidCC;

    /**
     * @var boolean
     */
    protected $isWaitingForCCDetails;

    /**
     * Customer balance
     * @var float
     */
    protected $guestBalance;

    /**
     * Last reservation agent's full name
     * @var string
     */
    protected $lastAgent;

    /**
     * @var string
     */
    protected $symbol;

    /**
     * @var int
     */
    protected $pendingTransactionsAmount;

    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->resNumber = (isset($data['res_number'])) ?
            $data['res_number'] : null;
    	$this->status = (isset($data['status'])) ?
            $data['status'] : null;
        $this->arrivalDate = (isset($data['arrival_date'])) ?
            $data['arrival_date'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ?
            $data['apartment_name'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ?
            $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ?
            $data['guest_last_name'] : null;
        $this->isValidCC = (isset($data['is_cc_valid'])) ?
            $data['is_cc_valid'] : null;
        $this->isWaitingForCCDetails =
            (isset($data['is_waiting_for_cc_details'])) ?
            $data['is_waiting_for_cc_details'] : null;
        $this->guestBalance = (isset($data['guest_balance'])) ?
            $data['guest_balance'] : null;
        $this->lastAgent = (isset($data['last_agent_fullname'])) ?
            $data['last_agent_fullname'] : null;
        $this->symbol = (isset($data['symbol'])) ?
            $data['symbol'] : null;
        $this->pendingTransactionsAmount = (isset($data['pending_transactions_amount'])) ?
            $data['pending_transactions_amount'] : null;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
    	return $this->resNumber;
    }

    /**
     * @return number
     */
    public function getStatus()
    {
    	return $this->status;
    }

    /**
     * @return string
     */
    public function getArrivalDate()
    {
    	return $this->arrivalDate;
    }

    /**
     * @return string
     */
    public function getApartmentName()
    {
    	return $this->apartmentName;
    }

    /**
     * @return string
     */
    public function getGuestFullName()
    {
    	return $this->guestFirstName . ' ' . $this->guestLastName;
    }

    /**
     * @return boolean
     */
    public function isCreditCardValid()
    {
    	return $this->isValidCC;
    }

    /**
     * @return boolean
     */
    public function isWaitingForCCDetails()
    {
    	return $this->isWaitingForCCDetails;
    }

    /**
     * @return number
     */
    public function getGuestBalance()
    {
    	return $this->guestBalance;
    }

    /**
     * @return string
     */
    public function getLastAgentFullName()
    {
    	return $this->lastAgent;
    }

    /**
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @return int
     */
    public function getPendingTransactionsAmount()
    {
        return $this->pendingTransactionsAmount;
    }
}

?>
