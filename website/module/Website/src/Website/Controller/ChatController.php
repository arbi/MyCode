<?php

namespace Website\Controller;

use Library\Controller\ControllerBase;
use Zend\View\Model\ViewModel;

class ChatController extends ControllerBase {
    public function indexAction() {
	    $vm = new ViewModel();
	    $vm->setTerminal(true);

	    return $vm;
    }
}
