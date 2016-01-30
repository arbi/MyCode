<?php

namespace UniversalDashboard;

/**
 * Module
 * @package UniversalDashboard
 */
class Module
{
    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'Library'     => __DIR__ . '/../../library/Library',
                    'DDD'     => __DIR__ . '/../../library/DDD',
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

    public function getServiceConfig() {
    	return include __DIR__ . '/config/service.config.php';
    }
}