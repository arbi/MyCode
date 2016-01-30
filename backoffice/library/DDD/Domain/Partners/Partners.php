<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Partners
 *
 * @author developer
 */
namespace DDD\Domain\Partners;

class Partners
{
    protected $gid;
    protected $partner_name;

    /**
     * Business model. Merchant or Invoice.
     * @var int
     */
    protected $businessModel;

    protected $contact_name;
    protected $email;
    protected $password;
    protected $mobile;
    protected $phone;
    protected $commission;
    protected $account_holder_name;
    protected $bank_bsr;
    protected $bank_account_num;
    protected $notes;
    protected $customer_email;
    protected $img;
    protected $create_date;
    protected $active;
    protected $is_ota;
    protected $discount;
    protected $showPartner;
    protected $applyFuzzyLogic;
    protected $isDeductedCommission;
    protected $additionalTaxCommission;

    public function exchangeArray($data)
    {
        $this->gid                     = (isset($data['gid'])) ? $data['gid'] : null;
        $this->partner_name            = (isset($data['partner_name'])) ? $data['partner_name'] : null;
        $this->businessModel           = (isset($data['business_model'])) ? $data['business_model'] : null;
        $this->contact_name            = (isset($data['contact_name'])) ? $data['contact_name'] : null;
        $this->email                   = (isset($data['email'])) ? $data['email'] : null;
        $this->password                = (isset($data['password'])) ? $data['password'] : null;
        $this->mobile                  = (isset($data['mobile'])) ? $data['mobile'] : null;
        $this->phone                   = (isset($data['phone'])) ? $data['phone'] : null;
        $this->commission              = (isset($data['commission'])) ? $data['commission'] : null;
        $this->account_holder_name     = (isset($data['account_holder_name'])) ? $data['account_holder_name'] : null;
        $this->bank_bsr                = (isset($data['bank_bsr'])) ? $data['bank_bsr'] : null;
        $this->bank_account_num        = (isset($data['bank_account_num'])) ? $data['bank_account_num'] : null;
        $this->notes                   = (isset($data['notes'])) ? $data['notes'] : null;
        $this->customer_email          = (isset($data['customer_email'])) ? $data['customer_email'] : null;
        $this->img                     = (isset($data['img'])) ? $data['img'] : null;
        $this->create_date             = (isset($data['create_date'])) ? $data['create_date'] : null;
        $this->active                  = (isset($data['active'])) ? $data['active'] : null;
        $this->is_ota                  = (isset($data['is_ota'])) ? $data['is_ota'] : null;
        $this->discount                = (isset($data['discount'])) ? $data['discount'] : null;
        $this->showPartner             = (isset($data['show_partner'])) ? $data['show_partner'] : null;
        $this->applyFuzzyLogic         = (isset($data['apply_fuzzy_logic'])) ? $data['apply_fuzzy_logic'] : null;
        $this->isDeductedCommission    = (isset($data['is_deducted_commission'])) ? $data['is_deducted_commission'] : null;
        $this->additionalTaxCommission = (isset($data['additional_tax_commission'])) ? $data['additional_tax_commission'] : null;
    }

    public function getIsOta()
    {
        return $this->is_ota;
    }

    public function getGid()
    {
        return $this->gid;
    }

    public function setGid($val)
    {
        $this->gid = $val;
    }

    public function getPartnerName()
    {
        return $this->partner_name;
    }

    public function setPartnerName($val)
    {
        $this->partner_name = $val;
    }

    /**
     * @return number
     */
    public function getBusinessModel()
    {
        return $this->businessModel;
    }

    public function getContactName()
    {
        return $this->contact_name;
    }

    public function setContactName($val)
    {
        $this->contact_name = $val;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($val)
    {
        $this->email = $val;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($val)
    {
        $this->password = $val;
    }

    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobile($val)
    {
        $this->mobile = $val;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($val)
    {
        $this->phone = $val;
    }

    public function getCommission()
    {
        return $this->commission;
    }

    public function setCommission($val)
    {
        $this->commission = $val;
    }

    public function getAccHolderName()
    {
        return $this->account_holder_name;
    }

    public function setAccHolderName($val)
    {
        $this->account_holder_name = $val;
    }

    public function getBankBsr()
    {
        return $this->bank_bsr;
    }

    public function setBankBsr($val)
    {
        $this->bank_bsr = $val;
    }

    public function getBankAccNum()
    {
        return $this->bank_account_num;
    }

    public function setBankAccNum($val)
    {
        $this->bank_account_num = $val;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($val)
    {
        $this->notes = $val;
    }

    public function getCustomerEmail()
    {
        return $this->customer_email;
    }

    public function setCustomerEmail($val)
    {
        $this->customer_email = $val;
    }

    public function getImg()
    {
        return $this->img;
    }

    public function setImg($val)
    {
        $this->img = $val;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setCreateDate($val)
    {
        $this->create_date = $val;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($val)
    {
        $this->active = $val;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function setShowPartner($showPartner)
    {
        $this->showPartner = $showPartner;
    }

    public function getShowPartner()
    {
        return $this->showPartner;
    }

    public function getApplyFuzzyLogic()
    {
        return $this->applyFuzzyLogic;
    }

    public function getIsDeductedCommission()
    {
        return $this->isDeductedCommission;
    }

    /**
     * @return mixed
     */
    public function hasAdditionalTaxCommission()
    {
        return $this->additionalTaxCommission;
    }
}
