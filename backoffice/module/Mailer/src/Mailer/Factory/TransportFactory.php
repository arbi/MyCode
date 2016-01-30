<?php
namespace Mailer\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Mail transport service factory.
 *
 */
class TransportFactory implements FactoryInterface
{
    /**
     * The default mail transport implementation namespace.
     *
     * @var string
     */
    const STD_TRANSPORT_NS = 'Zend\Mail\Transport';
    
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
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $config = $serviceLocator->get('config');
        
        if ($this->transport !== 'transport-alerts') {
            $this->transport = 'transport';
        }
        
        if (empty($config['mail'][$this->transport]['type'])) {
            throw new \Exception(
                'Config required in order to create Mailer\Factory\TransportFactory.'.
                'required config key: $config["mail"]["transport"].'
            );
        }

        $transportConfig = $config['mail'][$this->transport];
        
        $type = $transportConfig['type'];

        if (false === class_exists($type)) {
            $type = self::STD_TRANSPORT_NS . '\\' . ucfirst($type);
        }

        $transport = new $type;

        if (isset($transportConfig['options'])) {
            // by convention... SmtpOptions, SendmailOptions, etc..
            $optionsClass = $type . 'Options';
            $options = $transportConfig['options'];

            // create instance of options class if conventional
            // try succeeded, otherwise use what's in options
            if (class_exists($optionsClass)) {
                $options = new $optionsClass($options);
            }
            // Zend\Mail\Transport\Sendmail provides setParameters
            // Other transport types provide setOptions
            if (method_exists($transport, 'setOptions')) {
                $transport->setOptions($options);
            } else if (method_exists($transport, 'setParameters')) {
                $transport->setParameters($options);
            } 
        }

        return $transport;
    }
}