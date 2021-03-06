<?php

namespace FileManager;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;

class Module implements AutoloaderProviderInterface
{
	public function getAutoloaderConfig()
    {
		return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php'
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace ( '\\', '/', __NAMESPACE__ ),
                    'Library' => __DIR__ . '/../../library/Library',
                    'DDD' => __DIR__ . '/../../library/DDD'
                ]
            ]
		];
	}

    /**
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {

	}

    /**
     * @param MvcEvent $e
     */
    public function onViewHelper(MvcEvent $e)
    {

	}

    /**
     * @return array
     */
    public function getViewHelperConfig()
    {
		return [
            'factories' => [

            ]
		];
	}

    /**
     * @return mixed
     */
    public function getConfig()
    {
		return include __DIR__ . '/config/module.config.php';
	}

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [

            ],
            'factories' => [
                'FileManager\Service\GenericDownloader' =>  function($sm) {
                    $instance = new \FileManager\Service\GenericDownloader($sm);
                    return $instance;
                },
            ],
            'aliases' => [
                'fm_generic_downloader'  => 'FileManager\Service\GenericDownloader',
            ]
        ];
    }
}
