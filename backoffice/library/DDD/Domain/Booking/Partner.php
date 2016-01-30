<?php

namespace DDD\Domain\Booking;

class Partner {

    protected $id;
    protected $businessModel;
    protected $commission;
    protected $cubilis_partner_id;
    protected $partner_name;

    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->commission = (isset($data['commission'])) ? $data['commission'] : null;
        $this->businessModel = (isset($data['business_model'])) ? $data['business_model'] : null;
        $this->cubilis_partner_id = (isset($data['cubilis_partner_id'])) ? $data['cubilis_partner_id'] : null;
        $this->partner_name = (isset($data['partner_name'])) ? $data['partner_name'] : null;
    }

    public function getId() {
        return $this->id;
    }

	public function getCommission() {
		return $this->commission;
	}

	public function getBusinessModel() {
		return $this->businessModel;
	}
	
	public function getCubilisPartnerId() {
		return $this->cubilis_partner_id;
	}
	
	public function getPartnerName() {
		return $this->partner_name;
	}

    /**
     * @param mixed $businessModel
     */
    public function setBusinessModel($businessModel)
    {
        $this->businessModel = $businessModel;
    }

    /**
     * @param mixed $commission
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;
    }

    /**
     * @param mixed $cubilis_partner_id
     */
    public function setCubilisPartnerId($cubilis_partner_id)
    {
        $this->cubilis_partner_id = $cubilis_partner_id;
    }

    /**
     * @param mixed $partner_name
     */
    public function setPartnerName($partner_name)
    {
        $this->partner_name = $partner_name;
    }


}
