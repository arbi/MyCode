<?php

namespace Library\Authentication;

use DDD\Dao\User\UserManager;
use DDD\Service\User;

use Library\Utility\Debug;
use Library\Utility\Helper;
use Library\Authentication\BackofficeAcl;
use Library\Constants\Roles;

use Zend\Authentication\AuthenticationService;
use Zend\Crypt\Password\Bcrypt;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Storage\StorageInterface;

use ZF2Graylog2\Traits\Logger;

final class BackofficeAuthenticationService extends AuthenticationService implements ServiceManagerAwareInterface
{
    use Logger;

	/**
	 * @var ServiceManager
	 */
	private $serviceManager;

	function __construct(StorageInterface $storage = null, AdapterInterface $adapter = null)
    {
		parent::__construct($storage, $adapter);
	}

    /**
     * @param ServiceManager $serviceManager
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @return \Zend\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

	/**
	 * @see \Zend\Authentication\AuthenticationService::authenticate()
	 */
	public function authenticate($adapter = null, $identity = null, $credential = null)
    {
        // if identity isn't an email address then it is username and @ginosi.com will be concatenated to it
        if (strpos($identity, '@') === false) {
            $identity .= '@ginosi.com';
        }

        $this->getAdapter()->setIdentity($identity)->setCredential($credential);
		$result = $this->getAdapter()->authenticate();

		if ($this->hasIdentity()) {
			$this->clearIdentity();
		}

		if ($result->isValid()) {
			$this->getStorage()->write(
                $this->getAdapter()->getResultRowObject()
            );
		}

		return $result;
	}

	public function hasPermission($permission)
    {
		$allResource  = BackofficeAcl::getResourceRole();
		$groupId      = false;

		if (is_int($permission)) {
			if (isset($allResource[$permission])) {
                $groupId = $permission;
            }
		} else {
			foreach ($allResource as $key => $ar) {
				if (isset($ar['event']) && $ar['event'] == $permission) {
                    $groupId = $key;

					break;
				}
			}
		}

		if (!is_int($groupId)) {
            return false;
        }

		$service = $this->getServiceManager()->get('service_user');
		$result  = $service->getUsersGroup($this->getIdentity()->id, $groupId);

		if (!empty($result)) {
            return true;
        }

		return false;
	}

	public function hasRole($role)
    {
		/**
		 * @todo Refactor for better performance
		 */
        $service = $this->getServiceManager()->get('service_user');

        $identity = $this->getIdentity();

        if ($identity) {
            if ($service->getUsersGroup($identity->id, $role)) {
                return true;
            }
        }

    	return false;
    }

	/**
	 *
	 * @param int $dashboardID
     * @return \DDD\Service\User
	 */
	public function hasDashboard($dashboardID)
    {
		/**
         * @var \DDD\Service\User $backOfficeUserService
         */
        $backOfficeUserService = $this->getServiceManager()->get('service_user');
        $loggedInUserID        = $this->getIdentity()->id;

		return $backOfficeUserService->checkUserDashboardAvailability($loggedInUserID, $dashboardID);
	}

    public function getHomeUrl()
    {
        $sm           = $this->getServiceManager();
        $router       = $sm->get('router');
        $auth         = $sm->get('library_backoffice_auth');
        $redirect_url = $router->assemble(['controller' => 'universal-dashboard', 'action' => 'index'], ['name' => 'universal-dashboard/default']);

        return $redirect_url;
    }

    public function getUrlForRedirect()
    {
        $last_visit              = Helper::getSessionContainer('last_visit');
        $last_visit_url          = $last_visit->last_visit_url;
        $homeUrl                 = $this->getHomeUrl();
        $homeNamespase           = Helper::getSessionContainer('default_home_url');
        $homeNamespase->home_url = $homeUrl;

        if (!$last_visit_url) {
            $last_visit_url = $homeUrl;
        }

        return $last_visit_url;
    }

    public function setAsBackofficeUser($cookieDomain)
    {
        $auth = $this->getServiceManager()->get('library_backoffice_auth');
        setcookie('backoffice_user', $auth->getIdentity()->id, 0, "/", $cookieDomain);
    }

    public function setRememberMyEmail($email, $cookieDomain)
    {
        setcookie("remember_my_email", $email, time() + 14 * 24 * 60 * 60, "/", $cookieDomain);
    }

    /**
     * Check whether current/session user has permission to selected dashboard
     *
     * @param int $dashboardId
     * @param int $roleId
     * @return boolean
     */
    public function checkUniversalDashboardPermission($dashboardId, $roleId = false)
    {
        $auth = $this->getServiceManager()->get('library_backoffice_auth');

        if (!$auth->hasDashboard($dashboardId)) {
            return false;
        }

        if ($roleId && !$auth->hasRole($roleId)) {
            return false;
        }

        return true;
    }
}
