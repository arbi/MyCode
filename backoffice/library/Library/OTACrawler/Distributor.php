<?php

namespace Library\OTACrawler;

class Distributor
{
    /**
     * @var \ArrayObject
     */
    protected $rawItems;

    /**
     * @var DistributorItem[]|\ArrayObject
     */
    protected $items;

    public function __construct($data) {
        $this->rawItems = $data;
        $this->items = new \ArrayObject();
    }

    /**
     * @return \ArrayObject|DistributorItem[]
     */
    public function getAll() {
        if (!$this->items->count()) {
            if ($this->rawItems->count()) {
                foreach ($this->rawItems as $item) {
                    $this->items->append(
                        new DistributorItem($item)
                    );
                }
            }
        }

        return $this->items;
    }
}
