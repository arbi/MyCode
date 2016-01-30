<?php

namespace Backoffice\View\Helper;

use Library\Authentication\BackofficeAuthenticationService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;

class Identity extends AbstractHelper {
    use ServiceLocatorAwareTrait;

	public function __invoke() {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        return $auth->getIdentity();
    }
}
