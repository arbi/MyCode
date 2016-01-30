<?php

namespace DDD\Domain\Warehouse\Assets;

class ConsumableSkusRelation
{
    /**
     *
     * @var int
     */
   protected $id;

    /**
     *
     * @var int
     */
    protected $skuId;

    /**
     *
     * @var int
     */
    protected $assetId;

   
   public function exchangeArray($data)
   {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->skuId = (isset($data['sku_id'])) ? $data['sku_id'] : null;
        $this->assetId = (isset($data['asset_id'])) ? $data['asset_id'] : null;
   }

    /**
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSkuId()
    {
        return $this->skuId;
    }

    /**
     * @return int
     */
    public function getAssetId()
    {
        return $this->assetId;
    }
    




}