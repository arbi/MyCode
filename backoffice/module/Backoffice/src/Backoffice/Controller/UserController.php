<?php

namespace Backoffice\Controller;

use Backoffice\Form\InputFilter\SalarySchemeFilter;
use Backoffice\Form\InputFilter\UserAccountFilter;
use Backoffice\Form\SalarySchemeForm;
use Backoffice\Form\User as UserForm;
use Backoffice\Form\InputFilter\UserFilter as UserFormFilter;
use Backoffice\Form\UserAccountForm;
use Backoffice\Form\UserDocumentsForm;

use DDD\Service\Notifications as NotificationService;
use BackofficeUser\Form\AddEvaluationForm;
use BackofficeUser\Form\PlanEvaluationForm;
use DDD\Dao\User\Document\Documents;
use DDD\Service\Profile as ProfileService;
use DDD\Service\Team\Team as TeamService;
use DDD\Service\User\Evaluations as EvaluationService;
use DDD\Service\User\Main;
use DDD\Service\User as UserService;
use DDD\Service\User\Documents as DocumentService;
use Library\ActionLogger\Logger;
use Library\Asana\Asana;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\DbTables;
use Library\Controller\ControllerBase;
use Library\Finance\Base\Account;
use Library\Validator\ClassicValidator;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Library\Constants\Roles;
use Library\Constants\Constants;
use FileManager\Constant\DirectoryStructure;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;

class UserController extends ControllerBase
{
    private function getUserEditorLevel($userId = 0)
    {
        /**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $authUserIdentity = $auth->getIdentity();

        $authUserEditorLevel = 0;

        if ($auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT)) {
            $authUserEditorLevel = 2;
        } elseif ($auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR) || $userId) {
            $managerId = $this->getManagerIdByUserId($userId);

            if ($managerId && ($managerId == $authUserIdentity->id || $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR))) {
                $authUserEditorLevel = 1;
            }
        }

        return $authUserEditorLevel;
    }

    protected function getForm($id)
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $userData = null;

        if ($id > 0) {
	        $userData = $userService->getUsersById((int)$id, true);
        }

        $userOption = $userService->getUserOptions($id);
        $form = new UserForm('user', $userData, $userOption);

        return ['form' => $form, 'option' => $userOption, 'data' => $userData];
    }

    protected function getUserDocumentsForm()
    {
        $formData['documentTypes'] = $this->getUserDocumentTypesAsArray();

        $documentsForm = new UserDocumentsForm(NULL, $formData);

        return $documentsForm;
    }

    /**
     * @return array|bool
     */
    public function getUserEvaluationItemsAsArray()
    {
        /**
         * @var \DDD\Service\User\Evaluations $userEvaluationsService
         */
        $userEvaluationsService = $this->getServiceLocator()->get('service_user_evaluations');

        $userEvaluationItems = $userEvaluationsService->getEvaluationItems();

        if (is_null($userEvaluationItems)) {
            return false;
        }

        $evaluationItemsArray = [];
        foreach ($userEvaluationItems as $type) {
            $evaluationItemsArray[$type->getId()] = $type->getTitle();
        }

        return $evaluationItemsArray;
    }

    public function getUserDocumentTypesAsArray()
    {
        /**
         * @var \DDD\Service\User\Documents $userDocumentsService
         */
        $userDocumentsService = $this->getServiceLocator()->get('service_user_documents');

        $userDocumentTypes = $userDocumentsService->getDocumentTypes();

        if (is_null($userDocumentTypes)) {
            return false;
        }

        $documentTypesArray = [];
        foreach ($userDocumentTypes as $type) {
            $documentTypesArray[$type->getId()] = $type->getTitle();
        }

        return $documentTypesArray;
    }

    public function editAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\Profile $profileService
         * @var ProfileService $profileService
         * @var TeamService $teamService
         * @var EvaluationService $evaluationService
         * @var DocumentService $documentService
         * @var Logger $logger
         */
        $userId = (int)$this->params()->fromRoute('id', 0);
        $auth   = $this->getServiceLocator()->get('library_backoffice_auth');
        $logger = $this->getServiceLocator()->get('ActionLogger');

        if ($userId > 0 && !$this->getServiceLocator()->get('dao_user_user_manager')->checkRowExist(DbTables::TBL_BACKOFFICE_USERS, 'id', $userId)) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'company-directory']);
        }

        $managerId = $this->getManagerIdByUserId($userId);
        $itsMe     = false;
        $isManager = false;

        if ($auth->getIdentity()->id == $userId) {
            $itsMe = true;
        }

        if ($managerId == $auth->getIdentity()->id) {
            $isManager = true;
        }

        if (   !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT)
            && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)
            && !$isManager
        ) {
            Helper::setFlashMessage(['error' => 'No Permissions']);

            $this->redirect()->toRoute('backoffice/default', [
                'controller' => 'company-directory',
                'action'     => 'index'
            ]);

            return $this->response;
        }

        $profileService = $this->getServiceLocator()->get('service_profile');
        $teamService = $this->getServiceLocator()->get('service_team_team');
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $documentService = $this->getServiceLocator()->get('service_user_documents');

        $subordinates = $profileService->getUserSubordinates($userId);
        $subordinates = iterator_to_array($subordinates);

        $userTeams = $teamService->getUserTeams($userId);

        $documentForm   = $this->getUserDocumentsForm();
        $evaluationForm = new AddEvaluationForm();
        $planEvaluationForm = new PlanEvaluationForm();
        $planEvaluationForm->populateValues([
            'plan_user_id' => $userId,
            'plan_creator_id' => $auth->getIdentity()->id,
        ]);

        $evaluationsList = $evaluationService->getUserEvaluationsList($userId);
        $documentsList = $documentService->getUserDocumentsList($userId);

        $logger->setOutputFormat(Logger::OUTPUT_BOOKING);
        $logData = $this->getLogDatatableData(Logger::MODULE_USER, $userId);

        $formOption = $this->getForm($userId);
        $form       = $formOption['form'];
        $options    = $formOption['option'];
        $data       = $formOption['data'];

        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $managerName = $userService->getUserDataNameID($managerId);
        $permissionHierarchy = $userService->getUserGroups($userId);

        $hasPermissions = ($auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_PERMISSIONS) && $isManager);

        // check if user doesn't have transaction account
        try {
            /**
             * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
             */
            $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
            $transactionAccountId  = $transactionAccountDao->getAccountIdByHolderAndType($userId, Account::TYPE_PEOPLE);
        } catch (\Exception $e) {
            $transactionAccountId = false;
        }

        $userDevices = false;

        if ($auth->hasRole(Roles::ROLE_PEOPLE_DIGITAL_ID_MANAGEMENT)) {
            /**
             * @var \DDD\Dao\User\Devices $userDevicesDao
             */
            $userDevicesDao = $this->getServiceLocator()->get('dao_user_devices');
            $userDevices = $userDevicesDao->getDevicesByUserId($userId);
        }

        return new ViewModel([
            'hasPermissions'                 => $hasPermissions,
            'hasHR'                          => $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR),
            'hasSalaryManagerRole'           => $auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR),
            'hasSalaryViewerRole'            => $auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT),
            'hasGlobalEvaluationManagerRole' => $auth->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER),
            'canDelete'                      => $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT),
            'hasUserManagement'              => $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT),
            'hasTeamManagerRole'             => $auth->hasRole(Roles::ROLE_TEAM_MANAGER),
            'hasDigitalIdManagerRole'        => $auth->hasRole(Roles::ROLE_PEOPLE_DIGITAL_ID_MANAGEMENT),
            'userEvaluationItems'            => $this->getUserEvaluationItemsAsArray(),
            'userId'                         => $auth->getIdentity()->id,
            'historyData'                    => json_encode($logData),
            'evaluationsList'                => $evaluationsList,
            'userEvaluationForm'             => $evaluationForm,
            'planEvaluationForm'             => $planEvaluationForm,
            'documentsList'                  => $documentsList,
            'subordinates'                   => $subordinates,
            'userDocumentForm'               => $documentForm,
            'managerName'                    => $managerName,
            'userTeams'                      => $userTeams,
            'isManager'                      => $isManager,
            'editableUserId'                 => $userId,
            'itsMe'                          => $itsMe,
            'userForm'                       => $form,
            'data'                           => $data,
            'permissionHierarchy'            => $permissionHierarchy,
            'transactionAccountId'           => $transactionAccountId,
            'userDevices'                    => $userDevices
        ]);
    }

    public function ajaxgetcityAction()
    {
        $result = ['rc' => '00', 'result' => []];
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Service\User $userService
                 */
                $userService = $this->getServiceLocator()->get('service_user');

                $country_id = $request->getPost('country_id');

                $countrys   = $userService->getCityByCountryId($country_id);
                $res        = [];

                foreach ($countrys as $key => $row){
                   $res[$key]['id']   = $row->getId();
                   $res[$key]['name'] = $row->getName();
                }

                $result['result'] = $res;
            }
        } catch (\Exception $e) {
            $result['rc'] = '01';
        }

        return new JsonModel($result);
    }

    public function ajaxsaveAction()
    {
	    $request = $this->getRequest();
	    $result = [
			'msg'    => TextConstants::SUCCESS_UPDATE,
			'status' => 'success',
			'result' => [],
			'id'     => 0,
	    ];

        try {
            if ($request->isXmlHttpRequest()) {
				$id            = (int)$request->getPost('user_hidden_id', 0);
				$formOption    = $this->getForm($id);
				$form          = $formOption['form'];
				$messages      = '';

				$form->setInputFilter(new UserFormFilter($id));

               	if ($request->isPost()) {
                    $authUserEditorLevel = $this->getUserEditorLevel($id);
                    $filter = $form->getInputFilter();
                    $form->setInputFilter($filter);
                    $data = $request->getPost();

                    if (isset($data['vacationdays']) && $data['vacationdays'] == $data['vacationdays_rounded_value']) {
                        $data['vacationdays'] = $data['vacationdays_current_value'];
                    }

                   	switch ($authUserEditorLevel) {
                   		case 0:
                   			Helper::setFlashMessage(['error' => 'No Permissions']);
                   			$this->redirect()->toRoute('backoffice/default', ['controller' => 'company-directory']);

                   			return $this->response;

                   			break;
                   		case 1:
                   			$form->remove('accounts');
                   			$form->remove('conciergegroups');

                   			if (isset($data['accounts'])) {
                   				unset($data['accounts']);
		                    }

                   			if (isset($data['dashboards'])) {
                   				unset($data['dashboards']);
		                    }

                   			if (isset($data['conciergegroups'])) {
                   				unset($data['conciergegroups']);
		                    }

                   			break;
                   		case 2:
                   			break;
                   	}

                   	$form->setData($data);
                   	$email = $request->getPost('email');

                    /**
                     * @var \DDD\Service\User $userService
                     */
                    $userService = $this->getServiceLocator()->get('service_user');

                   	if ($email != '' && ClassicValidator::validateEmailAddress($email)) {
                   		if ($userService->checkEmail($email, $id)) {
                   			$messages = 'Email is in use<br>';
	                    }
                   	} else {
                   		$messages = 'Wrong email address format<br>';
                   	}

                   	if ($form->isValid() && $messages == '') {
                   		$responseDb = $userService->userSave($data, $id);

                   		if ($responseDb > 0) {
                   			if ($id) {
                                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                                $result['id'] = $id;
                            } else {
                   				$result['id'] = $responseDb;
                   				Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                   			}
                   		} else {
                   			$result['status'] = 'error';
                   			$result['msg'] = TextConstants::SERVER_ERROR;
                   		}
                   	} else {
                   		$errors = $form->getMessages();

                   		foreach ($errors as $key => $row) {
                   			if (!empty($row)) {
                   				$messages .= ucfirst($key) . ' ';
                   				$messagesSub = '';

                   				foreach ($row as $keyer => $rower) {
				                    $messagesSub .= $rower;
                   				}

                   				$messages .= $messagesSub . '<br>';
                   			}
                   		}

                   		$result['status'] = 'error';
                   		$result['msg'] = $messages;
                   	}
               }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxcheckusernameAction()
    {
	    /**
	     * @var Response $response
	     * @var Request $request
	     */
	    $request = $this->getRequest();
        $response = $this->getResponse();
        $response->setStatusCode(200);

        try {
            if ($request->isXmlHttpRequest()) {
                $email = $request->getPost('email');
                $userId = (int)$request->getPost('id');

                $authUserEditorLevel = $this->getUserEditorLevel($userId);

                if ($authUserEditorLevel != 2) {
                    $response->setContent("no_permissions");
                }

                /**
                 * @var \DDD\Service\User $userService
                 */
                $userService = $this->getServiceLocator()->get('service_user');

                if (ClassicValidator::validateEmailAddress($email) && !$userService->checkEmail($email, $userId)) {
                    $response->setContent("true");
                } else {
                    $response->setContent("false");
                }
            }
        } catch (\Exception $e) {
            $response->setContent("false");
        }

        return $response;
    }

    public function ajaxdeleteAction()
    {
	    /**
	     * @var Request $request
         * @var Main $userMainService
	     */
        $result         = ['rc' => '00'];
        $request        = $this->getRequest();
        $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

       try {
            if ($request->isXmlHttpRequest()) {
                $userId = (int)$request->getPost('id');

                $authUserEditorLevel = $this->getUserEditorLevel($userId);

               	if ($authUserEditorLevel != UserService::PERMISSION_ROLE) {
                    Helper::setFlashMessage(['error'=>  TextConstants::SERVER_ERROR]);
               	}

                $userMainService = $this->getServiceLocator()->get('service_user_main');
                $userInfo        = $usermanagerDao->fetchOne(['id' => $userId]);
                $endDate         = $userInfo->getEndDate();
                $endDate         = preg_replace('/\-/', '', $endDate);
                $userEmail       = $userInfo->getEmail();

                $deactivationDate = null;
                if (is_null($endDate) || !(int)$endDate) {
                    $deactivationDate = date('Y-m-d');
                }

                $userMainService->disableUser($userId, $deactivationDate, $userEmail);
                Helper::setFlashMessage(['success' => 'Successfully deactivated.']);
            }
       } catch (\Exception $e) {
           $result['rc'] = '01';
       }

        return new JsonModel($result);
    }

    public function ajaxactivateAction()
    {
        /**
         * @var Main $userMainService
         */
        $result = ['rc' => '00'];
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                $userId = (int)$request->getPost('id');

                $authUserEditorLevel = $this->getUserEditorLevel($userId);

                if ($authUserEditorLevel != UserService::PERMISSION_ROLE) {
                    Helper::setFlashMessage(['error'=>  TextConstants::SERVER_ERROR]);
                }

                $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

                $userInfo = $usermanagerDao->fetchOne(['id' => $userId]);

                if ($userInfo) {
                    $managerId = $userInfo->getManager_id();
                    $manager   = $usermanagerDao->getUserById($managerId);

                    if (!$manager) {
                        $result['status'] = 'warning';
                        $result['msg']    = "User's manager has been disabled";
                    }
                }

                $userMainService = $this->getServiceLocator()->get('service_user_main');
                $userMainService->activateUser($userId, $userInfo->getEmail(), $userInfo->getPassword());

                Helper::setFlashMessage(['success' => 'Successfully activated.']);
            }
        } catch (\Exception $e) {
            $result['rc'] = '01';
        }

        return new JsonModel($result);
    }

    public function ajaxActivateAlertAction()
    {
        $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $request = $this->getRequest();
        $result = ['status' => '', 'msg' => ''];

        try {
          if ($request->isXmlHttpRequest()) {
              $userId = (int)$request->getPost('id');

              $authUserEditorLevel = $this->getUserEditorLevel($userId);

              if ($authUserEditorLevel != 2) {
                 Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
              }

              $userInfo = $usermanagerDao->fetchOne(['id' => $userId]);

              if ($userInfo) {
                  $managerId = $userInfo->getManager_id();
                  $manager   = $usermanagerDao->getUserById($managerId);
                  $name = $userInfo->getFirstName() . ' ' . $userInfo->getLastName();

                  $msg = sprintf(
                      TextConstants::USER_ACTIVATE_ALERT,
                      $name,
                      $name
                  );

                  $result['msg'] = $msg;

                  if (!$manager) {
                      $managerInfo = $usermanagerDao ->fetchOne(['id' => $managerId]);

                      $managerName = $managerInfo->getFirstName() . ' ' . $managerInfo->getLastName();

                      $managerMsg = sprintf(
                          TextConstants::MANAGER_DISABLED,
                          $name,
                          $managerName
                      );

                      $result['status'] = 'warning';
                      $result['msg']   .= $managerMsg;
                  }

                  $usermanagerDao->save([
                      'vacation_days_per_year' => 0,
                      'vacation_days'          => 0,
                  ], ['id' => $userId]);
              }
          }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }


    /**
     * @todo what is this?
     * @return JsonModel
     */
    public function ajaxSendAction()
    {
		/**
		 * @var Request $request
		 */
		$result = ['status' => 'success', 'msg' => TextConstants::SUCCESS_SEND_MAIL];
		$request = $this->getRequest();

		try {
			if ($request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Service\User $userService
                 */
                $userService = $this->getServiceLocator()->get('service_user');

				$userId = (int)$request->getPost('id');

                if (!$userService->sendMail($userId)) {
                    $result['status'] = 'error';
                    $result['msg'] = TextConstants::ERROR_SEND_MAIL;
                }
			}
		} catch (\Exception $e) {
			$result['status'] = 'error';
            $result['msg'] = TextConstants::ERROR_SEND_MAIL;
		}

		return new JsonModel($result);
	}

    public function ajaxAddDocumentAction()
    {
        /**
         * @var Logger $logger
         */
        $request = $this->getRequest();
        $logger  = $this->getServiceLocator()->get('ActionLogger');
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $url     = $request->getPost('url');
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::USER_DOCUMENT_CANNOT_BE_CREATE,
                    ];
                } else {
                    $data = [
                        'userId'      => $request->getPost('user_id'),
                        'creatorId'   => $request->getPost('creator_id'),
                        'typeId'      => $request->getPost('type_id'),
                        'file'        => $request->getFiles(),
                        'url'         => !empty($url) ? $url : null,
                        'description' => $request->getPost('description'),
                    ];

                    if (empty($data['description'])) {
                        $result = [
                            'status' => 'error',
                            'msg'    => TextConstants::USER_DOCUMENT_EMPTY_DESCRIPTION,
                        ];
                    } else {
                        /**
                         * @var \DDD\Service\User\Documents $userDocumentsService
                         */
                        $userDocumentsService = $this->getServiceLocator()->get('service_user_documents');

                        $documentId = $userDocumentsService->addDocument($data);
                        if ($documentId === false) {
                            return new JsonModel([
                                'status' => 'error',
                                'msg'    => TextConstants::FILE_UPLOAD_ERROR,
                            ]);
                        }
                        $documentDomain = $userDocumentsService->getDocumentsData($documentId);

                        $msg = "{$documentDomain->getType()} document was added.";
                        $logger->save(Logger::MODULE_USER, $request->getPost('user_id'), Logger::ACTION_USER_DOCUMENT, $msg);

                        if ($documentId) {
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                            $result = [
                                'status' => 'success',
                                'msg'    => TextConstants::SUCCESS_ADD,
                            ];
                        }
                    }
                }
            } else {
                $result['msg'] = TextConstants::AJAX_NO_POST_ERROR;
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function removeAttachmentAction()
    {
        /**
         * @var \DDD\Service\User\Documents $userDocumentsService
         * @var \DDD\Dao\User\Document\Documents $userDocDao
         */
        $userDocumentsService   = $this->getServiceLocator()->get('service_user_documents');
        $userDocDao             = $this->getServiceLocator()->get('dao_user_document_documents');

        $documentId   = $this->params()->fromRoute('id', 0);
        $documentData = $userDocumentsService->getDocumentsData($documentId);

        if (!empty($documentData->getAttachment())) {
            $filepath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_USER_DOCUMENTS
                . $documentData->getUserId()
                . '/' . $documentData->getAttachment();

            if (is_writable($filepath)) {
                if (@unlink($filepath)) {
                    $userDocDao->save(
                        ['attachment' => null],
                        ['id' => $documentId]
                    );
                }
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
            } else {
                $this->gr2err("Cannot delete file", ['file' => TextConstants::SERVER_ERROR]);
            }
        }

        $url = $this->getRequest()->getHeader('Referer')->getUri();
        $this->redirect()->toUrl($url);
    }

    public function ajaxDeleteDocumentAction()
    {
        /**
         * @var Logger $logger
         */
        $request = $this->getRequest();
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            if ($request->isXmlHttpRequest()) {
                if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
                    $result = [
                        'status' => 'error',
                        'msg' => TextConstants::USER_DOCUMENT_CANNOT_BE_DELETE
                    ];
                } else {
                    $documentId = $this->params()->fromRoute('id', false);

                    if (is_numeric($documentId) AND $documentId > 0) {
                        /**
                         * @var \DDD\Service\User\Documents $userDocumentsService
                         */
                        $userDocumentsService = $this->getServiceLocator()->get('service_user_documents');

                        $documentDomain = $userDocumentsService->getDocumentsData($documentId);

                        $msg = "{$documentDomain->getType()} document was removed.";
                        $logger->save(Logger::MODULE_USER, $documentDomain->getUserId(), Logger::ACTION_USER_DOCUMENT, $msg);

                        $return = $userDocumentsService->deleteDocument($documentId);

                        if ($return === false) {
                            $result = [
                                'status' => 'error',
                                'msg' => TextConstants::SERVER_ERROR
                            ];
                        } else {
                            $result = [
                                'status' => 'success',
                                'msg' => TextConstants::SUCCESS_DELETE
                            ];

                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
                        }
                    } else {
                        $result = [
                            'status' => 'error',
                            'msg' => TextConstants::USER_DOCUMENT_NOT_FOUND
                        ];
                    }
                }
            } else {
                $result = [
                    'status' => 'error',
                    'msg' => TextConstants::AJAX_NO_POST_ERROR
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'msg' => $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function downloadDocumentAttachmentAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR
        ];

        try {
            if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
                $result['msg'] = TextConstants::USER_DOCUMENT_CANNOT_BE_DOWNLOAD;
            } else {
                $documentId = $this->params()->fromRoute('id', false);

                if (is_numeric($documentId) AND $documentId > 0) {
                    /**
                     * @var \DDD\Service\User\Documents $userDocumentsService
                     */
                    $userDocumentsService = $this->getServiceLocator()->get('service_user_documents');

                    $documentData = $userDocumentsService->getDocumentsData($documentId);

                    $filePath = DirectoryStructure::FS_UPLOADS_USER_DOCUMENTS . $documentData->getUserId() . '/' . $documentData->getAttachment();

                    /**
                     * @var \FileManager\Service\GenericDownloader $genericDownloader
                     */
                    $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');
                    $genericDownloader->downloadAttachment($filePath);

                    if ($genericDownloader->hasError()) {
                        Helper::setFlashMessage(['error' => $genericDownloader->getErrorMessages(true)]);

                        $url = $this->getRequest()->getHeader('Referer')->getUri();
                        $this->redirect()->toUrl($url);
                    }

                    return true;
                } else {
                    $result['msg'] = TextConstants::USER_DOCUMENT_ATTACHMENT_NOT_FOUND;
                }
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    /**
     * @todo #evaluations
     *
     * @return JsonModel
     */
    public function ajaxInformEvaluationAction()
    {
		$notifService      = $this->getServiceLocator()->get('service_notifications');
		$evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $auth              = $this->getServiceLocator()->get('library_backoffice_auth');

        $request  = $this->getRequest();
        $userId   = $auth->getIdentity()->id;
        $ownerId  = $request->getPost('ownerId');
        $sendType = $request->getPost('type');

        try {
            $isManager = ($this->getManagerIdByUserId($ownerId) == $userId);

            if ($request->isXmlHttpRequest()) {
                if (   !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT)
                    && !$auth->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER)
                    && !$isManager
                ) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::NOT_SEND_EVALUATE_INFORM_UD,
                    ];
                } else {
                    if ($sendType != $evaluationService::USER_EVALUATION_TYPE_EVALUATION) {
                        $sender  = ($sendType == $evaluationService::USER_EVALUATION_TYPE_COMMENT)
                            ? NotificationService::$comment
                            : NotificationService::$warning;

                        $evaluationInfo = $evaluationService->getEvaluationData($request->getPost('evalId'));
                        $message = strip_tags($evaluationInfo->getDescription());

                        $notificationData = [
                            'recipient' => $ownerId,
                            'sender'    => $sender,
                            'sender_id' => $userId,
                            'message'   => $message,
                            'show_date' => date('Y-m-d'),
                        ];

                        $notifService->createNotification($notificationData);

                        $result = [
                            'status' => 'success',
                            'msg'    => TextConstants::SEND_EVALUATE_INFORM_UD,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'msg'    => $e->getMessage(),
            ];
        }

        return new JsonModel($result);
    }

    public function editDocumentAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var \DDD\Service\User $userService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService = $this->getServiceLocator()->get('service_user');

        $documentId = $this->params()->fromRoute('id');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
            return $this->redirect()->toRoute('home');
        }

        $userData = $userService->getUserProfileByDocumentId($documentId);
        $form = $this->getUserDocumentsForm();

        $form->populateValues([
            'document_type_id' => $userData['type_id'],
            'document_url' => $userData['url'],
            'document_description' => $userData['description'],
        ]);

        if ($userData['attachment']) {
            $userService->addDownloadButton($documentId, $form, $this);
        }

        return new ViewModel([
            'form'     => $form,
            'userData' => $userData,
        ]);
    }

    public function ajaxEditDocumentAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         * @var \DDD\Domain\User\Document\Documents|bool $documentsDomain
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $documentsDao = new Documents($this->getServiceLocator());
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            $documentsDao->beginTransaction();
            if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
                $result['msg'] = TextConstants::USER_DOCUMENT_CANNOT_BE_CREATE;
            } else {
                if ($request->isPost() && $request->isXmlHttpRequest()) {
                    $documentId = $this->params()->fromRoute('id');
                    $documentFile = null;

                    if (!is_null($request->getFiles('fileInfo'))) {
                        $documentFile = $request->getFiles();
                    }

                    $url = $request->getPost('url');
                    $data = [
                        'creatorId'   => $auth->getIdentity()->id,
                        'typeId'      => $request->getPost('type_id'),
                        'file'        => $documentFile,
                        'url'         => !empty($url) ? $url : null,
                        'description' => $request->getPost('description'),
                    ];

                    /**
                     * @var \DDD\Service\User\Documents $userDocumentsService
                     */
                    $userDocumentsService = $this->getServiceLocator()->get('service_user_documents');

                    $documentDomain = $userDocumentsService->getDocumentsData($documentId);
                    $msg            = "{$documentDomain->getType()} document was edited.";

                    $userDocumentsService->addDocument($data, $documentId);
                    $logger->save(Logger::MODULE_USER, $request->getPost('user_id'), Logger::ACTION_USER_DOCUMENT, $msg);

                    $documentsDao->commitTransaction();

                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);

                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::SUCCESS_UPDATE,
                        'userId' => $documentDomain->getUserId()
                    ];
                }
            }
        } catch (\Exception $ex) {
            $documentsDao->rollbackTransaction();
        }

        return new JsonModel($result);
    }

    public function getLogDatatableData($moduleId, $identityId)
    {
        $actionLoggingDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');
        $actions = $actionLoggingDao->getByTicket($moduleId, $identityId);
        $logData = [];

        if ($actions->count()) {
            foreach ($actions as $log) {
                $rowClass = '';
                if ($log['user_name'] == TextConstants::SYSTEM_USER) {
                    $rowClass = "warning";
                }

                array_push($logData, [
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($log['timestamp'])),
                    $log['user_name'],
                    $log['value'],
                    "DT_RowClass" => $rowClass
                ]);
            }
        }

        return $logData;
    }

    public function getManagerIdByUserId($userId)
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $userDao = $userService->getUserManagerDao();
        $userProfile = $userDao->getUserById($userId, true);

        return $userProfile ? (int)$userProfile->getManager_id() : false;
    }

    public function getManagerProfileByUserId($userId)
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $userDao = $userService->getUserManagerDao();
        $userProfile = $userDao->getUserById($userId, true);

        return $userDao->getUserById($userProfile->getManager_id(), true);
    }

    public function ajaxGetAsanaIdAction()
    {
        /**
         * @var array|\ArrayObject[] $resultJson
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $email = $request->getPost('email');

            try {
                $config = $this->getServiceLocator()->get('config');
                $asanaFeedbackConfig = $config['asana-api']['feedback'];
                $asana = new Asana(['apiKey' => $asanaFeedbackConfig['api_key']]);

                $resultAsana = $asana->getUsers('email');

                if (!in_array($asana->responseCode, ['200', '201']) || is_null($resultAsana)) {
                    $result['msg'] = "Error while trying to connect to Asana, response code: $asana->responseCode";
                } else {
                    $resultJson = json_decode($resultAsana);
                    $resultJson = current($resultJson);

                    if (count($resultJson)) {
                        $found = false;

                        foreach ($resultJson as $asanaUser) {
                            if ($email == $asanaUser->email) {
                                $result = [
                                    'status' => 'success',
                                    'msg' => TextConstants::SUCCESS_FOUND,
                                    'asana_id' => $asanaUser->id,
                                ];

                                $found = true;
                                break;
                            }
                        }

                        if (!$found) {
                            $result['msg'] = 'No such user found in Asana, ensure you have created this user and try again.';
                        }
                    }
                }
            } catch (\Exception $ex) {
                $result['msg'] = $ex->getMessage();
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function ajaxCloneUserAction()
    {
        /**
         * @var \DDD\Service\User $service
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'result' => TextConstants::ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $txt      = strip_tags(trim($request->getPost('txt')));
                $editableUserId = $request->getPost('editable_user_id');
                $service  = $this->getServiceLocator()->get('service_user');
                $userList = $service->getCloneUser($txt, $editableUserId);
                $result['status'] = 'success';
                $result['result'] = $userList;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
        }

        return new JsonModel($result);
    }

    /**
     * Get User account list for datatable
     *
     * @return JsonModel
     */
    public function ajaxGetUserAccountListAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_VIEW,
            ]);
        }

        /**
         * @var \DDD\Service\User\ExternalAccount $userAccountService
         * @var \DDD\Service\User
         */
        $userAccountService = $this->getServiceLocator()->get('service_user_external_account');
        $userId = (int)$this->params()->fromRoute('id', 0);

        if((int) $this->params()->fromQuery('all', 0) == 2) {
            $status = UserService\ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED;
        } else {
            $status = UserService\ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ACTIVE;
        }

        /**
         * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
         */
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($userId, Account::TYPE_PEOPLE);

        /**
         * @var \DDD\Dao\Geolocation\Countries $countriesDao
         */
        $countriesDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $countriesById = $countriesDao->getCountriesById();

        // custom sorting
        $columns = [
            'is_default', 'name', 'type', 'full_legal_name', 'address', 'countryId', 'iban', 'swft'
        ];

        $sortCol = $this->params()->fromQuery('iSortCol_0', 0);
        $sortDir = $this->params()->fromQuery('sSortDir_0', 0);

        $sort = [];
        if ($columns[$sortCol]) {
            $sort = [
                $columns[$sortCol] => $sortDir
            ];
        }

        // get query parameters and reservations data
        $userAccounts = $userAccountService->getExternalAccountsByParams([
            'transactionAccountID' => $transactionAccountID,
            'status'               => $status,
            'sort'                 => $sort
        ]);

        $data = [];
        foreach ($userAccounts as $key => $userAccount) {
            /**
             * @var \DDD\Domain\User\ExternalAccount $userAccount
             */
            if ($userAccount->getIsDefault() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                $data[$key][] = '<span class="label label-primary">Default</span>';
            } else {
                $data[$key][] = '';
            }
            $data[$key][] = $userAccount->getName();
            if ($userAccount->getType() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_DIRECT_DEPOSIT) {
                $data[$key][] = 'Direct Deposit';
            } elseif ($userAccount->getType() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_CHECK) {
                $data[$key][] = 'Check';
            } elseif ($userAccount->getType() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_CASH) {
                $data[$key][] = 'Cash';
            } elseif ($userAccount->getType() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_TYPE_COMPANY_CARD) {
                $data[$key][] = 'Company Card';
            }
            $data[$key][] = $userAccount->getFullLegalName();

            $addressString = '';
            if (strlen($userAccount->getBillingAddress()) > 0) {
                $addressString .= $userAccount->getBillingAddress() . '<br>';
            }
            if (strlen($userAccount->getMailingAddress()) > 0) {
                $addressString .= $userAccount->getMailingAddress() . '<br>';
            }
            if (strlen($userAccount->getBankAddress()) > 0) {
                $addressString .= $userAccount->getBankAddress() . '<br>';
            }
            $data[$key][] = $addressString;
            $data[$key][] = $countriesById[$userAccount->getCountryId()]->getName();
            $data[$key][] = $userAccount->getIban();
            $data[$key][] = $userAccount->getSwft();

            if ($userAccount->getStatus() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED || !$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
                // special for datatable edit link
                $data[$key][] = 0;
            } else {
                // special for datatable edit link
                $data[$key][] = $userAccount->getId();
            }
        }

        return new JsonModel(
            [
                'iTotalRecords'        => sizeof($data),
                "aaData"               => $data,
                'sEcho'                => $this->params()->fromQuery('sEcho'),
                'iDisplayStart'        => $this->params()->fromQuery('iDisplayStart'),
                'iDisplayLength'       => $this->params()->fromQuery('iDisplayLength'),
                'iTotalDisplayRecords' => sizeof($data),
            ]
        );
    }

    /**
     * Get user account form via ajax
     *
     * @return JsonModel|ViewModel
     */
    public function ajaxUserAccountEditAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_MANAGE,
            ]);
        }

        /**
         * @var array|\ArrayObject[] $resultJson
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('user_id')) {
            $postData = $request->getPost();
            $userId   = $postData->get('user_id');

            // get User Account form
            $userAccountForm = $this->getUserAccountForm();

            if ($postData->get('id')) {
                $id = $postData->get('id');
                /**
                 * @var \DDD\Dao\User\ExternalAccount $userAccountDao
                 */
                $userAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
                $userAccount    = $userAccountDao->getById($id);
                $userAccountForm->bind($userAccount);

                if ($userAccount->getIsDefault() == UserService\ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                    $userAccountForm->get('isDefault')->setAttribute('checked', 'checked');
                }
            }

            $userAccountForm->prepare();

            if ($postData->get('id')) {
                $vars = [
                    'id'   => $userAccount->getId()
                ];
            }

            $vars['form']   = $userAccountForm;
            $vars['userId'] = $userId;

            $viewModel = new ViewModel();
            $viewModel->setTemplate('backoffice/user/edit-account');
            $viewModel->setTerminal(true);
            $viewModel->setVariables($vars);

            return $viewModel;
        }

        return new JsonModel($result);
    }


    /**
     * Inactive User account
     *
     * @return array
     */
    public function ajaxSetUserAccountArchiveAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'message' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('userId')) {
            $postData      = $request->getPost();
            $userAccountId = $postData->get('id');
            $userId        = $postData->get('userId');

            if ($userAccountId) {
                /**
                 * @var \DDD\Dao\User\ExternalAccount $userAccountDao
                 */
                $userAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
                $userAccountDao->save([
                    'status'     => UserService\ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED,
                    'is_default' => 0,
                ], [
                    'id' => $userAccountId
                ]);

                Helper::setFlashMessage(["success" => "Successfully Deactivated."]);

                return new JsonModel([
                    "status"  => "success",
                    "message" => "Successfully updated.",
                    "url"     => $this->url()->fromRoute('backoffice/default', ['controller' => 'user', 'action' => 'edit', 'id' => $userId])
                ]);
            }
        }

        return new JsonModel($result);

    }

    /**
     * Save User Account data
     *
     * @return JsonModel
     */
    public function ajaxSaveUserAccountAction()
    {
        /**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'message' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $postData = $request->getPost();

            try {
                $form        = $this->getUserAccountForm();
                $inputFilter = new UserAccountFilter();

                $form->setInputFilter($inputFilter->getInputFilter());
                $form->setData($postData);
                $form->prepare();

                if ($form->isValid()) {
                    $validData = $form->getData();

                    /**
                     * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
                     */
                    $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
                    $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($postData->get('userId'), Account::TYPE_PEOPLE);

                    $where = [];
                    $data  = [
                        'name'                   => $validData['name'],
                        'transaction_account_id' => $transactionAccountID,
                        'type'                   => $validData['type'],
                        'full_legal_name'        => $validData['fullLegalName'],
                        'billing_address'        => $validData['billingAddress'],
                        'mailing_address'        => $validData['mailingAddress'],
                        'bank_address'           => $validData['bankAddress'],
                        'country_id'             => $validData['countryId'],
                        'status'                 => $validData['status'],
                        'iban'                   => $validData['iban'],
                        'swft'                   => $validData['swft'],
                        'is_default'             => $postData->offsetExists('isDefault') ? UserService\ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT : 0,
                        'creator_id'             => $auth->getIdentity()->id
                    ];

                    if ($postData->get('id')) {
                        $where['id']           = $postData->get('id');
                    } else {
                        $data['creation_date'] = date(Constants::DATABASE_DATE_TIME_FORMAT);
                        $data['status']        = UserService\ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ACTIVE;
                    }

                    /**
                     * @var \DDD\Dao\User\ExternalAccount $userAccountDao;
                     */
                    $userAccountDao = $this->getServiceLocator()->get('dao_user_external_account');

                    // check unique isDefault column
                    $defaultAccount = $userAccountDao->checkDefault($data['transaction_account_id']);
                    if ($defaultAccount && $data['is_default'] == UserService\ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                        if (!isset($where['id']) || ($defaultAccount->getId() != $where['id'])) {
                            return new JsonModel([
                                "status" => "error",
                                "msg"    => "Default Account Already Exist",
                            ]);
                        }
                    }

                    $userAccountDao->save($data, $where);

                    Helper::setFlashMessage(["success" => "Successfully updated."]);

                    return new JsonModel([
                        "status"  => "success",
                        "message" => "Successfully updated.",
                        "url"     => $this->url()->fromRoute('backoffice/default', ['controller' => 'user', 'action' => 'edit', 'id' => $postData->get('userId')])
                    ]);
                } else {
                    foreach ($form->getMessages() as $messageId => $messages) {
                        $result['message'] = '';
                        foreach ($messages as $message) {
                            $result['message'] .= ' Validation failure ' . $messageId . ' : ' . $message;
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['message'] = $e->getMessage();
            }
        } else {
            $result['message'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     * Get salary scheme list for datatable
     *
     * @return JsonModel
     */
    public function ajaxGetSalarySchemeListAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_VIEW,
            ]);
        }

        /**
         * @var \DDD\Service\User\SalaryScheme $salarySchemeService
         * @var \DDD\Service\User
         */
        $salarySchemeService = $this->getServiceLocator()->get('service_user_salary_scheme');
        $userId = (int)$this->params()->fromRoute('id', 0);

        /**
         * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
         */
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($userId, Account::TYPE_PEOPLE);

        /**
         * @var \DDD\Service\Currency\Currency $currencyService
         * @var \DDD\Service\User\ExternalAccount  $userAccountService
         */
        $currencyService    = $this->getServiceLocator()->get('service_currency_currency');
        $userAccountService = $this->getServiceLocator()->get('service_user_external_account');

        $currencies       = $currencyService->getSimpleCurrencyList();
        $userAccountsByID = $userAccountService->getExternalAccountsByTransactionAccountIdAndIdKey($transactionAccountID);

        if ((int) $this->params()->fromQuery('all', 0) == 1) {
            $status = UserService\SalaryScheme::SALARY_SCHEME_STATUS_ACTIVE;
        } elseif((int) $this->params()->fromQuery('all', 0) == 2) {
            $status = UserService\SalaryScheme::SALARY_SCHEME_STATUS_INACTIVE;
        } elseif((int) $this->params()->fromQuery('all', 0) == 3) {
            $status = UserService\SalaryScheme::SALARY_SCHEME_STATUS_ARCHIVED;
        } else {
            $status = 0;
        }

        // custom sorting
        $columns = [
            'status', 'type', 'name', 'external_account_id', 'effective_from', 'pay_frequency_type', 'salary'
        ];

        $sortCol = $this->params()->fromQuery('iSortCol_0', 0);
        $sortDir = $this->params()->fromQuery('sSortDir_0', 0);

        $sort = [];
        if ($columns[$sortCol]) {
            $sort = [
                $columns[$sortCol] => $sortDir
            ];
        }

        // get query parameters and scheme data
        $salarySchemes = $salarySchemeService->getSalarySchemesByParams([
            'transactionAccountId' => $transactionAccountID,
            'status'               => $status,
            'sort'                 => $sort
        ]);

        $data = [];
        foreach ($salarySchemes as $key => $salaryScheme) {
            /**
             * @var \DDD\Domain\User\SalaryScheme $salaryScheme
             */
            if ($salaryScheme->getStatus() == UserService\SalaryScheme::SALARY_SCHEME_STATUS_ACTIVE) {
                $data[$key][] = '<span class="label label-success">Active</span>';
            } elseif ($salaryScheme->getStatus() == UserService\SalaryScheme::SALARY_SCHEME_STATUS_INACTIVE) {
                $data[$key][] = '<span class="label label-warning">Inactive</span>';
            } else {
                $data[$key][] = '<span class="label label-danger">Archived</span>';
            }

            if ($salaryScheme->getType() == UserService\SalaryScheme::SALARY_SCHEME_TYPE_LOAN) {
                $data[$key][] = 'Loan';
            } elseif ($salaryScheme->getStatus() == UserService\SalaryScheme::SALARY_SCHEME_TYPE_SALARY) {
                $data[$key][] = 'Salary';
            } else {
                $data[$key][] = 'Compensation';
            }

            $data[$key][] = $salaryScheme->getName();
            $data[$key][] = $userAccountsByID[$salaryScheme->getExternalAccountId()]->getName();
            $data[$key][] = $salaryScheme->getEffectiveFrom() . ' - ' . $salaryScheme->getEffectiveTo();

            if ($salaryScheme->getPayFrequencyType() == UserService\SalaryScheme::SALARY_SCHEME_PAY_FREQUENCY_TYPE_WEEKLY) {
                $data[$key][] = 'Weekly';
            } elseif ($salaryScheme->getPayFrequencyType() == UserService\SalaryScheme::SALARY_SCHEME_PAY_FREQUENCY_TYPE_BI_WEEKLY) {
                $data[$key][] = 'Bi-Weekly';
            } elseif ($salaryScheme->getPayFrequencyType() == UserService\SalaryScheme::SALARY_SCHEME_PAY_FREQUENCY_TYPE_MONTHLY) {
                $data[$key][] = 'Monthly';
            }

            $data[$key][] = $salaryScheme->getSalary() . ' ' . $currencies[$salaryScheme->getCurrencyId()];

            if ($salaryScheme->getStatus() == UserService\SalaryScheme::SALARY_SCHEME_STATUS_ARCHIVED || !$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
                // special for datatable edit link
                $data[$key][] = 0;
            } else {
                // special for datatable edit link
                $data[$key][] = $salaryScheme->getId();
            }
        }

        return new JsonModel(
            [
                'iTotalRecords'        => sizeof($data),
                "aaData" => $data,
                'sEcho'                => $this->params()->fromQuery('sEcho'),
                'iDisplayStart'        => $this->params()->fromQuery('iDisplayStart'),
                'iDisplayLength'       => $this->params()->fromQuery('iDisplayLength'),
                'iTotalDisplayRecords' => sizeof($data),
            ]
        );
    }

    /**
     * Get salary scheme form via ajax
     *
     */
    public function ajaxSalarySchemeEditAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_MANAGE,
            ]);
        }

        /**
         * @var array|\ArrayObject[] $resultJson
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('user_id')) {
            $postData = $request->getPost();
            $userID   = $postData->get('user_id');

            // get Salary Scheme form
            $salarySchemeForm = $this->getSalarySchemeForm($userID);

            if ($postData->get('id')) {
                $id = $postData->get('id');
                /**
                 * @var \DDD\Dao\User\SalaryScheme $salarySchemeDao
                 */
                $salarySchemeDao = $this->getServiceLocator()->get('dao_user_salary_scheme');
                $salaryScheme    = $salarySchemeDao->getById($id);
                $salarySchemeForm->bind($salaryScheme);
            }

            $salarySchemeForm->prepare();

            if ($postData->get('id')) {
                $vars = [
                    'id'   => $salaryScheme->getId()
                ];
            }

            $vars['form']   = $salarySchemeForm;
            $vars['userId'] = $userID;

            $viewModel = new ViewModel();
            $viewModel->setTemplate('backoffice/user/edit-scheme');
            $viewModel->setTerminal(true);
            $viewModel->setVariables($vars);

            return $viewModel;
        }

        return new JsonModel($result);
    }

    /**
     * Save Salary Scheme data
     *
     * @return JsonModel
     */
    public function ajaxSaveSalarySchemeAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'message' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('userId')) {
            $postData = $request->getPost();
            $userID   = $postData->get('userId');

            try {
                $form        = $this->getSalarySchemeForm($userID);
                $inputFilter = new SalarySchemeFilter();

                $form->setInputFilter($inputFilter->getInputFilter());
                $form->setData($postData);
                $form->prepare();

                if ($form->isValid()) {
                    $validData = $form->getData();

                    $where = [];
                    $data  = [
                        'name'                => $validData['name'],
                        'external_account_id' => $validData['externalAccountId'],
                        'type'                => $validData['type'],
                        'pay_frequency_type'  => $validData['payFrequencyType'],
                        'salary'              => $validData['salary'],
                        'currency_id'         => $validData['currencyId'],
                        'effective_from'      => $validData['effectiveFrom'],
                        'effective_to'        => $validData['effectiveTo'],
                        'status'              => $validData['status']
                    ];

                    if ($postData->get('id')) {
                        $where['id']           = $postData->get('id');
                    } else {
                        $data['creation_date'] = date('Y-m-d H:i:s');
                        $data['status']        = UserService\SalaryScheme::SALARY_SCHEME_STATUS_ACTIVE;
                    }

                    /**
                     * @var \DDD\Dao\User\ExternalAccount $userAccountDao;
                     */
                    $salarySchemeDao = $this->getServiceLocator()->get('dao_user_salary_scheme');
                    $salarySchemeDao->save($data, $where);

                    Helper::setFlashMessage(["success" => "Successfully updated."]);

                    return new JsonModel([
                        "status"  => "success",
                        "message" => "Successfully updated.",
                        "url"     => $this->url()->fromRoute('backoffice/default', ['controller' => 'user', 'action' => 'edit', 'id' => $userID])
                    ]);
                } else {
                    foreach ($form->getMessages() as $messageId => $messages) {
                        $result['message'] = '';
                        foreach ($messages as $message) {
                            $result['message'] .= ' Validation failure ' . $messageId . ' : ' . $message;
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['message'] = $e->getMessage();
            }
        } else {
            $result['message'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     * Inactive Salary scheme
     *
     * @return array
     */
    public function ajaxSetSalarySchemeArchiveAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::USER_SALARY_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'message' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('userId')) {
            $postData       = $request->getPost();
            $salarySchemeId = $postData->get('id');
            $userId         = $postData->get('userId');
            $status         = $postData->get('status');

            if ($status == UserService\SalaryScheme::SALARY_SCHEME_STATUS_ARCHIVED || $status == UserService\SalaryScheme::SALARY_SCHEME_STATUS_INACTIVE) {
                if ($salarySchemeId) {
                    /**
                     * @var \DDD\Dao\User\SalaryScheme $salarySchemeDao
                     */
                    $salarySchemeDao = $this->getServiceLocator()->get('dao_user_salary_scheme');
                    $salarySchemeDao->save([
                        'status' => $status
                    ], [
                        'id' => $salarySchemeId
                    ]);

                    if ($status == UserService\SalaryScheme::SALARY_SCHEME_STATUS_ARCHIVED) {
                        Helper::setFlashMessage(["success" => "Successfully Archived."]);
                    } else if($status == UserService\SalaryScheme::SALARY_SCHEME_STATUS_INACTIVE) {
                        Helper::setFlashMessage(["success" => "Successfully Deactivated."]);
                    }

                    return new JsonModel([
                        "status"  => "success",
                        "message" => "Successfully updated.",
                        "url"     => $this->url()->fromRoute('backoffice/default', ['controller' => 'user', 'action' => 'edit', 'id' => $userId])
                    ]);
                }
            }
        }

        return new JsonModel($result);

    }

    /**
     * Get User Account Form
     *
     * @return bool|UserAccountForm
     */
    private function getUserAccountForm()
    {
        /**
         * @var \DDD\Dao\Geolocation\Countries $countriesDao
         */
        $countriesDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $countries    = $countriesDao->getCountriesListWithCities();

        return new UserAccountForm($name = 'user-account-form', $countries);
    }

    /**
     * Get Salary Scheme Form
     *
     * @param $userId
     * @return bool|SalarySchemeForm
     */
    private function getSalarySchemeForm($userId)
    {
        /**
         * @var \DDD\Service\User\ExternalAccount  $externalAccountService
         * @var \DDD\Service\Currency\Currency $currencyService
         */
        $externalAccountService = $this->getServiceLocator()->get('service_user_external_account');
        $currencyService        = $this->getServiceLocator()->get('service_currency_currency');

        /**
         * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
         */
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($userId, Account::TYPE_PEOPLE);

        $userAccounts = $externalAccountService->getActiveAccountsByTransactionAccountId($transactionAccountID);
        $currencies   = $currencyService->getSimpleCurrencyList();

        return new SalarySchemeForm($name = 'salary-scheme-form', $userAccounts, $currencies);
    }

    public function ajaxAddDeviceAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isPost() || !$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $request->getPost();

            /**
             * @var \DDD\Dao\User\Devices $userDeviceDao
             */
            $userDeviceDao = $this->getServiceLocator()->get('dao_user_devices');

            $result = $userDeviceDao->save([
                'user_id'       => $postData['user_id'],
                'hash'          => $postData['hash'],
                'date_added'    => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);

                $result = [
                    'status'    => 'success',
                    'msg'       => TextConstants::SUCCESS_ADD
                ];
            } else {
                $result = [
                    'status'    => 'error',
                    'msg'       => TextConstants::SERVER_ERROR
                ];
            }
        } catch (\Exception $e) {
            $this->gr2logException($e);

            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function ajaxUnlinkDeviceAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isPost() || !$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $request->getPost();


            /**
             * @var \DDD\Dao\User\Devices $userDeviceDao
             */
            $userDeviceDao = $this->getServiceLocator()->get('dao_user_devices');

            $result = $userDeviceDao->delete([
                'id' => $postData['device_id']
            ]);

            if ($result) {
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);

                $result = [
                    'status'    => 'success',
                    'msg'       => TextConstants::SUCCESS_DELETE
                ];
            } else {
                $result = [
                    'status'    => 'error',
                    'msg'       => TextConstants::SERVER_ERROR
                ];
            }
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
