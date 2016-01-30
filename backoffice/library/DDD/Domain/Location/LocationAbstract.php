<?php

namespace DDD\Domain\Location;

abstract class LocationAbstract
{
	/**
	 * @access protected
	 * @var int
	 */
    protected $id;
    
    /**
     * @access protected
     * @var int
     */
    protected $parentID;
    
    /**
     * @access protected
     * @var int
     */
    protected $detailsID;
    
    /**
     * @access protected
     * @var name
     */
    protected $name;
    
    /**
     * @access protected
     * @var number
     */
    protected $locationType;
    
    /**
     * @access protected
     * @var hasChild
     */
    protected $childrenCount;
    
    /**
     * @access public
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id     		= (isset($data['id'])) 			? $data['id'] 			: null;
        $this->name 	= (isset($data['name']))            ? $data['name'] 	: null;
        $this->childrenCount 	= (isset($data['children_count']) && isset($data['child_id']))            ? $data['children_count'] 	: null;
        $this->parentID 	= (isset($data['parent_id'])) 	? $data['parent_id'] 	: null;
        $this->detailsID	= (isset($data['details_id'])) 	? $data['details_id'] 	: null;
    }
    
    /**
     * @access public
     * @return number
     */
    public function getName() {
    	return $this->name;
    }
    
    /**
     * @access public
     * @return number
     */
    public function getID() {
    	return $this->id;
    }
    
    /**
     * @access public
     * @return number
     */
    public function getParentID() {
    	return $this->parentID;
    }
    
    /**
     * @access public
     * @return number
     */
    public function getDetailsID() {
    	return $this->detailsID;
    }
    
    /**
     * @access public
     * @return number
     */
    public function getLocationType() {
    	return $this->locationType;
    }
    
    /**
     * @access public
     * @param int $locationType
     */
    public function getChildrenCount() {
    	return $this->childrenCount;
    }
    
    /**
     * @access public
     * @param int $locationType
     */
    public function setLocationType($locationType) {
    	$this->locationType = $locationType;
    }
}