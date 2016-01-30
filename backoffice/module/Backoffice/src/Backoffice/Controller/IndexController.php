<?php

namespace Backoffice\Controller;

use Library\Controller\ControllerBase;

class IndexController extends ControllerBase {
    public function indexAction() {
        return $this->redirect()->toUrl('/authentication/login');
    }
}
