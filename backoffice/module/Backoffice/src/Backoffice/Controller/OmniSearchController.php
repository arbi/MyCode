<?php

namespace Backoffice\Controller;

use DDD\Service\User;
use Library\Controller\ControllerBase;
use Library\Validator\ReservationExists;
use Zend\Http\Request;
use Zend\Json\Json;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Library\Constants\Roles;

class OmniSearchController extends ControllerBase
{
    const LIMIT_PER_ITEM = 10;
    public function indexAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Dao\Apartment\General $apartmentGeneralDao
         * @var \DDD\Dao\User\UserManager $userManagerDao
         * @var \DDD\Service\User $usersService
         * @var \DDD\Service\ApartmentGroup $apartmentGroupService
         * @var \DDD\Service\Frontier $frontierService
         */

        $apartmentGroupData = [];
        $usersData          = [];
        $apartmentsData     = [];
        $data               = [];
        $cardsData          = [];

        try {
            $request = $this->getRequest();

            if (!is_null($request->getPost('resNum'))) {

                $auth = $this->getServiceLocator()->get('library_backoffice_auth');

                $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

                $userId = $auth->getIdentity()->id;
                $userPermission = $userManagerDao->getUserRoles($userId, 'ROLE_BOOKING_MANAGEMENT');

                $reservationNumber = $request->getPost('resNum');

                if ($userPermission instanceof \DDD\Domain\User\User) {

                    $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                    $reservationExistValidator = new ReservationExists(['adapter' => $dbAdapter]);

                    $reservationExist = $reservationExistValidator->isValid($reservationNumber);

                    if (!$reservationExist) {
                        $data = ['status' => 'error'];
                    } else {
                        $data = [
                            'status' => 'success',
                            'result' => $reservationNumber
                        ];
                    }

                } else {
                    $data = [
                        'status' => 'success',
                        'result' => $reservationNumber
                    ];

                }
                return new JsonModel($data);
            }

            $query = trim($request->getPost('query'));

	        if (strlen($query) >= 2 /* restriction to query length */) {
                $auth           = $this->serviceLocator->get('library_backoffice_auth');
                $userId         = $auth->getIdentity()->id;
                $userRolesArray = $this->serviceLocator->get('service_user')->getUsersGroup($userId);
                $userRoles      = [];

                foreach ($userRolesArray as $role) {
                    $userRoles[] = $role['group_id'];
                }

                if (in_array(Roles::ROLE_APARTMENT_MANAGEMENT, $userRoles)) {
                    $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
                    $apartmentsData = $apartmentGeneralDao->getApartmentsForOmnibox($query, self::LIMIT_PER_ITEM);
                }

                if ($auth->hasRole(Roles::ROLE_PROFILE) || ($auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY) && (
                    $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) || $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)))
                ) {
                    $usersService = $this->getServiceLocator()->get('service_user');
                    $usersData = $usersService->getUsersForOmnibox($query, self::LIMIT_PER_ITEM);
                }

                if (in_array(Roles::ROLE_APARTMENT_GROUP_MANAGEMENT, $userRoles)) {
                    $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

                    $hasDevTestRole = in_array(Roles::ROLE_DEVELOPMENT_TESTING, $userRoles) ? true : false;

                    $buildings = $apartmentGroupService->getApartmentGroupsForOmnibox($query, self::LIMIT_PER_ITEM, $hasDevTestRole);

                    foreach ($buildings as $key => $value) {
                        array_push(
                            $apartmentGroupData,
                            [
                                'id'    => $value['id'],
                                'text'  => $value['name'],
                                'label' => 'apartment-group',
                            ]
                        );
                    }
                }

                if (in_array(Roles::ROLE_FRONTIER_MANAGEMENT, $userRoles)) {
                    $frontierService = $this->getServiceLocator()->get('service_frontier');
                    $cardsData       = $frontierService->findCards($query, self::LIMIT_PER_ITEM);
                }

                if (isset($apartmentsData) || isset($usersData) || isset($apartmentGroupData) || isset($cardsData)) {
                    $data = array_merge(
                        $apartmentsData,
                        $usersData,
                        $apartmentGroupData,
                        $cardsData
                    );
                }
	        }
        } catch (\Exception $e) {
            $data = [];
        }

	    return new JsonModel($data);
    }
}
