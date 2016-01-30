<?php

namespace DDD\Domain\Apartment\Inventory;


class GroupInventory
{
    private $id;
    private $name;

    /**
     * @var int
     */
    private $availability;

    private $date;
    private $unit_number;
    private $max_capacity;
    private $bedroom_count;
    private $bathroom_count;
    private $floor;
    private $reservation_data;
    private $block;
    private $building_name;
    private $amenityId;

    public function exchangeArray($data) {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->name             = (isset($data['name'])) ? $data['name'] : null;
        $this->availability     = (isset($data['availability'])) ? $data['availability'] : null;
        $this->date             = (isset($data['date'])) ? $data['date'] : null;
        $this->unit_number      = (isset($data['unit_number'])) ? $data['unit_number'] : null;
        $this->max_capacity     = (isset($data['max_capacity'])) ? $data['max_capacity'] : null;
        $this->bedroom_count    = (isset($data['bedroom_count'])) ? $data['bedroom_count'] : null;
        $this->bathroom_count   = (isset($data['bathroom_count'])) ? $data['bathroom_count'] : null;
        $this->floor            = (isset($data['floor'])) ? $data['floor'] : null;
        $this->reservation_data = (isset($data['reservation_data'])) ? $data['reservation_data'] : null;
        $this->block            = (isset($data['block'])) ? $data['block'] : null;
        $this->building_name    = (isset($data['building_name'])) ? $data['building_name'] : null;
        $this->amenityId        = (isset($data['amenity_id'])) ? $data['amenity_id'] : null;
    }

    public function getBlock()
    {
        return $this->block;
    }

	public function getReservationData()
    {
		return $this->reservation_data;
	}

	public function getFloor() {
        if ($this->floor == 100) {
            return 'PH';
        } else if($this->floor == 0) {
            return 'GF';
        }

		return $this->floor;
	}

	public function getBathroom_count()
    {
		return $this->bathroom_count;
	}

	public function getBedroom_count()
    {
		return $this->bedroom_count;
	}

	public function getMax_capacity()
    {
		return $this->max_capacity;
	}

	public function getUnit_number()
    {
		return $this->unit_number;
	}

	public function getDate()
    {
		return $this->date;
	}

    /**
     * @return int
     */
    public function getAvailability()
    {
		return $this->availability;
	}

    public function getName()
    {
        return $this->name;
    }

	public function getBuildingName()
    {
		return $this->building_name;
	}

	public function getId()
    {
		return $this->id;
	}

    public function getAmenityId()
    {
        return $this->amenityId;
    }
}
