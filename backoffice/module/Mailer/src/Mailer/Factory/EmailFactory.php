<?php
namespace Mailer\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Mailer\Service\Email;

/**
 * Email service factory.
 */
class EmailFactory implements FactoryInterface
{
    private $transport;
    
    public function __construct($transport = 'default')
    {
        $this->transport = $transport;
    }

    /**
     * Create, configure and return the email transport.
     *
     * @see FactoryInterface::createService()
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        switch ($this->transport) {
            case 'default': 
                $transport = $serviceLocator->get('Mailer\Transport');
                break;
            case 'alerts': 
                $transport = $serviceLocator->get('Mailer\Transport-Alerts');
                break;
        }
        $renderer  = $serviceLocator->get('Mailer\Renderer');

        return new Email($transport, $renderer);
    }
}