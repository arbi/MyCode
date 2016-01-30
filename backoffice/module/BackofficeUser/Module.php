<?php

namespace BackofficeUser;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;
use BackofficeUser\View\Helper\DynamicLoginForm;
use BackofficeUser\View\Helper\DynamicLoginFormBt3;

class Module implements AutoloaderProviderInterface
{
	public function getAutoloaderConfig() {
		return array (
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php'
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array (
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace ( '\\', '/', __NAMESPACE__ ),
                    'Library' => __DIR__ . '/../../library/Library',
                    'DDD' => __DIR__ . '/../../library/DDD'
                )
            )
		);
	}

	public function onBootstrap(MvcEvent $e)
    {

	}

	public function onViewHelper(MvcEvent $e)
    {

	}

	public function getViewHelperConfig()
    {
		return array (
            'factories' => array(
                'dynamicLoginWidget' => function ($sm) {
                    $viewHelper = new DynamicLoginForm();

                    return $viewHelper;
                },
                'dynamicLoginWidgetBt3' => function ($sm) {
                    $viewHelper = new DynamicLoginFormBt3();

                    return $viewHelper;
                },
            )
		);
	}

	public function getConfig()
    {
		return include __DIR__ . '/config/module.config.php';
	}

    public function getServiceConfig()
    {
        return array(
            'invokables' => array()
        );
    }
}
