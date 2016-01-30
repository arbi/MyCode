<?php

namespace DDD\Domain\Contacts;


class Contact
{
    protected $id;
    protected $creatorId;
    protected $dateCreated;
    protected $dateModified;
    protected $teamId;
    protected $apartmentId;
    protected $buildingId;
    protected $partnerId;
    protected $name;
    protected $company;
    protected $position;
    protected $city;
    protected $address;
    protected $email;
    protected $skype;
    protected $url;
    protected $phoneMobileCountryId;
    protected $phoneMobile;
    protected $phoneCompanyCountryId;
    protected $phoneCompany;
    protected $phoneOtherCountryId;
    protected $phoneOther;
    protected $phoneFaxCountryId;
    protected $phoneFax;
    protected $notes;
    protected $scope;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id']) ? $data['id'] : null);
        $this->creatorId    = (isset($data['creator_id']) ? $data['creator_id'] : null);
        $this->dateCreated  = (isset($data['date_created']) ? $data['date_created'] : null);
        $this->dateModified = (isset($data['date_modified']) ? $data['date_modified'] : null);
        $this->teamId       = (isset($data['team_id']) ? $data['team_id'] : null);
        $this->scope        = (isset($data['scope']) ? $data['scope'] : null);
        $this->apartmentId  = (isset($data['apartment_id']) ? $data['apartment_id'] : null);
        $this->buildingId   = (isset($data['building_id']) ? $data['building_id'] : null);
        $this->partnerId    = (isset($data['partner_id']) ? $data['partner_id'] : null);
        $this->name         = (isset($data['name']) ? $data['name'] : null);
        $this->company      = (isset($data['company']) ? $data['company'] : null);
        $this->position     = (isset($data['position']) ? $data['position'] : null);
        $this->city         = (isset($data['city']) ? $data['city'] : null);
        $this->address      = (isset($data['address']) ? $data['address'] : null);
        $this->email        = (isset($data['email']) ? $data['email'] : null);
        $this->skype        = (isset($data['skype']) ? $data['skype'] : null);
        $this->url          = (isset($data['url']) ? $data['url'] : null);
        $this->phoneMobileCountryId  = (isset($data['phone_mobile_country_id']) ? $data['phone_mobile_country_id'] : 0);
        $this->phoneMobile          = (isset($data['phone_mobile']) ? $data['phone_mobile'] : null);
        $this->phoneCompanyCountryId = (isset($data['phone_company_country_id']) ? $data['phone_company_country_id'] : 0);
        $this->phoneCompany         = (isset($data['phone_company']) ? $data['phone_company'] : null);
        $this->phoneOtherCountryId   = (isset($data['phone_other_country_id']) ? $data['phone_other_country_id'] : 0);
        $this->phoneOther           = (isset($data['phone_other']) ? $data['phone_other'] : null);
        $this->phoneFaxCountryId     = (isset($data['phone_fax_country_id']) ? $data['phone_fax_country_id'] : 0);
        $this->phoneFax     = (isset($data['phone_fax']) ? $data['phone_fax'] : null);
        $this->notes        = (isset($data['notes']) ? $data['notes'] : null);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCreatorId()
    {
        return $this->creatorId;
    }

    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    public function getDateModified()
    {
        return $this->dateModified;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    public function getBuildingId()
    {
        return $this->buildingId;
    }

    public function getPartnerId()
    {
        return $this->partnerId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getSkype()
    {
        return $this->skype;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getPhoneMobileCountryId()
    {
        return $this->phoneMobileCountryId;
    }

    public function getPhoneMobile()
    {
        return $this->phoneMobile;
    }

    public function getPhoneCompanyCountryId()
    {
        return $this->phoneCompanyCountryId;
    }


    public function getPhoneCompany()
    {
        return $this->phoneCompany;
    }

    public function getPhoneOtherCountryId()
    {
        return $this->phoneOtherCountryId;
    }

    public function getPhoneOther()
    {
        return $this->phoneOther;
    }

    public function getPhoneFaxCountryId()
    {
        return $this->phoneFaxCountryId;
    }

    public function getPhoneFax()
    {
        return $this->phoneFax;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }
}
