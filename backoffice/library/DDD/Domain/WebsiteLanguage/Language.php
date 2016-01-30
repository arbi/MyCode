<?php

namespace DDD\Domain\WebsiteLanguage;

/**
 * Basic domain class for website language row.
 * @final
 * @category core
 * @package domain
 *
 * @author Tigran Petrosyan
 */
final class Language {
	/**
	 * @access protected
	 * @var int
	 */
    protected $id;
    
    /**
     * @access protected
     * @var string
     */
    protected $englishName;
    
    /**
     * @access protected
     * @var string
     */
    protected $isoCode;

    /**
     * @access protected
     * @var boolean
     */
    protected $enabled;
    
    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id 				= (isset($data['id'])) ? $data['id'] : null;
        $this->englishName 		= (isset($data['name'])) ? $data['name'] : null;
        $this->isoCode	 		= (isset($data['iso_code'])) ? $data['iso_code'] : null;
        $this->enabled 			= (isset($data['enabled'])) ? $data['enabled'] : null;
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
     * @return string
     */
    public function getEnglishName() {
    	return $this->englishName;
    }
    
    /**
     * @access public
     * @return string
     */
    public function getIsoCode() {
    	return $this->isoCode;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function getEnabled() {
    	return $this->enabled;
    }
}

?>
