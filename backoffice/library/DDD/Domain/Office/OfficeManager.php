<?php

namespace DDD\Domain\Office;

use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Objects;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class OfficeManager
{
    protected $id;
    protected $name;
    protected $description;
    protected $officeManagerId;
    protected $itManagerId;
    protected $financeManagerId;
    protected $address;
    protected $countryId;
    protected $provinceId;
    protected $cityId;
    protected $city;
    protected $country;
    protected $createdDate;
    protected $modifiedDate;
    protected $disable;
    protected $sections;
    protected $phone;
    protected $receptionEntryTextline;
    protected $mapAttachment;

    public function exchangeArray($data)
    {
        $this->id                       = (isset($data['id'])) ? $data['id'] : null;
        $this->name                     = (isset($data['name'])) ? $data['name'] : null;
        $this->description              = (isset($data['description'])) ? $data['description'] : null;
        $this->address                  = (isset($data['address'])) ? $data['address'] : null;
        $this->officeManagerId          = (isset($data['office_manager_id'])) ? $data['office_manager_id'] : null;
        $this->itManagerId              = (isset($data['it_manager_id'])) ? $data['it_manager_id'] : null;
        $this->financeManagerId         = (isset($data['finance_manager_id'])) ? $data['finance_manager_id'] : null;
        $this->countryId                = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->provinceId               = (isset($data['province_id'])) ? $data['province_id'] : null;
        $this->cityId                   = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->createdDate              = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->country                  = (isset($data['country'])) ? $data['country'] : null;
        $this->city                     = (isset($data['city'])) ? $data['city'] : null;
        $this->modifiedDate             = (isset($data['modified_date'])) ? $data['modified_date'] : null;
        $this->disable                  = (isset($data['disable'])) ? $data['disable'] : null;
        $this->sections                 = (isset($data['sections']) && isset($data['section_id'])) ? $data['sections'] : 0;
        $this->phone                    = isset($data['phone']) ? $data['phone'] : 0;
        $this->receptionEntryTextline   = isset($data['textline_id']) ? $data['textline_id'] : 0;
        $this->mapAttachment            = isset($data['map_attachment']) ? $data['map_attachment'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getOfficeManagerId()
    {
        return $this->officeManagerId;
    }

    public function getItManagerId()
    {
        return $this->itManagerId;
    }

    public function getFinanceManagerId()
    {
        return $this->financeManagerId;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getCountryId()
    {
        return $this->countryId;
    }

    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
        return $this;
    }
    public function getCityId()
    {
        return $this->cityId;
    }

    public function setCityId($cityId)
    {
        $this->cityId = $cityId;
        return $this;
    }
    public function getProvinceId()
    {
        return $this->provinceId;
    }

    public function setProvinceId($provinceId)
    {
        $this->provinceId = $provinceId;
        return $this;
    }
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;
        return $this;
    }

    public function getDisable()
    {
        return $this->disable;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setDisable($disable)
    {
        $this->disable = $disable;
        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getReceptionEntryTextline()
    {
        return $this->receptionEntryTextline;
    }

    /**
     * @return mixed
     */
    public function getMapAttachment()
    {
        return $this->mapAttachment;
    }
}
