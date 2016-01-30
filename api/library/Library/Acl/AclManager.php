<?php
namespace ApiLibrary\Acl;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

use ApiLibrary\Authentication\ApiAcl;

define('ROLE_GUEST', '0');

/**
 * Handle ACL generation
 */
class AclManager extends Acl
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
        $this->addResource(new Resource('authenticate'));
        $this->addResource(new Resource('oauth'));

        $this->allow( ROLE_GUEST, 'authenticate', [] );
        $this->allow( ROLE_GUEST, 'oauth', [] );

        // user authentication service
        $authenticationService = $serviceLocator->get('library_backoffice_auth');

        if ($authenticationService->hasIdentity()) {
        	// user service
        	$userService = $serviceLocator->get('service_user');

        	// define and add logged in user role
            $role = $authenticationService->getIdentity()->id;
            $this->addRole(new Role($role), ROLE_GUEST);

            // get all defined resources
            $definedResources = ApiAcl::getResourceRole();

            // add resources that allowed to every authorized user
            $resourcesAllowedToEveryAuthorizedUser = $definedResources[0];
            foreach ($resourcesAllowedToEveryAuthorizedUser as $row) {
            	$resource = new Resource($row['route']);
                $this->addResource($resource);
                $this->allow($role, $row['route'], $row['methods']);
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
	            		if ($filteredResourceArray['route'] == $resourceArray['route']) {
	            			// current resource is already in filtered resorces array
	            			$check = false;

	            			// check for action existance
	            			if (!empty($filteredResourceArray['methods'])) {
	            				if (empty($resourceArray['methods'])) {
	            					$filteredResources[$key]['methods'] = [];
	            				} else {
	            					$filteredResources[$key]['methods'] = array_merge((array)$filteredResources[$key]['methods'], (array)$resourceArray['methods']);
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
                if (!$this->hasResource($resourceArray['route'])) {
                    $this->addResource(new Resource($resourceArray['route']));
                }

                if (in_array($resourceArray['resource_id'], $userResourceIDs)) {
                  	if (empty($resourceArray['methods'])) {
                  		  $this->allow($role, $resourceArray['route']);
                    } else {
                  		  $this->allow($role, $resourceArray['route'], $resourceArray['methods']);
                    }
                }
            }
        }
    }
}
