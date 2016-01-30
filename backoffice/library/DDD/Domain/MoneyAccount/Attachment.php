<?php

namespace DDD\Domain\MoneyAccount;

/**
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class Attachment
{
    /**
     * @access private
     * @var int
     */
    private $id;

    /**
     * @access private
     * @var int
     */
    private $moneyAccountId;

    /**
     * @access private
     * @var string
     */
    private $description;

    /**
     * @access private
     * @var int
     */
    private $attacherId;

    /**
     * @access private
     * @var date
     */
    private $createdDate;

    /**
     * @access private
     * @var string
     */
    private $firstname;

    /**
     * @access private
     * @var string
     */
    private $lastname;

    /**
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->moneyAccountId = (isset($data['money_account_id'])) ? $data['money_account_id'] : null;
        $this->description   = (isset($data['description'])) ? $data['description'] : null;
        $this->attacherId    = (isset($data['attacher_id'])) ? $data['attacher_id'] : null;
        $this->createdDate   = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->firstname     = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname      = (isset($data['lastname'])) ? $data['lastname'] : null;
    }

    /**
     * @access public
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @access public
     *
     * @return int
     */
    public function getMoneyAccountId()
    {
        return $this->moneyAccountId;
    }


    public function setMoneyAccountId($moneyAccountId)
    {
        $this->moneyAccountId = $moneyAccountId;
        return $this;
    }

    /**
     * @access public
     *
     * @return int
     */
    public function getAttacherId()
    {
        return $this->attacherId;
    }

    /**
     * @param int $attacherId
     */
    public function setAttacherId($attacherId)
    {
        $this->attacherId = $attacherId;
        return $this;
    }


    /**
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @access public
     * @return string
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param string $createdDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    /**
     * @access public
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @access public
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }
}