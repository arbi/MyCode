<?php

namespace DDD\Domain\UniversalDashboard\Widget;

class PendingCancelation {
    protected $id;
    protected $res_number;
	protected $cancelation_date;
    protected $aff_name;
    protected $partner_ref;
    protected $guest_balance;
    protected $acc_name;
    protected $apartelId;
    protected $apartel;
    protected $symbol;

    public function exchangeArray($data) {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number       = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->cancelation_date = (isset($data['cancelation_date'])) ? $data['cancelation_date'] : null;
        $this->aff_name         = (isset($data['aff_name'])) ? $data['aff_name'] : null;
        $this->partner_ref      = (isset($data['partner_ref'])) ? $data['partner_ref'] : null;
        $this->guest_balance    = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->acc_name         = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->apartelId        = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->apartel          = (!empty($data['apartel']) ? $data['apartel'] : null);
        $this->symbol           = (isset($data['symbol'])) ? $data['symbol'] : null;
    }

	public function getApartmentName() {
		return $this->acc_name;
	}

	public function getAffName() {
		return $this->aff_name;
	}

	public function getPartnerRef() {
		return $this->partner_ref;
	}

	public function getCancelationDate() {
		return $this->cancelation_date;
	}

	public function getGuestBalance() {
		return $this->guest_balance;
	}

	public function getApartelId() {
		return $this->apartelId;
	}

	public function getApartel() {
		return ($this->apartel ? $this->apartel : (!empty($this->getApartelId()) ? 'Unknown' : 'Non Apartel'));
	}

	public function getResNumber() {
		return $this->res_number;
	}

	public function getSymbol() {
		return $this->symbol;
	}

    public function getId()
    {
        return $this->id;

    }
}
