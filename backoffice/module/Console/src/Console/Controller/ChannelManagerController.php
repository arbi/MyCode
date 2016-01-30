<?php

namespace Console\Controller;

use DDD\Dao\Apartment\Details;
use DDD\Service\Queue\InventorySynchronizationQueue;
use Library\ChannelManager\ChannelManager;
use Library\ChannelManager\CivilResponder;
use Library\ChannelManager\Provider\Cubilis\Cubilis;
use Library\Constants\Constants;
use Library\Constants\TextConstants;
use Library\Constants\EmailAliases;
use Library\Controller\ConsoleBase;
use Library\Plugins\Email as EmailPlugin;
use Library\Utility\Debug;

/**
 * Class ChannelManagerController
 * @package Console\Controller
 */
class ChannelManagerController extends ConsoleBase
{
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');

        if ($this->getRequest()->getParam('start')) {
            $this->queueStart = true;
        }

        if ($this->getRequest()->getParam('restart')) {
            $this->queueRestart = true;
        }

        switch ($action) {
            case 'help': $this->helpAction();
                break;
            case 'pullreservation': $this->pullReservationAction();
                break;
            default :
                echo '- type true parameter ( chm pullreservation | chm help )' . $action . PHP_EOL;
                return false;
        }
    }

    public function pullReservationAction()
    {
        try {
            /**
             * @var \DDD\Service\ChannelManager $channelManagerService
             */
            $channelManagerService = $this->getServiceLocator()->get('service_channel_manager');

            $channelManagerService->pullReservation();

            $msg = 'ChannelManager: pull reservations done successfully.';

            $this->outputMessage($msg);

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'ChannelManager: pull reservations possible. Script broken!');

            $this->outputMessage($e->getMessage());

            return false;
        }
    }

    public function helpAction()
    {
        echo '- type "ginosole chm pullreservation" for pulling new reservations via channel manager.'.PHP_EOL;
    }
}

