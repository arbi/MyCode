<?php
namespace MailChimp\Factory;

use MailChimp\Service\MailChimp;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Email renderer factory.
 *
 */
class MailChimpFactory implements FactoryInterface
{
    /**
     * Create, configure and return MailChimp wrapper service.
     *
     * @see FactoryInterface::createService()
     * @throws \Exception
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');

        if (empty($config['mail_chimp']['api_key'])) {
            throw new \Exception(
            'Config required in order to create MailChimp service.' .
            'required config key: $config["mail_chimp"]["api_key"].'
            );
        }

        $apiKey = $config['mail_chimp']['api_key'];

        return new MailChimp($apiKey);
    }

}