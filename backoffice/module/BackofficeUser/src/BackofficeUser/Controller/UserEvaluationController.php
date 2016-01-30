<?php

namespace BackofficeUser\Controller;

use BackofficeUser\Form\EditEvaluationForm;

use DDD\Dao\ActionLogs\ActionLogs;
use DDD\Dao\User\UserManager as UserDAO;
use DDD\Domain\User\Evaluation\EvaluationExtended;
use DDD\Domain\User\Evaluation\EvaluationValues;
use DDD\Service\User\Evaluations as EvaluationService;
use DDD\Service\User\Main as UserMainService;

use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\Constants;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Class UserEvaluationController
 * @package BackofficeUser\Controller
 *
 * @author Tigran Petrosyan
 */
class UserEvaluationController extends ControllerBase
{
    private $userId = NULL;

    public function indexAction()
    {

    }

    public function editPlannedEvaluationAction()
    {
        /**
         * @var EvaluationService $evaluationService
         */
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $editPlannedEvaluationForm = new EditEvaluationForm();

        $userId = $this->params()->fromRoute('user_id', 0);
        $evaluationId = $this->params()->fromRoute('evaluation_id', 0);

        $evaluation = $evaluationService->getEvaluationDataFull($evaluationId);

        if (!$evaluation) {
            Helper::setFlashMessage([
                'error' => 'Cannot retrieve evaluation with given ID'
            ]);

            $redirectToUrl = $this->url()->fromRoute('backoffice/default',
                [
                    'controller' => 'user',
                    'action' => 'edit',
                    'id' => $userId
                ], [], false
            );

            $redirectToUrl .= '#evaluations';

            return $this->redirect()->toUrl($redirectToUrl);
        }

        $editPlannedEvaluationForm->populateValues([
            'creator_id' => $evaluation->getCreatorId(),
            'user_id' => $userId,
            'evaluation_id' => $evaluationId,
            'evaluation_description' => $evaluation->getDescription()
        ]);

        return new ViewModel([
            'userId' => $userId,
            'evaluationId' => $evaluationId,
            'evaluation' => $evaluation,
            'editPlannedEvaluationForm' => $editPlannedEvaluationForm,
            'evaluationItems' => $evaluationService->getEvaluationItemsArray()
        ]);
    }

    public function ajaxSavePlannedEvaluationAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $request = $this->getRequest();
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            /**
             * @var UserMainService $userMainService
             */
            $userMainService = $this->getServiceLocator()->get('service_user_main');

            $isManager = ($userMainService->getUserManagerId($request->getPost('user_id')) == $authenticationService->getIdentity()->id);

            if ($request->isXmlHttpRequest()) {
                if (!$authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$authenticationService->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER) && !$isManager) {
                    $result = [
                        'status' => 'error',
                        'msg' => 'You have not permitted to do certain action.',
                    ];
                } else {
                    $data = [
                        'description' => $request->getPost('description'),
                        'creator_id' => $request->getPost('creator_id'),
                        'user_id' => $request->getPost('user_id'),
                        'evaluation_id' => $request->getPost('evaluation_id'),
                    ];

                    $counter = 0;
                    $sum     = 0;

                    for ($i = 1; $request->getPost('evaluation_item_' . $i) !== null; $i++) {
                        $data['evaluation_items'][$i] = $request->getPost('evaluation_item_' . $i);

                        if ($request->getPost('evaluation_item_' . $i) != -1) {
                            $counter++;
                            $sum += $request->getPost('evaluation_item_' . $i);
                        }
                    }

                    $data['average'] = round($counter ? $sum / $counter : 0, 4);


                    if (empty($data['description'])) {
                        $result = [
                            'status' => 'error',
                            'msg'    => TextConstants::USER_EVALUATION_EMPTY_DESCRIPTION,
                        ];
                    } else {
                        /**
                         * @var EvaluationService $evaluationService
                         */
                        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');

                        if ($evaluationService->savePlannedEvaluation($data['evaluation_id'], $data)) {
                            $result = [
                                'status' => 'success',
                                'msg'    => TextConstants::USER_EVALUATION_CREATED,
                            ];

                            Helper::setFlashMessage(['success' => $result['msg']]);

                        } else {
                            $result = [
                                'status' => 'error',
                                'msg'    => 'Problem during adding evaluation.',
                            ];
                        }
                    }
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

    public function ajaxPlanEvaluationAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $request = $this->getRequest();
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            /**
             * @var UserMainService $userMainService
             */
            $userMainService = $this->getServiceLocator()->get('service_user_main');

            $isManager = ($userMainService->getUserManagerId($request->getPost('user_id')) == $authenticationService->getIdentity()->id);

            if ($request->isXmlHttpRequest()) {
                if (!$authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$authenticationService->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER) && !$isManager) {
                    $result = [
                        'status' => 'error',
                        'msg' => 'You have not permitted to do certain action.',
                    ];
                } else {
                    $data = [
                        'description' => $request->getPost('evaluation_description'),
                        'creator_id' => $request->getPost('creator_id'),
                        'user_id' => $request->getPost('user_id'),
                        'date' => date('Y-m-j', strtotime($request->getPost('date'))),
                    ];

                    if (empty($data['description'])) {
                        $result = [
                            'status' => 'error',
                            'msg'    => TextConstants::USER_EVALUATION_EMPTY_DESCRIPTION,
                        ];
                    } else {
                        /**
                         * @var EvaluationService $evaluationService
                         */
                        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');

                        if ($evaluationService->manualPlanEvaluation($data['creator_id'], $data['user_id'], $data['date'], $data['description'])) {
                            $result = [
                                'status' => 'success',
                                'msg'    => 'Evaluation planned successfully',
                            ];

                        } else {
                            $result = [
                                'status' => 'error',
                                'msg'    => 'Problem during adding evaluation.',
                            ];
                        }
                    }
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

    public function addAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $request = $this->getRequest();
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            /**
             * @var UserMainService $userMainService
             */
            $userMainService = $this->getServiceLocator()->get('service_user_main');

            $isManager = ($userMainService->getUserManagerId($request->getPost('user_id')) == $auth->getIdentity()->id);

            if ($request->isXmlHttpRequest()) {
                if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER) && !$isManager) {
                    $result = [
                        'status' => 'error',
                        'msg' => TextConstants::USER_EVALUATION_CANNOT_BE_CREATE,
                    ];
                } else {
                    $data = [
                        'user_id'      => $request->getPost('user_id'),
                        'creator_id'   => $request->getPost('creator_id'),
                        'type_id'      => $request->getPost('type_id'),
                        'description' => $request->getPost('description'),
                    ];

                    if ($data['type_id'] == EvaluationService::USER_EVALUATION_TYPE_EVALUATION) {
                        $counter = 0;
                        $sum     = 0;

                        for ($i = 1; $request->getPost('evaluation_item_' . $i) !== null; $i++) {
                            $data['evaluation_items'][$i] = $request->getPost('evaluation_item_' . $i);

                            if ($request->getPost('evaluation_item_' . $i) != -1) {
                                $counter++;
                                $sum += $request->getPost('evaluation_item_' . $i);
                            }
                        }

                        $data['average'] = round($counter ? $sum / $counter : 0, 4);
                    } else {
                        $data['evaluation_items'] = NULL;
                    }

                    if (empty($data['description'])) {
                        $result = [
                            'status' => 'error',
                            'msg'    => TextConstants::USER_EVALUATION_EMPTY_DESCRIPTION,
                        ];
                    } else {
                        /**
                         * @var EvaluationService $evaluationService
                         */
                        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');

                        if ($evaluationService->addEvaluation($data)) {
                            switch ($data['type_id']) {
                                case EvaluationService::USER_EVALUATION_TYPE_COMMENT:
                                    $message = TextConstants::USER_EVALUATION_COMMENT_CREATED;
                                    break;
                                case EvaluationService::USER_EVALUATION_TYPE_WARNING:
                                    $message = TextConstants::USER_EVALUATION_WARNING_CREATED;
                                    break;
                                case EvaluationService::USER_EVALUATION_TYPE_EVALUATION:
                                    $message = TextConstants::USER_EVALUATION_CREATED;
                                    break;
                            }

                            $result = [
                                'status' => 'success',
                                'msg'    => $message,
                            ];
                        } else {
                            $result = [
                                'status' => 'error',
                                'msg'    => 'Problem during adding evaluation.',
                            ];
                        }
                    }
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

    public function ajaxGetUserEvaluationsAction()
    {
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                $requestParams = $this->params()->fromQuery();
                $this->userId = (int)$requestParams['userId'];

                /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
                $auth = $this->getServiceLocator()->get('library_backoffice_auth');

                /* @var $userMainService \DDD\Service\User\Main */
                $userMainService = $this->getServiceLocator()->get('service_user_main');

                $hasUserManagement              = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT);
                $hasGlobalEvaluationManagement  = $auth->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER);

                $isManager = ($userMainService->getUserManagerId($this->userId) == $auth->getIdentity()->id);

                $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
                $evaluationsList = $evaluationService->getUserEvaluationsList($this->userId);

                $resultData = [];

                if ($evaluationsList && $evaluationsList->count()) {
                    foreach ($evaluationsList as $evaluation) {
                        $statusLabel = '<span class="label %s">'
                            . EvaluationService::getEvaluationStatusOptions()[$evaluation->getStatus()]
                            . '</span>';

                        switch ($evaluation->getStatus())
                        {
                            case EvaluationService::USER_EVALUATION_STATUS_PLANNED:
                                $statusLabel = sprintf($statusLabel, 'label-warning');
                                break;
                            case EvaluationService::USER_EVALUATION_STATUS_DONE:
                                $statusLabel = sprintf($statusLabel, 'label-success');
                                break;
                            case EvaluationService::USER_EVALUATION_STATUS_CANCELLED:
                                $statusLabel = sprintf($statusLabel, 'label-default');
                                break;
                        }

                        $removeUrl = '//' . \Library\Constants\DomainConstants::BO_DOMAIN_NAME. $this->url()->fromRoute('evaluation/delete',[
                            'user_id' => $evaluation->getUserId(),
                            'evaluation_id' => $evaluation->getId()
                        ]);

                        $printUrl = '//' . \Library\Constants\DomainConstants::BO_DOMAIN_NAME . $this->url()->fromRoute('evaluation/print', [
                            'evaluation_id' => $evaluation->getId()
                        ]) . '#print';

                        $viewUrl = '//' . \Library\Constants\DomainConstants::BO_DOMAIN_NAME . $this->url()->fromRoute('evaluation/view', [
                            'evaluation_id' => $evaluation->getId()
                        ]);

                        $cancelUrl = '//' . \Library\Constants\DomainConstants::BO_DOMAIN_NAME . $this->url()->fromRoute('evaluation/cancel', [
                            'evaluation_id' => $evaluation->getId()
                        ]);

                        $evaluateUrl = '//' . \Library\Constants\DomainConstants::BO_DOMAIN_NAME . $this->url()->fromRoute('evaluation/edit',[
                            'user_id' => $evaluation->getUserId(),
                            'evaluation_id' => $evaluation->getId()
                        ]);

                        $cancelButton = ($evaluation->getStatus() == EvaluationService::USER_EVALUATION_STATUS_PLANNED) ?
                            '<li><a href="#" onclick="cancelPlanEvaluation(this)" data-url="' . $cancelUrl . '" class="evaluation-cancel">Cancel</a></li>' : '';

                        if ( $hasGlobalEvaluationManagement || $auth->getIdentity()->id == $evaluation->getCreatorId() )
                         {
                            $removeButton = '<li><a href="#" onclick="removeEvaluation(this)" data-url="' . $removeUrl . '" class="evaluation-remove">Remove</a></li>';
                        } else {
                            $removeButton = '';
                        }

                        if ($evaluation->getStatus() == EvaluationService::USER_EVALUATION_STATUS_PLANNED) {
                            $evaluateButton = '<li><a href="' . $evaluateUrl . '" class="evaluation-edit">Evaluate</a></li>';
                        } else {
                            $evaluateButton = '';
                        }

                        $actionButtons = '
                            <div class="btn-group">
                                <a href="' . $viewUrl . '" class="btn btn-xs btn-primary btn-default">View</a>
                                <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">
                                    <span class="caret"></span>
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="' . $printUrl . '" target="_blank">Print</a></li>
                        ' . $evaluateButton . $cancelButton . $removeButton;

                        $evaluationDescription = $evaluation->getDescription();
                        $evaluationDescription = strip_tags($evaluationDescription);

                        $dots = '';
                        if (strlen($evaluationDescription) > 80) {
                            $dots = '...';
                        }

                        $evaluationDescription = mb_substr($evaluationDescription, 0, 80, 'utf-8');
                        $evaluationDescription .= $dots;

                        $averageScore = $evaluation->getTypeId() == EvaluationService::USER_EVALUATION_TYPE_EVALUATION ? $evaluation->getAverageScore() : '';

                        if ($evaluation->getTypeId() != 3) {
                            $actionButtons .= '
                                <li>
                                    <a  href="#"
                                        data-message = "' . $evaluationDescription . '"
                                        data-eval_id="' . $evaluation->getId() . '"
                                        data-type = "' . $evaluation->getTypeId() . '"
                                        data-owner_id = "' . $this->userId . '"
                                        class="evaluation-inform-ud"
                                        onclick="informEmployee(this)"
                                    >Send to Employee</a>
                                </li>';
                        }

                        $actionButtons .= '</ul></div>';


                        $resultData[] = [
                            $statusLabel,
                            date(Constants::GLOBAL_DATE_FORMAT, strtotime($evaluation->getDateCreated())),
                            $evaluation->getTypeTitle(),
                            $evaluation->getCreatorFullName(),
                            '<div class="text-center">' . $averageScore . '</div>',
                            $evaluationDescription,
                            $actionButtons,
                        ];
                    }
                }

                $result = [
                    'status' => 'success',
                    'aaData' => $resultData
                ];
            }

        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'result' => null
            ];
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function deleteAction()
    {
        /**
         * @var UserMainService $userMainService
         * @var EvaluationService $evaluationService
         * @var BackofficeAuthenticationService $authenticationService
         * @var UserDAO $userDao
         */
        $userMainService = $this->getServiceLocator()->get('service_user_main');
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $request           = $this->getRequest();
        $employeeId        = $this->params()->fromRoute('user_id', false);
        $evaluationId      = $this->params()->fromRoute('evaluation_id', false);
        $evaluationsDomain = $evaluationService->getEvaluationDataFull($evaluationId);

        try {
            if ($request->isXmlHttpRequest()) {
                if (
                    (!$authenticationService->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER))
                    && $authenticationService->getIdentity()->id != $evaluationsDomain->getCreatorId()
                ) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::USER_EVALUATION_CANNOT_BE_DELETE
                    ];
                } else {
                    if (is_numeric($evaluationId) && $evaluationId > 0) {
                        $commentType = $evaluationsDomain->getTypeTitle();
                        $return = $evaluationService->deleteEvaluation($evaluationId);

                        if ($return === false) {
                            $result = [
                                'status' => 'error',
                                'msg'    => TextConstants::SERVER_ERROR
                            ];
                        } else {
                            $result = [
                                'status' => 'success',
                                'msg'    => TextConstants::SUCCESS_DELETE
                            ];

                            $logger->save(
                                Logger::MODULE_USER,
                                $employeeId,
                                Logger::ACTION_COMMENT,
                                '<b>' . '</b> has removed the evaluation with type: ' .
                                '<b>' . $commentType . '</b> On: <b>' . date(Constants::GLOBAL_DATE_FORMAT) . '</b>.'
                            );

                        }
                    } else {
                        $result = [
                            'status' => 'error',
                            'msg'    => TextConstants::USER_EVALUATION_NOT_FOUND
                        ];
                    }
                }
            } else {
                $result = [
                    'status' => 'error',
                    'msg'    => TextConstants::AJAX_NO_POST_ERROR
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

    /**
     * @return JsonModel
     */
    public function cancelAction()
    {
        /**
         * @var EvaluationService $evaluationService
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
         */
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        $evaluationId = (int)$this->params()->fromRoute('evaluation_id', 0);

        $result = [];

        if ($evaluationId) {
            /**
             * @var EvaluationExtended $evaluationDomain
             */
            $evaluationDomain = $evaluationDao->fetchOne(
                ['id' => $evaluationId],
                [
                    'type_id',
                    'status'
                ]
            );

            if (
                $evaluationDomain->getTypeId() == EvaluationService::USER_EVALUATION_TYPE_EVALUATION
                &&
                $evaluationDomain->getStatus() == EvaluationService::USER_EVALUATION_STATUS_PLANNED
            ) {
                $cancelResult = $evaluationService->cancelEvaluation($evaluationId);

                if ($cancelResult) {
                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::SUCCESS_CANCELLED
                    ];

                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_CANCELLED]);
                } else {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::USER_EVALUATION_CANNOT_CANCELLED
                    ];
                }
            } else {
                $result = [
                    'status' => 'error',
                    'msg'    => 'Only evaluations with "Evaluation" type and "Planned" status can be cancelled.'
                ];
            }
        } else {
            $result = [
                'status' => 'error',
                'msg'    => TextConstants::USER_EVALUATION_NOT_FOUND
            ];
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function resolveAction()
    {
        /**
         * @var EvaluationService $evaluationService
         * @var \DDD\Dao\User\Evaluation\Evaluations $evaluationDao
         */
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $evaluationDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        $evaluationId = (int)$this->params()->fromRoute('evaluation_id', 0);

        if ($evaluationId) {
            /**
             * @var EvaluationExtended $evaluationDomain
             */
            $evaluationDomain = $evaluationDao->fetchOne(
                ['id' => $evaluationId],
                [
                    'type_id',
                    'status'
                ]
            );

            if (
                $evaluationDomain->getTypeId() == EvaluationService::USER_EVALUATION_TYPE_EVALUATION
                &&
                $evaluationDomain->getStatus() == EvaluationService::USER_EVALUATION_STATUS_DONE
            ) {
                $evaluationService->resolveEvaluation($evaluationId);

                $result = [
                    'status' => 'success',
                    'msg'    => TextConstants::SUCCESS_RESOLVED
                ];
            } else {
                $result = [
                    'status' => 'error',
                    'msg'    => 'Only evaluations with "Evaluation" type and "Done" status can be resolved.'
                ];
            }
        } else {
            $result = [
                'status' => 'error',
                'msg'    => TextConstants::USER_EVALUATION_NOT_FOUND
            ];
        }

        return new JsonModel($result);
    }

    public function viewAction()
    {
        /**
         * @var UserMainService $userMainService
         * @var EvaluationService $evaluationService
         * @var BackofficeAuthenticationService $authenticationService
         */
        $userMainService        = $this->getServiceLocator()->get('service_user_main');
        $evaluationService      = $this->getServiceLocator()->get('service_user_evaluations');
        $authenticationService  = $this->getServiceLocator()->get('library_backoffice_auth');

        $evaluationTicketId     = (int)$this->params()->fromRoute('evaluation_id', 0);
        $evaluationDomain       = $evaluationService->getEvaluationDataFull($evaluationTicketId);
        $evaluationValuesDomain = [];

        $error = false;

        if ($evaluationTicketId && $evaluationDomain) {
            $isManager = ($userMainService->getUserManagerId($evaluationDomain->getUserId()) == $authenticationService->getIdentity()->id);

            if ($authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) || $authenticationService->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER) || $isManager) {
                if (!$evaluationDomain) {
                    $error = 'Evaluation with given Id not found.';
                } else {
                    if ($evaluationDomain->getTypeId() == EvaluationService::USER_EVALUATION_TYPE_EVALUATION) {
                        $evaluationValuesDomain = $evaluationService->getEvaluationValuesFull($evaluationTicketId);
                    }
                }
            } else {
                $error = 'Permission denied!';
            }
        } else {
            $error = 'Wrong Evaluation Id.';
        }

        return new ViewModel([
            'error' => $error,
            'data' => isset($evaluationDomain) ? $evaluationDomain : false,
            'values' => $evaluationValuesDomain,
        ]);
    }

    public function printAction()
    {
        /**
         * @var UserMainService $userMainService
         * @var EvaluationService $evaluationService
         * @var BackofficeAuthenticationService $authenticationService
         */
        $userMainService = $this->getServiceLocator()->get('service_user_main');
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        $evaluationTicketId = (int)$this->params()->fromRoute('evaluation_id', 0);
        $evaluationsDomain = $evaluationService->getEvaluationDataFull($evaluationTicketId);

        $isManager = ($userMainService->getUserManagerId($evaluationsDomain->getUserId()) == $authenticationService->getIdentity()->id);
        $error = false;

        if ($evaluationTicketId) {
            if ($authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) || $authenticationService->hasRole(Roles::ROLE_GLOBAL_EVALUATION_MANAGER) || $isManager) {
                if (!$evaluationsDomain) {
                    $error = 'Evaluation with given Id not found.';
                } else {
                    if ($evaluationsDomain->getTypeId() == EvaluationService::USER_EVALUATION_TYPE_EVALUATION) {
                        $evaluationValuesDomain = $evaluationService->getEvaluationValuesFull($evaluationTicketId);
                    }
                }
            } else {
                $error = 'Permission denied!';
            }
        } else {
            $error = 'Wrong Evaluation Id.';
        }

        return new ViewModel([
            'error' => $error,
            'data' => isset($evaluationsDomain) ? $evaluationsDomain : false,
            'values' => isset($evaluationValuesDomain) ? $evaluationValuesDomain : new \ArrayObject(),
        ]);
    }
}
