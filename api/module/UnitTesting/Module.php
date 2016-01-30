<?php

namespace UnitTesting;


class Module
{
    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'Library'     => '/ginosi/backoffice/library/Library',
                    'DDD'         => '/ginosi/backoffice/library/DDD',
                ),
            ),
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * OnBootstrap listener
     * @param $e
     */
    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
    }


}