<?php

namespace DDD\Domain\User;

/**
 * @categ
 * @author Tigran Petrosyan
 */
class UserDashboards
{
	/**
	 * @var int
	 */
    protected $id;
    
    /**
     * @var int
     */
    protected $userID;
    
    /**
     * @var int
     */
    protected $dashboardID;
    
    /**
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->userID 		= (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->dashboardID	= (isset($data['dashboard_id'])) ? $data['dashboard_id'] : null;
    }

    /**
     * @return number
     */
    public function getId() {
    	return $this->id;
    }
    
    /**
     * @param int $val
     * @return \DDD\Domain\User\UserDashboards
     */
    public function setId($id) {
    	$this->id = $id;
    	return $this;
    }
    
    /**
     * @return number
     */
    public function getDashboardID() {
    	return $this->dashboardID;
    }
    
    /**
     * @param int $dashboardID
     * @return \DDD\Domain\User\UserDashboards
     */
    public function setDashboardID($dashboardID) {
    	$this->dashboardID = $dashboardID;
    	return $this;
    }
    
    /**
     * @return number
     */
    public function getUserID() {
    	return $this->userID;
    }
    
    /**
     * @param int $userID
     * @return \DDD\Domain\User\UserDashboards
     */
    public function setUserID($userID) {
    	$this->userID = $userID;
    	return $this;
    }
}
