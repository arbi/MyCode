<?php

namespace DDD\Domain\Warehouse\Storage;

class Threshold
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
   protected $asset_category_id;

   /**
    *
    * @var int
    */
   protected $threshold;

    /**
    *
    * @var int
    */
   protected $storage_id;
   
   public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->asset_category_id = (isset($data['asset_category_id'])) ? $data['asset_category_id'] : null;
        $this->threshold = (isset($data['threshold'])) ? $data['threshold'] : null;
        $this->storage_id = (isset($data['storage_id'])) ? $data['storage_id'] : null;
    }
    
    /**
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     *
     * @return int
     */
    public function getAssetCategoryId() {
        return $this->asset_category_id;
    }

    /**
     *
     * @return int
     */
    public function getThreshold() {
        return $this->threshold;
    }

    /**
     *
     * @return int
     */
    public function getStorageId() {
        return $this->storage_id;
    }

}