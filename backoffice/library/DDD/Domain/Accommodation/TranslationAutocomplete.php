<?php

namespace DDD\Domain\Accommodation;

/**
 * Specific domain to use in product autocomplete
 * @author Tigran Petrosyan
 * @final
 * 
 * @package core
 * @subpackage core/domain
 */
final class TranslationAutocomplete
{
	/**
	 * @var int
	 */
    protected $id;
    
    /**
     * @var string product name
     */
    protected $name;
    
  
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->name 		= (isset($data['name'])) ? $data['name'] : null;
    }

    /**
     * Get product ID
     * @access public
     * 
     * @return int
     */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get product name
	 * @access public
	 * 
	 * @return string product name
	 */
	public function getName() {
		return $this->name;
	}
}