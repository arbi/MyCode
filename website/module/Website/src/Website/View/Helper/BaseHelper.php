<?php

namespace Website\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;

use Zend\Session\Container as SessionContainer;

class BaseHelper extends AbstractHelper
{
    protected $serviceLocator;
    
    /**
     *
     * @var \DDD\Service\Textline $textlineService
     */
    protected $textlineService;
    protected $cacheService;
    protected $language;
    
    public function __construct()
    {
        $session = new SessionContainer('visitor');
        $this->language = $session->language;
    }
    
    public function setServiceLocator(ServiceManager $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        
        $this->textlineService = $this->serviceLocator->get('service_textline');
        $this->cacheService = $this->serviceLocator->get('service_website_cache');
    }
}