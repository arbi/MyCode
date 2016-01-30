<?php
namespace DDD\Service;


class Language extends ServiceBase
{
    protected $serviceLocator;
    
    /**
     * Method to get languages list
     * @access public
     *
     * @return array
     */
    public function getAllLanguages($columns = array()) {
    	$dao = $this->getLanguageDao();
    	
    	$languages = $dao->fetchAll();
    	
    	return $languages;
    }

    /**
     * Method to get all languages ISO codes
     * @access public
     *
     * @return array
     */
    public function getEnabledLanguagesIsoCodes() {
    	$dao = $this->getLanguageDao();
    	
    	$where = array('enabled' => 1);
    	$columns = array('iso_code');
    	$languages = $dao->fetchAll($where, $columns, array('ordering' => "ASC"));
    	
    	return $languages;
    }
    
    /**
     * @return \DDD\Dao\WebsiteLanguage\Language
     */
    private function getLanguageDao($domain = 'DDD\Domain\WebsiteLanguage\Language') {
    	
    	
    	return $this->getServiceLocator()->get('dao_website_language_language');
    	
    	//return new \DDD\Dao\WebsiteLanguage\Language($this->getServiceLocator(), $domain);
    }
}
