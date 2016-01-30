<?php

namespace Parking;

use Library\Utility\Debug;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Parking\View\Helper\ParkingNavigation;
use Parking\View\Helper\ParkingInventoryCalendarMonthNavigation;
use Parking\View\Helper\ParkingPageTitle;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;
use Zend\Validator\Db\RecordExists;
use Library\Constants\DbTables;

class Module implements AutoloaderProviderInterface {
	public function getAutoloaderConfig()
    {
		return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace ( '\\', '/', __NAMESPACE__ ),
                    'Library' => __DIR__ . '/../../library/Library',
                    'DDD' => __DIR__ . '/../../library/DDD'
                ],
            ],
		];
	}

	public function onBootstrap(MvcEvent $e)
    {
		$eventManager = $e->getApplication ()->getEventManager ();
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);

		$e->getApplication ()->getEventManager ()->getSharedManager ()->attach ( 'Zend\Mvc\Controller\AbstractActionController', 'dispatch', function ($e) {
			$controller = $e->getTarget ();

			$routeMatch = $e->getRouteMatch ();
			$parkingLotId = $routeMatch->getParam ( 'parking_lot_id', 0 ); // get the parking lot id

			if (method_exists ( $controller, 'setParkingLotId' )) {

				$serviceManager = $e->getApplication()->getServiceManager();
				$dbAdapter = $serviceManager->get('Zend\Db\Adapter\Adapter');

				$parkingLotExistsValidator = new RecordExists(['adapter' => $dbAdapter, 'table' => DbTables::TBL_PARKING_LOTS, 'field' => 'id']);

				if (!$parkingLotExistsValidator->isValid($parkingLotId) && $parkingLotId != 0) {
					$url = $e->getRouter()->assemble(
                        [
                            'controller' => 'parking',
                            'action' => 'index'
                        ],
                        ['name' => 'parking']
                    );
	        		$response = $e->getResponse();
	        		$response->getHeaders()->addHeaderLine('Location', $url);
	        		$response->setStatusCode(302);
	        		$response->sendHeaders();
	        		return $response;
				}

				$controller->setParkingLotId($parkingLotId);
			}
		}, 100 );
	}

	public function getViewHelperConfig() {
		return [
            'factories' => [
                'parkingPageTitle' => function($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ParkingPageTitle();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'parkingNavigation' => function($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ParkingNavigation();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'parkingInventoryCalendarMonthNavigation' => function($sm) {
                    $viewHelper = new ParkingInventoryCalendarMonthNavigation();

                    return $viewHelper;
                },

            ]
		];
	}

	/**
	 */
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
}
