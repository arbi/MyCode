<?php

namespace DDD\Domain\WHOrder;


class Order
{
    protected $id;
    protected $creatorId;
    protected $dateCreated;
    protected $title;
    protected $assetCategoryId;
    protected $assetCategoryName;
    protected $assetCategoryType;
    protected $targetId;
    protected $targetType;
    protected $status;
    protected $quantity;
    protected $quantityType;
    protected $expenseId;
    protected $supplierId;
    protected $supplierTrackingNumber;
    protected $supplierTransactionId;
    protected $url;
    protected $estimatedDateStart;
    protected $estimatedDateEnd;
    protected $description;
    protected $receivedDate;
    protected $receivedQuantity;
    protected $supplierName;
    protected $orderDate;
    protected $trackingUrl;
    protected $locationName;
    protected $price;
    protected $teamId;
    protected $teamName;
    protected $statusShipping;
    protected $currencyId;
    protected $poId;
    protected $poItemId;
    protected $user;
    protected $poRAItemId;

    public function exchangeArray($data)
    {
        $this->id                       = (isset($data['id']) ? $data['id'] : null);
        $this->creatorId                = (isset($data['creator_id']) ? $data['creator_id'] : null);
        $this->dateCreated              = (isset($data['date_created']) ? $data['date_created'] : null);
        $this->title                    = (isset($data['title']) ? $data['title'] : null);
        $this->assetCategoryId          = (isset($data['asset_category_id']) ? $data['asset_category_id'] : null);
        $this->assetCategoryName        = (isset($data['asset_category_name']) ? $data['asset_category_name'] : null);
        $this->assetCategoryType        = (isset($data['asset_category_type']) ? $data['asset_category_type'] : null);
        $this->targetId                 = (isset($data['target_id']) ? $data['target_id'] : null);
        $this->targetType               = (isset($data['target_type']) ? $data['target_type'] : null);
        $this->status                   = (isset($data['status']) ? $data['status'] : null);
        $this->quantity                 = (isset($data['quantity']) ? $data['quantity'] : null);
        $this->quantityType             = (isset($data['quantity_type']) ? $data['quantity_type'] : null);
        $this->expenseId                = (isset($data['expense_id']) ? $data['expense_id'] : null);
        $this->supplierId               = (isset($data['supplier_id']) ? $data['supplier_id'] : null);
        $this->supplierTrackingNumber   = (isset($data['supplier_tracking_number']) ? $data['supplier_tracking_number'] : null);
        $this->supplierTransactionId    = (isset($data['supplier_transaction_id']) ? $data['supplier_transaction_id'] : null);
        $this->url                      = (isset($data['url']) ? $data['url'] : null);
        $this->estimatedDateStart       = (isset($data['estimated_date_start']) ? $data['estimated_date_start'] : null);
        $this->estimatedDateEnd         = (isset($data['estimated_date_end']) ? $data['estimated_date_end'] : null);
        $this->description              = (isset($data['description']) ? $data['description'] : null);
        $this->receivedDate             = (isset($data['received_date']) ? $data['received_date'] : null);
        $this->receivedQuantity         = (isset($data['received_quantity']) ? $data['received_quantity'] : null);
        $this->supplierName             = (isset($data['supplier_name']) ? $data['supplier_name'] : null);
        $this->orderDate                = (isset($data['order_date']) ? $data['order_date'] : null);
        $this->trackingUrl              = (isset($data['tracking_url']) ? $data['tracking_url'] : null);
        $this->locationName             = (isset($data['location_name']) ? $data['location_name'] : null);
        $this->price                    = (isset($data['price']) ? $data['price'] : null);
        $this->teamId                   = (isset($data['team_id']) ? $data['team_id'] : null);
        $this->teamName                 = (isset($data['team_name']) ? $data['team_name'] : null);
        $this->statusShipping           = (isset($data['status_shipping']) ? $data['status_shipping'] : null);
        $this->currencyId               = (isset($data['currency_id']) ? $data['currency_id'] : null);
        $this->poId                     = (isset($data['po_id']) ? $data['po_id'] : null);
        $this->poItemId                 = (isset($data['po_item_id']) ? $data['po_item_id'] : null);
        $this->user                     = (isset($data['user']) ? $data['user'] : null);
        $this->poRAItemId               = (isset($data['po_ra_item_id']) ? $data['po_ra_item_id'] : null);
    }

    /**
     * @return mixed
     */
    public function getPoRAItemId()
    {
        return $this->poRAItemId;
    }

    /**
     * @return mixed
     */
    public function getTeamName()
    {
        return $this->teamName;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getPoId()
    {
        return $this->poId;
    }

    /**
     * @return mixed
     */
    public function getPoItemId()
    {
        return $this->poItemId;
    }

    /**
     * @return mixed
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @return mixed
     */
    public function getStatusShipping()
    {
        return $this->statusShipping;
    }

    /**
     * @return mixed
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return integer
     */
    public function getQuantityType()
    {
        return $this->quantityType;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * @return mixed
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getAssetCategoryId()
    {
        return $this->assetCategoryId;
    }

    /**
     * @return mixed
     */
    public function getAssetCategoryName()
    {
        return $this->assetCategoryName;
    }

    /**
     * @return mixed
     */
    public function getAssetCategoryType()
    {
        return $this->assetCategoryType;
    }

    /**
     * @return mixed
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * @return mixed
     */
    public function getTargetType()
    {
        return $this->targetType;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getExpenseId()
    {
        return $this->expenseId;
    }

    /**
     * @return mixed
     */
    public function getSupplierId()
    {
        return $this->supplierId;
    }

    /**
     * @return mixed
     */
    public function getSupplierTrackingNumber()
    {
        return $this->supplierTrackingNumber;
    }

    /**
     * @return mixed
     */
    public function getSupplierTransactionId()
    {
        return $this->supplierTransactionId;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getEstimatedDateStart()
    {
        return $this->estimatedDateStart;
    }

    /**
     * @return mixed
     */
    public function getEstimatedDateEnd()
    {
        return $this->estimatedDateEnd;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $creatorId
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;
    }

    /**
     * @param mixed $dateCreated
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param mixed $assetCategoryId
     */
    public function setAssetCategoryId($assetCategoryId)
    {
        $this->assetCategoryId = $assetCategoryId;
    }

    /**
     * @param mixed $assetCategoryName
     */
    public function setAssetCategoryName($assetCategoryName)
    {
        $this->assetCategoryName = $assetCategoryName;
    }

    /**
     * @param mixed $targetId
     */
    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;
    }

    /**
     * @param mixed $targetType
     */
    public function setTargetType($targetType)
    {
        $this->targetType = $targetType;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @param mixed $expenseId
     */
    public function setExpenseId($expenseId)
    {
        $this->expenseId = $expenseId;
    }

    /**
     * @param mixed $supplierId
     */
    public function setSupplierId($supplierId)
    {
        $this->supplierId = $supplierId;
    }

    /**
     * @param mixed $supplierTrackingNumber
     */
    public function setSupplierTrackingNumber($supplierTrackingNumber)
    {
        $this->supplierTrackingNumber = $supplierTrackingNumber;
    }

    /**
     * @param mixed $supplierTransactionId
     */
    public function setSupplierTransactionId($supplierTransactionId)
    {
        $this->supplierTransactionId = $supplierTransactionId;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param mixed $estimatedDateStart
     */
    public function setEstimatedDateStart($estimatedDateStart)
    {
        $this->estimatedDateStart = $estimatedDateStart;
    }

    /**
     * @param mixed $estimatedDateEnd
     */
    public function setEstimatedDateEnd($estimatedDateEnd)
    {
        $this->estimatedDateEnd = $estimatedDateEnd;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getSupplierName()
    {
        return $this->supplierName;
    }

    /**
     * @return mixed
     */
    public function getReceivedDate()
    {
        return $this->receivedDate;
    }

    /**
     * @return mixed
     */
    public function getReceivedQuantity()
    {
        return $this->receivedQuantity;
    }

    /**
     * @param string $orderDate
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $orderDate;
    }

    /**
     * @return string $orderDate
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * @param string $trackingUrl
     */
    public function setTrackingUrl($trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;
    }

    /**
     * @return string $trackingUrl
     */
    public function getTrackingUrl()
    {
        return $this->trackingUrl;
    }

    /**
     * @return string $locationName
     */
    public function getLocationName()
    {
        return $this->locationName;
    }
}
