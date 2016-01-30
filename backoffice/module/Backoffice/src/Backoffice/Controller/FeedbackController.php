<?php

namespace Backoffice\Controller;

use Library\Asana\Asana;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use DDD\Service\Asana\Feedback;
use Library\Utility\Helper;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use Library\ActionLogger\Logger;

class FeedbackController extends ControllerBase
{
    public function uploadAction()
    {
        $request = $this->getRequest();
        $key = (int)$request->getQuery('key', '0');
        $targetPath = $this->getAttachmentsDirectory() . '/' . $key;
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if (!is_readable($targetPath)) {
                if (!@mkdir($targetPath, true)) {
                    throw new \Exception('Permission denied (mkdir).');
                }

                if (!@chmod($targetPath, 0775)) {
                    throw new \Exception('Permission denied (chmod).');
                }
            }

            if ($request->getQuery('base64')) {
                $imageData = $_REQUEST['file'];
                $targetFile =  $targetPath . '/screenshot.png';

                list(, $imageData) = explode(';', $imageData);
                list(, $imageData) = explode(',', $imageData);
                $imageData = base64_decode($imageData);

                if (file_put_contents($targetFile, $imageData)) {
                    $result = [
                        'status' => 'success',
                        'msg' => 'Successfully uploaded',
                    ];
                }
            } else {
                if ($request->getFiles()->count()) {
                    $file = $request->getFiles()->get('file');
                    $tempFile = $file['tmp_name'];
                    $targetFile =  $targetPath . '/' . $file['name'];

                    if (move_uploaded_file($tempFile, $targetFile)) {
                        $result = [
                            'status' => 'success',
                            'msg' => 'Successfully uploaded',
                        ];
                    }
                } else {
                    $result['msg'] = 'No file selected';
                }
            }
        } catch (\Exception $ex) {
            $result['msg'] = $ex->getMessage();
        }

        return new JsonModel($result);
    }

    public function saveAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        ini_set('max_execution_time', 0);
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $config = $this->getServiceLocator()->get('config');
        $asanaFeedbackConfig = $config['asana-api']['feedback'];
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $asana = new Asana(['apiKey' => $asanaFeedbackConfig['api_key']]);

            $prop = $request->getPost('prop');
            $identity = $auth->getIdentity();

            $followers        = [];
            $notes            = '';
            $taskTitle        = '';
            $additionalParams = [];
            $selectedType     = $request->getPost('selected-type');
            switch ($selectedType) {
                case Feedback::FEEDBACK_TYPE_SOFTWARE_FEEDBACK_VALUE:
                    $requestFeedbackType = $request->getPost('feedback-application-types');
                    $projectId = $asanaFeedbackConfig['project_id_development'];
                    if ($requestFeedbackType == Feedback::FEEDBACK_APPLICATION_TYPE_CALL_CENTER) {
                        $projectId = $asanaFeedbackConfig['project_id_hardware'];
                    }

                    $taskTitle = $request->getPost('feedback-title');

                    if (Feedback::FEEDBACK_APPLICATION_TYPE_MOBILE_APPLICATION == $request->getPost('feedback-application-types')) {
                        $mobileApplication = $request->getPost('mobile-application-sub-type');
                        $projectId         = $asanaFeedbackConfig['project_id_hardware'];
                        $notes            .= 'Mobile Application: ' . $mobileApplication . PHP_EOL;
                    } elseif (Feedback::FEEDBACK_APPLICATION_TYPE_GOOGLE_INFRASTRUCTURES == $request->getPost('feedback-application-types')) {
                        $projectId  = $asanaFeedbackConfig['project_id_hardware'];
                        $notes     .= 'Google Infrastructure: ' . $requestFeedbackType . PHP_EOL;
                    } elseif (Feedback::FEEDBACK_APPLICATION_TYPE_BACKOFFICE != $request->getPost('feedback-application-types')) {
                        $notes .= 'Application: ' . $requestFeedbackType . PHP_EOL;
                    }

                    $notes .= $request->getPost('feedback-description');
                    break;
                case Feedback::FEEDBACK_TYPE_ACCOUNT_MANAGEMENT_VALUE:
                    $projectId = $asanaFeedbackConfig['project_id_hardware'];
                        switch ($request->getPost('feedback-account-management-type')) {
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT_VALUE:
                                $taskTitle = Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT_TEXT . ' - ' . $request->getPost('feedback-firstname') . ' ' . $request->getPost('feedback-lastname');
                                $notes .=  'Existing email address: '  . $request->getPost('feedback-existing-email-address')  . PHP_EOL .
                                  'Department: '  . $request->getPost('feedback-department')  . PHP_EOL .
                                  'Position and Title: '  . $request->getPost('feedback-position-and-title')  . PHP_EOL .
                                  'Location: '  . $request->getPost('feedback-location')  . PHP_EOL .
                                  'Personal information: '  . $request->getPost('feedback-personal-info', ' - ')  . PHP_EOL ;
                                $additionalParams['due_on'] =  date("Y-m-d", strtotime($request->getPost('feedback-duedate')));
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT_VALUE:
                                $taskTitle = Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT_TEXT;
                                $notes .=  'Accounts that need to be removed: ' . $request->getPost('feedback-account')  .PHP_EOL .
                                    'Account username / email: ' . $request->getPost('feedback-account-username')  .PHP_EOL .
                                    'Email where Google Drive files should be transferred to: ' . $request->getPost('feedback-google-drive-transfer-email')  .PHP_EOL ;

                                $additionalParams['due_on'] =  date("Y-m-d", strtotime($request->getPost('feedback-duedate')));
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT_VALUE:
                                $taskTitle = Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT_TEXT;
                                $notes .=  'Accounts that need to be suspended: ' . $request->getPost('feedback-account')  .PHP_EOL .
                                    'Account username / email: ' . $request->getPost('feedback-account-username')  .PHP_EOL ;

                                $additionalParams['due_on'] =  date("Y-m-d", strtotime($request->getPost('feedback-duedate')));
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT_VALUE:
                                $taskTitle = Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT_TEXT;
                                $notes .=  'Accounts that need to be unsuspended: ' . $request->getPost('feedback-account')  .PHP_EOL .
                                    'Account username / email: ' . $request->getPost('feedback-account-username')  .PHP_EOL ;

                                $additionalParams['due_on'] =  date("Y-m-d", strtotime($request->getPost('feedback-duedate')));
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_MAILING_LISTS_VALUE:
                                if (Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_ADD_USER_TO_MAILING_LISTS_VALUE == $request->getPost('feedback-mailing-list-action', Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_ADD_USER_TO_MAILING_LISTS_VALUE)) {
                                    $taskTitle = Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_ADD_USER_TO_MAILING_LISTS_TEXT;
                                } else {
                                    $taskTitle = Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_USER_FROM_MAILING_LISTS_TEXT;
                                }

                                $notes .=  'Account username / email: ' . $request->getPost('feedback-account-username')  .PHP_EOL .
                                    'Mailing List: ' . $request->getPost('feedback-mailing-list')  .PHP_EOL ;
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CALL_CENTER_VALUE:
                                $taskTitle = 'Add Call Center Account // ' . $request->getPost('feedback-full-name');

                                $notes .=
                                    'Department: ' . $request->getPost('feedback-department')  .PHP_EOL
                                    . 'Account full name: ' . $request->getPost('feedback-full-name')  .PHP_EOL
                                    . 'Reason: ' . $request->getPost('feedback-reason')  .PHP_EOL;
                                break;
                        }
                    break;
                case Feedback::FEEDBACK_TYPE_TRAINING_REQUEST_VALUE:
                    $projectId = $asanaFeedbackConfig['project_id_development'];
                    $taskTitle = $request->getPost('feedback-title');
                    $notes .= $request->getPost('feedback-description');

                    break;
                case Feedback::FEEDBACK_TYPE_ELECTRONICS_REQUEST_VALUE:
                    $projectId = $asanaFeedbackConfig['project_id_hardware'];
                    $taskTitle = Feedback::FEEDBACK_TYPE_ELECTRONICS_REQUEST_TEXT;
                    $notes .=  'Location: ' . $request->getPost('feedback-location')  .PHP_EOL .
                        'Reason: ' . $request->getPost('feedback-reason')  .PHP_EOL ;

                    break;
                case Feedback::FEEDBACK_TYPE_MARKETING_IDEA_VALUE:
                    $projectId = $asanaFeedbackConfig['project_id_marketing'];
                    $taskTitle = $request->getPost('feedback-title');
                    $notes .= $request->getPost('feedback-description');
                    break;
                case Feedback::FEEDBACK_TYPE_CONTENT_IDEA_VALUE:
                    $projectId = $asanaFeedbackConfig['project_id_content'];
                    $taskTitle = $request->getPost('feedback-title');
                    $notes .= $request->getPost('feedback-description');
                    break;
            }

            $notBOInfo = '';
            if (Feedback::FEEDBACK_APPLICATION_TYPE_BACKOFFICE == $request->getPost('feedback-application-types')) {
                $notBOInfo = "
Screen Size: {$prop['screen_size']}
URL: {$prop['url']}";
            }

            $notes .= PHP_EOL . "
--------------------------------------------------------------
System Parameters
{$notBOInfo}
User: {$identity->firstname} {$identity->lastname} (#{$identity->id})
Email: {$identity->email}
Timezone: {$identity->timezone}
--------------------------------------------------------------";

            if ($identity->asana_id) {
                array_push($followers, $identity->asana_id);
            }

            $resultAsana = $asana->createTask(array_merge([
                'workspace' => $asanaFeedbackConfig['workspace_id'],
                'name'      => $taskTitle,
                'notes'     => $notes,
                'projects'  => [$projectId],
                'followers' => $followers,
            ]),$additionalParams);

            if (!in_array($asana->responseCode, ['200', '201']) || is_null($resultAsana)) {
                $result['msg'] = "Error while trying to connect to Asana, response code: $asana->responseCode";
            } else {
                $resultJson = json_decode($resultAsana);
                $taskId = $resultJson->data->id;

                // attaching attachments to the task
                $targetPath = $this->getAttachmentsDirectory() . '/' . (int)$request->getPost('key');

                if (is_readable($targetPath)) {
                    $files = glob($targetPath . '/*');

                    if (count($files)) {
                        foreach ($files as $file) {
                            $asana->addAttachmentToTask($taskId, ['file' => $file]);

                            if (in_array($asana->responseCode, ['200', '201'])) {
                                unlink($file);
                            }
                        }
                    }

                    Helper::deleteDirectory($targetPath);
                }

                //doing post task create actions for specific types (subtasks, tags)
                switch ($selectedType) {
                    case Feedback::FEEDBACK_TYPE_SOFTWARE_FEEDBACK_VALUE:
                        if ((int) $request->getPost('feedback-is-bug') == 1) {
                            //add bug tag
                            $asana->addTagToTask($taskId, $asanaFeedbackConfig['bug_tag_id']);
                        }
                        break;
                    case Feedback::FEEDBACK_TYPE_ACCOUNT_MANAGEMENT_VALUE:
                        $logger  = $this->getServiceLocator()->get('ActionLogger');
                        switch ($request->getPost('feedback-account-management-type')) {
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT_VALUE:
                                //add subtasks
                                if ((int)$request->getPost('feedback-subscribe-google-groups') == 1) {
                                    $asana->createSubTask($taskId, [
                                        'name' => 'Subscribe to department mailing list',
                                    ]);
                                }
                                if (strlen($request->getPost('feedback-other-account')) > 1) {
                                    $feedbackOtherAccount = trim($request->getPost('feedback-other-account'));
                                    if (sizeof(explode(',', $feedbackOtherAccount)) > 1) {
                                        $separator = ' accounts';
                                    } else {
                                        $separator = ' account';
                                    }

                                    $asana->createSubTask($taskId, [
                                        'name' => 'Create ' . $feedbackOtherAccount . $separator,
                                    ]);
                                }
                                if ((int)$request->getPost('feedback-lastpass-account') == 1) {
                                    $asana->createSubTask($taskId, [
                                        'name' => 'Create LastPass account',
                                    ]);
                                }
                                if ((int)$request->getPost('feedback-bo-account') == 1) {
                                    $asana->createSubTask($taskId, [
                                        'name' => 'Create BO account',
                                    ]);
                                }
                                if ((int)$request->getPost('feedback-google-account') == 1) {
                                    $asana->createSubTask($taskId, [
                                        'name' => 'Create Google account',
                                    ]);
                                }
                                if ((int)$request->getPost('computer-setup') == 1) {
                                    $asana->createSubTask($taskId, [
                                        'name' => 'Setup computer',
                                    ]);
                                }
                                //log into backoffice
                                $msg = 'Requested to create an account for <b>' . $request->getPost('feedback-firstname') .
                                    ' ' . $request->getPost('feedback-lastname') . '</b>';
                                $logger->save(Logger::MODULE_USER, $identity->id, Logger::ACTION_HR_ACCOUNT_MANAGEMENT, $msg);
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT_VALUE:
                                //log into backoffice
                                $msg = 'Requested to remove <b>' . $request->getPost('feedback-account') .
                                    '</b> accounts for <b>' . $request->getPost('feedback-account-username') . '</b>';
                                $logger->save(Logger::MODULE_USER, $identity->id, Logger::ACTION_HR_ACCOUNT_MANAGEMENT, $msg);
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT_VALUE:
                                //log into backoffice
                                $msg = 'Requested to suspend <b>' . $request->getPost('feedback-account') .
                                    '</b> accounts for <b>' . $request->getPost('feedback-account-username') . '</b>';
                                $logger->save(Logger::MODULE_USER, $identity->id, Logger::ACTION_HR_ACCOUNT_MANAGEMENT, $msg);
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT_VALUE:
                                //log into backoffice
                                $msg = 'Requested to suspend <b>' . $request->getPost('feedback-account') .
                                    '</b> accounts for <b>' . $request->getPost('feedback-account-username') . '</b>';
                                $logger->save(Logger::MODULE_USER, $identity->id, Logger::ACTION_HR_ACCOUNT_MANAGEMENT, $msg);
                                break;
                            case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_ADD_USER_TO_MAILING_LISTS_VALUE:
                                //log into backoffice
                                $msg = 'Requested to add <b>' . $request->getPost('feedback-account-username') . '</b> to <b>' .
                                    $request->getPost('feedback-mailing-list') . '</b> Mailing list';
                                $logger->save(Logger::MODULE_USER, $identity->id, Logger::ACTION_HR_ACCOUNT_MANAGEMENT, $msg);
                                break;
                        }
                        break;
                    case Feedback::FEEDBACK_TYPE_TRAINING_REQUEST_VALUE:
                        $asana->addTagToTask($taskId, $asanaFeedbackConfig['training_tag_id']);
                        break;
                    case Feedback::FEEDBACK_TYPE_ELECTRONICS_REQUEST_VALUE:

                        break;
                    case Feedback::FEEDBACK_TYPE_MARKETING_IDEA_VALUE:

                        break;
                    case Feedback::FEEDBACK_TYPE_CONTENT_IDEA_VALUE:

                        break;
                }

                $result = [
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_ADD,
                    'data'   => [
                        'asana_user'   => (count($followers) ? true : false),
                        'workspace_id' => $asanaFeedbackConfig['workspace_id'],
                        'project_id'   => $projectId,
                        'task_id'      => $taskId,
                        'url'          => "https://app.asana.com/0/{$projectId}/{$taskId}",
                    ],
                ];
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function renderTemplateAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $feedbackType = (int) $request->getPost('feedback_type');
            $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
            $partialFile ='backoffice/asana-feedback/partial/';
            switch ($feedbackType) {
                case Feedback::FEEDBACK_TYPE_SOFTWARE_FEEDBACK_VALUE:
                    $partialFile .= 'software-feedback';
                    break;
                case Feedback::FEEDBACK_TYPE_ACCOUNT_MANAGEMENT_VALUE:
                    $partialFile .= 'account-management';
                    break;
                case Feedback::FEEDBACK_TYPE_TRAINING_REQUEST_VALUE:
                    $partialFile .= 'training-request';
                    break;
                case Feedback::FEEDBACK_TYPE_ELECTRONICS_REQUEST_VALUE:
                    $partialFile .= 'electronics-request';
                    break;
                case Feedback::FEEDBACK_TYPE_MARKETING_IDEA_VALUE:
                    $partialFile .= 'marketing-idea';
                    break;
                case Feedback::FEEDBACK_TYPE_CONTENT_IDEA_VALUE:
                    $partialFile .= 'content-idea';
                    break;
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_MOBILE_APPLICATION_VALUE:
                    $partialFile .= 'mobile-application';
                    break;
            }

            $result['partial_html'] = $partial($partialFile,[]);
            $result['status'] = 'success';
            unset($result['msg']);

        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);

        }

    public function renderAccountManagementTemplateAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $accountManagementOperation = (int) $request->getPost('account_management_operation');
            $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
            $partialFile ='backoffice/asana-feedback/partial/account-management/';
            $params = [];
            switch ($accountManagementOperation) {
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT_VALUE:
                    $teamService     = $this->getServiceLocator()->get('service_team_team');
                    $departments = $teamService->getTeamList(null, 1);
                    $params['departments'] = $departments;
                    $partialFile .= 'create-account';
                    break;
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT_VALUE:
                    $partialFile .= 'remove-account';
                    break;
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT_VALUE:
                    $partialFile .= 'suspend-account';
                    break;
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT_VALUE:
                    $partialFile .= 'unsuspend-account';
                    break;
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_MAILING_LISTS_VALUE:
                    $partialFile .= 'mailing-list';
                    break;
                case Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CALL_CENTER_VALUE:
                    $teamService     = $this->getServiceLocator()->get('service_team_team');
                    $departments = $teamService->getTeamList(null, 1);
                    $params['departments'] = $departments;
                    $partialFile .= 'call-center';
                    break;
            }

            $result['partial_html'] = $partial($partialFile,$params);
            $result['status'] = 'success';
            unset($result['msg']);

        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);

    }

    public function renderMobileApplicationTypesTemplateAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $partial     = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
            $partialFile ='backoffice/asana-feedback/partial/mobile-application';
            $params      = [];

            $result['partial_html'] = $partial($partialFile, $params);
            $result['status'] = 'success';
            unset($result['msg']);

        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    private function getAttachmentsDirectory() {
        return '/ginosi/uploads/tmp';
    }
}
