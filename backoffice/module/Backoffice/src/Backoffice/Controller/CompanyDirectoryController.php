<?php

namespace Backoffice\Controller;

use DDD\Domain\User\UserTableRow;
use DDD\Service\User;
use Library\Controller\ControllerBase;

use Library\Constants\Objects;
use Library\Constants\Constants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Zend\Json\Expr;
use Backoffice\Form\SearchGinosikForm;
use Library\Constants\Roles;

/**
 * Company Directory controller.
 * Every ginosik can have access to this controller,
 * but only ginosiks that have "User Management" role can edit or do other management actions with users
 *
 * @package backoffice
 * @subpackage backoffice_controller
 *
 * @author Tigran Petrosyan
 */
class CompanyDirectoryController extends ControllerBase
{
	/**
	 * Entry point for CompanyDirectory, uses to display list of ginosiks
	 *
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
    public function indexAction()
    {
        /**
         * @var User $userService
         */
    	$auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $hasPeopleDirectoryModule = $auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY);
        $hasPeopleManagementRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT);
        $hasPeopleHRRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR);
        $hasPeopleDirectoryPermissionsRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_PERMISSIONS);
        $hasProfileModule = $auth->hasRole(Roles::ROLE_PROFILE);

        $searchForm = null;
        $canAdd     = false;

        if ($hasPeopleManagementRole || $hasPeopleHRRole || $hasPeopleDirectoryModule) {
	        $userService = $this->getServiceLocator()->get('service_user');
	        $searchFormResources = $userService->prepareSearchFormResources();

	        $searchForm = new SearchGinosikForm(
                'search_ginosik', $searchFormResources, true
            );

	        $canAdd = true;
        }

        return new ViewModel([
            'search_form'                       => $searchForm,
            'can_add'                           => $canAdd,
            'hasPeopleDirectoryModule'          => $hasPeopleDirectoryModule,
            'hasPeopleManagementRole'           => $hasPeopleManagementRole,
            'hasPeopleHRRole'                   => $hasPeopleHRRole,
            'hasPeopleDirectoryPermissionsRole' => $hasPeopleDirectoryPermissionsRole,
            'hasProfileModule'                  => $hasProfileModule,
            'data_url'                          => '/company-directory/get-json'
        ]);
    }

    /**
     * Get users json to use as source for datatable, filtered by params came from datatable
     * @access public
     *
     * @return \Zend\View\Model\JsonModel
     *
     * @author Tigran Petrosyan
     */
    public function getJsonAction()
    {
    	/**
    	 * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\User $userService
    	 * @var \DDD\Domain\User\UserTableRow[]|\ArrayObject $users
    	 */
    	$auth = $this->getServiceLocator()->get('library_backoffice_auth');
    	$userService = $this->getServiceLocator()->get('service_user');
    	$userIdentity = $auth->getIdentity();

        $hasPeopleDirectoryModule = $auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY);
        $hasPeopleManagementRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT);
        $hasPeopleHRRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR);
        $hasPeopleDirectoryPermissionsRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_PERMISSIONS);
        $hasProfileModule = $auth->hasRole(Roles::ROLE_PROFILE);

    	// get query parameters
    	$queryParams = $this->params()->fromQuery();

        $iDisplayStart  = $queryParams["iDisplayStart"];
    	$iDisplayLength = $queryParams["iDisplayLength"];
    	$sortCol        = (int)$queryParams['iSortCol_0'];
    	$sortDir        = $queryParams['sSortDir_0'];

        // Allow to search deactivated user accounts those who have "People HR - Role" or "People Management - Role"
    	if (!$hasPeopleManagementRole && !$hasPeopleHRRole) {
    		$queryParams['active'] = 0;
    	}

    	// get users data
    	$usersResponse = $userService->getUsersBasicInfo(
            $iDisplayStart,
            $iDisplayLength,
            $queryParams,
            $sortCol,
            $sortDir
        );

        /**
         * @var UserTableRow[] $users
         */
        $users = $usersResponse['result'];
        $count = $usersResponse['total'];

    	// prepare users array
    	$filteredArray     = [];

        /* @var $user \DDD\Domain\User\UserTableRow */
    	foreach ($users as $user) {

            $status = !$user->getDisabled() ?
                '<span class="label label-success">Active</span>' :
                '<span class="label label-default">Inactive</span>';

            $evaluationDate = '--';

            $vacationDaysGranted = '--';
            $vacationDaysVested = '--';

            $startDate = '--';
            $endDate = '--';

            if ($hasPeopleHRRole) {
                if (!empty($user->getNextEvaluation()) && $user->getNextEvaluation() != '0000-00-00') {
                    $evaluationDate = date(Constants::GLOBAL_DATE_FORMAT, strtotime($user->getNextEvaluation()));
                }

                $vacationDaysGranted = round($user->getVacationDaysPerYear(), 2);
                $vacationDaysVested = round($user->getVacationDaysLeft(), 2);

                $startDate = $user->getStartDate();
                $endDate = $user->getEndDate();
            }

            $avatar    = $userService->getAvatarForSelectize($user->getId(), $user->getAvatar());
            $avatarImg = '<img class="ginosik-avatar-selectize hidden-xs" src="'. $avatar .'" title="'. $user->getFirstName() . ' ' . $user->getLastName() .'">';

    		$row = [
                $status,
                $avatarImg . ' ' . $user->getFirstName() . ' ' . $user->getLastName(),
                $user->getCity(),
    			$user->getPosition(),
    			$user->getDepartment(),
                $evaluationDate,
                $vacationDaysGranted,
                $vacationDaysVested,
    			$startDate,
    			$endDate
    		];

            if ($hasProfileModule || ($hasPeopleDirectoryModule && ($hasPeopleManagementRole || $hasPeopleHRRole))) {
    			$row[] = $user->getId();
    		}

    		if (!$hasPeopleManagementRole && !$hasPeopleHRRole && $user->getManagerID() != $userIdentity->id) {
    			$row[] = 0;
            } else {
    			$row[] = $user->getId();
            }

    		$filteredArray[] = $row;
    	}

        $responseArray = [
    		'iTotalRecords'        => $count,
    		'iTotalDisplayRecords' => $count,
    		'iDisplayStart'        => $iDisplayStart,
    		'iDisplayLength'       => (int)$iDisplayLength,
    		'aaData'               => $filteredArray
    	];

    	return new JsonModel(
    	    $responseArray
    	);
    }
}
