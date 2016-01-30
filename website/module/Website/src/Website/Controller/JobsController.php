<?php

namespace Website\Controller;

use DDD\Service\User;
use Library\Constants\Roles;
use Website\Form\JobsForm;
use Website\Form\InputFilter\JobsFilter;

use Library\Controller\WebsiteBase;
use Library\Upload\Files;
use Library\Constants\Constants;
use Library\Validator\ClassicValidator;
use DDD\Dao\User\UserGroup;

use DDD\Service\Recruitment\Job as JobService;

use DDD\Service\Notifications as NotificationService;

use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class JobsController extends WebsiteBase
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Website\Job $jobService
         */
        $jobService = $this->getServiceLocator()->get('service_website_job');
        $result     = $jobService->getJobsForWebsite();

        $jobs = [];
        foreach ($result as $row) {
            $jobs[$row['city_id']]['jobs'][]     = $row;
            $jobs[$row['city_id']]['img']        = '/locations/' . $row['detail_id'] . '/' . $row['city_img'];
            $jobs[$row['city_id']]['country_id'] = $row['country_id'];
        }
        return new ViewModel(['jobs' => $jobs]);
    }

    public function announcementAction()
    {
        /** @var \DDD\Service\Website\Job $jobService */
        $slug         = $this->params()->fromRoute('slug');
        $location     = $this->params()->fromRoute('location');
        $jobService   = $this->getServiceLocator()->get('service_website_job');
        $slug         = $location . '/' . $slug;
        $announcement = $jobService->getJobAnnouncementBySlugDate($slug);

        if ($announcement && $announcement->getStatus() == JobService::LIVE_STATUS) {
            return new ViewModel(['announcement' => $announcement]);
        } else {
            return $this->redirect()->toRoute('jobs');
        }
    }

    public function applyAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => 'Unable to save application. Please try again later.',
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                /** @var \DDD\Service\Website\Job $jobService */
                $jobService = $this->getServiceLocator()->get('service_website_job');

                $form         = new JobsForm('announcement-form');
                $inputs       = $request->getPost()->toArray();
                $validateTags = ClassicValidator::checkScriptTags($inputs);

                if (!$validateTags) {
                    return  new JsonModel([
                        'status' => 'error',
                        'msg'    => 'Unable to save application. Please try again later.',
                    ]);
                }

                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );

                $form->setInputFilter(new JobsFilter());
                $form->setData($post);

                if ($form->isValid()) {
                    $data              = $form->getData();
                    $filesObj          = new Files($request->getFiles()->toArray());
                    $acceptedFileTypes = ['pdf', 'doc', 'docx', 'odt', 'rtf'];
                    $filenames         = $filesObj->saveFiles(
                        '/ginosi/uploads/hr/applicants/' . date('Y/m/'),
                        $acceptedFileTypes,
                        false,
                        true
                    );

                    if ($filenames['cv']) {
                        $data['cv'] = $filenames['cv'];
                    } else {
                        unset($data['cv']);
                    }

                    $data['date_applied'] = date('Y-m-d H:i:s');
                    $applicantId = $jobService->saveApplicant($data);

                    if ($applicantId) {
                        /** @var \DDD\Dao\Recruitment\Job\Job $jobDao */
                        $jobDao  = $this->getServiceLocator()->get('dao_recruitment_job_job');

                        /** @var \DDD\Domain\Recruitment\Job\Job $jobInfo */
                        $jobInfo = $jobDao->fetchOne(['id' => $data['job_id']]);

                        $hiringManager = $jobInfo->getHiringManagerId();

                        if ($jobInfo && $jobInfo->getNotifyManager() && $hiringManager && !is_null($hiringManager)) {
                            $notificationService = $this
                                ->getServiceLocator()
                                ->get('service_notifications');

                            $sender = NotificationService::$applicants;

                            $message = 'You have a new applicant for ' .
                                $jobInfo->getTitle() . ' position - ' .
                                $data['firstname'] . ' ' . $data['lastname'] .
                                '. Applied on ' . date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($data['date_applied']));

                            $url = '/recruitment/applicants/edit/'.$applicantId;
                            $notificationData = [
                                'recipient' => $hiringManager,
                                'sender'    => $sender,
                                'sender_id' => User::SYSTEM_USER_ID,
                                'message'   => $message,
                                'url'       => $url,
                                'show_date' => date('Y-m-d'),
                            ];
                            $notificationService->createNotification($notificationData);
                        }
                    }
                    $result = [
                        'status' => 'success',
                        'msg'    => 'Application saved'
                    ];
                }
            }
        } catch (\Exception $ex) {
            $this->gr2logException($ex, 'Website: Job application saving process failed');
        }

        return new JsonModel($result);
    }
}
