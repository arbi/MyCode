<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;

class AboutUsController extends WebsiteBase {
    public function indexAction() {
	    return new ViewModel();
    }

	public function privacyPolicyAction() {
		return new ViewModel();
	}

	public function termsAndConditionsAction() {
		return new ViewModel();
	}
}
