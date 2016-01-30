<?php

namespace DDD\Domain\Venue;


class Items
{
    protected $id;
    protected $venueId;
    protected $title;
    protected $description;
    protected $price;
    protected $isAvailable;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->venueId      = (isset($data['venue_id'])) ? $data['venue_id'] : null;
        $this->title        = (isset($data['title'])) ? $data['title'] : null;
        $this->description  = (isset($data['description'])) ? $data['description'] : null;
        $this->price        = (isset($data['price'])) ? $data['price'] : null;
        $this->isAvailable  = (isset($data['is_available'])) ? $data['is_available'] : null;
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
    public function getVenueId()
    {
        return $this->venueId;
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
    public function getDescription()
    {
        return $this->description;
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
    public function getIsAvailable()
    {
        return $this->isAvailable;
    }
}
