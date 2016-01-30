<?php

namespace Apartment;

use Apartment\View\Helper\ApartmentCurrencyBadge;
use Apartment\View\Helper\Badges;
use Library\Utility\Debug;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Apartment\View\Helper\ApartmentNavigation;
use Apartment\View\Helper\ApartmentNameAndAddress;
use Apartment\View\Helper\InventoryCalendarMonthNavigation;
use Apartment\View\Helper\RateNavigation;
use Apartment\View\Helper\ApartmentWebsiteLink;
use Apartment\View\Helper\ApartmentPrintLink;
use Apartment\View\Helper\ApartmentReservationsLink;
use Apartment\View\Helper\ApartmentStatusBadge;
use Apartment\View\Helper\ApartmentCubilisBadge;
use Apartment\View\Helper\ApartmentReviewScoreBadge;
use Apartment\View\Helper\ApartmentCurrentReservationLink;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;
use Zend\Validator\Db\RecordExists;
use Library\Constants\DbTables;

class Module implements AutoloaderProviderInterface {
	public function getAutoloaderConfig() {
		return array (
            'Zend\Loader\StandardAutoloader' => array (
                'namespaces' => array (
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace ( '\\', '/', __NAMESPACE__ ),
                    'Library' => __DIR__ . '/../../library/Library',
                    'DDD' => __DIR__ . '/../../library/DDD'
                )
            )
		);
	}

	public function onBootstrap(MvcEvent $e) {
		$eventManager = $e->getApplication()->getEventManager();
		$eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'onViewHelper']);
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);

		$e->getApplication()->getEventManager()->getSharedManager()->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function ($e) {
			$controller = $e->getTarget();

			$routeMatch = $e->getRouteMatch();
			$apartmentId = $routeMatch->getParam('apartment_id', 0); // get the apartment ID

			if (method_exists($controller, 'setApartmentID')) {

				$serviceManager = $e->getApplication()->getServiceManager();
				$dbAdapter = $serviceManager->get('Zend\Db\Adapter\Adapter');

				$apartmentExistValidator = new RecordExists(['adapter' => $dbAdapter, 'table' => DbTables::TBL_APARTMENTS, 'field' => 'id']);

				if (!$apartmentExistValidator->isValid($apartmentId) && $apartmentId != 0) {
					$url = $e->getRouter()->assemble(array('controller' => 'apartment', 'action' => 'search'), ['name' => 'apartments']);
	        		$response = $e->getResponse();
	        		$response->getHeaders()->addHeaderLine('Location', $url);
	        		$response->setStatusCode(302);
	        		$response->sendHeaders();
	        		return $response;
				}

				$controller->setApartmentID($apartmentId);
			}
		}, 100);
	}

	/**
	 *
	 * @param MvcEvent $e
	 */
	public function onViewHelper(MvcEvent $e) {
		$routeMatch = $e->getRouteMatch();
		$routeParts = explode('/', $routeMatch->getMatchedRouteName());

		if (count($routeParts) && $routeParts[0] == 'apartment') {
			$e->getApplication()->getEventManager()->getSharedManager()->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function ($e) {
				/**
				 * @var RendererInterface $renderer
				 * @var \ArrayObject $viewModel
				 */
				$serviceManager = $e->getApplication()->getServiceManager();
				$renderer = $serviceManager->get('Zend\View\Renderer\RendererInterface');
				$routeMatch = $e->getRouteMatch();
				$apartmentId = $routeMatch->getParam('apartment_id', 0);

				// Create the views
				$view = new ViewModel(['apartmentId' => $apartmentId]);
				$view->setTemplate('apartment/partial/badges');

				// Render the message
				$markup = $renderer->render($view);

				$viewModel = $e->getViewModel();
				$viewModel->apartmentBadges = $markup;
			}, 100);
		}
	}

	public function getViewHelperConfig() {
		return array (
            'factories' => array (
                'apartmentNavigation' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentNavigation();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentNameAndAddress' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentNameAndAddress();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentWebsiteLink' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentWebsiteLink();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentPrintLink' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentPrintLink();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentStatusBadge' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentStatusBadge();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentCurrencyBadge' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentCurrencyBadge();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentCubilisBadge' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentCubilisBadge();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'apartmentReviewScoreBadge' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentReviewScoreBadge();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'inventoryCalendarMonthNavigation' => function ($sm) {
                    $viewHelper = new InventoryCalendarMonthNavigation();

                    return $viewHelper;
                },
                'rateNavigation' => function ($sm) {
                    $viewHelper = new RateNavigation();

                    return $viewHelper;
                },
                'apartmentReservationsLink' => function ($sm) {
                    $viewHelper = new ApartmentReservationsLink();

                    return $viewHelper;
                },
                'apartmentCurrentReservationLink' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new ApartmentCurrentReservationLink();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
            )
		);
	}

	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}

    public function getServiceConfig() {
        return array(
            'invokables' => array(
                'service_apartment_media'  => 'DDD\Service\Apartment\Media',
            )
        );
    }
}
