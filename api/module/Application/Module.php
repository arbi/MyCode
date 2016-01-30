<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Authentication\AuthStorage;
use Library\Authentication\BcryptDbAdapter;
use Library\Constants\DbTables;

use Application\Listener\OAuth;
use Application\Entity\Error;

use ApiLibrary\Acl\AclManager;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {

        $serviceManager = $e->getApplication()->getServiceManager();
        $application    = $e->getApplication();
        $router         = $e->getRouter();
        $request        = $e->getRequest();

        $eventManager = $application->getEventManager();

        // OAuth::onRoute($e);

        $eventManager->attach('route', array('Application\Listener\OAuth', 'onRoute'), 1000);
        $eventManager->attach('dispatch', array($this, 'onPreDispatch'), 100);
    }

    /**
     * @param MvcEvent $e
     * @return Response, \Zend\Json\Server\Response
     */
    public function onPreDispatch(MvcEvent $e)
    {
        $app            = $e->getTarget();
        $sm             = $app->getServiceManager();
        $requestHandler = $sm->get('Service\RequestHandler');

        /**
         * @var Response $response
         */
        $routeMatch = $e->getRouteMatch();
        $router     = $e->getRouter();
        $request    = $e->getRequest();

        $controller = strtolower($routeMatch->getParam('controller'));
        $action     = strtolower($routeMatch->getParam('action'));

        $validRoutes = ['auth', 'oauth', 'file'];

        if (   !is_null($router->match($request))
            && !in_array($router->match($request)->getMatchedRouteName(), $validRoutes)
            && !preg_match('/zf-apigility(.*)/', $router->match($request)->getMatchedRouteName())
        ) {
            $routeName                 = $router->match($request)->getMatchedRouteName();
            $requestMethod             = $request->getMethod();

            $serviceManager            = $e->getApplication()->getServiceManager();
            $request                   = $serviceManager->get('Request');
            $serverParam               = $request->getServer();
            $serviceUserAuthentication = $serviceManager->get('library_backoffice_auth');

            $aclManager                = new AclManager($serviceManager);
            $permissionDeny            = false;

            if ($serviceUserAuthentication->hasIdentity()) {
                if (!$serviceUserAuthentication->getIdentity()) {
                    $role = ROLE_GUEST;
                } else {
                    $role = $serviceUserAuthentication->getIdentity()->id;
                }

                if (!$aclManager->hasResource($routeName) || !$aclManager->isAllowed($role, $routeName, $requestMethod)) {
                    $permissionDeny = true;
                }
            } else {
              $permissionDeny = true;
            }

            if ($permissionDeny) {
              $error = $requestHandler->getHttpContentBody(
                Error::AUTHENTICATION_FAILED_CODE,
                true,
                $requestHandler::RESPONSE_TYPE_JSON
              );

              $response = $e->getResponse();

              $response->setStatusCode($error['code']);
              $response->getHeaders()->addHeaderLine("Content-Type", 'application/json');

              $response->setContent($error['errorBody']);

              $responseArray = json_decode($error['errorBody'], true);

              $requestHandler->apiMonitor(
                  $error['code'],
                  $responseArray['status'],
                  $responseArray['detail']['message']
              );

              return $response;
            }
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'ApiLibrary'  => __DIR__ . '/../../library/Library',
                    'Library'     => '/ginosi/backoffice/library/Library',
                    'DDD'         => '/ginosi/backoffice/library/DDD',
                ],
            ],
        ];
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_user' => 'DDD\Service\User',
            ],
            'factories' => [
                'Service\RequestHandler' => function ($sm) {
                    return new Service\RequestHandler($sm);
                },
                'DDD\Dao\Api\ApiRequests' => function($sm) {
                    return new \DDD\Dao\Api\ApiRequests($sm);
                },
                'Library\Authentication\BackofficeAuthenticationService' => function($sm) {
                    $authAdapter = new BcryptDbAdapter($sm->get('dbadapter'), DbTables::TBL_BACKOFFICE_USERS, 'email', 'password');
                    $authStorage = new AuthStorage();

                    $backofficeAuthenticationService = new BackofficeAuthenticationService($authStorage, $authAdapter);
                    $backofficeAuthenticationService->setServiceManager($sm);

                    return $backofficeAuthenticationService;
                },
                'OAuthProvider'                         => 'Application\Service\OAuthProviderFactory',
                'TokenAuthentication'                   => 'Application\Service\TokenAuthentication',
                'Zend\Db\Adapter\AdapterServiceFactory' => 'Zend\Db\Adapter\AdapterServiceFactory',
            ],
            'aliases'=> [
                'library_backoffice_auth' => 'Library\Authentication\BackofficeAuthenticationService',
                'dao_api_api_requests'    => 'DDD\Dao\Api\ApiRequests',
            ]

        ];
    }
}
