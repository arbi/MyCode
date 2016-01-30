<?php

namespace BackofficeUser\Controller;

use DDD\Service\Team\Team;
use DDD\Service\Team\Usages\Base;
use DDD\Service\User\Schedule;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Constants\Objects;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Class UserEvaluationController
 * @package BackofficeUser\Controller
 *
 * @author Tigran Ghabuzyan
 */
class UserScheduleController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \DDD\Dao\Geolocation\Cities $cityDao
         * @var \DDD\Dao\Office\OfficeManager $officeDao
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
         */
        $cityDao = $this->getServiceLocator()->get('dao_geolocation_cities');
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $teamStaffDao = $this->getServiceLocator()->get('dao_team_team_staff');

        $cities = $cityDao->getBoUserCities();

        /**
         * @var Base $teamBaseUsageService
         */
        $teamBaseUsageService = $this->getServiceLocator()->get('service_team_usages_base');

        $teams = $teamBaseUsageService->getTeamsBySeveralUsages(
            [
                Base::TEAM_USAGE_DEPARTMENT,
                Base::TEAM_USAGE_FRONTIER
            ]
        );

        $myTeams = [];
        $teamsSelect = [];

        $myTeamRoles = [
            Team::STAFF_MEMBER,
            Team::STAFF_OFFICER,
            Team::STAFF_MANAGER,
            Team::STAFF_DIRECTOR
        ];

        foreach ($teams as $team) {
            if ($teamStaffDao->isTeamStaff($auth->getIdentity()->id, $team->getId(), $myTeamRoles)) {
                $myTeams[$team->getId()] = $team->getName();
            } else {
                $teamsSelect[$team->getId()] = $team->getName();
            }
        }

        if (empty($myTeams)) {
            $allTeams = $teamsSelect;
        } else {
            $allTeams = $myTeams + ['disabled' => '-------'] + $teamsSelect;
        }

        $offices = $officeDao->getOfficeList(null, false);

        return new ViewModel([
            'cities'            => $cities,
            'teams'             => $allTeams,
            'offices'           => iterator_to_array($offices),
            'schedule_types'    => Objects::getShift()
        ]);
    }

    public function ajaxSaveScheduleAction()
    {
        $request = $this->getRequest();

        try {
            /**
             * @var \DDD\Service\User\Schedule $scheduleService
             * @var \DDD\Dao\User\UserManager $userDao
             */
            $scheduleService = $this->getServiceLocator()->get('service_user_schedule');
            $userDao = $this->getServiceLocator()->get('dao_user_user_manager');

            if ($request->isXmlHttpRequest()) {
                $days = $request->getPost('days');
                $userId = $request->getPost('user_id');
                $scheduleType = $request->getPost('schedule_type');
                $scheduleStart = $request->getPost('schedule_start');
                $officeId = $request->getPost('office_id');
                if (!($userId && $days && $scheduleType && $scheduleStart)) {
                    $result = [
                        'status' => 'error',
                        'msg' => 'Please fill all the required fields',
                    ];
                } else {
                    $userDao->save(
                        [
                            'schedule_type' => $scheduleType,
                            'schedule_start' => date('Y-m-d', strtotime($scheduleStart)),
                        ],
                        [
                            'id' => $userId
                        ]
                    );

                    $scheduleService->saveSchedule($days, $userId);
                    $scheduleService->fillInventory($userId, $scheduleStart, $officeId);

                    $result = [
                        'status' => 'success',
                        'msg' => 'Schedule successfully saved',
                    ];
                }
            } else {
                $result = [
                    'status' => 'error',
                    'msg' => TextConstants::AJAX_NO_POST_ERROR,
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'msg' => $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }

    public function ajaxShowScheduleAction()
    {
        $request = $this->getRequest();

        try {
            /** @var \DDD\Service\User\Schedule $scheduleService */
            $scheduleService = $this->getServiceLocator()->get('service_user_schedule');

            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $hasEditor = $auth->hasRole(Roles::ROLE_PEOPLE_SCHEDULE_EDITOR);

            if ($request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
                 */
                $teamStaffDao = $this->getServiceLocator()->get('dao_team_team_staff');

                $dateRange = $request->getPost('date_range');
                $dateSort = $request->getPost('date_sort');
                $teamId = $request->getPost('team_id', 0);
                $scheduleTypeId = $request->getPost('schedule_type_id', 0);
                $officeId = $request->getPost('office_id', 0);

                $from = $to = '';
                if ($dateRange) {
                    $dates = explode(' - ', $dateRange);
                    if (2 == count($dates)) {
                        $from = date('Y-m-d', strtotime('-1 day', strtotime($dates[0])));
                        $to   = date('Y-m-d', strtotime('+1 day', strtotime($dates[1])));
                    }
                } else {
                    $from = date('Y-m-d', strtotime('-1 day', strtotime('last monday', strtotime('tomorrow'))));
                    $to   = date('Y-m-d', strtotime('+1 day', strtotime('next sunday', strtotime('yesterday'))));
                }

                $data = $scheduleService->getScheduleTable($teamId, $from, $to, $scheduleTypeId, $officeId);

                $tableData = [];
                $days = [];
                $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                $loggedInUserId = $auth->getIdentity()->id;
                $previousDate = false;

                foreach ($data as $row) {
                    $tableData[$row->getUserId()]['name'] = $row->getUserFullName();
                    $tableData[$row->getUserId()]['user_id'] = $row->getUserId();

                    if ($teamId > 0) {
                        $teamStaff = $teamStaffDao->isTeamStaff($row->getUserId(), $teamId);
                        $tableData[$row->getUserId()]['team_type'] = $teamStaff->getType();
                    } else {
                        $tableData[$row->getUserId()]['team_type'] = 0;
                    }

                    $tableData[$row->getUserId()]['days'][$row->getDate()] = [
                        'id'           => $row->getId(),
                        'time_from1'   => $row->getTimeFrom1(),
                        'time_to1'     => $row->getTimeTo1(),
                        'time_from2'   => $row->getTimeFrom2(),
                        'time_to2'     => $row->getTimeTo2(),
                        'availability' => $row->getAvailability(),
                        'may_edit'     => ($row->getManagerId() == $loggedInUserId || $hasEditor) ? 1 : 0,
                        'office_id'    => $row->getOfficeId(),
                        'color'        => $row->getInventoryColorId(),
                        'note'         => $row->getNote()
                    ];

                    if ($row->getAvailability() >= 1) {
                        $tableData[$row->getUserId()]['days'][$row->getDate()]['vacation_type'] = '';
                    } else if ($row->getAvailability() >= 0.5) {
                        $tableData[$row->getUserId()]['days'][$row->getDate()]['vacation_type'] = 'Part Day Off';
                    } else {
                        $tableData[$row->getUserId()]['days'][$row->getDate()]['vacation_type'] = $row->getVacationTypeText();
                    }

                    if ($previousDate && !empty($tableData[$row->getUserId()]['days'][$previousDate])) {
                        if (
                            !empty($tableData[$row->getUserId()]['days'][$previousDate]['time_to2'])
                            && $tableData[$row->getUserId()]['days'][$previousDate]['time_to2'] == '24:00'
                            && $tableData[$row->getUserId()]['days'][$row->getDate()]['time_from1'] == '00:00'
                            && $tableData[$row->getUserId()]['days'][$previousDate]['office_id']
                                == $tableData[$row->getUserId()]['days'][$row->getDate()]['office_id']
                        ) {
                            $tableData[$row->getUserId()]['days'][$previousDate]['time_to2'] = $tableData[$row->getUserId()]['days'][$row->getDate()]['time_to1'];
                            $tableData[$row->getUserId()]['days'][$row->getDate()]['time_to1'] = '';
                            $tableData[$row->getUserId()]['days'][$row->getDate()]['time_from1'] = '';
                        } else if (
                            !empty($tableData[$row->getUserId()]['days'][$previousDate]['time_to1'])
                            && $tableData[$row->getUserId()]['days'][$previousDate]['time_to1'] == '24:00'
                            && $tableData[$row->getUserId()]['days'][$row->getDate()]['time_from1'] == '00:00'
                            && $tableData[$row->getUserId()]['days'][$previousDate]['office_id']
                                == $tableData[$row->getUserId()]['days'][$row->getDate()]['office_id']
                        ) {
                            $tableData[$row->getUserId()]['days'][$previousDate]['time_to1'] = $tableData[$row->getUserId()]['days'][$row->getDate()]['time_to1'];
                            $tableData[$row->getUserId()]['days'][$row->getDate()]['time_from1'] = '';
                            $tableData[$row->getUserId()]['days'][$row->getDate()]['time_to1'] = '';
                        }

                        if (!$tableData[$row->getUserId()]['days'][$row->getDate()]['time_from1'] && !$tableData[$row->getUserId()]['days'][$row->getDate()]['time_from2']) {
                            $tableData[$row->getUserId()]['days'][$row->getDate()]['availability'] = 0;
                        }
                        $tableData[$row->getUserId()]['days'][$previousDate]['next_id'] = $row->getId();
                    }

                    $previousDate = $row->getDate();
                }

                usort($tableData, function($a, $b)
                {
                    $result = $b['team_type'] - $a['team_type'];

                    if ($a['team_type'] == $b['team_type']) {
                        $result = strcmp($a['name'], $b['name']);
                    }

                    return $result;
                });

                $nextDate = strtotime($from);
                $lastDate = strtotime("-1 day", strtotime($to));
                for ($i = 1; $nextDate < $lastDate; $i++) {
                    $nextDate = strtotime("+1 day", $nextDate);
                    array_push($days, date('Y-m-d', $nextDate));
                }

                if (!empty($dateSort)) {
                    usort($tableData, function($a, $b) use ($dateSort) {
                        $date1 = (isset($a['days'][$dateSort]['time_from1']) && !empty($a['days'][$dateSort]['time_from1']))
                            ? $a['days'][$dateSort]['time_from1']
                            : '25';

                        $date2 = (isset($b['days'][$dateSort]['time_from1']) && !empty($b['days'][$dateSort]['time_from1']))
                            ? $b['days'][$dateSort]['time_from1']
                            : '25';

                        if (!empty($date1) && !empty($date2)) {
                            if ($date1 == $date2) {
                                return 0;
                            } elseif ($date1 < $date2) {
                                return -1;
                            } else {
                                return 1;
                            }
                        }
                    });
                }

                $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                $tablePartial = $partial('backoffice-user/user-schedule/partial/schedule-table', [
                    'data' => $tableData,
                    'days' => $days,
                    'dateSort' => $dateSort
                ]);

                $result = [
                    'status'       => 'success',
                    'tableContent' => $tablePartial,
                ];

            } else {
                $result = [
                    'status' => 'error',
                    'msg' => TextConstants::AJAX_NO_POST_ERROR,
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'msg' => $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }

    public function ajaxSaveDayAction()
    {
        $request = $this->getRequest();

        try {
            /** @var \DDD\Dao\User\Schedule\Inventory $scheduleDao */
            $scheduleDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');

            if ($request->isXmlHttpRequest()) {
                if ($request->getPost('office', 0) == 0) {
                    throw new \Exception(TextConstants::SCHEDULE_OFFICE_REQUIRED);
                }

                $id = $request->getPost('id');

                /** @var \DDD\Domain\User\Schedule\Inventory $currDay */
                $currDay = $scheduleDao->fetchOne(['id' => $id]);
                /** @var \DDD\Domain\User\Schedule\Inventory $nextDay */
                $nextDay = $scheduleDao->fetchOne(['id' => $request->getPost('next_id')]);

                // check Availability before update
                if ($currDay->getAvailability() == Schedule::SCHEDULE_TYPE_PART_TIME && $request->getPost('availability') == Schedule::SCHEDULE_TYPE_WORK) {
                    $availability = $currDay->getAvailability();
                } else {
                    $availability = $request->getPost('availability');
                }

                $saveData = [
                    'date'         => date('Y-m-d', strtotime($request->getPost('day'))),
                    'availability' => $availability,
                    'time_from1'   => $request->getPost('from1', ''),
                    'time_from2'   => $request->getPost('from2', ''),
                    'time_to1'     => $request->getPost('to1', ''),
                    'time_to2'     => $request->getPost('to2', ''),
                    'office_id'    => $request->getPost('office', ''),
                    'is_changed'   => 1,
                    'color_id'     => $request->getPost('color_id', 0),
                    'note'         => $request->getPost('note', '')
                ];

                // If user tries to save a data affecting next day. Ex. Time 14:00 - 03:00
                if ($saveData['time_to1'] < $saveData['time_from1'] || $saveData['time_to2'] < $saveData['time_from2']) {
                    if ($saveData['time_to1'] < $saveData['time_from1']) {
                        $nextTimeTo = $saveData['time_to1'];
                        $saveData['time_to1'] = '24:00';
                    } else {
                        $nextTimeTo = $saveData['time_to2'];
                        $saveData['time_to2'] = '24:00';
                    }

                    if ($nextDay) {
                        if ($nextDay->getTimeFrom1() == '00:00') {
                            $scheduleDao->save(
                                ['time_to1' => $nextTimeTo],
                                ['id'       => $nextDay->getId()],
                                ['note'     => $saveData['note']]
                            );
                        } else {
                            $scheduleDao->save(
                                [
                                    'time_from1' => '00:00',
                                    'time_to1'   => $nextTimeTo,
                                    'time_from2' => $nextDay->getTimeFrom1(),
                                    'time_to2'   => $nextDay->getTimeTo1(),
                                    'note'       => $saveData['note'],
                                ],
                                ['id' => $nextDay->getId()]
                            );
                        }
                    } else {
                        $scheduleDao->save([
                            'user_id'      => $currDay->getUserId(),
                            'date'         => date('Y-m-d', strtotime('+1 day', strtotime($request->getPost('day')))),
                            'availability' => $availability,
                            'time_from1'   => '00:00',
                            'time_to1'     => $nextTimeTo,
                            'note'         => $saveData['note'],
                        ]);
                    }
                // If previous data was affecting next day, but now it does not
                } else {
                    if (($currDay->getTimeTo1() == '24:00' || $currDay->getTimeTo2() == '24:00') && $nextDay && $nextDay->getTimeFrom1() == '00:00') {
                        $scheduleDao->save(
                            [
                                'time_from1' => $nextDay->getTimeFrom2(),
                                'time_to1'   => $nextDay->getTimeTo2(),
                                'time_from2' => '',
                                'time_to2'   => '',
                                'note'       => $saveData['note'],
                            ],
                            ['id' => $nextDay->getId()]
                        );
                    }
                }

                $scheduleDao->save($saveData, ['id' => $id]);

                $result = [
                    'status' => 'success',
                    'msg'    => 'Successfully updated',
                ];

            } else {
                $result = [
                    'status' => 'error',
                    'msg' => TextConstants::AJAX_NO_POST_ERROR,
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'msg' => $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }
}
