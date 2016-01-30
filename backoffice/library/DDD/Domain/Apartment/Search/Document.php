<?php

namespace DDD\Domain\Apartment\Search;

class Document
{

    protected $id;
    protected $typeId;
    protected $securityLevel;
    protected $apartmentId;
    protected $supplierId;
    protected $description;
    protected $url;
    protected $attachment;
    protected $createdDate;
    protected $typeName;
    protected $supplierName;
    protected $apartmentName;
    protected $buildingName;
    protected $cityName;
    protected $accountNumber;
    protected $accountHolder;
    protected $teamName;
    protected $validFrom;
    protected $validTo;
    protected $signatoryFirstName;
    protected $signatoryLastName;
    protected $legalEntityName;
    protected $managerId;

    public function exchangeArray($data)
    {
        $this->id                 = (isset($data['id'])) ? $data['id'] : null;
        $this->typeId             = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->securityLevel      = (isset($data['security_level'])) ? $data['security_level'] : null;
        $this->apartmentId        = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->supplierId         = (isset($data['supplier_id'])) ? $data['supplier_id'] : null;
        $this->description        = (isset($data['description'])) ? $data['description'] : null;
        $this->url                = (isset($data['url'])) ? $data['url'] : null;
        $this->attachment         = (isset($data['attachment'])) ? $data['attachment'] : null;
        $this->createdDate        = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->typeName           = (isset($data['type_name'])) ? $data['type_name'] : null;
        $this->supplierName       = (isset($data['supplier_name'])) ? $data['supplier_name'] : null;
        $this->apartmentName      = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->buildingName       = (isset($data['building_name'])) ? $data['building_name'] : null;
        $this->cityName           = (isset($data['city_name'])) ? $data['city_name'] : null;
        $this->accountNumber      = (isset($data['account_number'])) ? $data['account_number'] : null;
        $this->accountHolder      = (isset($data['account_holder'])) ? $data['account_holder'] : null;
        $this->teamName           = (isset($data['team_name'])) ? $data['team_name'] : null;
        $this->validFrom          = (isset($data['valid_from'])) ? $data['valid_from'] : null;
        $this->validTo            = (isset($data['valid_to'])) ? $data['valid_to'] : null;
        $this->signatoryFirstName = (isset($data['signatory_first_name'])) ? $data['signatory_first_name'] : null;
        $this->signatoryLastName  = (isset($data['signatory_last_name'])) ? $data['signatory_last_name'] : null;
        $this->legalEntityName    = (isset($data['legal_entity_name'])) ? $data['legal_entity_name'] : null;
        $this->managerId          = (isset($data['manager_id'])) ? $data['manager_id'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTypeId()
    {
        return $this->typeId;
    }

    public function getSecurityLevel()
    {
        return $this->securityLevel;
    }

    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    public function getSupplierId()
    {
        return $this->supplierId;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getAttachment()
    {
        return $this->attachment;
    }

    public function getDateCreated()
    {
        return $this->createdDate;
    }

    public function getTypeName()
    {
        return $this->typeName;
    }

    public function getSupplierName()
    {
        return $this->supplierName;
    }

    public function getApartmentName()
    {
        return $this->apartmentName;
    }

    public function getBuildingName()
    {
        return $this->buildingName;
    }

    public function getCityName()
    {
        return $this->cityName;
    }

    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    public function getTeamName()
    {
        return $this->teamName;
    }

    public function getValidFrom()
    {
        return $this->validFrom;
    }

    public function getValidTo()
    {
        return $this->validTo;
    }

    public function getSignatoryFullName()
    {
        return $this->signatoryFirstName . ' ' . $this->signatoryLastName;
    }

    public function getLegalEntityName()
    {
        return $this->legalEntityName;
    }

    public function getManagerId()
    {
        return $this->managerId;
    }

}
