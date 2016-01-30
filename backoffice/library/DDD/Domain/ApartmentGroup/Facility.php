<?php

/**
 * Description of Facilities
 *
 * @author Tigran Gh.
 * 
 * @package core
 * @subpackage core/domain
 */

namespace DDD\Domain\ApartmentGroup;

class Facility
{
    /**
	 * @access private
	 * @var int
	 */
    private $id;
    
    /**
     * @access private
     * @var Int
     */
    private $textlineId;
    
    /**
     * @access private
     * @var String
     */
    private $name;
    
    /**
     * @access private
     * @var String
     */
    private $description;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->name         = (isset($data['name'])) ? $data['name'] : null;
        $this->textlineId  = (isset($data['textline_id'])) ? $data['textline_id'] : null;
        $this->description  = (isset($data['description'])) ? $data['description'] : null;
    }
    
    /**
     * @access public
     * @return int
     */
	public function getId()
    {
		return $this->id;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getName()
    {
		return $this->name;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getTextlineId()
    {
		return $this->textlineId;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getDescription()
    {
		return $this->description;
	}
}
