<?php

namespace DDD\Domain\Apartment\Rate;

/**
 * Apartment Rate Domain class to hold rate name
 * @author Tigran Petrosyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class Select
{
    private $id;
    private $name;
    private $color;
    private $type;
    private $weekPercent;
    private $weekendPercent;
    private $active;

	/**
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->weekPercent = (isset($data['week_percent'])) ? $data['week_percent'] : null;
        $this->weekendPercent = (isset($data['weekend_percent'])) ? $data['weekend_percent'] : null;
        $this->active = (isset($data['active'])) ? $data['active'] : null;
    }

    /**
     * @access public
     * @return int
     */
	public function getID() {
		return $this->id;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return the $color
	 */
	public function getColor() {
		return $this->color;
	}

	/**
	 * @param int $color
	 */
	public function setColor($color) {
		$this->color = $color;
	}

    /**
     * @access public
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getWeekPercent() {
        return $this->weekPercent;
    }

    /**
     * @return mixed
     */
    public function getWeekendPercent() {
        return $this->weekendPercent;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->active;
    }
}
