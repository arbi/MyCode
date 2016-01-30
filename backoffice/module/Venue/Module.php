<?php

namespace Venue;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;

/**
 * Class Module
 *
 * @package Venue
 * @author  Harut Grigoryan
 */
class Module implements AutoloaderProviderInterface
{
    /**
     * @return array
     */
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
                    'Library'     => __DIR__ . '/../../library/Library',
                    'DDD'         => __DIR__ . '/../../library/DDD'
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
                'service_venue_venue'   => 'DDD\Service\Venue\Venue',
                'service_venue_charges' => 'DDD\Service\Venue\Charges',
                'service_venue_items' => 'DDD\Service\Venue\Items',
            ],
            'aliases'=> [
                'dao_venue_venue'                   => 'DDD\Dao\Venue\Venue',
                'dao_venue_charges'                 => 'DDD\Dao\Venue\Charges',
                'dao_venue_items'                   => 'DDD\Dao\Venue\items',
                'dao_venue_lunchroom_order_archive' => 'DDD\Dao\Venue\LunchroomOrderArchive'
            ],
            'factories' => [
                'DDD\Service\Venue\Venue' => function($sm){
                    $service = new \DDD\Service\Venue\Venue();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Service\Venue\Charges' => function($sm){
                    $service = new \DDD\Service\Venue\Charges();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Service\Venue\Items' => function($sm){
                    $service = new \DDD\Service\Venue\Items();
                    $service->setServiceLocator($sm);
                    return $service;
                },

                'DDD\Dao\Venue\Venue' => function($sm){
                    $dao = new \DDD\Dao\Venue\Venue($sm);
                    return $dao;
                },
                'DDD\Dao\Venue\Charges' => function($sm){
                    $dao = new \DDD\Dao\Venue\Charges($sm);
                    return $dao;
                },
                'DDD\Dao\Venue\Items' => function($sm){
                    $dao = new \DDD\Dao\Venue\Items($sm);
                    return $dao;
                },
                'DDD\Dao\Venue\LunchroomOrderArchive' => function($sm){
                    $dao = new \DDD\Dao\Venue\LunchroomOrderArchive($sm);
                    return $dao;
                }
            ]
        ];
    }
}
