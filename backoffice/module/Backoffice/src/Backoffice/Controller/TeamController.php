<?php

namespace Backoffice\Controller;

use Library\Utility\Debug;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Library\Constants\Constants;
use Library\Constants\TextConstants;
use Library\Constants\Objects;
use Library\Controller\ControllerBase;
use Backoffice\Form\Team as TeamForm;
use Backoffice\Form\InputFilter\TeamFilter;
use Library\Validator\ClassicValidator;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Zend\View\Model\ViewModel;

class TeamController extends ControllerBase
{
    /**
     * @var $_team \DDD\Service\Team\Team
     */
    private $_team;

	public function indexAction()
    {
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_TEAM)) {
            // No Permission, redirect to UD
        }

        $global = false;
		if ($auth->hasRole(Roles::ROLE_TEAM_MANAGER)) {
            $global = true;
        }

		return new ViewModel([
            'global'        => $global,
            'ajaxSourceUrl' => '/team/get-json'
		]);
    }

    public function getJsonAction()
    {
        $request     = $this->params();
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $router      = $this->getServiceLocator()->get('router');

        if (!$auth->hasRole(Roles::ROLE_TEAM)) {
            // No Permission, redirect to UD
        }

        $results = [];
        $global  = false;

        if ($auth->hasRole(Roles::ROLE_TEAM_MANAGER)) {
            $global = true;
        }

        $teamsList = $this->getTeam()->getTeamListDetails(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $teamListCount = $this->getTeam()->getTeamListCount(
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        /**
         * @var $teamRow \DDD\Domain\Team\PeopleTeamsTableRow
         */
        foreach ($teamsList as $teamRow) {

            $editable = ($global || $this->getTeam()->isTeamManagerOrDirector($teamRow->getId(), $auth->getIdentity()->id)) ? 1 : 0;

            if ($editable) {
                $editLink = $router->assemble(
                    [
                        'controller' => 'team',
                        'action'     => 'edit',
                        'id'         => $teamRow->getId()
                    ],
                    ['name' => 'backoffice/default']
                );
                $edit =  '<a href="'.$editLink .'" class="btn btn-xs btn-primary" data-html-content="Edit"></a>';
            } else {
                $edit = '';
            }

            $status = !$teamRow->getIsActive() ?
                '<span class="label label-success">Active</span>' :
                '<span class="label label-default">Inactive</span>';

            $results[] = [
                $status,
                $teamRow->getName(),
                $teamRow->getDescription(),
                $teamRow->getSize(),
                $teamRow->getUsageDepartment(),
                $teamRow->getUsageNotifiable(),
                $teamRow->getUsageFrontier(),
                $teamRow->getUsageSecurity(),
                $teamRow->getUsageTaskable(),
                $teamRow->getUsageProcurement(),
                $teamRow->getUsageHiring(),
                $teamRow->getUsageStorage(),
                $edit
            ];
        }

        if(!isset($results))
            $result[] = [' ', '', '', '', '', '', '', '', '', ''];

        $resultArray = [
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $teamListCount,
            'iTotalDisplayRecords' => $teamListCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (int)$request->fromQuery('iDisplayLength'),
            'aaData'               => $results,
        ];

        return new JsonModel($resultArray);
    }

    public function editAction()
    {
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService = $this->getServiceLocator()->get('service_user');
        $taskTypeDao = $this->getServiceLocator()->get('dao_task_type');

        $global   = false;
		$id       = (int)$this->params()->fromRoute('id', 0);
        if ($id == 0 || $auth->hasRole(Roles::ROLE_TEAM_MANAGER)) {
            $global = true;
        }
        if (!$global && !$this->getTeam()->isTeamManagerOrDirector($id, $auth->getIdentity()->id)) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'team']);
        }

        $isDirector   = false;
        $isPermanent  = 0;
        $status       = 0;
        $isDepartment = 0;

        if ($id > 0 ) {
            /**
             * @var $teamInfo \DDD\Domain\Team\Team
             */
            $teamInfo = $this->getTeam()->getTeamDao()->getTeamBasicInfo($id);
            if (!$teamInfo) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('backoffice/default', ['controller' => 'team']);
            }

            $directorId = $teamInfo->getTeamDirectorId();

            if ($auth->getIdentity()->id == $directorId) {
                $isDirector = true;
            }

            $isDepartment = $teamInfo->getIsDepartment();
            $isPermanent  = $teamInfo->isPermanent();
            $status       = $teamInfo->getIsDisabled();
        }
        $accommodationDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $apartmentsThatAreAlreadyAssignedToAnotherTeam = $accommodationDao->getApartmentsListForTeam($id);
        $apartmentsThatAreAlreadyAssignedToAnotherTeamArray = [];
        foreach ($apartmentsThatAreAlreadyAssignedToAnotherTeam as $apartment) {
            $apartmentsThatAreAlreadyAssignedToAnotherTeamArray[$apartment['id']] = $apartment['team_name'];
        }

        $form = $this->getForm($id, $global, $isDirector, $apartmentsThatAreAlreadyAssignedToAnotherTeamArray);

        if (!$form) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'team']);
        }

        $ginosiks         = $userService->getUsersList();
        $taskTypeInfo     = $taskTypeDao->fetchOne(['associated_team_id' => $id]);
        $isAssociatedTeam = 0;
        $taskName         = null;

        if ($taskTypeInfo) {
            $isAssociatedTeam = 1;
            $taskName         = $taskTypeInfo->getName();
        }

		return new ViewModel(
            [
                'userId'           => $auth->getIdentity()->id,
                'teamForm'         => $form,
                'id'               => $id,
                'global'           => $global,
                'memberList'       => $ginosiks,
                'isDepartment'     => $isDepartment,
                'isPermanent'      => $isPermanent,
                'isDirector'       => $isDirector,
                'status'           => $status,
                'isAssociatedTeam' => $isAssociatedTeam,
                'taskName'         => $taskName,
                'apartmentsThatAreAlreadyAssignedToAnotherTeam' => $apartmentsThatAreAlreadyAssignedToAnotherTeamArray,
		    ]
        );
    }

    public function getForm($id, $global, $isDirector, $alreadyAttachedApartments = [])
    {
        $previousData = null;

        if ($id > 0) {
            $previousData = $this->getTeam()->getData($id);
            if (!$previousData && !$global) {
                return false;
            }

		}

        $ginosiksOption = $this->getTeam()->getTeamOptions($previousData);

		return new TeamForm(
            'ginosiks_team',
            $previousData,
            $ginosiksOption,
            $global,
            $isDirector,
            $alreadyAttachedApartments
        );

    }

	public function ajaxsaveAction()
    {
        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $id   = (int)$request->getPost('team_id');

            $global   = false;
            $isDirector = false;
            if ($auth->hasRole(Roles::ROLE_TEAM_MANAGER)) {
                $global = true;
            }

            if (!$global && $id) {
                $director = $this->getTeam()->getTeamDirector($id);

                if ($director) {
                    $isDirector = ($auth->getIdentity()->id == $director->getUserId());
                }
            }

            $result = [
                'result' => [],
                'id'     => $id,
                'status' => 'success',
                'msg'    => TextConstants::SUCCESS_UPDATE,
            ];


            $form     = $this->getForm($id, $global, $isDirector);
            $messages = '';
            $data = $request->getPost();

            $form->setInputFilter(new TeamFilter($global, $data['usage_frontier']));
            $filter = $form->getInputFilter();
            $form->setInputFilter($filter);

            $form->setData($data);
            $name = strip_tags(trim($request->getPost('name')));
            if ($global) {
                if ($name != '') {
                    if ($this->getTeam()->checkName($name, $id)) {
                        $messages = 'Team name is already in use<br>';
                    }
                }
            }
            if ($form->isValid() && $messages == '') {
                $data = (array)$data;

                if ($result['status'] != 'error') {

                    $responseDb = $this->getTeam()->teamSave($data, $id, $global, $isDirector);

                    if ($responseDb['id'] > 0) {
                        if (!$id) {
                            $result['id']  = $responseDb['id'];
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                        } else {
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                        }

                        if ($responseDb['diffTimezone']) {
                            Helper::setFlashMessage(['warning' => "Apartments: <b>" . $responseDb['diffTimezone'] . "</b> are in different timezones from the frontier ones."]);
                        }
                    } else {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::SERVER_ERROR;
                    }
                }
            } else {
                $errors = $form->getMessages();

                foreach ($errors as $key => $row) {
                    if (!empty($row)) {
                        $messages     .= ucfirst($key) . ' ';
                        $messages_sub = '';

                        foreach ($row as $rower) {
                            $messages_sub .= $rower;
                        }

                        $messages .= $messages_sub . '<br>';
                    }
                }

                $result['status'] = 'error';
                $result['msg']    = $messages;
            }
        }

		return new JsonModel($result);
	}

	public function ajaxChangeActiveStatusAction()
	{
		$result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

        $teamDao = $this->getServiceLocator()->get('dao_team_team');
        $request = $this->getRequest();

		try{
			if($request->isXmlHttpRequest()) {
				$id = (int)$request->getPost('id');
                $teamData = $teamDao->fetchOne(['id' => $id]);

                if (!(int)$teamData->getIsDisabled() && $teamData->isPermanent()) {
                    $result['msg']    = TextConstants::CANNOT_DEACTIVATE_PERMANENT_TEAM;
                    $result['status'] = 'error';
                } else {

                    if ((int)$teamData->getIsDisabled()) {
                        $teamDao->save(['is_disable' => 0], ['id' => $id]);
                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ACTIVATE]);
                    } else {
                        $teamDao->save(['is_disable' => 1], ['id' => $id]);
                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DEACTIVATE]);
                    }
                }

			}
		} catch (\Exception $e) {

            $result['msg'] = TextConstants::SERVER_ERROR;
			$result['status'] = 'error';
		}

		return new JsonModel($result);
	}

	public function ajaxchecknameAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		$response->setStatusCode(200);
		try{
			if($request->isXmlHttpRequest()) {
				$name = strip_tags(trim($request->getPost('name')));
				$id   = (int)$request->getPost('id');
                $this->getTeam();

				if(!$this->getTeam()->checkName($name, $id)){
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

    /**
     * @return \DDD\Service\Team\Team
     */
    public function getTeam()
    {
		if (!($this->_team)) {
			$this->_team = $this->getServiceLocator()->get('service_team_team');
		}

		return $this->_team;
	}

}
