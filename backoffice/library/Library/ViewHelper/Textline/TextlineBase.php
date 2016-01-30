<?php

namespace Library\ViewHelper\Textline;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;

class TextlineBase extends AbstractHelper
{
    /**
     * @var ServiceManager $serviceLocator
     */
    protected $serviceLocator;

    /**
     * @var \DDD\Service\Textline $textlineService
     */
    protected $textlineService;

    /**
     * @var $cacheService
     */
    protected $cacheService;

    /**
     * TextlineBase constructor.
     * @param ServiceManager $serviceLocator
     */
    public function __construct(ServiceManager $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        $this->textlineService = $this->serviceLocator->get('service_textline');
        $this->cacheService = $this->serviceLocator->get('service_cache_memcache');
    }

    /**
     * @param ServiceManager $serviceLocator
     */
    public function setServiceLocator(ServiceManager $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
