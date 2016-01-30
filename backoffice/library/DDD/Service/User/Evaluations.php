<?php

namespace DDD\Service\User;

use DDD\Dao\User\Evaluation\EvaluationItems as EvaluationItemsDAO;
use DDD\Dao\User\UserGroup;
use DDD\Dao\User\UserManager;
use DDD\Service\Notifications as NotificationService;
use DDD\Service\User as UserBase;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;
use Library\Constants\TextConstants;

/**
 * Class Evaluations
 * @package DDD\Service\User
 */
class Evaluations extends UserBase
{
    const USER_EVALUATION_TYPE_COMMENT     = 1;
    const USER_EVALUATION_TYPE_WARNING     = 2;
    const USER_EVALUATION_TYPE_EVALUATION  = 3;

    const USER_EVALUATION_STATUS_PLANNED   = 1;
    const USER_EVALUATION_STATUS_DONE      = 2;
    const USER_EVALUATION_STATUS_CANCELLED = 4;

    const USER_EVALUATION_PERIOD_NONE      = 0;
    const USER_EVALUATION_PERIOD_QUARTERLY = 3;
    const USER_EVALUATION_PERIOD_BI_ANNUAL = 6;
    const USER_EVALUATION_PERIOD_YEARLY    = 12;


    /**
     * @return array
     */
    public static function getEvaluationStatusOptions()
    {
        return [
            self::USER_EVALUATION_STATUS_PLANNED => 'Planned',
            self::USER_EVALUATION_STATUS_DONE => 'Done',
            self::USER_EVALUATION_STATUS_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * @return array
     */
    public static function getEvaluationTypeOptions()
    {
        return [
            self::USER_EVALUATION_TYPE_EVALUATION => 'Evaluation',
            self::USER_EVALUATION_TYPE_COMMENT => 'Comment',
            self::USER_EVALUATION_TYPE_WARNING => 'Warning'
        ];
    }

    /**
     * @return array
     */
    public static function getEvaluationPeriodOptions()
    {
        return [
            self::USER_EVALUATION_PERIOD_NONE => 'None',
            self::USER_EVALUATION_PERIOD_QUARTERLY => 'Quarterly',
            self::USER_EVALUATION_PERIOD_BI_ANNUAL => 'Bi-annual',
            self::USER_EVALUATION_PERIOD_YEARLY => 'Yearly',
        ];
    }

    /**
     * @param $userId int
     * @return \DDD\Domain\User\Evaluation\EvaluationExtended[]
     */
    public function getUserEvaluationsList($userId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
         */
        $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        return $evaluationDao->getUserEvaluations($userId);
    }

    /**
     * @return \DDD\Domain\User\Evaluation\EvaluationItem[]
     */
    public function getEvaluationItems()
    {
        /**
         * @var EvaluationItemsDAO $evaluationItemsDao
         */
        $evaluationItemsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluation_items');

        return $evaluationItemsDao->getEvaluationItems();
    }

    /**
     * @return array
     */
    public function getEvaluationItemsArray()
    {
        $evaluationItems = $this->getEvaluationItems();

        $evaluationItemsArray = [];
        foreach ($evaluationItems as $evaluationItem) {
            $evaluationItemsArray[$evaluationItem->getId()] = $evaluationItem->getTitle();
        }

        return $evaluationItemsArray;
    }

    /**
     * @param $evaluationId int
     * @return \DDD\Domain\User\Evaluation\Evaluation
     */
    public function getEvaluationData($evaluationId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
         */
        $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        return $evaluationDao->getEvaluationById($evaluationId);
    }

    /**
     * @param $evaluationId int
     * @return \DDD\Domain\User\Evaluation\EvaluationExtended
     */
    public function getEvaluationDataFull($evaluationId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
         */
        $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        return $evaluationDao->getEvaluationsFullById($evaluationId);
    }

    /**
     * @param $data
     * @return bool
     */
    public function addEvaluation($data)
    {
        try {
            /**
             * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
             */
            $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

            $evaluationDao->getAdapter()->getDriver()->getConnection()->beginTransaction();

            // save actual evaluation
            $evaluationId = $evaluationDao->save([
                'user_id'      => $data['user_id'],
                'creator_id'   => $data['creator_id'],
                'status'       => self::USER_EVALUATION_STATUS_DONE,
                'type_id'      => $data['type_id'],
                'date_created' => date('Y-m-d H:i:s'),
                'description'  => $data['description'],
                'average'      => isset($data['average']) ? $data['average'] : 0,
            ]);

            if ($data['type_id'] == self::USER_EVALUATION_TYPE_EVALUATION) {
                $this->doPostEvaluationActions($evaluationId, $data);
            }

            $evaluationDao->getAdapter()->getDriver()->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $evaluationDao->getAdapter()->getDriver()->getConnection()->rollback();

            return false;
        }
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function savePlannedEvaluation($id, $data)
    {
        try {
            /**
             * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
             */
            $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

            $evaluationDao->getAdapter()->getDriver()->getConnection()->beginTransaction();

            // save evaluation
            $evaluationDao->update(
                [
                    'status'       => self::USER_EVALUATION_STATUS_DONE,
                    'date_created' => date('Y-m-d H:i:s'),
                    'description'  => $data['description'],
                    'average'      => isset($data['average']) ? $data['average'] : 0,
                ],
                [
                    'id' => $id
                ]
            );

            $this->doPostEvaluationActions($id, $data);

            $evaluationDao->getAdapter()->getDriver()->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $evaluationDao->getAdapter()->getDriver()->getConnection()->rollback();

            return false;
        }
    }

    /**
     * @param $evaluationId int
     * @param $data []
     */
    private function doPostEvaluationActions($evaluationId, $data)
    {
        /**
         * @var UserManager $userManagerDao
         * @var \DDD\Domain\User\User $employeeData
         */
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        //save evaluation item values
        $this->saveEvaluationItems($evaluationId, $data['evaluation_items']);

        $employeeData = $userManagerDao->fetchOne(
            ['id' => $data['user_id']],
            [
                'firstname',
                'lastname',
                'period_of_evaluation'
            ]
        );
        $employeeFullName = $employeeData->getFirstName() . ' ' . $employeeData->getLastName();

        // notify HR about evaluation
        $this->notifyAboutEvaluation($data['user_id'], $employeeFullName, $data['description']);

        if ($employeeData->getPeriodOfEvaluation()) {
            // plan new evaluation based on employee evaluation period
            $this->planEvaluation($data['creator_id'], $data['user_id'], $employeeData->getPeriodOfEvaluation());
        }
    }


    /**
     * @param $id int
     * @param $items []
     * @return bool
     */
    private function saveEvaluationItems($id, $items)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\EvaluationValues $evaluationValuesDao
         */
        $evaluationValuesDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluation_values');

        foreach ($items as $itemId => $value) {
            $evaluationValuesDao->save([
                'evaluation_id' => $id,
                'item_id'       => $itemId,
                'value'         => $value
            ]);
        }

        return true;
    }

    /**
     * @param $employeeId
     * @param $employeeFullName
     * @param $description
     * @return bool
     */
    private function notifyAboutEvaluation($employeeId, $employeeFullName, $description)
    {
        /**
         * @var NotificationService $notificationService
         * @var BackofficeAuthenticationService $authenticationService
         */
        $notificationService = $this->getServiceLocator()->get('service_notifications');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        $notificationSender = NotificationService::$peopleEvaluations;

        /**
         * @var UserGroup $userGroupDao
         */
        $userGroupDao   = $this->getServiceLocator()->get('dao_user_user_groups');

        // Compose array of recipient ids
        // For those who has "Global Evaluation Manager - Role" will be generated notifications about employee evaluation
        $usersWhoHasGlobalEvaluationManagerRole = $userGroupDao->getUsersByGroupId(Roles::ROLE_GLOBAL_EVALUATION_MANAGER);
        $notificationRecipients = [];
        foreach ($usersWhoHasGlobalEvaluationManagerRole as $recipient) {
            $notificationRecipients[] = $recipient->getUserId();
        }

        $url = '/user/edit/' . $employeeId . '#evaluations';
        $message = sprintf(
            TextConstants::USER_EVALUATION_ADD,
            $url,
            $employeeFullName,
            $authenticationService->getIdentity()->firstname . ' ' . $authenticationService->getIdentity()->lastname,
            strip_tags($description)
        );

        $notificationData = [
            'recipient' => $notificationRecipients,
            'user_id'   => $employeeId,
            'sender'    => $notificationSender,
            'sender_id' => $employeeId,
            'message'   => $message,
            'url'   => $url,
            'show_date' => date('Y-m-d')
        ];

        return $notificationService->createNotification($notificationData);
    }

    /**
     * @param $creatorId int
     * @param $employeeId int
     * @param $periodOfEvaluation string
     * @return bool
     */
    public function planEvaluation($creatorId, $employeeId, $periodOfEvaluation)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationsDao
         */
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        //calculate next evaluation date
        $plannedDate = strtotime('+' . $periodOfEvaluation . ' month');
        $plannedDate = date('Y-m-d H:i:s', $plannedDate);

        $id = $evaluationsDao->insert([
           'user_id' => $employeeId,
           'creator_id' => $creatorId,
           'date_created' => $plannedDate,
           'type_id' => self::USER_EVALUATION_TYPE_EVALUATION
        ]);

        return $id ? true : false;
    }

    /**
     * @param $creatorId
     * @param $employeeId
     * @param $date
     * @param $description
     * @return bool
     */
    public function manualPlanEvaluation($creatorId, $employeeId, $date, $description)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationsDao
         */
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        $id = $evaluationsDao->insert([
            'user_id' => $employeeId,
            'creator_id' => $creatorId,
            'date_created' => $date,
            'type_id' => self::USER_EVALUATION_TYPE_EVALUATION,
            'status' => self::USER_EVALUATION_STATUS_PLANNED,
            'description' => $description
        ]);

        return $id ? true : false;
    }

    /**
     * @param $evaluationId int
     * @return bool
     */
    public function deleteEvaluation($evaluationId)
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationsDao
         * @var \DDD\Dao\User\Evaluation\EvaluationValues $evaluationValuesDao
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');
        $evaluationValuesDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluation_values');
        try {
            $evaluationsDao->getAdapter()->getDriver()->getConnection()->beginTransaction();

            $evaluationValuesDao->delete([
                'evaluation_id' => $evaluationId
            ]);

            $evaluationsDao->delete([
                'id' => $evaluationId
            ]);

            $evaluationsDao->getAdapter()->getDriver()->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $evaluationsDao->getAdapter()->getDriver()->getConnection()->rollback();
            return false;
        }
    }

    /**
     * @param $evaluationId int
     * @return bool
     */
    public function cancelEvaluation($evaluationId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationsDao
         */
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        try {
            $evaluationsDao->update(
                ['status' => self::USER_EVALUATION_STATUS_CANCELLED],
                ['id' => $evaluationId]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $evaluationId int
     * @return bool
     */
    public function resolveEvaluation($evaluationId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationsDao
         */
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        try {
            $evaluationsDao->update(
                ['is_resolved' => 1],
                ['id' => $evaluationId]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $evaluationId
     * @return \ArrayObject|\DDD\Domain\User\Evaluation\EvaluationValues[]
     */
    public function getEvaluationValuesFull($evaluationId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\EvaluationValues $evaluationValuesDao
         */
        $evaluationValuesDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluation_values');

        return $evaluationValuesDao->getValuesFullByEvaluationId($evaluationId);
    }

    /**
     *
     * @param int $evaluationId
     * @return \DDD\Domain\User\Evaluation\EvaluationValues
     */
    public function getEvaluationValues($evaluationId)
    {
        /**
         * @var \DDD\Dao\User\Evaluation\EvaluationValues $evaluationValuesDao
         */
        $evaluationValuesDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluation_values');

        return $evaluationValuesDao->getValuesByEvaluationId($evaluationId);
    }

    /**
     * @return \DDD\Domain\User\Evaluation\EvaluationExtended []
     */
    public function getNotResolvedEvaluations()
    {
        /** @var \DDD\Dao\User\Evaluation\Evaluations $evaluationsDao */
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        return $evaluationsDao->getNotResolvedEvaluations();
    }
}
