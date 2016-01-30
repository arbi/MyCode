<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Crypt\Password\Bcrypt;
use Zend\Http\Header\Authorization;
use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Entity\Error;
use Application\Service\ApiException;

use Library\Constants\DomainConstants;
use Library\Constants\Roles;

use OAuth2\ResponseType\AccessToken;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;


class AuthenticateController extends AbstractRestfulController
{
    /**
     * @return array
     *
     * @api {get} API_DOMAIN/auth/login Login
     * @apiVersion 1.0.0
     * @apiName Login
     * @apiGroup Authentication
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiDescription This method is used to login into Ginosi system. It requires a valid access_token retrieved from /auth/authorization end point
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "12",
     *         "firstname": "App",
     *         "lastname": "User",
     *         "email": "app.user@ginosi.com",
     *         "cityId": "62",
     *         "countryId": "21",
     *         "permissions": {
     *             "warehouse": true,
     *             "incident": true
     *         },
     *         "profileUrl": "https://images.ginosi.com/profile/12/144342324403_0_150.png"
     *     }
     */
    public function loginAction()
    {
        try {
            $requestHandler = $this->getServiceLocator()->get('Service\RequestHandler');
            $authService    = $this->getServiceLocator()->get('library_backoffice_auth');
            $userService    = $this->getServiceLocator()->get('service_user');

            if (   !$this->getRequest()->getHeaders()->get('Authorization') instanceof Authorization
                || !$authService->hasRole(Roles::ROLE_MOBILE_APPLICATION)
            ) {
                throw new ApiException(Error::AUTHENTICATION_FAILED_CODE);
            }

            $userInfo = $userService->getAuthenticatedUserInfo();
            if (!$userInfo) {
                throw new ApiException(Error::INVALID_TOKEN_CODE);
            }

            return new JsonModel($userInfo);
        } catch (\Exception $e) {
            return new ApiProblemResponse($requestHandler->handleException($e));
        }
    }

    /**
     * @return array
     *
     * @api {post} API_DOMAIN/auth/authorization Authorization
     * @apiVersion 1.0.0
     * @apiName Authorization
     * @apiGroup Authentication
     *
     * @apiHeader {String} Content-Type application/json
     *
     * @apiDescription This is the function of specifying access rights to resources and access control in particular.
     * This method returns a valid OAuth 2.0 token set for future authentication requests based on the provider parameter
     *
     * @apiParam {String} provider      This is the authorization provider string. The possible values are Ginosi and Google
     * @apiParam {String} [username]    The username of the account for Ginosi provider
     * @apiParam {String} [password]    The password of the account for Ginosi provider
     * @apiParam {String} grant_type    This is the OAuth 2.0 grant_type parameter. The possible values are password and refresh_token
     * @apiParam {String} client_id     This is the OAuth 2.0 client_id parameter. The value for this parameter can be found in credentials set
     * @apiParam {String} client_secret This is the OAuth 2.0 client_secret parameter. The value for this parameter can be found in credentials set
     *
     * @apiParamExample {json} Ginosi Sample Request:
     *     {
     *       "provider": "ginosi",
     *       "username": "app.user@ginosi.com",
     *       "password": "XXX"
     *       "grant_type": "password",
     *       "client_id": "XXX",
     *       "client_secret": "XXX"
     *     }
     *
     * @apiParamExample {json} Google Sample Request:
     *     {
     *       "provider": "google",
     *       "token": "32c6f1120265f610bcf6829148a5c01c19ea2013",
     *       "grant_type": "password",
     *       "client_id": "XXX",
     *       "client_secret": "XXX"
     *     }
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "access_token": "32c6f1120265f610bcf6829148a5c01c19ea2013",
     *         "expires_in": 86400,
     *         "token_type": "Bearer",
     *         "scope": null,
     *         "refresh_token": "a9a36d81152a2e06945646ab74924bc5d50ceedf"
     *     }
     */
    public function authorizationAction()
    {
        try {
            $requestHandler = $this->getServiceLocator()->get('Service\RequestHandler');

            $data = $this->getRequest()->getContent();
            $data = \Zend\Json\Json::decode($data, \Zend\Json\Json::TYPE_ARRAY);
            // If request has token parameter so its social login, otherwise its ginosi login
            if (isset($data['provider'])) {
                switch ($data['provider']) {
                    case 'ginosi':
                        return $this->ginosiAuth($data);
                        break;
                    case 'google':
                        return $this->googleAuth($data);
                        break;
                    default:
                        throw new ApiException(Error::AUTHENTICATION_FAILED_CODE);
                }
            } else {
                throw new ApiException(Error::AUTHENTICATION_FAILED_CODE);
            }

        } catch (\Exception $e) {
            return new ApiProblemResponse($requestHandler->handleException($e));
        }
    }

    /**
     * @param $data
     * @return JsonModel|ApiProblemResponse
     */
    private function ginosiAuth($data)
    {
        try {
            $requestHandler = $this->getServiceLocator()->get('Service\RequestHandler');

            if (   !isset($data['username'])
                || !isset($data['password'])
                || !isset($data['grant_type'])
                || !isset($data['client_id'])
                || !isset($data['client_secret'])
            ) {
                throw new ApiException(Error::INCOMPLETE_PARAMETERS_CODE);
            }

            $postData = [
                'username'      => $data['username'],
                'password'      => $data['password'],
                'grant_type'    => $data['grant_type'],
                'client_id'     => $data['client_id'],
                'client_secret' => $data['client_secret'],
            ];
            $jsonData = json_encode($postData);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/oauth');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            curl_close ($ch);
            $response = json_decode($response);

            if (!isset($response->access_token)) {
                $this->getResponse()->setStatusCode($response->status);
            }

            return new JsonModel((array)$response);

        } catch (\Exception $e) {
            return new ApiProblemResponse($requestHandler->handleException($e));
        }
    }

    /**
     * @param $data
     * @return JsonModel|ApiProblemResponse
     */
    private function googleAuth($data)
    {
        try {
            $userManagerService  = $this->getServiceLocator()->get('service_user');
            $userManagerDao      = $this->getServiceLocator()->get('dao_user_user_manager');
            $requestHandler      = $this->getServiceLocator()->get('Service\RequestHandler');
            $authService         = $this->getServiceLocator()->get('library_backoffice_auth');
            $oauth2ServerFactory = $this->getServiceLocator()->get('ZF\OAuth2\Service\OAuth2Server');

            if (   !isset($data['token'])
                || !isset($data['grant_type'])
                || !isset($data['client_id'])
                || !isset($data['client_secret'])
            ) {
                throw new ApiException(Error::INCOMPLETE_PARAMETERS_CODE);
            }

            $userinfo   = 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $data['token'];
            $googleInfo = @file_get_contents($userinfo);

            if (!$googleInfo) {
                throw new ApiException(Error::USER_NOT_FOUND_CODE);
            }

            $userInfoArray = json_decode($googleInfo, true);

            $googleEmail = $userInfoArray['email'];

            $columns  = ['id', 'email', 'firstname', 'lastname', 'cityId' => 'city_id', 'countryId' => 'country_id', 'password'];
            $userInfo = $userManagerDao->getUserByEmail($googleEmail, true, $columns);

            if (!$userInfo) {
                throw new ApiException(Error::USER_NOT_FOUND_CODE);
            }

            $server              = call_user_func($oauth2ServerFactory, '/oauth');
            $accessTokenStorage  = $server->getStorage('access_token');
            $refreshTokenStorage = $server->getStorage('refresh_token');

            $accessToken = new AccessToken($accessTokenStorage, $accessTokenStorage);
            $response    = $accessToken->createAccessToken($data['client_id'], $userInfo['email'], null);

            if (!$response) {
                throw new ApiException(Error::INVALID_TOKEN_CODE);
            }

            return new JsonModel($response);
        } catch (\Exception $e) {
            return new ApiProblemResponse($requestHandler->handleException($e));
        }
    }

    /**
     * @return array
     *
     *
     * @api {get} API_DOMAIN/auth/logout Logout
     * @apiVersion 1.0.0
     * @apiName Logout
     * @apiGroup Authentication
     *
     * @apiDescription This method is used to logout from the Ginosi system
     *
     * @apiHeader {String} Content-Type application/json
     * @apiHeader {String} Authorization Bearer ACCESS_TOKEN
     *
     * @apiSuccessExample {json} Sample Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "response": "Success"
     *     }
     */
    public function logoutAction()
    {
        try {
            $userService    = $this->getServiceLocator()->get('service_user');
            $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
            $requestHandler = $this->getServiceLocator()->get('Service\RequestHandler');

            if ($auth->hasIdentity()) {

                $email = $auth->getIdentity()->email;

                $userService->deleteUserToken($email);

                $auth->clearIdentity();

                $session = new Container('accesstoken');
                $session->getManager()->getStorage()->clear('accesstoken');

                $logoutSession = new Container('logout');
                $logoutSession->offsetSet('loggedOut', true);
            }

            return new JsonModel(['response' => 'Success']);
        } catch (\Exception $e) {
            return new ApiProblemResponse($requestHandler->handleException($e));
        }
    }

    //OAUTH AUTHENTICATION API DOC <<< DO NOT REMOVE IT >>>

     /**
      * @return array
      *
      * @api {post} API_DOMAIN/auth/refresh-token Refresh Token
      * @apiVersion 1.0.0
      * @apiName RefreshToken
      * @apiGroup Authentication
      *
      * @apiHeader {String} Content-Type application/json
      *
      * @apiDescription This method returns a new valid OAuth 2.0 token set based on the refresh_token
      *
      * @apiParamExample {json} Sample Request:
      *     {
      *         "grant_type": "refresh_token",
      *         "refresh_token": "2b2cf16711c98ced53e17c14c2016e5259491fb4",
      *         "client_id": "XXX",
      *         "client_secret": "XXX"
      *     }
      *
      * @apiSuccessExample {json} Sample Response:
      *     HTTP/1.1 200 OK
      *     {
      *          "access_token": "32c6f1120265f610bcf6829148a5c01c19ea2013",
      *          "expires_in": 86400,
      *          "token_type": "Bearer",
      *          "scope": null,
      *          "refresh_token": "a9a36d81152a2e06945646ab74924bc5d50ceedf"
      *     }
      */
     public function refreshTokenAction()
     {
         try {
             $requestHandler = $this->getServiceLocator()->get('Service\RequestHandler');

             $data = $this->getRequest()->getContent();
             $data = \Zend\Json\Json::decode($data, \Zend\Json\Json::TYPE_ARRAY);

             if (   !isset($data['grant_type'])
                 || !isset($data['client_id'])
                 || !isset($data['client_secret'])
                 || !isset($data['refresh_token'])
             ) {
                 throw new ApiException(Error::INCOMPLETE_PARAMETERS_CODE);
             }

             $postData = [
                 'grant_type'    => $data['grant_type'],
                 'refresh_token' => $data['refresh_token'],
                 'client_id'     => $data['client_id'],
                 'client_secret' => $data['client_secret'],
             ];

             $jsonData = json_encode($postData);

             $ch = curl_init();

             curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/oauth');
             curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
             curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
             curl_setopt($ch, CURLOPT_POST, 1);
             curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
             curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

             $response = curl_exec($ch);
             curl_close ($ch);
             $response = json_decode($response);

             if (!isset($response->access_token)) {
                 $this->getResponse()->setStatusCode($response->status);
             }

             return new JsonModel((array)$response);

         } catch (\Exception $e) {
             return new ApiProblemResponse($requestHandler->handleException($e));
         }
     }

}
