<?php

namespace Backoffice\Controller;

use DDD\Service\Office;
use FileManager\Constant\DirectoryStructure;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\DomainConstants;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Utility\CsvGenerator;
use Library\Constants\Constants;
use Library\Constants\Roles;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use Backoffice\Form\Office as OfficeForm;
use Backoffice\Form\InputFilter\OfficeFilter as OfficeFilter;

use DDD\Service\Location as LocationService;

class OfficeController extends ControllerBase
{
    /**
     * @var Office $_office
     */
    private $_office;

	public function indexAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $global = false;

		if ($auth->hasRole(Roles::ROLE_OFFICE_MANAGER)) {
			$global = true;
        }

        return new ViewModel([
            'ajaxSourceUrl' => '/office/get-json',
            'global' => $global,
        ]);
    }

    public function getJsonAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $request = $this->params();
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $officeService = $this->getOfficeService();
        $global = false;

		if ($auth->hasRole(Roles::ROLE_OFFICE_MANAGER)) {
			$global = true;
        }

        $officeLists = $officeService->getOfficeListDetail(
            $request->fromQuery('iDisplayStart'),
            $request->fromQuery('iDisplayLength'),
            $request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $officeCount = $this->_office->officeCount(
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $results = [];

        foreach ($officeLists as $officeList) {
            $staffCount = $this->_office->getOfficeStaffCount($officeList->getId());

            if ($global) {
                $action = '<a href="/office/edit/' . $officeList->getId() . '" class="btn btn-xs btn-primary" data-html-content="Edit"></a>';
            } else {
                $action = '';
            }

            $status = $officeList->getDisable()
                ? '<span class="label label-default">Inactive</span>'
                : '<span class="label label-success">Active</span>';

            array_push($results, [
                $status,
                $officeList->getName(),
                $officeList->getCity() . ', ' . $officeList->getCountry(),
                $officeList->getAddress(),
                $officeList->getPhone(),
                $staffCount,
                $action,
            ]);
        }

        if (!isset($results)) {
            array_push($result, ['', '', '', '', '', '', '', '', '']);
        }

        return new JsonModel([
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $officeCount,
            'iTotalDisplayRecords' => $officeCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => $request->fromQuery('iDisplayLength'),
            'aaData'               => $results,
        ]);
    }

    public function editAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $officeSecDao = $this->getServiceLocator()->get('dao_office_office_section');
        /** @var \DDD\Dao\Office\OfficeManager $officeManagerDao */
        $officeManagerDao = $this->getServiceLocator()->get('dao_office_office_manager');
        $userService = $this->getServiceLocator()->get('service_user');

		$id       = (int)$this->params()->fromRoute('id', 0);
		$form     = $this->getForm($id);

        if (!$form && $id) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'office']);
        }

        $ginosiks = $userService->getUsersList();

		if (!$auth->hasRole(Roles::ROLE_OFFICE_MANAGER)) {
            return $this->redirect()->toUrl('/');
        }

        $officeStatus = 0;
        $sections = null;

        $mapAttachment = false;
        $receptionEntryTextline = false;
        if ($id) {
            /** @var \DDD\Domain\Office\OfficeManager $officeInfo */
            $officeInfo             = $officeManagerDao->fetchOne(['id' => $id]);
            $sections               = $officeSecDao->getAllSecByOfficeId($id);
            $officeStatus           = $officeInfo->getDisable();
            $receptionEntryTextline = $officeInfo->getReceptionEntryTextline();

            if ($officeInfo->getMapAttachment()) {
                $mapAttachment = '//' . DomainConstants::IMG_DOMAIN_NAME . '/office/' . $id . '/' . $officeInfo->getMapAttachment();
            }
        }

		return new ViewModel([
            'userId'     => $auth->getIdentity()->id,
            'officeForm' => $form,
            'id'         => $id,
            'global'     => true,
            'memberList' => $ginosiks,
            'sections'   => $sections,
            'officeSts'  => $officeStatus,
            'mapAttachment' => $mapAttachment,
            'roleCosts'  => $auth->hasRole(Roles::ROLE_OFFICE_COST_VIEWER),
            'receptionEntryTextline' => $receptionEntryTextline,
        ]);
    }

    public function ajaxGetOfficeCostsAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_NO_POST_ERROR);
            }

            /**
             * @var BackofficeAuthenticationService $auth
             * @var \DDD\Dao\Finance\Expense\ExpenseCost $expenseCostDao
             */
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $expenseCostDao = $this->getServiceLocator()->get('dao_finance_expense_expense_cost');

            $officeId = $this->params()->fromRoute('id');
            $datatableParams = [
                'iDisplayStart'     => $this->params()->fromQuery('iDisplayStart'),
                'iDisplayLength'    => $this->params()->fromQuery('iDisplayLength'),
                'iSortCol_0'        => $this->params()->fromQuery('iSortCol_0'),
                'sSortDir_0'        => $this->params()->fromQuery('sSortDir_0'),
                'sSearch'           => $this->params()->fromQuery('sSearch')
            ];

            $officeCosts            = $expenseCostDao->getOfficeCosts($officeId, $datatableParams, true);
            $officeCostsFiltered    = $expenseCostDao->getOfficeCosts($officeId, $datatableParams);

            $totalCount = $officeCosts->count();
            $data = [];

            foreach ($officeCostsFiltered as $cost) {
                $viewUrl = $this->url()->fromRoute('finance/purchase-order/edit', ['id' => $cost['expense_id']]);
                $view = '<a class="btn btn-xs btn-primary" href="' . $viewUrl . '" target="_blank">View</a>';
                $rows = [
                    $cost['id'],
                    $cost['category'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($cost['date'])),
                    $cost['currency_code'],
                    $cost['amount'],
                    Helper::truncateNotBreakingHtmlTags($cost['purpose']),
                ];

                if ($auth->hasRole(Roles::ROLE_EXPENSE_MANAGEMENT)) {
                    array_push($rows, $view);
                }

                array_push($data, $rows);
            }

            $result = [
                'iTotalRecords'         => $totalCount,
                'iTotalDisplayRecords'  => $totalCount,
                'iDisplayStart'         => 0,
                'iDisplayLength'        => 25,
                'aaData'                => $data
            ];

        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get Office Costs');

            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR . ' <b>' . $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function ajaxDownloadOfficeCostsCsvAction()
    {
        try {
            /**
             * @var \DDD\Dao\Finance\Expense\ExpenseCost $expenseCostDao
             */
            $expenseCostDao = $this->getServiceLocator()->get('dao_finance_expense_expense_cost');

            $officeId = $this->params()->fromRoute('id');
            $filter = $this->params()->fromQuery('filter');

            $officeCostsFiltered = $expenseCostDao->getOfficeCosts($officeId, [
                'sSearch'       => $filter,
                'iSortCol_0'    => 2,
                'sSortDir_0'    => 'desc'
            ]);

            $costArray = [];

            foreach ($officeCostsFiltered as $cost) {
                $costArray [] = [
                    "ID"        => $cost['id'],
                    "Category"  => $cost['category'],
                    "Date"      => date(Constants::GLOBAL_DATE_FORMAT, strtotime($cost['date'])),
                    "Currency"  => $cost['currency_code'],
                    "Amount"    => $cost['amount'],
                    "Purpose"   => $cost['purpose']
                ];
            }

            if (!empty($costArray)) {
                $response = $this->getResponse();
                $headers  = $response->getHeaders();

                $utilityCsvGenerator = new CsvGenerator();
                $filename            = 'costs_office_' .$officeId . '_' . str_replace(' ', '_', date('Y-m-d')) . '.csv';
                $utilityCsvGenerator->setDownloadHeaders($headers, $filename);

                $csv = $utilityCsvGenerator->generateCsv($costArray);
                $response->setContent($csv);

                return $response;
            } else {
                $flash_session        = Helper::getSessionContainer('use_zf2');
                $flash_session->flash = ['notice' => 'There are empty data, nothing to download.'];

                $url = $this->getRequest()->getHeader('Referer')->getUri();
                $this->redirect()->toUrl($url);
            }


        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot Download Office Costs Csv');
        }

        return $this->redirect()->toUrl('/');
    }

    public function getForm($id)
    {
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $officeService = $this->getOfficeService();

        $prepareOption = $officeService->getEditOfficeFormOptions();

		$previousData = null;
		$global       = false;

		if ($id > 0) {
            $activateUsers = $prepareOption['ginosiksList'];
            $previousData = $officeService->getData($id, $activateUsers);

            if (!$previousData) {
                return false;
            }

            $countryId    = $previousData['office']->getCountryId();
            $provinceId   = $previousData['office']->getProvinceId();

            $this->prepareFormContent($countryId, $provinceId);

			if ($auth->hasRole(Roles::ROLE_OFFICE_MANAGER)) {
				$global = true;
			}
		} else {
            $countryId  = 1;
            $provinceId = 1;
        }

        $locationInfo = $this->prepareFormContent($countryId, $provinceId);
        $previousData['location'] = $locationInfo;

		if ($auth->hasRole(Roles::ROLE_OFFICE_MANAGER)) {
			$global = true;
		}

		return new OfficeForm(
            'ginosiks_office',
            $previousData,
            $prepareOption,
            $global
        );

    }

	public function ajaxsaveAction()
    {
		$request = $this->getRequest();
        $officeService = $this->getOfficeService();;
		$result = [
			'result' => [],
			'id'     => 0,
			'status' => 'success',
			'msg'    => TextConstants::SUCCESS_UPDATE,
		];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $auth     = $this->getServiceLocator()->get('library_backoffice_auth');
                $id       = (int)$request->getPost('office_id');
                $form     = $this->getForm($id);
                $messages = false;
                $global   = false;

                if ($auth->hasRole(Roles::ROLE_OFFICE_MANAGER)) {
                    $global = true;
                }

                $form->setInputFilter(new OfficeFilter($global));
                $filter = $form->getInputFilter();
                $form->setInputFilter($filter);
                $data = $request->getPost();
                $form->setData($data);
                $name = trim(strip_tags($request->getPost('name')));

                if ($global) {
                    if ($name != '') {
                        if ($officeService->checkName($name, $id)) {
                            $messages = 'Wrong Office name. Office name is already in use.<br>';
                        }
                    }
                }

                if ($form->isValid() && !$messages) {
                    if ($result['status'] != 'error') {
                        $mapAttachmentFile = null;

                        if (!is_null($request->getFiles('map_attachment'))) {
                            $mapAttachmentFile = $request->getFiles();
                        }

                        $responsDb = $officeService->officeSave($data, $mapAttachmentFile, $id, $global);

                        if ($responsDb) {
                            if (!$id) {
                                $result['id']  = $responsDb;
                                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                            } else {
                                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                                $result['id'] = $id;
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
                            $messages .= ucfirst($key) . ' ';
                            $messagesSub = '';

                            foreach ($row as $keyer => $rower) {
                                $messagesSub .= $rower;
                            }

                            $messages .= $messagesSub . '<br>';
                        }
                    }

                    $result['status'] = 'error';
                    $result['msg']    = $messages;
                }
            }
        } catch(\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR . ' <b>';
        }

		return new JsonModel($result);
	}

	public function ajaxdeleteofficeAction()
	{
        $request = $this->getRequest();
		$result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$id = (int)$request->getPost('id');
				$officeService = $this->getOfficeService();

                $officeService->deleteOffice($id);

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
			} else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
		} catch (\Exception $e) {
			$result['status'] = 'error';
			$result['msg']    = TextConstants::SERVER_ERROR;
		}

		return new JsonModel($result);
	}

	public function ajaxdeactiveofficeAction()
	{
        $request = $this->getRequest();
		$result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$id = (int)$request->getPost('id');
                $officeService = $this->getOfficeService();

                $officeService->changeOfficeStatus($id, 1);

				Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DEACTIVATE]);
			} else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
		} catch (\Exception $e) {
			$result['status'] = 'error';
			$result['msg']    = TextConstants::SERVER_ERROR;
		}

		return new JsonModel($result);
	}

    public function ajaxactiveofficeAction()
	{
        $request = $this->getRequest();
		$result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$id = (int)$request->getPost('id');
                $officeService = $this->getOfficeService();

                $officeService->changeOfficeStatus($id, 0);

				Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ACTIVATE]);
			} else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
		} catch (\Exception $e) {
			$result['status'] = 'error';
			$result['msg']    = TextConstants::SERVER_ERROR;
		}

		return new JsonModel($result);
	}

	public function ajaxchecknameAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		$response->setStatusCode(200);

		try {
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$name = strip_tags(trim($request->getPost('name')));
				$id = (int)$request->getPost('id');

                $officeService =$this->getOfficeService();

                if (!$officeService->checkName($name, $id)) {
					$response->setContent('true');
				} else {
					$response->setContent('false');
				}
			} else {
                $result['msg'] = TextConstants::BAD_REQUEST;
            }
		} catch (\Exception $e) {
			$response->setContent('false');
		}

		return $response;
	}

	public function getProvinceOptionsAction()
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');
		$countryId = (int)$this->params()->fromQuery('country', 0);
		$provinceOptions = [];

		if ($countryId) {
			$provinces = $generalLocationService->getActiveChildLocations(
                LocationService::LOCATION_TYPE_PROVINCE,
                $countryId
            );

			foreach ($provinces as $province) {
                array_push($provinceOptions, [
                    'id' => $province->getID(),
					'name' => $province->getName(),
				]);
			}
		} else {
            array_push($provinceOptions, [
                'id' => 0,
                'name' => '--',
            ]);
        }

		return new JsonModel($provinceOptions);
	}

	public function getCityOptionsAction()
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');
		$provinceId = (int)$this->params()->fromQuery('province', 0);
		$cityOptions = [];

		if ($provinceId) {
			$cities = $generalLocationService->getActiveChildLocations(
                LocationService::LOCATION_TYPE_CITY,
                $provinceId
            );

			foreach ($cities as $city) {
                array_push($cityOptions, [
					'id' => $city->getID(),
					'name' => $city->getName(),
				]);
			}
		} else {
            array_push($cityOptions, [
                'id'   => 0,
                'name' => '--',
            ]);
        }

		return new JsonModel($cityOptions);
	}

	/**
	 * Prepare needed data before form construction, especially options for select elements
	 *
	 * @param int $countryId
	 * @param int $provinceId
	 * @return array
	 */
	private function prepareFormContent($countryId, $provinceId)
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');

        // country options
        $countries = $generalLocationService->getAllActiveCountries();

        $countryOptions = ['-- Choose --'];
        $content = [];

        foreach ($countries as $country) {
            if ($country->getChildrenCount() != '') {
                $countryOptions[$country->getID()] = $country->getName();
            }
		}

		$content['countryOptions'] = $countryOptions;

		// province options
		$provinces = $generalLocationService->getActiveChildLocations(
            LocationService::LOCATION_TYPE_PROVINCE,
            $countryId
        );

		$provinceOptions = [];
        $cityOptions = [];

		foreach ($provinces as $province) {
			$provinceOptions[$province->getID()] = $province->getName();
		}

		// city options
		$cities = $generalLocationService->getActiveChildLocations(
            LocationService::LOCATION_TYPE_CITY,
            $provinceId
        );

		foreach ($cities as $city) {
			$cityOptions[$city->getID()] = $city->getName();
		}

        $content['provinceOptions'] = $provinceOptions;
		$content['cityOptions'] = $cityOptions;

		return $content;
	}

    public function changeSectionStatusAction()
    {
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

		try {
            $secId = (int)$this->params()->fromQuery('secId', 0);
            $officeService = $this->getOfficeService();

            $secInfo = $officeService->getSectionById($secId);
            $setStatus = intval($secInfo->getDisable() === '0');

            $officeService->changeOfficeSectionStatus($secId, $setStatus);

            $result['status']  = 'success';
            $result['msg']     = TextConstants::SUCCESS_UPDATE;
            $result['disable'] = $setStatus;
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    /**
     * @return Office
     */
    public function getOfficeService()
    {
		if (!isset($this->_office)) {
			$this->_office = $this->getServiceLocator()->get('service_office');
        }

		return $this->_office;
	}
}
