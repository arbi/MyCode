<?php

namespace Application\Listener;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

use OAuth2\Server as OAuth2Server;
use OAuth2\Request as OAuth2Request;

use Application\Entity\Error;

class OAuth
{
    /**
     * @param MvcEvent $e
     */
    public static function onRoute(MvcEvent $e)
    {
        $router       = $e->getRouter();
        $request      = $e->getRequest();
        $validRoutes  = ['oauth'];
        $validActions = ['refresh-token', 'authorization'];

        try {
            $app            = $e->getTarget();
            $sm             = $app->getServiceManager();
            $requestHandler = $sm->get('Service\RequestHandler');

            if (!$router->match($request) instanceof \Zend\Mvc\Router\Http\RouteMatch) {
                $error    = $requestHandler->getHttpContentBody(Error::NOT_FOUND_CODE, true, $requestHandler::RESPONSE_TYPE_JSON);
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

            if ((  !is_null($router->match($request))
                && !in_array($router->match($request)->getMatchedRouteName(), $validRoutes)
                && !in_array($router->match($request)->getParam('action'), $validActions)
                && !preg_match('/zf-apigility(.*)/', $router->match($request)->getMatchedRouteName())
                )
            ) {
                $token     = $e->getRequest()->getQuery('oauth_token', null);
                $hasHeader = $e->getRequest()->getHeaders()->has('Authorization');

                if (!$token && !$hasHeader || (!$e->getRequest() instanceof HttpRequest)) {
                    $error    = $requestHandler->getHttpContentBody(Error::INVALID_TOKEN_CODE, true, $requestHandler::RESPONSE_TYPE_JSON);
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

                $backofficeAuthenticationService = $sm->get('library_backoffice_auth');

                $zfoauth2serviceoauth2server = call_user_func($app->getServiceManager()->get('zfoauth2serviceoauth2server'));
                if (!$zfoauth2serviceoauth2server->verifyResourceRequest(OAuth2Request::createFromGlobals())) {

                    $error    = $requestHandler->getHttpContentBody(Error::INVALID_TOKEN_CODE, true, $requestHandler::RESPONSE_TYPE_JSON);
                    $response = $e->getResponse();

                    $response->setStatusCode($error['code']);
                    $response->getHeaders()->addHeaderLine("Content-Type", 'application/json');
                    $response->setContent($error['errorBody']);

                    $backofficeAuthenticationService->clearIdentity();

                    $session = new Container('accesstoken');
                    $session->getManager()->getStorage()->clear('accesstoken');

                    $logoutSession = new Container('logout');
                    $logoutSession->offsetSet('loggedOut', true);

                    $responseArray = json_decode($error['errorBody'], true);

                    $requestHandler->apiMonitor(
                        $error['code'],
                        $responseArray['status'],
                        $responseArray['detail']['message']
                    );

                    return $response;
                } else {
                    $bearerTokenArray = [];
                    $bearerToken      = $app->getRequest()->getHeaders()->get('Authorization')->getFieldValue();
                    $bearerTokenArray = explode(' ', $bearerToken);
                    $token            = $bearerTokenArray[1];
                    $userService      = $sm->get('service_user');
                    $userInfoArray    = $userService->getUserInfoByToken($token);

                    if (!$userInfoArray) {
                        $error    = $requestHandler->getHttpContentBody(Error::USER_NOT_FOUND_CODE, true, $requestHandler::RESPONSE_TYPE_JSON);
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
                    $backofficeAuthenticationService->clearIdentity();

                    $userData = (object)$userInfoArray;
                    $backofficeAuthenticationService->getStorage()->write($userData);

                    // update user last login date and time
                    $userManagerService = $sm->get('service_user');
                    $userManagerService->updateLastLogin($userData->id);
                }

                return true;
            }
        } catch (\OAuthException $e) {
            return;
        }
    }
}
