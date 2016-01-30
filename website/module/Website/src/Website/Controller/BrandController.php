<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;

class BrandController extends WebsiteBase {
    public function logoAction() {
	    return new ViewModel();
    }
}
