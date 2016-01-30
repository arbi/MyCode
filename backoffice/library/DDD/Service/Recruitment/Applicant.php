<?php

namespace DDD\Service\Recruitment;

use DDD\Service\Queue\EmailQueue;
use DDD\Service\ServiceBase;
use Zend\Db\Sql\Expression;
use Zend\Form\Form;
use Library\Controller\ControllerBase;

final class Applicant extends ServiceBase
{
    const APPLICANT_STATUS_ALL       = 0;
    const APPLICANT_STATUS_NEW       = 1;
    const APPLICANT_STATUS_SCREEN    = 2;
    const APPLICANT_STATUS_REVIEW    = 3;
    const APPLICANT_STATUS_INTERVIEW = 4;
    const APPLICANT_STATUS_PRACTICAL = 5;
    const APPLICANT_STATUS_ACCEPT    = 6;
    const APPLICANT_STATUS_REJECT    = 7;
    const APPLICANT_STATUS_STOP      = 8;
    const APPLICANT_STATUS_HIRE      = 9;
    const APPLICANT_STATUS_PAUSE     = 10;
    const APPLICANT_STATUS_DELETE    = 11;

    public static $status = [
        self:: APPLICANT_STATUS_NEW       => 'New',
        self:: APPLICANT_STATUS_SCREEN    => 'Screening',
        self:: APPLICANT_STATUS_INTERVIEW => 'Interview',
        self:: APPLICANT_STATUS_PRACTICAL => 'Practical',
        self:: APPLICANT_STATUS_REVIEW    => 'Review',
        self:: APPLICANT_STATUS_ACCEPT    => 'Accepted',
        self:: APPLICANT_STATUS_PAUSE     => 'Paused',
        self:: APPLICANT_STATUS_REJECT    => 'Rejected',
        self:: APPLICANT_STATUS_STOP      => 'Stopped',
        self:: APPLICANT_STATUS_HIRE      => 'Hired',
        self:: APPLICANT_STATUS_DELETE    => 'Deleted'
    ];

    /**
     * @var \DDD\Dao\Recruitment\Applicant\Applicant
     */
    private $daoApplicant = false;

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @param $all
     * @param array $hiringTeamId
     * @return \DDD\Domain\Recruitment\Applicant\Applicant[]
     */
    public function getApplicantList(
        $offset, $limit, $sortCol, $sortDir, $like, $all, $hiringTeamId = [],
        $isGlobManager = false, $hiringCountryId = false, $userId = false, $userTeamIds = []
    ) {
        return $this->getApplicantDao()->getApplicantList(
            $offset, $limit, $sortCol, $sortDir, $like, $all, $hiringTeamId,
            $isGlobManager, $hiringCountryId, $userId, $userTeamIds
        );
    }

    public function getApplicantCount(
        $like,
        $all,
        $isGlobManager   = false,
        $hiringCountryId = false,
        $userId          = false,
        $userTeamIds     = []
    ) {
        return $this->getApplicantDao()->getApplicantCount($like, $all, $isGlobManager, $hiringCountryId, $userId, $userTeamIds);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Recruitment\Applicant\Applicant
     */
    public function getApplicantById($id)
    {
        return $this->getApplicantDao()->getApplicantById($id);
    }

    public function getApplicantsByEmail($email, $id)
    {
        return $this->getApplicantDao()->getApplicantByEmail($email, $id);
    }

    public function getData($id)
    {
        $userService = $this->getServiceLocator()->get('service_user');
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $activeUsers  = $userService->getPeopleList();
        $job = $this->getJobDao()->fetchOne(['id' => $id]);
        $hiringManagerId = null;
        $users = [];

        if (!$job) {
            return false;
        }


        foreach ($activeUsers as $user) {
            $users[$user['id']] = $user['firstname'] . ' ' . $user['lastname'];
        }

        if (!array_key_exists($job->getHiringManagerId(), $users)) {
            $disabledUser = $userManagerDao->fetchOne([
                'id'       => $job->getHiringManagerId(),
                'disabled' => 1,
            ]);

            if ($disabledUser) {
                $hiringManagerId = [
                    $disabledUser->getId() => $disabledUser->getFirstName() . ' ' . $disabledUser->getLastName(),
                ];
             }
        }

        return [
            'job' => $job,
            'disabledHM' => $hiringManagerId,
        ];

    }

    public function getApplicantOptions()
    {
        $userService = $this->getServiceLocator()->get('service_user');

        return ['ginosiksList' => $userService->getPeopleList()];
    }

    public function saveApplicantStatus($id, $status)
    {
        /**
         * @var \DDD\Dao\Queue\EmailQueue $queueDao
         */
        $queueDao = $this->getServiceLocator()->get('dao_queue_email_queue');
        $this->getApplicantDao();

        if (Applicant::APPLICANT_STATUS_REJECT == $status) {
            $exists = $queueDao->fetchOne([
                'entity_id' => $id,
                'type' => EmailQueue::TYPE_APPLICANT_REJECTION,
            ]);

            if (!$exists) {
                $queueDao->save([
                    'entity_id' => $id,
                    'type' => EmailQueue::TYPE_APPLICANT_REJECTION,
                    'send_time' => new Expression('DATE_ADD(NOW(), INTERVAL 1 DAY)'),
                ]);
            }
        } else {
            $queueDao->delete([
                'entity_id' => $id,
                'type' => EmailQueue::TYPE_APPLICANT_REJECTION,
            ]);
        }

        $this->daoApplicant->save(['status' => $status], ['id' => $id]);
    }

    public function addDownloadButton($applicantId, Form $form,  ControllerBase $controller) {
        $router = $controller->getEvent()->getRouter();
        $downloadUrl = $router->assemble(['controller' => 'applicants', 'action' => 'downloadCv', 'id' => $applicantId], ['name' => 'recruitment/applicants']);
        $removeUrl = $router->assemble(['controller' => 'applicants', 'action' => 'removeAttachment', 'id' => $applicantId], ['name' => 'recruitment/applicants']);
        $form->add(array(
            'name' => 'download',
            'type' => 'Zend\Form\Element\Button',
            'attributes' => array(
                'value' => $downloadUrl,
                'id'    => 'download-attachment',
                'class' =>'btn btn-info btn-large pull-left self-submitter state hidden-file-input',
            ),
            'options' => array(
                'label' => 'Download Attachment',
                'download-icon' => 'icon-download-alt icon-white',
                'remove-icon' => 'icon-remove icon-white',
                'remove-url' => $removeUrl,
            ),
        ), array(
            'name' => 'download',
            'priority' => 9,
        ));
    }

    public function getApplicantDao()
    {
        if (!($this->daoApplicant)) {
            $this->daoApplicant = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant');
        }

        return $this->daoApplicant;
    }
}
