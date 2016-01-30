<?php

namespace Website\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ViewModel;
use DDD\Dao\Location\City;
use Library\Utility\Helper;
use Library\Validator\ClassicValidator;

class SearchWidget extends AbstractHelper
{
    protected $serviceLocator;
    protected $viewTemplate;

    public function __invoke($options = array()) {
	    $render = array_key_exists('render', $options) ? $options['render'] : true;
        $searchService = $this->getServiceLocator()->get('service_website_search');
        $options = $searchService->getOptions();
        $request   = $this->getServiceLocator()->get('request');
        $getParams = $request->getQuery(); $setParams = [];
        if(isset($getParams['city']) && ClassicValidator::checkCityName($getParams['city'])) {
            $setParams['city_url']  = $getParams['city'];
            $city                   = Helper::urlForSearch($getParams['city']);
//            $setParams['city']      = ;

            if (isset($getParams['guest'])) {
                $setParams['guest'] = $getParams['guest'];
            }
            
            $date                   = $searchService->getFixedDate($getParams);
            $setParams['arrival']   = $date['arrival'];
            $setParams['departure'] = $date['departure'];
        }


        $vm = new ViewModel(['options' => $options, 'setParams' => $setParams]);
        $vm->setTemplate($this->viewTemplate);

	    return ($render ? $this->getView()->render($vm) : $vm);
    }

    public function setViewTemplate($viewTemplate) {
        $this->viewTemplate = $viewTemplate;

        return $this;
    }

    public function setServiceLocator(ServiceManager $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }

    public function getServiceLocator() {
		return $this->serviceLocator;
	}
}
