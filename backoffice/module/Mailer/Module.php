<?php
namespace Mailer;

/**
 * Module
 * @package Mailer
 */
class Module
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
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * OnBootstrap listener
     * @param $e
     */
    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
    }

    public function getServiceConfig() {
    	return array(
    		'factories' => array(
    			'Mailer\Email' => function ($serviceLocator){
        			$as = new \Mailer\Factory\EmailFactory();
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
                'Mailer\Email-Alerts' => function ($serviceLocator){
        			$as = new \Mailer\Factory\EmailFactory('alerts');
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
        		'Mailer\Transport' => function ($serviceLocator){
        			$as = new \Mailer\Factory\TransportFactory();
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
                'Mailer\Transport-Alerts' => function ($serviceLocator){
        			$as = new \Mailer\Factory\TransportFactory('transport-alerts');
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
        		'Mailer\Renderer' => function ($serviceLocator){
        			$as = new \Mailer\Factory\RendererFactory();
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
			)
    	);
    }
}