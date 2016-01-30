<?php
namespace Backoffice\Controller;

use Library\Controller\ControllerBase;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

/**
 * Language controller.
 * 
 * @package backoffice
 * @subpackage backoffice_controller
 * 
 * @author Tigran Petrosyan
 */
class LanguageController extends ControllerBase
{
	/**
	 * Entry point for LanguageController, uses to display list of languages
	 * 
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
    public function indexAction()
    {
        $viewModel = new ViewModel();
    	return $viewModel;
    }

    /**
     * Get reservations json to use as source for datatable, filtered by params came from datatable 
     * @access public
     * 
     * @return \Zend\View\Model\JsonModel
     * 
     * @author Tigran Petrosyan
     */
    public function getLanguagesJsonAction() {
    	
    	// get query parameters
    	$queryParams = $this->params()->fromQuery();
    	
    	// get languages data
    	$languageService = $this->getLanguageService();
    	$languages = $languageService->getAllLanguages($queryParams);
    	
    	// prepare languages array
    	$filteredArray = array();
    	
    	foreach ($languages as $language){
    		$row = array(
    			$language->getID(),
    			$language->getIsoCode(),
    			$language->getEnglishName(),
    			$language->getEnabled()
    		);
    		
    		$filteredArray[] = $row;
    	}
    	
    	// build response
    	$responseArray = array(
    			"aaData" => $filteredArray
    	);
    	 
    	return new JsonModel(
    		$responseArray
    	);
    }
    
    /**
     * Return language service class object
     * @access private
     * 
     * @return \DDD\Service\Language
     */
    private function getLanguageService() {
    	return $this->getServiceLocator()->get('service_language');
    }
}
