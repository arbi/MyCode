<?php

namespace Apartel;

use Apartel\View\Helper\ApartelHeader;
use Apartel\View\Helper\ApartelNavigation;
use Apartel\View\Helper\TypeRateNavigation;
use Apartel\View\Helper\InventoryCalendar;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;

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
        $event        = $e->getApplication();
        $eventManager = $event->getEventManager();

        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, [$this, 'onPreDispatch']);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    /**
     * @param MvcEvent $e
     */
    public function onPreDispatch(MvcEvent $e)
    {
        $e->getApplication ()->getEventManager ()->getSharedManager ()->attach ( 'Zend\Mvc\Controller\AbstractActionController', 'dispatch', function ($e) {
            $controller = $e->getTarget ();

            $routeMatch = $e->getRouteMatch ();
            $apartelId = $routeMatch->getParam ( 'apartel_id', 0 );
            if (method_exists ( $controller, 'setApartelId' )) {
                $controller->setApartelId ( $apartelId );

                if (!$controller->getApartelId()) {
                    $url = $e->getRouter()->assemble(array('controller' => 'apartment-group'), ['name' => 'backoffice/default']);
                    $response = $e->getResponse();
                    $response->getHeaders()->addHeaderLine('Location', $url);
                    $response->setStatusCode(302);
                    $response->sendHeaders();
                    return $response;
                }
                $viewModel = $e->getViewModel();
                $viewModel->apartelId = $apartelId;
            }
        }, 100 );
    }

    /**
     * @return array
     */
    public function getViewHelperConfig()
    {
        return array (
            'factories' => array (
                'apartelHeader' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartelHeader();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartelNavigation' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartelNavigation();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'typeRateNavigation' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new TypeRateNavigation();
                    $viewHelper->setServiceLocator($locator);
                    return $viewHelper;
                },
                'inventoryCalendar' => function ($sm) {
                    $viewHelper = new InventoryCalendar();
                    return $viewHelper;
                },
            )
        );
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
                'service_apartel_ota_distribution'  => 'DDD\Service\Apartel\OTADistribution',
                'service_distribution_view'         => 'DDD\Service\Apartel\DistributionView',
                'service_apartel_connection'        => 'DDD\Service\Apartel\Connection',
                'service_apartel_type'              => 'DDD\Service\Apartel\Type',
                'service_apartel_inventory'         => 'DDD\Service\Apartel\Inventory',
                'service_apartel_rate'              => 'DDD\Service\Apartel\Rate',
                'service_apartel_general'           => 'DDD\Service\Apartel\General',
                'service_apartel_calendar'          => 'DDD\Service\Apartel\Calendar',
                'service_apartel_content'           => 'DDD\Service\Apartel\Content',
            ],
            'factories' => [
                'DDD\Dao\Apartel\OTADistribution' =>  function($sm) {
                    $instance = new \DDD\Dao\Apartel\OTADistribution($sm);
                    return $instance;
                },
                'DDD\Dao\Apartel\General' => function($sm){
                    $as = new \DDD\Dao\Apartel\General($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Type' => function($sm){
                    $as = new \DDD\Dao\Apartel\Type($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Rate' => function($sm){
                    $as = new \DDD\Dao\Apartel\Rate($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Inventory' => function($sm){
                    $as = new \DDD\Dao\Apartel\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\RelTypeApartment' => function($sm){
                    $as = new \DDD\Dao\Apartel\RelTypeApartment($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Details' => function($sm){
                    $as = new \DDD\Dao\Apartel\Details($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Fiscal' => function($sm){
                    $as = new \DDD\Dao\Apartel\Fiscal($sm);
                    return $as;
                },
            ],
            'aliases' => [
                'dao_apartel_ota_distribution'      => 'DDD\Dao\Apartel\OTADistribution',
                'dao_apartel_general'               => 'DDD\Dao\Apartel\General',
                'dao_apartel_type'                  => 'DDD\Dao\Apartel\Type',
                'dao_apartel_rate'                  => 'DDD\Dao\Apartel\Rate',
                'dao_apartel_inventory'             => 'DDD\Dao\Apartel\Inventory',
                'dao_apartel_rel_type_apartment'    => 'DDD\Dao\Apartel\RelTypeApartment',
                'dao_apartel_details'               => 'DDD\Dao\Apartel\Details',
                'dao_apartel_fiscal'                => 'DDD\Dao\Apartel\Fiscal',
            ]
        ];
    }
}
