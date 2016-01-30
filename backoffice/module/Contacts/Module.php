<?php

namespace Contacts;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ],
            ],
        ];
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_contact_contact'   => 'DDD\Service\Contacts\Contact',
            ],
            'aliases'=> [
                'dao_contacts_contact'      => 'DDD\Dao\Contacts\Contact',
            ],
            'factories' => [
                'DDD\Service\Contacts\Contact' => function($sm){
                    $service = new \DDD\Service\Contacts\Contact();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Dao\Contacts\Contact' => function($sm){
                    $dao = new \DDD\Dao\Contacts\Contact($sm);
                    return $dao;
                },
            ]
        ];
    }
}
