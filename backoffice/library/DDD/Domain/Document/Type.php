<?php

namespace DDD\Domain\Document;

/**
 * Apartment Document Type Domain class
 * @author Tigran Petrosyan
 * @final
 * 
 * @package core
 * @subpackage core/domain
 */
final class Type
{
	/**
	 * @access private
	 * @var int
	 */
    private $id;
    
    /**
     * @access private
     * @var string
     */
    private $name;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id		= (isset($data['id'])) ? $data['id'] : null;
        $this->name 	= (isset($data['name'])) ? $data['name'] : null;
    }
    
    /**
     * Get type ID
     * @access public
     * 
     * @return int
     */
	public function getID() {
		return $this->id;
	}

	/**
	 * Get type name ID
	 * @access public
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}