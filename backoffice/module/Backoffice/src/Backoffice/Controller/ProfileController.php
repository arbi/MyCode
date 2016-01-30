<?php

namespace Backoffice\Controller;

use DDD\Dao\User\UserManager;
use DDD\Service\User\Main as UserMainService;
use DDD\Service\Venue\Charges;
use Library\Utility\Debug;
use Zend\View\Model\JsonModel;
use Library\Controller\ControllerBase;
use Backoffice\Form\ProfileDetails as ProfileDetailsForm;
use Backoffice\Form\ProfilePassword as ProfilePasswordForm;
use Backoffice\Form\InputFilter\ProfileDetailsFilter as ProfileDetailsFilter;
use Backoffice\Form\InputFilter\ProfilePasswordFilter as ProfilePasswordFilter;
use Backoffice\Form\Vacationdays as VacationdaysForm;
use Backoffice\Form\InputFilter\VacationdaysFilter as VacationdaysFilter;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Constants\Constants;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Zend\View\Model\ViewModel;

class ProfileController extends ControllerBase {
    protected $_profileService = null;

    public function indexAction()
    {
	    /** @var UserManager $managerDao */
        $managerDao                  = $this->getServiceLocator()->get('dao_user_user_manager');
        $teamService                 = $this->getServiceLocator()->get('service_team_team');
        $auth                        = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var \DDD\Service\User\Schedule $scheduleService */
        $scheduleService = $this->getServiceLocator()->get('service_user_schedule');
        $userId                      = $this->params()->fromRoute('id');
        $userSessionId               = $auth->getIdentity()->id;
        $isLoggedInUserGlobalManager = false;
        $profileViewer               = false;
        $isManager                   = false;
        $itsMe                       = false;

        if ($auth->hasRole(Roles::ROLE_PROFILE_VIEWER)) {
            $profileViewer = true;
        }

        if ($auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT)) {
            $isLoggedInUserGlobalManager = true;
        }

        $hasHRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR);

        if ($userId === null || $userId == $userSessionId) {
            $userId = $userSessionId;
            $itsMe = true;
        }

        $rUserDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $rUser = $rUserDao->findUserById((int)$userId);

        if (!$rUser) {
            return $this->redirect()->toUrl( '/' );
        }

        if ($rUser->getStartDate() && $rUser->getStartDate() != '0000-00-00') {
            //604800 is the number of seconds in one week
            //31536000 is the number of seconds in one year
            $weeksWorked = floor(((time() - strtotime($rUser->getStartDate())) % 31536000) / 604800);
            $yearsWorked = floor((time() - strtotime($rUser->getStartDate())) / 31536000);
            if ($weeksWorked < 0 && $yearsWorked < 0) {
                $weeksWorked = 0;
                $yearsWorked = 0;
            }
        } else {
            $weeksWorked = 0;
            $yearsWorked = 0;
        }

        // get User Manager id
        $managerId = $rUser->getManager_id();

        if ($managerId === '0') {
            $managerId = 64;
        }

        // get Manager Profile
        $myManager = $managerDao->getUserById((int)$managerId);

        if (   $auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY)
            && ($userSessionId == $managerId)
        ) {
            $isManager = true;
        }

        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        /** @var \DDD\Service\Profile $profileService */
        $profileService = $this->getProfileService();
        $cityDao        = $this->getServiceLocator()->get('dao_geolocation_city');

        $userDao        = $userService->getUsersById((int)$userId, ($isManager || $isLoggedInUserGlobalManager || $hasHRole));

        if (!$userDao) {
            $view = new ViewModel();
            $view->setTemplate('backoffice/profile/disabled.phtml');

            return $view;
        }

        $userProfile      = $userDao->get('user_main');
        $userOptions      = $userService->getUserOptions((int)$userId);
        $countryId        = $userProfile->getCountry_id();
        $cities           = $cityDao->getCityByCountryId((int)$countryId);
        // Get user schedule for 3 weeks
        $userSchedule     = $scheduleService->getUserScheduleInRange($userId, date('Y-m-d'), date('Y-m-d', strtotime('+20 day')));
        $userSubordinates = $profileService->getUserSubordinates($userId);
        $vacationRequest  = $userService->getUserVacationRequest((int)$userId);

        $detailsForm  = new ProfileDetailsForm('changeDetails');
        $passwordForm = new ProfilePasswordForm('changePassword');

        // reservation ginosik
        $resevations       = [];
        $stayedDays        = 0;
        $bookingDao        = new \DDD\Dao\Booking\Booking($this->getServiceLocator(), 'DDD\Domain\Booking\BookingProfile');
        $resevationsResult = $bookingDao->getGinosikResevations($userProfile->getEmail(), $userProfile->getAlt_email());

        foreach ($resevationsResult as $row) {
            $days = Helper::getDaysFromTwoDate($row->getDate_from(), $row->getDate_to());
            $stayedDays += $days;

            array_push($resevations, sprintf(
	            TextConstants::PROFILE_GINOSI_RESERVATION,
	            '/booking/edit/' . $row->getReservationNumber(), $row->getReservationNumber(), $row->getApartmentName(), $row->getDate_from(), $row->getDate_to(), $days, $row->getGuestEmail()
            ));
        }

        /**
         * @var UserMainService $userMainService
         */
        $userMainService  = $this->getServiceLocator()->get('service_user_main');
        $userDepartmentId = $userMainService->getUserDepartmentId($userId);

        $isDepartment = 1;
        $departments  = $teamService->getTeamList(null, $isDepartment);

        $coDepartments     = [];
        $coDepartments[-1] = '-- Departments --';

        foreach ($departments as $department) {
            $coDepartments[$department->getId()] = $department->getName();
        }

        $userTeams = $teamService->getUserTeams($userId);

        $vacationDaysCountUsedInThisYear = $userService->getVacationDaysCountUsedInThisYear($userId);

        $cashingDays = $userService->calculateVacationCashableDaysCount(
            $userDao->get('user_main')->getVacation_days_per_year(),
            $userDao->get('user_main')->getVacation_days(),
            true
        );


        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($userProfile->getTimezone()));
        $dateTimeNow = $datetime->format(Constants::GLOBAL_DATE_TIME_FORMAT);

        $goodTimeToCall = $scheduleService->isUserWorking($userProfile->getId(), $datetime);

        $hasAccessToManage = (
            $auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY) && (
            $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) ||
            $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR) ||
            $isManager
        ));

        $userCurrencyId = $userDao->get('user_main')->getCurrencyId();
        $ginocoinSpentSoFar = false;

        if ($itsMe) {
            $ginocoinSpentSoFar = $this->calculateGinocoinSpentSoFar($auth->getIdentity()->id, $userCurrencyId);
        }

        return new ViewModel([
            'user'                     => $userDao,
            'userOptions'              => $userOptions,
            'resevations'              => $resevations,
            'stayedDays'               => $stayedDays,
            'cities'                   => $cities,
            'myManager'                => $myManager,
            'itsMe'                    => $itsMe,
            'isManager'                => $isManager,
            'hasAccessToManage'        => $hasAccessToManage,
            'profileViewer'            => $profileViewer,
            'changeDetailsForm'        => $detailsForm,
            'changePasswordForm'       => $passwordForm,
            'schedule'                 => $userSchedule,
            'local_datetime'           => $dateTimeNow,
            'good_time_to_call'        => $goodTimeToCall,
            'manager_subordinates'     => $userSubordinates,
            'vacationRequestOld'       => $vacationRequest['old'],
            'vacationRequestNew'       => $vacationRequest['new'],
            'vacationDaysUsedThisYesr' => $vacationDaysCountUsedInThisYear,
            'cashingDays'              => $cashingDays,
            'weeksWorked'              => $weeksWorked,
            'yearsWorked'              => $yearsWorked,
            'userTeams'                => $userTeams,
            'userDepartment'           => $userDepartmentId,
            'departments'              => $coDepartments,
            'hasHRole'                 => $hasHRole,
            'hasHRoleHrVacationEditor' => $auth->hasRole(Roles::ROLE_HR_VACATION_EDITOR),
            'ginocoinSpentSoFar'       => $ginocoinSpentSoFar
        ]);
    }

    public function ajaxChangePasswordAction()
    {
        /**
         * @var UserManager $rUserDao
         * @var \DDD\Domain\User\User $rUser
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_UPDATE,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $form     = new ProfilePasswordForm('changePassword');
                $messages = '';
                $data     = $request->getPost();
                $form->setInputFilter(new ProfilePasswordFilter());

                if ($request->isPost()) {
                    /**
                     * @var \DDD\Service\User $userService
                     */
                    $userService = $this->getServiceLocator()->get('service_user');

                    $filter  = $form->getInputFilter();
                    $form->setInputFilter($filter);

                    $rUserDao = $this->getServiceLocator()->get('dao_user_user_manager');
                    $rUser    = $rUserDao->findUserById((int)$data['userId']);

                    if (!Helper::bCryptVerifyPassword($data['currentPassword'], $rUser->getPassword())) {
                        return new JsonModel([
                            'status' => 'error',
                            'msg'    => 'Current password is wrong.',
                        ]);
                    }

                    $form->setData($data);

                    if ($form->isValid()) {
                         $userService->changePassword($data);
                         Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    } else {
                        $errors = $form->getMessages();
                        foreach ($errors as $key => $row) {
                            if (!empty($row)) {
                                $messages .= ucfirst($key) . ' ';
                                $messages_sub = '';
                                foreach ($row as $keyer => $rower) {
                                    $messages_sub .= $rower;
                                }
                                $messages .= $messages_sub . '<br>';
                            }
                        }
                        $result['status'] = 'error';
                        $result['msg'] = $messages;
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxChangeDetailsAction()
    {

        $result = array('status'=>'success', 'msg'=>TextConstants::SUCCESS_UPDATE);
        $request = $this->getRequest();
        try{
            if($request->isXmlHttpRequest()) {
                $form = new ProfileDetailsForm('changeDetails');
                $messages = '';
                $data = $request->getPost();

                $form->setInputFilter(new ProfileDetailsFilter());
                if ($request->isPost()) {
                    /**
                     * @var \DDD\Service\User $userService
                     */
                    $userService = $this->getServiceLocator()->get('service_user');

                    $filter  = $form->getInputFilter();
                    $form->setInputFilter($filter);

                    $form->setData($data);
                    if ($form->isValid()) {
                         $userService->changeDetails($data);
                         Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_UPDATE]);
                    } else {
                        $errors = $form->getMessages();
                        foreach ($errors as $key => $row) {
                            if (!empty($row)) {
                                $messages .= ucfirst($key) . ' ';
                                $messages_sub = '';
                                foreach ($row as $keyer => $rower) {
                                    $messages_sub .= $rower;
                                }
                                $messages .= $messages_sub . '<br>';
                            }
                        }
                        $result['status'] = 'error';
                        $result['msg'] = $messages;
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function vacationRequestAction()
    {
        /**
         * @var \DDD\Service\User $userService
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Dao\User\Vacationdays $vacationDao
         */
        $userService = $this->getServiceLocator()->get('service_user');
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $vacationDao = $this->getServiceLocator()->get('dao_user_vacation_days');

        $userDao     = $userService->getUsersById((int)$auth->getIdentity()->id);

        $vacationLeft      = $userDao->get('user_main')->getVacation_days();
        $vacationOverall   = $userDao->get('user_main')->getVacation_days_per_year();
        $employmentPercent = $userDao->get('user_main')->getEmployment() / 100;

        $vacationDaysCountUsedInThisYear = $userService->getVacationDaysCountUsedInThisYear((int)$auth->getIdentity()->id);

        $cashingDays = $userService->calculateVacationCashableDaysCount(
            $vacationOverall,
            $vacationLeft,
            true
        );

        $takenSickDays = 0;
        $totalSickDays = $userDao->get('user_main')->getSickDays();
        $sickDays      = $vacationDao->getSickDays((int)$auth->getIdentity()->id);
        if ($sickDays) {
            foreach ($sickDays as $sickDay) {
                $takenSickDays += abs($sickDay['total_number']);
            }
        }

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $form = new VacationdaysForm();

        return new ViewModel([
			'vacationForm'             => $form,
            'vacationLeft'             => round($vacationLeft, 2),
            'vacationOverall'          => $vacationOverall,
            'employmentPercent'        => $employmentPercent,
            'userName'                 => $auth->getIdentity()->firstname . ' ' . $auth->getIdentity()->lastname,
            'vacationDaysUsedThisYesr' => $vacationDaysCountUsedInThisYear,
            'cashingDays'              => $cashingDays,
            'totalSickDays'            => $totalSickDays,
            'takenSickDays'            => $takenSickDays,
        ]);
    }

    public function ajaxsaveAction() {
	    $request = $this->getRequest();
        $result = [
	        'result' => [],
	        'id' => 0,
	        'status' => 'success',
	        'msg' => TextConstants::SUCCESS_UPDATE,
        ];

        try{
            if($request->isXmlHttpRequest()) {
                $messages = '';
                if ($request->isPost()) {
                    /**
                     * @var \DDD\Service\User $userService
                     */
                    $userService = $this->getServiceLocator()->get('service_user');

                    $data    = $request->getPost();

                    $form = new VacationdaysForm('vacationdays-form');
                    $form->setInputFilter(new VacationdaysFilter($data));

                    $filter  = $form->getInputFilter();

                    $form->setInputFilter($filter);

                    $form->setData($data);
                    if ($form->isValid()) {
                        $vData = $form->getData();
                        $from  = $vData['from'];
                        $to    = $vData['to'];
                        if($from > $to){
                            $result['status'] = 'error';
                            $result['msg'] = 'From date should be less than to date.';
                        } else {
                            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                            $respons_db = $userService->vacationdaysSave($vData, $auth->getIdentity()->id);

                            if($respons_db > 0){
                                 Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_ADD]);
                            } else {
                                $result['status'] = 'error';
                                $result['msg'] = TextConstants::SERVER_ERROR;
                            }
                       }
                   } else {
                       $errors = $form->getMessages();
                       foreach ($errors as $key => $row) {
                           if (!empty($row)) {
                               $messages .= ucfirst($key) . ' ';
                               $messages_sub = '';
                               foreach ($row as $keyer => $rower) {
                                   $messages_sub .= $rower;
                               }
                               $messages .= $messages_sub . '<br>';
                           }
                       }
                       $result['status'] = 'error';
                       $result['msg'] = $messages;
                   }
               }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

	public function getProfileService() {
		if ($this->_profileService === null) {
			$this->_profileService = $this->getServiceLocator()->get('service_profile');
		}

		return $this->_profileService;
	}

    private function calculateGinocoinSpentSoFar($userId, $currencyId)
    {
        /**
         * @var \DDD\Dao\Venue\Charges $venueChargesDao
         */
        $venueChargesDao = $this->getServiceLocator()->get('dao_venue_charges');
        $venueChargesDao->setEntity(new \DDD\Domain\Venue\GinocoinCharges());

        $statuses = [Charges::CHARGE_STATUS_TRANSFERRED];
        $orderStatuses = [Charges::ORDER_STATUS_VERIFIED];
        $startDate = new \DateTime('first day of this month');

        $charges = $venueChargesDao->getChargesByChargedUserId(
            $userId, $statuses, $orderStatuses, $currencyId, $startDate->format('Y-m-d')
        );

        $result = 0;

        foreach ($charges as $charge) {
            if ($charge->getAmount() > $charge->getPerdayMaxPrice()) {
                $result += $charge->getAmount() - $charge->getPerdayMaxPrice();
            }
        }

        return $result;
    }

    public function ajaxSaveGinocoinAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isPost() || !$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $request->getPost();

            /**
             * @var \Library\Authentication\BackofficeAuthenticationService $auth
             * @var \DDD\Dao\User\UserManager $userManager
             */
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $userManager = $this->getServiceLocator()->get('dao_user_user_manager');

            $userManager->save(
                [
                    'ginocoin_limit_amount' => $postData['amount'],
                    'ginocoin_pin'          => $postData['pin']
                ],
                ['id' => $auth->getIdentity()->id]
            );

            $result = [
                'status'    => 'success',
                'msg'       => TextConstants::SUCCESS_UPDATE
            ];

        } catch (\Exception $e) {
            $this->gr2logException($e);

            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }
}
