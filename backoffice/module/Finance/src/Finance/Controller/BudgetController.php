<?php

namespace Finance\Controller;

use DDD\Service\Finance\Budget as BudgetService;
use DDD\Service\Finance\Expense\ExpenseTicket;
use Finance\Form\BudgetSearch;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Finance\Form\Budget as BudgetForm;
use Finance\Form\InputFilter\Budget as BudgetFilter;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Library\Authentication\BackofficeAuthenticationService;


class BudgetController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \DDD\Dao\User\UserManager $userDao
         * @var BackofficeAuthenticationService $auth
         */
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');

        // if you are not global manager you can just see your own created budget
        $userId = false;
        if (!$auth->hasRole(Roles::ROLE_BUDGET_MANAGER_GLOBAL)) {
            $userId = $auth->getIdentity()->id;
        }

        $users = $userDao->getUserListOrUser($userId);
        $form = new BudgetSearch($users, $this->getOptions());

        return [
            'form' => $form
        ];
	}

    public function getDatatableAction()
    {
        $result = [
            'iTotalRecords'        => 0,
            'iTotalDisplayRecords' => 0,
            'iDisplayStart'        => 0,
            'iDisplayLength'       => 0,
            'aaData'               => [],
        ];

        /**
         * @var \DDD\Service\Finance\Budget $service
         * @var BackofficeAuthenticationService $auth
         */
        $service = $this->getServiceLocator()->get('service_finance_budget');
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();

        if ($request->isGet() && $request->isXmlHttpRequest()) {
            try {

                // if you are not global manager you can just see your own created budget
                $userId = false;
                if (!$auth->hasRole(Roles::ROLE_BUDGET_MANAGER_GLOBAL)) {
                    $userId = $auth->getIdentity()->id;
                }

                $budgetData = $service->getDatatableData($this->params()->fromQuery(), $userId, $this->getOptions());

                if ($budgetData['iTotalRecords']) {
                    $result = $budgetData;
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function editAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $service
         * @var BackofficeAuthenticationService $auth
         */
        $service      = $this->getServiceLocator()->get('service_finance_budget');
        $auth         = $this->getServiceLocator()->get('library_backoffice_auth');

        $request         = $this->getRequest();
        $budgetId        = $this->params()->fromRoute('id', 0);
        $frozen          = $archived = 0;
        $isGlobalManager = $auth->hasRole(Roles::ROLE_BUDGET_MANAGER_GLOBAL);

        $form = new BudgetForm($budgetId, $isGlobalManager, $this->getOptions());

        $form->setInputFilter(new BudgetFilter());
        $form->prepare();

        $disableForm = 'no';
        $budgetData = false;

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                if ($redirectId = $service->saveBudget($postData, $budgetId)) {
                    Helper::setFlashMessage(['success' => ($budgetId > 0) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
                    $this->redirect()->toRoute('finance/budget/edit', ['controller' => 'edit', 'action' => 'edit', 'id' => $redirectId]);
                } else {
                    Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
                    $this->redirect()->toRoute('finance/budget', ['controller' => 'budget']);
                }
            }
        } else {
            if ($budgetId) {
                // if you are not global manager you can just see your own created budget
                $userId = false;

                if (!$isGlobalManager) {
                    $userId = $auth->getIdentity()->id;
                }

                $budgetData = $service->getBudgetData($budgetId, $userId);

                if ($budgetData) {
                    /**
                     * @var ExpenseTicket $expenseService
                     */
                    $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
                    if ($budgetId != BudgetService::BUDGET_NULL_ID) {
                        $posAttachedToThisBudget = $expenseService->getPOsByBudgetId($budgetId);
                    } else {
                        $posAttachedToThisBudget = [];
                    }

                    $form->populateValues($budgetData);
                    $frozen   = $budgetData['frozen'];
                    $archived = $budgetData['archived'];

                    if (!$auth->hasRole(Roles::ROLE_BUDGET_MANAGER_GLOBAL) && $budgetData['status'] != BudgetService::BUDGET_STATUS_DRAFT) {
                        $disableForm = 'yes';
                    }

                } else {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    $this->redirect()->toRoute('finance/budget', ['controller' => 'budget']);
                }
            }
        }

        return [
            'form'        => $form,
            'id'          => $budgetId,
            'frozen'      => $frozen,
            'archived'    => $archived,
            'disableForm' => $disableForm,
            'budgetData'  => $budgetData,
            'posAttachedToThisBudget' => isset($posAttachedToThisBudget) ? $posAttachedToThisBudget : false
        ];
    }

    public function frozenAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $service
         */
        $service = $this->getServiceLocator()->get('service_finance_budget');
        $budgetId = $this->params()->fromRoute('id', 0);
        $frozen = $this->params()->fromRoute('frozen', 0);

        if ($budgetId > 0) {
            $service->frozen($budgetId, $frozen);
            Helper::setFlashMessage(['success' => $frozen ? TextConstants::SUCCESS_FROZEN : TextConstants::SUCCESS_UNFROZEN]);
        } else {
            Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('finance/budget/edit', ['controller' => 'edit', 'action' => 'edit', 'id' => $budgetId]);
    }

    public function archiveAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $service
         */
        $service = $this->getServiceLocator()->get('service_finance_budget');
        $budgetId = $this->params()->fromRoute('id', 0);
        $archive = $this->params()->fromRoute('archive', 0);

        if ($budgetId > 0) {
            $service->archive($budgetId, $archive);
            Helper::setFlashMessage(['success' => $archive ? TextConstants::SUCCESS_ARCHIVE : TextConstants::SUCCESS_UNARCHIVE]);
        } else {
            Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('finance/budget/edit', ['controller' => 'edit', 'action' => 'edit', 'id' => $budgetId]);
    }

    public function changeStatusAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $service
         */
        $service = $this->getServiceLocator()->get('service_finance_budget');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id = $request->getPost('id', 0);
                $status = $request->getPost('status', 0);

                if ($id && $status) {
                    $service->changeStatus($id, $status);
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);

                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE,
                    ];
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function chartAction()
    {
        return new ViewModel();
    }

    public function drawChartAction()
    {
        /**
         * @var \DDD\Service\Finance\Budget $service
         */
        $service = $this->getServiceLocator()->get('service_finance_budget');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $year = $request->getPost('year', 0);
                $budgets = $service->getBudgetsForChart($year);
                    $result = [
                        'status' => 'success',
                        'budgets' => $budgets
                    ];
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    private function getOptions()
    {
        $teamService  = $this->getServiceLocator()->get('service_team_team');
        $daoCountries = $this->getServiceLocator()->get('dao_geolocation_countries');

        $departments = $teamService->getTeamList(null, 1);

        $coDepartments     = [];
        $coDepartments[-1] = '-- Choose Department --';

        foreach ($departments as $department) {
            $coDepartments[$department->getId()] = $department->getName();
        }

        $countries     = $daoCountries->getCountriesListWithCities();
        $countriesList = ['-1' => '-- Choose Countries --'];

        foreach ($countries as $country){
            $countriesList[$country->getId()] = $country->getName();
        }

        return ['departments' => $coDepartments, 'countries' => $countriesList];
    }
}
