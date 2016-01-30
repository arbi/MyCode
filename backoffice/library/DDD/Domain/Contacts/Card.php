<?php

namespace DDD\Domain\Contacts;


class Card
{
    protected $id;
    protected $creatorId;
    protected $dateCreated;
    protected $dateModified;
    protected $teamId;
    protected $scope;
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
    protected $phoneMobileCountryCode;
    protected $phoneMobile;
    protected $phoneCompanyCountryCode;
    protected $phoneCompany;
    protected $phoneOtherCountryCode;
    protected $phoneOther;
    protected $phoneFaxCountryCode;
    protected $phoneFax;
    protected $notes;

    protected $teamName;
    protected $apartmentName;
    protected $buildingName;
    protected $partnerName;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id']) ? $data['id'] : null);
        $this->creatorId    = (isset($data['creator_id']) ? $data['creator_id'] : null);
        $this->dateCreated  = (isset($data['date_created']) ? $data['date_created'] : null);
        $this->dateModified = (isset($data['date_modified']) ? $data['date_modified'] : null);
        $this->scope        = (isset($data['scope']) ? $data['scope'] : null);
        $this->teamId       = (isset($data['team_id']) ? $data['team_id'] : null);
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
        $this->phoneMobileCountryCode   = (isset($data['phone_mobile_country_code']) ? $data['phone_mobile_country_code'] : null);
        $this->phoneMobile              = (isset($data['phone_mobile']) ? $data['phone_mobile'] : null);
        $this->phoneCompanyCountryCode  = (isset($data['phone_company_country_code']) ? $data['phone_company_country_code'] : null);
        $this->phoneCompany             = (isset($data['phone_company']) ? $data['phone_company'] : null);
        $this->phoneOtherCountryCode    = (isset($data['phone_other_country_code']) ? $data['phone_other_country_code'] : null);
        $this->phoneOther               = (isset($data['phone_other']) ? $data['phone_other'] : null);
        $this->phoneFaxCountryCode      = (isset($data['phone_fax_country_code']) ? $data['phone_fax_country_code'] : null);
        $this->phoneFax                 = (isset($data['phone_fax']) ? $data['phone_fax'] : null);
        $this->notes        = (isset($data['notes']) ? $data['notes'] : null);

        $this->teamName         = (isset($data['team_name']) ? $data['team_name'] : null);
        $this->apartmentName    = (isset($data['apartment_name']) ? $data['apartment_name'] : null);
        $this->buildingName     = (isset($data['building_name']) ? $data['building_name'] : null);
        $this->partnerName      = (isset($data['partner_name']) ? $data['partner_name'] : null);
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

    public function getPhoneMobileCountryCode()
    {
        return $this->phoneMobileCountryCode;
    }

    public function getPhoneMobile()
    {
        return $this->phoneMobile;
    }

    public function getPhoneCompanyCountryCode()
    {
        return $this->phoneCompanyCountryCode;
    }

    public function getPhoneCompany()
    {
        return $this->phoneCompany;
    }

    public function getPhoneOtherCountryCode()
    {
        return $this->phoneOtherCountryCode;
    }

    public function getPhoneOther()
    {
        return $this->phoneOther;
    }

    public function getPhoneFaxCountryCode()
    {
        return $this->phoneFaxCountryCode;
    }

    public function getPhoneFax()
    {
        return $this->phoneFax;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function getTeamName()
    {
        return $this->teamName;
    }

    public function getApartmentName()
    {
        return $this->apartmentName;
    }

    public function getBuildingName()
    {
        return $this->buildingName;
    }

    public function getPartnerName()
    {
        return $this->partnerName;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }
}
