<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;

class FaqController extends WebsiteBase
{
    public function indexAction()
    {
        
        return new ViewModel();
    }
}
