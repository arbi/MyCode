<?php

namespace Library\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Authentication\BackofficeAuthenticationService;
use Zend\Config\Config;
use Zend\Config\Writer\PhpArray;
use Zend\Session\Container;

set_include_path(get_include_path() . PATH_SEPARATOR .'/ginosi/backoffice/library/google/apiclient/src');
require_once 'Google/Client.php';
require_once 'Google/Service/Oauth2.php';

class GoogleAuth implements FactoryInterface
{
    private $serviceLocator = null;

    public function createService(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
        return $this;

    }

    public function authenticate($sl)
    {
        $response  = null;
        $config    = $sl->get('config');
        $googleApi = $config['google-api'];
        $gClient   = new \Google_Client();

        $gClient->setApplicationName('ginosi');
        $gClient->setClientId($googleApi['clientId']);
        $gClient->setClientSecret($googleApi['clientSecret']);
        $gClient->setRedirectUri($googleApi['redirectUri']);
        $gClient->setDeveloperKey($googleApi['developerKey']);
        $gClient->setScopes($googleApi['scopes']);

        $google_oauthV2 = new \Google_Service_Oauth2($gClient);

        if (isset($_REQUEST['reset'])) {
            unset($_SESSION['token']);
            $gClient->revokeToken();
            header(
                'Location: ' .
                filter_var(
                    $google_redirect_url,
                    FILTER_SANITIZE_URL
                )
            );
        }

        if (isset($_GET['code'])) {
            $session = new Container('accesstoken');
            $gClient->authenticate($_GET['code']);
            $accessToken = $gClient->getAccessToken();
            $data        = \Zend\Json\Json::decode(
                $accessToken,
                \Zend\Json\Json::TYPE_ARRAY
            );

            $session->token = $data['access_token'];
        }

        if (isset($accessToken)) {
            $gClient->setAccessToken($gClient->getAccessToken());
        }

        if ($gClient->getAccessToken()) {
            $user  = $google_oauthV2->userinfo->get();
            $email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);

            $result = ['verified', $email];

            return $result;
        } else {
            return $gClient->createAuthUrl();
        }
    }
}
