<?php

namespace BackofficeUser\Controller;

use DDD\Service\Authentication;
use Library\Controller\ControllerBase;
use BackofficeUser\Form\Login as LoginForm;
use BackofficeUser\Form\LoginFilter;
use Zend\View\Model\ViewModel;
use BackofficeUser\Form\Login;
use Zend\Authentication\Result;
use Zend\View\Model\JsonModel;
use Library\Constants\Constants;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Authentication\DbAdapter;
use Library\Authentication\AuthStorage;
use Library\Constants\DbTables;
use Library\Utility\Helper;
use Zend\Session\Container;
use DDD\Dao\User\UserManager;

class AuthenticationController extends ControllerBase
{
    const AUTHENTICATION_FAILED = 1;
    const CONNECTION_TIMEDOUT   = 2;

    public function loginAction()
    {
        /**
         * @var BackofficeAuthenticationService $backofficeAuthenticationService
         */
        $this->layout('layout/login');
        $session        = new Container('authFailed');
        $router         = $this->getEvent()->getRouter();
        $request        = $this->getRequest();
        $lastRequestUrl = $request->getQuery()->request_url;
        $loginUrl       = $router->assemble([], ['name' => 'backoffice_user_login']);

        if ($session->authFailed) {
            $form = new LoginForm();
            $form->setInputFilter(new LoginFilter());

            $error = ($session->authFailed === self::CONNECTION_TIMEDOUT) ?
                'Connection Timed Out.' :
                'Authentication failed.';

            $session->getManager()->getStorage()->clear('authFailed');

            return new ViewModel(
                [
                    'form'              => $form,
                    'error'             => $error,
                    'backofficeVersion' => Constants::APP_VERSION,
                ]
            );
        }
        $serviceLocator = $this->getServiceLocator();

        $backofficeAuthenticationService = $serviceLocator->get('library_backoffice_auth');

        if ($backofficeAuthenticationService->hasIdentity()) {

            $redirectHome = $backofficeAuthenticationService->getHomeUrl();

            if (!empty($lastRequestUrl) && ($lastRequestUrl != $loginUrl)) {
                $redirectUrl = $lastRequestUrl;
            } else {
                $redirectUrl = $redirectHome;
            }

            return $this->redirect()->toUrl($redirectUrl);
        }

        $failure = '';

        $request = $this->getRequest();
        $form = new LoginForm();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setInputFilter(new LoginFilter());
            $form->setData($postData);

            if ($form->isValid()) {
                $formData = $form->getData();
                $result = $backofficeAuthenticationService->authenticate(null, $formData['identity'], $formData['credential']);

                if ($result->isValid()) {
                    $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                    $userIdentity = $auth->getIdentity();

                    // update user last login date and time
                    $userManagerService = $serviceLocator->get('service_user');
                    $userManagerService->updateLastLogin($userIdentity->id);

                    $appConfig = $serviceLocator->get('config');

                    $backofficeAuthenticationService->setAsBackofficeUser($appConfig['session']['config']['options']['cookie_domain']);
                    $backofficeAuthenticationService->setRememberMyEmail($formData['identity'], $appConfig['session']['config']['options']['cookie_domain']);

                    $redirect = $backofficeAuthenticationService->getUrlForRedirect();

                    if (!empty($lastRequestUrl) && $lastRequestUrl != $loginUrl) {
                        $redirect = $lastRequestUrl;
                    } else {
                        $redirect = $redirect;
                    }

                    return $this->redirect()->toUrl($redirect);
                } else {
                    $failure = 'Authentication failed.';
                }
            } else {
                $failure = 'Authentication failed.';
            }
        }

        $session1 = Helper::getSessionContainer('logout');

        if ($session1->offsetExists('loggedOut') && $session1->offsetGet('loggedOut')) {
            $session1->getManager()->getStorage()->clear();
        }

        return new ViewModel([
            'form'              => $form,
            'error'             => $failure,
            'lastRequestUrl'    => $lastRequestUrl,
            'backofficeVersion' => Constants::APP_VERSION,
        ]);
    }

    /**
     * General-purpose authentication action
     */
    public function authenticateAction()
    {
        /**
         * @var BackofficeAuthenticationService $backofficeAuthenticationService
         */
        $backofficeAuthenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            "result" => Result::FAILURE,
        ];

    	if ($request->isPost()) {
    		$postData = $request->getPost();
    		$form = $this->getLoginForm();
    		$form->setData($postData);

    		if ($form->isValid()) {
                $formData = $form->getData();
    			$authResult = $backofficeAuthenticationService->authenticate(null, $formData['identity'], $formData['credential']);

    			if ($authResult->isValid()) {
                    $appConfig = $this->getServiceLocator()->get('config');

    				$backofficeAuthenticationService->setAsBackofficeUser($appConfig['session']['config']['options']['cookie_domain']);
    				$backofficeAuthenticationService->setRememberMyEmail($formData['identity'], $appConfig['session']['config']['options']['cookie_domain']);

                    $result['result'] = Result::SUCCESS;
    			}
    		}
    	}

        return new JsonModel($result);
    }

    public function logoutAction()
    {
        $backofficeAuthenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
    	$backofficeAuthenticationService->clearIdentity();

        $session = new Container('accesstoken');
        $session->getManager()->getStorage()->clear('accesstoken');

        $session1 = new Container('logout');
        $session1->offsetSet('loggedOut', true);

        if (isset($_COOKIE['backoffice_user'])) {
            unset($_COOKIE['backoffice_user']);

            $appConfig = $this->getServiceLocator()->get('config');
            setcookie('backoffice_user', 0, time() - 3600, '/', $appConfig['session']['config']['options']['cookie_domain']);
        }

        return $this->redirect()->toRoute('backoffice_user_login');
    }

    public function googleSigninAction()
    {
        $backofficeAuthenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            $request        = $this->getRequest();
            $router         = $this->getEvent()->getRouter();
            $loginUrl       = $router->assemble([], ['name' => 'backoffice_user_login']);
            $lastRequestUrl = $request->getQuery('request_url', null);
            $dbAdapter      = $this->getServiceLocator()->get('dbadapter');
            $error          = $this->params()->fromQuery('error', false);

            if (!empty($error)) {
                return $this->redirect()->toUrl('/');
            }

            if (!is_null($lastRequestUrl)) {
                $session = new Container('requestUrl');
                $session->lastRequestUrl = $lastRequestUrl;
            }

            $this->layout('layout/login');
            $googleAuth = $this->getServiceLocator()->get('library_service_google_auth');
            $response   = $googleAuth->authenticate($this->getServiceLocator());

            if ($response[0] == 'verified') {
                $userManager = new UserManager($this->getServiceLocator());
                $userInfo    = $userManager->getUserByEmail($response[1]);

                if (!$userInfo instanceof \DDD\Domain\User\User) {
                    $requestUrl = new Container('requestUrl');

                    if (!empty($requestUrl)) {
                        $lastRequestUrl = $requestUrl->lastRequestUrl;
                    } else {
                        $lastRequestUrl = null;
                    }

                    $session = new Container('authFailed');
                    $session->authFailed = true;

                    return $this->redirect()->toRoute(
                        "backoffice_user_login",
                        ["action" => "login"],
                        ['query'  => ['request_url' => $lastRequestUrl]]
                    );
                }

                $userData = [];
                foreach ((array)$userInfo as $key => $row) {
                    $rawKey = preg_replace('/\\0\*\\0/', '', $key);
                    $userData[$rawKey] = $row;
                }

                $userData = (object)$userData;

                $backofficeAuthenticationService->getStorage()->write($userData);

                $appConfig = $this->getServiceLocator()->get('config');

                $backofficeAuthenticationService->setAsBackofficeUser($appConfig['session']['config']['options']['cookie_domain']);
                $backofficeAuthenticationService->setRememberMyEmail($userData->email, $appConfig['session']['config']['options']['cookie_domain']);

                // update user last login date and time
                $userManagerService = $this->getServiceLocator()->get('service_user');
                $userManagerService->updateLastLogin($userData->id);

                $redirectUrl = $backofficeAuthenticationService->getUrlForRedirect();

                $requestUrl = new Container('requestUrl');

                if (!empty($requestUrl)) {
                    $lastRequestUrl = $requestUrl->lastRequestUrl;
                } else {
                    $lastRequestUrl = null;
                }

                if (!is_null($lastRequestUrl) && ($lastRequestUrl != $loginUrl)) {
                    $redirect = $lastRequestUrl;
                } else {
                    $redirect = $redirectUrl;
                }

                return $this->redirect()->toUrl($redirect);

            } else {
                header('Location: '. $response);
                exit;
            }
        } catch (\Exception $e) {
            $session = new Container('authFailed');
            $session->authFailed = self::CONNECTION_TIMEDOUT;
            return $this->redirect()->toRoute('backoffice_user_login');
        }
    }

    private function getLoginForm()
    {
        $loginForm   = new Login();
        $loginFilter = new LoginFilter();
    	$loginForm->setInputFilter($loginFilter);

    	return $loginForm;
    }
}
