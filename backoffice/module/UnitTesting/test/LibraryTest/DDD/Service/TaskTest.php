<?php
namespace LibraryTest\DDD\Service;

use Library\UnitTesting\BaseTest;
use DDD\Dao\Task\Staff as TaskStaffDAO;

use DDD\Dao\Task\Task as TaskDAO;
use DDD\Dao\Task\Type as TaskTypeDAO;
use DDD\Domain\Booking\Booking as BookingDomain;
use DDD\Service\Team\Team as TeamService;
use DDD\Service\User\Main as UserMain;
use DDD\Service\User as UserService;
use DDD\Service\User;
use DDD\Service\Task;

use Library\Constants\Constants;

class TaskTest extends BaseTest
{
    /**
     * Testing createAutoTaskReceiveCccaForm method
     */
    public function testCreateAutoTaskReceiveCccaForm()
    {
        /**
         * Data for testing
         */
        $reservationId = 46778;

        /**
         * @var TaskDAO $taskDao
         * @var TaskTypeDAO $taskTypeDao
         * @var TaskStaffDAO $taskStaffDao
         * @var \DDD\Dao\Booking\Booking $reservationsDao
         */
        $taskService     = $this->getApplicationServiceLocator()->get('service_task');
        $taskDao         = $this->getApplicationServiceLocator()->get('dao_task_task');
        $taskTypeDao     = $this->getApplicationServiceLocator()->get('dao_task_type');
        $taskStaffDao    = $this->getApplicationServiceLocator()->get('dao_task_staff');
        $reservationsDao = $this->getApplicationServiceLocator()->get('dao_booking_booking');

        $this->assertInstanceOf('DDD\Service\Task', $taskService);
        $this->assertInstanceOf('DDD\Dao\Task\Task', $taskDao);
        $this->assertInstanceOf('DDD\Dao\Task\Type', $taskTypeDao);
        $this->assertInstanceOf('DDD\Dao\Task\Staff', $taskStaffDao);
        $this->assertInstanceOf('DDD\Dao\Booking\Booking', $reservationsDao);

        /**
         * @var BookingDomain $reservationData
         */
        $reservationData = $reservationsDao->fetchOne(
            ['id' => $reservationId],
            [
                'res_number',
                'date_from',
                'apartment_id_assigned'
            ]
        );

        $this->assertInstanceOf('DDD\Domain\Booking\Booking', $reservationData);

        $taskTypeCCCA = $taskTypeDao->fetchOne(
            ['id' => Task::TYPE_CCCA],
            ['associated_team_id']
        );

        $this->assertInstanceOf('DDD\Domain\Task\Type', $taskTypeCCCA);

        $actionsSet = [
            Task::ACTION_CHANGE_DETAILS  => 1,
            Task::ACTION_CHANGE_STATUS   => 1,
            Task::ACTION_MANAGE_STAFF    => 1,
            Task::ACTION_MANAGE_SUBTASKS => 1
        ];

        $currentDate = date(Constants::GLOBAL_DATE_FORMAT . ' H:i');

        $endDate = date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime('+36 hour'));

        if (strtotime($reservationData->getDateFrom()) < strtotime('+36 hour')) {
            $endDate = date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservationData->getDateFrom())) . ' 23:59';
        }

        $taskData = [
            'title'             => 'Collect CCCA Form from customer ( R# ' . $reservationData->getResNumber() . ' )',
            'task_type'         => Task::TYPE_CCCA,
            'team_id'           => $taskTypeCCCA->getAssociatedTeamId(),
            'following_team_id' => TeamService::TEAM_CONTACT_CENTER,
            'res_id'            => $reservationId,
            'property_id'       => $reservationData->getApartmentIdAssigned(),
            'start_date'        => $currentDate,
            'end_date'          => $endDate,
            'task_status'       => Task::STATUS_NEW,
            'task_priority'     => Task::TASK_PRIORITY_NORMAL,
            'creator_id'        => UserMain::SYSTEM_USER_ID,
            'verifier_id'       => UserService::AUTO_VERIFY_USER_ID
        ];

        $taskId = $taskService->taskSave($taskData, $actionsSet, true);

        $this->assertTrue((boolval($taskId)));

        if ($taskId) {
            $taskStaffId = $taskStaffDao->save(
                [
                    'task_id' => $taskId,
                    'user_id' => UserService::ANY_TEAM_MEMBER_USER_ID,
                    'type'    => Task::STAFF_RESPONSIBLE
                ]
            );

            $this->assertTrue((boolval($taskStaffId)));
        }

        // remove testing data
        $taskDao->delete(
            [
                'id' => $taskId,
            ]
        );
        $taskStaffDao->delete(
            [
                'task_id' => $taskId,
            ]
        );
    }
}