<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use Library\Service\MaxMind;
use Zend\Text\Table\Table;
use Library\Constants\ExternalServices;
use Library\Constants\EmailAliases;
use Library\Constants\TextConstants;

/**
 * Class MaxMindController
 * @package Console\Controller
 */
class MaxMindController extends ConsoleBase
{

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode');

        switch ($action) {
            case 'update-geolite-country-database':   $this->updateGeoliteCountryDatabaseAction();
                break;
            default :
                echo '- type true parameter ( max-mind update-geolite-country-database )' . PHP_EOL;
        }
    }

    public function updateGeoliteCountryDatabaseAction()
    {
        $maxMindService = new MaxMind();
        $maxMindService->setServiceLocator($this->getServiceLocator());

        $result = $maxMindService->applyGeoliteCountryCSV();

        $this->outputMessage($result);
    }
}
