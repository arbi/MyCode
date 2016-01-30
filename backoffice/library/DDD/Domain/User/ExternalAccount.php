<?php

namespace DDD\Domain\User;

/**
 * Class    ExternalAccount
 * @package DDD\Domain\User
 * @author  Harut Grigoryan
 */
class ExternalAccount
{
    protected $id;
    protected $transactionAccountId;
    protected $name;
    protected $type;
    protected $fullLegalName;
    protected $billingAddress;
    protected $mailingAddress;
    protected $bankAddress;
    protected $countryId;
    protected $iban;
    protected $swft;
    protected $creatorId;
    protected $creationDate;
    protected $status;
    protected $isDefault;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->transactionAccountId = (isset($data['transaction_account_id'])) ? $data['transaction_account_id'] : null;
        $this->name                 = (isset($data['name'])) ? $data['name'] : null;
        $this->type                 = (isset($data['type'])) ? $data['type'] : null;
        $this->fullLegalName        = (isset($data['full_legal_name'])) ? $data['full_legal_name'] : null;
        $this->billingAddress       = (isset($data['billing_address'])) ? $data['billing_address'] : null;
        $this->mailingAddress       = (isset($data['mailing_address'])) ? $data['mailing_address'] : null;
        $this->bankAddress          = (isset($data['bank_address'])) ? $data['bank_address'] : null;
        $this->countryId            = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->iban                 = (isset($data['iban'])) ? $data['iban'] : null;
        $this->swft                 = (isset($data['swft'])) ? $data['swft'] : null;
        $this->creatorId            = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->creationDate         = (isset($data['creation_date'])) ? $data['creation_date'] : null;
        $this->status               = (isset($data['status'])) ? $data['status'] : null;
        $this->isDefault            = (isset($data['is_default'])) ? $data['is_default'] : 0;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setId($val)
    {
        $this->id = $val;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTransactionAccountId()
    {
        return $this->transactionAccountId;
    }

    /**
     * @param  $transactionAccountId
     * @return $this
     */
    public function setTransactionAccountId($transactionAccountId)
    {
        $this->transactionAccountId = $transactionAccountId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFullLegalName()
    {
        return $this->fullLegalName;
    }

    /**
     * @param $fullLegalName
     * @return $this
     */
    public function setFullLegalName($fullLegalName)
    {
        $this->fullLegalName = $fullLegalName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param $billingAddress
     * @return $this
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMailingAddress()
    {
        return $this->mailingAddress;
    }

    /**
     * @param $mailingAddress
     * @return $this
     */
    public function setMailingAddress($mailingAddress)
    {
        $this->mailingAddress = $mailingAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBankAddress()
    {
        return $this->bankAddress;
    }

    /**
     * @param $bankAddress
     * @return $this
     */
    public function setBankAddress($bankAddress)
    {
        $this->bankAddress = $bankAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * @param $countryId
     * @return $this
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @param $iban
     * @return $this
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSwft()
    {
        return $this->swft;
    }

    /**
     * @param $swft
     * @return $this
     */
    public function setSwft($swft)
    {
        $this->swft = $swft;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * @param $creatorId
     * @return $this
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return integer
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param  $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}
