<?php
namespace Library\Acl;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Library\Authentication\BackofficeAcl;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Handle ACL generation
 */
class Aclmanager extends Acl
{
    /**
     * @access protected
     * @var ServiceManager
     */
    protected $serviceLocator;

    /**
     * Constructor
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($serviceLocator)
    {
        // define guest role
        $this->addRole(new Role(ROLE_GUEST));

        // add hardcoded resources
        $this->addResource(new Resource('controller_backofficeuser_authentication'));
        $this->addResource(new Resource('cron'));

        $this->allow(
            ROLE_GUEST,
            'controller_backofficeuser_authentication',
            ['login', 'logout', 'authenticate', 'google-signin']
        );

        $this->allow(ROLE_GUEST, 'cron', []);

        // user authentication service
        $authenticationService = $serviceLocator->get('library_backoffice_auth');

        if ($authenticationService->hasIdentity()) {
        	// user service
        	$userService = $serviceLocator->get('service_user');

        	// define and add logged in user role
            $role = $authenticationService->getIdentity()->id;
            $this->addRole(new Role($role), ROLE_GUEST);

            // get all defined resources
            $definedResources = BackofficeAcl::getResourceRole();

            // add resources that allowed to every authorized user
            $resourcesAllowedToEveryAuthorizedUser = $definedResources[0];
            foreach ($resourcesAllowedToEveryAuthorizedUser as $row) {
            	$resource = new Resource($row['controller']);
                $this->addResource($resource);
                $this->allow($role, $row['controller'], $row['action']);
            }

            // fetch user groups from database and generate array of resource IDs
            $userGroups = $userService->getUsersGroup($role);
            $userResourceIDs = [];
            foreach ($userGroups as $row) {
            	$userResourceIDs[] = $row['group_id'];
            }

            // do extra checks, compare user resources with defined resources
            $userResources = [];
            foreach ($userResourceIDs as $resourceID) {
            	if (isset($definedResources[$resourceID]) && $resourceID != 0) {
            		$userResources[$resourceID] = $definedResources[$resourceID];
                }
            }

            // construct filtered array of user resources
            $filteredResources = [];
            foreach ($userResources as $resourceID => $currentResourceItems) {
            	foreach ($currentResourceItems as $resourceArray) {
	            	$check = true;

	            	// check if current resource is already in filtered resources
	            	foreach ($filteredResources as $key => $filteredResourceArray) {
	            		if ($filteredResourceArray['controller'] == $resourceArray['controller']) {
	            			// current resource is already in filtered resorces array
	            			$check = false;

	            			// check for action existance
	            			if (!empty($filteredResourceArray['action'])) {
	            				if (empty($resourceArray['action'])) {
	            					$filteredResources[$key]['action'] = [];
	            				} else {
	            					$filteredResources[$key]['action'] = array_merge((array)$filteredResources[$key]['action'], (array)$resourceArray['action']);
	            				}
	            			}
	            		}
	            	}

	            	if ($check) {
	            		$filteredResources[] = array_merge($resourceArray, ['resource_id' => $resourceID]);
                    }
            	}
            }

            // finally, generate ACL based on filtered resources
            foreach ($filteredResources as $resourceArray) {
            	// add resources
            	if (!$this->hasResource($resourceArray['controller'])) {
            		$this->addResource(new Resource($resourceArray['controller']));
                }

            	if (in_array($resourceArray['resource_id'], $userResourceIDs)) {
            		if (empty($resourceArray['action'])) {
            			$this->allow($role, $resourceArray['controller']);
                    } else {
            			$this->allow($role, $resourceArray['controller'], $resourceArray['action']);
                    }
            	}
            }
        }
    }
}
