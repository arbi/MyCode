<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;

class IndexController extends WebsiteBase
{
    public function indexAction()
    {
        /* @var $indexService \DDD\Service\Website\Index */
        $indexService = $this->getServiceLocator()->get('service_website_index');
        $options = $indexService->getOptions();
        return new ViewModel([
	        'options' => $options
        ]);
    }
}
