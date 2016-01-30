<?php

namespace DDD\Domain\Venue;

/**
 * Class Venue
 *
 * @package DDD\Domain\Venue
 * @author  Harut Grigoryan
 */
class Venue
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $currencyId;

    /**
     * @var integer
     */
    protected $currencyCode;

    /**
     * @var integer
     */
    protected $cityId;

    /**
     * @var double
     */
    protected $thresholdPrice;

    /**
     * @var double
     */
    protected $discountPrice;

    /**
     * @var double
     */
    protected $perdayMaxPrice;

    /**
     * @var double
     */
    protected $perdayMinPrice;

    /**
     * @var integer
     */
    protected $acceptOrders;

    /**
     * @var integer
     */
    protected $status;

    /**
     * @var integer
     */
    protected $managerId;

    /**
     * @var integer
     */
    protected $cashierId;

    /**
     * @var
     */
    protected $creationDate;

    protected $uniqueId; // <-- "account_id" in db
    protected $accountId;
    protected $accountName;
    protected $accountType;
    protected $type;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id             = (isset($data['id'])) ? $data['id'] : null;
        $this->name           = (isset($data['name'])) ? $data['name'] : null;
        $this->currencyId     = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->currencyCode   = (isset($data['currency_code'])) ? $data['currency_code'] : null;
        $this->cityId         = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->thresholdPrice = (isset($data['threshold_price'])) ? $data['threshold_price'] : null;
        $this->discountPrice  = (isset($data['discount_price'])) ? $data['discount_price'] : null;
        $this->perdayMaxPrice = (isset($data['perday_max_price'])) ? $data['perday_max_price'] : null;
        $this->perdayMinPrice = (isset($data['perday_min_price'])) ? $data['perday_min_price'] : null;
        $this->acceptOrders   = (isset($data['accept_orders'])) ? $data['accept_orders'] : 0;
        $this->status         = (isset($data['status'])) ? $data['status'] : 0;
        $this->managerId      = (isset($data['manager_id'])) ? $data['manager_id'] : null;
        $this->cashierId      = (isset($data['cashier_id'])) ? $data['cashier_id'] : null;
        $this->creationDate   = (isset($data['creation_date'])) ? $data['creation_date'] : null;
        $this->uniqueId       = (isset($data['unique_id'])) ? $data['unique_id'] : null;
        $this->accountId      = (isset($data['account_id'])) ? $data['account_id'] : null;
        $this->accountName    = (isset($data['account_name'])) ? $data['account_name'] : null;
        $this->accountType    = (isset($data['account_type'])) ? $data['account_type'] : null;
        $this->type           = (isset($data['type'])) ? $data['type'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
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
     * @return int
     */
	public function getCurrencyId()
    {
		return $this->currencyId;
	}

    /**
     * @param $currencyId
     * @return $this
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * @param $cityId
     * @return $this
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * @return float
     */
    public function getThresholdPrice()
    {
        return $this->thresholdPrice;
    }

    /**
     * @param $thresholdPrice
     * @return $this
     */
    public function setThresholdPrice($thresholdPrice)
    {
        $this->thresholdPrice = $thresholdPrice;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountPrice()
    {
        return $this->discountPrice;
    }

    /**
     * @param $discountPrice
     * @return $this
     */
    public function setDiscountPrice($discountPrice)
    {
        $this->discountPrice = $discountPrice;

        return $this;
    }

    /**
     * @return float
     */
    public function getPerdayMaxPrice()
    {
        return $this->perdayMaxPrice;
    }

    /**
     * @param $perdayMaxPrice
     * @return $this
     */
    public function setPerdayMaxPrice($perdayMaxPrice)
    {
        $this->perdayMaxPrice = $perdayMaxPrice;

        return $this;
    }

    /**
     * @return float
     */
    public function getPerdayMinPrice()
    {
        return $this->perdayMinPrice;
    }

    /**
     * @param $perdayMinPrice
     * @return $this
     */
    public function setPerdayMinPrice($perdayMinPrice)
    {
        $this->perdayMinPrice = $perdayMinPrice;

        return $this;
    }

    /**
     * @return int
     */
    public function getAcceptOrders()
    {
        return $this->acceptOrders;
    }

    /**
     * @param $acceptOrders
     * @return $this
     */
    public function setAcceptOrders($acceptOrders)
    {
        $this->acceptOrders = $acceptOrders;

        return $this;
    }

    /**
     * @return int
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
     * @return int
     */
    public function getManagerId()
    {
        return $this->managerId;
    }

    /**
     * @param $managerId
     * @return $this
     */
    public function setManagerId($managerId)
    {
        $this->managerId = $managerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCashierId()
    {
        return $this->cashierId;
    }

    /**
     * @param $cashierId
     * @return $this
     */
    public function setCashierId($cashierId)
    {
        $this->cashierId = $cashierId;

        return $this;
    }

    /**
     * @return string
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

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @return mixed
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * @return mixed
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @return int
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}
