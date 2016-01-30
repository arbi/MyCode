<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use Library\Constants\EmailAliases;
use Library\Constants\DomainConstants;
use Zend\Validator\EmailAddress;

/**
 * Class ContactUsController
 * @package Console\Controller
 */
class ContactUsController extends ConsoleBase
{
    private $name       = FALSE;
    private $email      = FALSE;
    private $remarks    = FALSE;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode');

        if ($this->getRequest()->getParam('name')) {
            $this->name = $this->getRequest()->getParam('name');
        }

        if ($this->getRequest()->getParam('email')) {
            $this->email = $this->getRequest()->getParam('email');
        }

        if ($this->getRequest()->getParam('remarks')) {
            $this->remarks = $this->getRequest()->getParam('remarks');
        }

        switch ($action) {
            case 'send': $this->sendAction();
                break;
            default :
                echo '- type true parameter ( contact-us  send  )'.PHP_EOL;
                return FALSE;
        }
    }

    public function sendAction()
    {
        $emailValidator = new EmailAddress();

        if (!$this->name OR !$this->email OR !$this->remarks) {
            echo 'error: need to fill in all params. see "ginosole --usage"'.PHP_EOL;
            return FALSE;
        } elseif (!$emailValidator->isValid($this->email)) {
            echo 'Error: Email not valid - '.$this->email.PHP_EOL;
            return FALSE;
        }

        $serviceLocator = $this->getServiceLocator();
        $mailer = $serviceLocator->get('Mailer\Email');


        $mailer->send(
            'contact-us', [
                'layout'            => 'clean',
                'to'                => EmailAliases::TO_CONTACT,
                'to_name'           => 'Ginosi Apartments',
                'replyTo'           => $this->email,
                'from_address'      => EmailAliases::FROM_MAIN_MAIL,
                'from_name'         => $this->name,
                'subject'           => 'Ginosi Apartments ✡ Contact Us ✡ From '.$this->name,
                'name'              => $this->name,
                'email'             => $this->email,
                'remarks'           => $this->remarks,
        ]);
    }
}