<?php

namespace MailChimp;


use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Class Module
 * @package MailChimp
 */
class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig() {
        $environment = getenv('APPLICATION_ENV') ?: 'production';

        return include __DIR__ . '/config/module.config.' . $environment . '.php';
    }

    /**
     * OnBootstrap listener
     * @param $e
     */
    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                'MailChimp' => function ($serviceLocator){
                    $as = new \MailChimp\Factory\MailChimpFactory();
                    $as = $as->createService($serviceLocator);
                    return $as;
                },
            ],
        ];
    }
}