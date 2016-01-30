<?php

namespace DDD\Domain\Partners;

class PartnerBooking
{
    protected $gid;
    protected $partner_name;
    protected $commission;
    protected $business_model;

    public function exchangeArray($data)
    {
        $this->gid = (isset($data['gid']))             ? $data['gid'] : null;
        $this->partner_name = (isset($data['partner_name']))    ? $data['partner_name'] : null;
        $this->commission = (isset($data['commission']))    ? $data['commission'] : null;
        $this->business_model = (isset($data['business_model'])) ? $data['business_model'] : null;
    }
    
    public function getCommission(){
        return $this->commission;
    }
    
    public function getGid(){
        return $this->gid;
    }
    
    public function getPartnerName(){
        return $this->partner_name;
    }

    public function getBusinessModel(){
        return $this->business_model;
    }

    /**
     * @param mixed $business_model
     */
    public function setBusinessModel($business_model)
    {
        $this->business_model = $business_model;
    }

    /**
     * @param mixed $commission
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;
    }

    /**
     * @param mixed $gid
     */
    public function setGid($gid)
    {
        $this->gid = $gid;
    }

    /**
     * @param mixed $partner_name
     */
    public function setPartnerName($partner_name)
    {
        $this->partner_name = $partner_name;
    }


}