<?php

namespace Lock\Controller;

use Lock\Controller\Base as LockBaseController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Lock\Form\LockForm;
use Lock\Form\LockFormEdit;
use Lock\Form\SearchLockForm;
use Lock\Form\InputFilter\LockFilter;
use Library\Constants\TextConstants;
use Library\Constants\Roles;
use Library\Utility\Helper;
use Library\Controller\ControllerBase;

/**
 * Class Document
 * @package Lock\Controller
 *
 * @author Hrayr Papikyan
 */
class General extends ControllerBase
{
	/**
	 * @return \Zend\Http\Response|ViewModel
	 */
    public function indexAction()
    {
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');

		if (!$auth->hasRole(Roles::ROLE_LOCK_MANAGEMENT)) {
			return $this->redirect()->toRoute('home');
		}
		$lockService  = $this->getServiceLocator()->get('service_lock_general');
		$allLockTypes = $lockService->getAllLockTypesForSelect();
		$allLockTypes[0]  = '-- All Types --';
		$searchForm   = new SearchLockForm($allLockTypes);
		$viewModel  = new ViewModel();
		$viewModel->setVariables(array(
			'search_form'      => $searchForm,
			'addNewLockUrl'    => $this->url()->fromRoute('lock/add')
		));
		$viewModel->setTemplate('lock/general/search-lock');
		return $viewModel;
	}

	/**
	 * @return JsonModel
	 */
	public function getLockJsonAction()
	{
		$requestParams = $this->params()->fromQuery();

		/**
		 * @var \DDD\Service\Lock\General $lockService
		 */
		$lockService   = $this->getServiceLocator()->get('service_lock_general');
		$locks         = $lockService->getLocksSearchResults($requestParams);

        $result = [];

        if(count($locks) > 0) {
			foreach($locks as $lock) {
				$description = $lock->getDescription();
				if ($description != '' && strlen($description) > 50) {
					$description = substr($description, 0, 50) . '...';
				}
				$apartment = ($lock->isUsefulForApartment()) ? '<span class="glyphicon glyphicon-ok"></span>' : '';
				$building  = ($lock->isUsefulForBuilding()) ? '<span class="glyphicon glyphicon-ok"></span>' : '';
				$parking   = ($lock->isUsefulForParking()) ? '<span class="glyphicon glyphicon-ok"></span>' : '';
				$editUrl = $this->url()->fromRoute('lock/edit', ['id' => $lock->getId()]);
				$editAction = '<a class="btn btn-xs btn-primary" href="' . $editUrl . '" data-html-content="Edit"></a>';
				array_push($result, [
					$lock->getName(),
					$description,
					$lock->getTypeName(),
					$apartment,
					$building,
					$parking,
					$editAction,
				]);
			}
		}

		return new JsonModel([
			"aaData" => $result
		]);

	}

	/**
	 * @return ViewModel
	 */
	public function editAction()
	{
		$lockService  = $this->getServiceLocator()->get('service_lock_general');
		$allLockTypes = $lockService->getAllLockTypesForSelect();
		$lockId       = $this->params("id", 0);
		$lockInfo     = $lockService->getLockInfo($lockId);

		if ($lockInfo === false) {
			return $this->redirect()->toRoute('lock');
		}

		$assignedLinks          = [];
		$apartmentService       = $this->getServiceLocator()->get('service_apartment_general');
		$apartmentsWithThisLock = $apartmentService->getAllApartmentsWithLock($lockId);
		if (count($apartmentsWithThisLock)) {
			foreach ($apartmentsWithThisLock as $apartment) {
				$apartmentUrlParams = ['apartment_id' => $apartment['id']];
				$assignedLinks[] = '<a href="'.$this->url()->fromRoute('apartment/general',            $apartmentUrlParams) . '"  name="general"  target="_blank">' . $apartment['name'] . ' (apartment)</a>';
			}
		}

		/** @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao */
		$buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $buildingsWithThisLock  = $buildingSectionsDao->getAllBuildingsSectionWithLock($lockId);
		if (count($buildingsWithThisLock)) {
			foreach ($buildingsWithThisLock as $building) {
				$assignedLinks[] = '<a href="/concierge/edit/' . $building['building_id'] . '" target="_blank">' . $building['building_section_name'] . ' (buiding)</a>';
			}
		}

		$parkingService       = $this->getServiceLocator()->get('service_parking_general');
		$parkingsWithThisLock = $parkingService->getAllParkingsWithLock($lockId);
		if (count($parkingsWithThisLock)) {
			foreach ($parkingsWithThisLock as $parking) {
				$assignedLinks[] = '<a href="/parking/' . $parking['id'] . '/general" target="_blank">' . $parking['name'] . ' (parking)</a>';
			}
		}

		$form = new LockFormEdit($allLockTypes,$lockInfo['settingsWithNames']);

		$form->get('submit')->setValue('Save Changes');

		$form->prepare();

		$form->populateValues($lockInfo['formData']);

		$viewModel  = new ViewModel();
		$viewModel->setVariables(array(
			'form'              => $form,
			'lockId'            => $lockId,
			'settingsWithNames' => $lockInfo['settingsWithNames'],
			'explanation'       => $lockInfo['formData']['explanation'],
			'pageTitle'         => 'Edit Locks',
			'assignedLinks'     => $assignedLinks,
		));
		$viewModel->setTemplate('lock/general/edit');
		return $viewModel;

	}

	/**
	 * @return ViewModel
	 */
	public function addAction()
	{
		/**
		 * @var \DDD\Service\Lock\General $lockService
		 */
		$lockService = $this->getServiceLocator()->get('service_lock_general');
		$allLockTypes = $lockService->getAllLockTypesForSelect();
		$allLockExplanations = $lockService->getAllLockTypeExplanations();
        $form = new LockForm($allLockTypes);
		$form->get('submit')->setValue('Add Lock');
		$inputFilter = new LockFilter();
		$form->setInputFilter($inputFilter->getInputFilter());
		$form->prepare();
		$viewModel  = new ViewModel();
		$viewModel->setVariables(array(
			'form'      => $form,
			'pageTitle' => 'Add Lock',
			'allLockExplanations' => $allLockExplanations
		));
		$viewModel->setTemplate('lock/general/add');
		return $viewModel;
	}

	/**
	 * @return JsonModel
	 */
	public function ajaxGetSettingsByTypeAction()
	{
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => TextConstants::SERVER_ERROR,
		];
		if ($request->isPost() && $request->isXmlHttpRequest()) {
           try {
			   $lockService = $this->getServiceLocator()->get('service_lock_general');
			   $lockTypeId = $request->getPost('type_id');
			   $htmlOfSettingsForType = $lockService ->getHtmlOfSettingsForType($lockTypeId);
			   $result = [
				   'status' => 'success',
				   'msg' => TextConstants::SUCCESS_UPDATE,
				   'generatedHtml' => $htmlOfSettingsForType
			   ];
		   } catch (\Exception $ex) {
			   $result['msg'] = $ex->getMessage();
		   }
		} else {
			$result['msg'] = TextConstants::ERROR_BAD_REQUEST;
		}

		return new JsonModel($result);
	}

	/**
	 * @return JsonModel
	 */
	public function ajaxSaveNewLockAction()
	{
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => TextConstants::SERVER_ERROR,
		];
		if ($request->isPost() && $request->isXmlHttpRequest()) {
			try {
				$lockService = $this->getServiceLocator()->get('service_lock_general');
                $data = $request->getPost();
				$lockService->saveNewLock($data);
				$result = [
					'status' => 'success',
					'msg' => TextConstants::SUCCESS_ADD,
				];
				Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
			} catch (\Exception $ex) {
				$result['msg'] = $ex->getMessage();
			}
		} else {
			$result['msg'] = TextConstants::ERROR_BAD_REQUEST;
		}

		return new JsonModel($result);
	}

	/**
	 * @return JsonModel
	 */
	public function ajaxEditLockAction()
	{
		$request = $this->getRequest();
		$result  = [
			'status' => 'error',
			'msg'    => TextConstants::SERVER_ERROR,
		];

		if ($request->isPost() && $request->isXmlHttpRequest()) {
			try {
				$lockService = $this->getServiceLocator()->get('service_lock_general');
				$data        = $request->getPost();

				if ($data['id'] && $data['is_physical']) {
                    $isDuplicatePhysicalLock = $lockService->checkDuplicatePhysicalLock(
                        '',
                        $data['id'],
                        false,
                        true
                    );

                    if ((int)$isDuplicatePhysicalLock['total'] > 1) {
                        return new JsonModel(
                            [
                                "status" => "error",
                                "msg"    => "This Lock can not be set as <b>physical</b> lock."
                            ]
                        );
                    }
                }

				$lockService->editLock($data);

				$result = [
					'status' => 'success',
					'msg'    => TextConstants::SUCCESS_UPDATE,
				];
			} catch (\Exception $ex) {
				$result['msg'] = $ex->getMessage();
			}
		} else {
			$result['msg'] = TextConstants::ERROR_BAD_REQUEST;
		}

		return new JsonModel($result);
	}

	/**
	 * @return JsonModel
	 */
	public function ajaxDeleteLockAction()
	{
		$request = $this->getRequest();
		$result = [
			'status' => 'error',
			'msg' => TextConstants::SERVER_ERROR,
		];
		if ($request->isPost() && $request->isXmlHttpRequest()) {
			try {
                /**
                 * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
                 */
                $lockId = $request->getPost('lock_id');

				$lockService = $this->getServiceLocator()->get('service_lock_general');
				$apartmentService = $this->getServiceLocator()->get('service_apartment_general');
                $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
                $parkingService = $this->getServiceLocator()->get('service_parking_general');

                $apartmentsWithThisLock = $apartmentService->getAllApartmentsWithLock($lockId);
                $buildingsWithThisLock  = $buildingSectionsDao->getAllBuildingsSectionWithLock($lockId);
				$parkingsWithThisLock = $parkingService->getAllParkingsWithLock($lockId);
				if (count($apartmentsWithThisLock) || count($buildingsWithThisLock) || count($parkingsWithThisLock)) {
					return new JsonModel(
						[
							'status' => 'error',
							'msg' => 'The lock is already assigned to an apartment, building, or parking'
						]
					);
				} else {
					$lockService->deleteLock($lockId);
					$result = [
						'status' => 'success',
						'msg' => TextConstants::SUCCESS_DELETE,
					];
					Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
				}


			} catch (\Exception $ex) {
				$result['msg'] = $ex->getMessage();
			}
		} else {
			$result['msg'] = TextConstants::ERROR_BAD_REQUEST;
		}

		return new JsonModel($result);
	}



}
