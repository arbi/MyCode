<?php

namespace Finance\Controller;

use Library\Controller\ControllerBase;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Library\Constants\TextConstants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Finance\Form\ExpenseItemCategoriesForm;
use Finance\Form\InputFilter\ExpenseItemCategoriesFilter;

/**
 * ExpenseItemCategoriesController
 * @package finance
 */
class ExpenseItemCategoriesController extends ControllerBase
{
    /**
     * @var
     */
    private $_categoryService;

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     * @return JsonModel
     */
    public function ajaxCategoryListAction()
    {
	    $request = $this->params();

        $this->getCategoryService();

        $results = $this->_categoryService->getCategoryList(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $categoryCount = $this->_categoryService
            ->getCategoryCount(
                $request->fromQuery('sSearch'),
                $request->fromQuery('all', '1')
        );

        $result = [];

        foreach ($results as $row) {
            $status = $row->getActive() ?
                '<span class="label label-success">Active</span>' :
			    '<span class="label label-default">Inactive</span>';

            $result[] = [
                $status,
                $row->getName(),
                $row->getDescription(),
                '<a href="/finance/expense-item-categories/edit/' . $row->getId() .
                    '" class="btn btn-xs btn-primary" data-html-content="Edit"></a>',
            ];
        }

        if(!isset($result))
            $result[] = [' ', '', '', '', '', '', '', '', ''];

        $resultArray = [
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $categoryCount,
            'iTotalDisplayRecords' => $categoryCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (int)$request->fromQuery('iDisplayLength'),
            'aaData'               => $result,
        ];

        return new JsonModel($resultArray);
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $categoryId = null;
        $this->getCategoryService();

    	$form = new ExpenseItemCategoriesForm();
    	$inputFilter = new ExpenseItemCategoriesFilter();
        $form->get('submit')->setValue('Add Category');

    	$form->setInputFilter($inputFilter->getInputFilter());
    	$form->prepare();

    	if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            // check for validation
            if ($form->isValid()) {
                // get validated data from form
                $formData = $form->getData();

                // unseting submit button, fieldsets, etc.
                unset($formData["submit"]);

                // save the category
                $formData['is_active'] = 1;

                $categoryId = $this->_categoryService->saveCategory($formData);

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);

                $this->redirect()->toRoute('finance/expense-item-categories', [
                    'controller' => 'expense-item-categories',
                    'action' => 'index',
                ]);
            } else {
                $form->populateValues($postData);
            }
    	}

    	$viewModel = new ViewModel();

        $viewModel->setVariables([
            'form'       => $form,
            'categoryId' => $categoryId,
            'pageTitle'  => $categoryId ? 'Edit Category' : 'Add Category',
        ]);

        $viewModel->setTemplate('finance/expense-item-categories/edit');

    	return $viewModel;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $this->getCategoryService();
        $categoryId = $this->params("id", 0);

        $form = new ExpenseItemCategoriesForm();
    	$inputFilter = new ExpenseItemCategoriesFilter();

        $form->get('submit')->setValue('Save Changes');

    	$form->setInputFilter($inputFilter->getInputFilter());
    	$form->prepare();

        $this->getCategoryService();
        $category = $this->_categoryService->getCategoryById($categoryId);

        if (!$category) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('finance/expense-item-categories');
        }

    	if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $formData = $form->getData();

                unset($formData["submit"]);

                $this->_categoryService->saveCategory($formData, $categoryId);

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $this->redirect()->toRoute('finance/expense-item-categories', [
                    'controller' => 'expense-item-categories',
                    'action'     => 'index',
                ]);
            } else {
                $form->populateValues($postData);
            }
    	} else {
            if ($categoryId) {
                $form->populateValues([
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'is_active' => $category->getActive(),
                ]);
            }
    	}

    	$viewModel = new ViewModel();
    	$viewModel->setVariables([
            'form'       => $form,
            'categoryId' => $categoryId,
            'isActive'   => (bool)$category->getActive(),
            'pageTitle'  => $categoryId ? 'Edit Category' : 'Add Category'
        ]);

        $viewModel->setTemplate('finance/expense-item-categories/edit');

    	return $viewModel;
    }

    public function activateAction()
    {
        $this->getCategoryService();

		$categoryId = $this->params()->fromRoute('id', false);
		$status     = (int)$this->params()->fromQuery('status', null);

        $status = !is_null($status) ? (int)$status : null;

        if (   $categoryId
            && !is_null($status)
            && is_int($status)
        ) {
            $result = $this
                ->_categoryService
                ->changeStatus($categoryId, $status);

            $successText = ($status) ?
                TextConstants::SUCCESS_ACTIVATE :
                TextConstants::SUCCESS_DEACTIVATE;

            if ($result) {
                Helper::setFlashMessage(['success' => $successText]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        $this->redirect()->toRoute(
            'finance/expense-item-categories',
            ['controller' => 'expense-item-categories']
        );
    }

    public function ajaxCheckNameAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $response->setStatusCode(200);
        try{
            if($request->isXmlHttpRequest()) {

                $name = strip_tags(trim($request->getPost('name')));
                $id    = (int)$request->getPost('id');

                $this->getCategoryService();

                if(!$this->_categoryService->checkTitle($name, $id)){
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
     *
     * @return \DDD\Service\Finance\Expense\ExpenseItemCategories
     */
    public function getCategoryService()
    {
        if (empty($this->_categoryService)) {

            $this->_categoryService = $this
                ->getServiceLocator()
                ->get('service_finance_expense_expenses_item_categories');
        }

        return $this->_categoryService;
    }

    public function ajaxGetSubcategoriesAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $queryData = $request->getQuery();
            $status = 1;

            if (isset($queryData['status'])) {
                $status = $queryData['status'];
            }

            /**
             * @var \DDD\Dao\Finance\Expense\ExpenseItemSubCategories $subCategoryDao
             */
            $subCategoryDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_sub_categories');

            $subCategoriesList = $subCategoryDao->getSubCategoriesByCategoryId($queryData['category_id'], $status);

            $subCategories = [];
            foreach ($subCategoriesList as $subCategory) {
                if ($subCategory->getIsActive()) {
                    $actionClass = 'btn-danger disable-subcategory';
                    $actionTitle = 'Disable';
                } else {
                    $actionClass = 'btn-default enable-subcategory';
                    $actionTitle = 'Enable';
                }

                $action = '<button
                    id="' . $subCategory->getId() . '"
                    class="btn btn-xs btn-block subcategory-action ' . $actionClass .'">
                    ' . $actionTitle . '
                    </button>';

                array_push($subCategories, [
                    $subCategory->getName(),
                    $action
                ]);
            }

            $result = [
                'aaData' => $subCategories
            ];
        } catch (\Exception $e) {
            $this->gr2logException($e);

            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function ajaxActionSubcategoryAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isPost() || !$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $request->getPost();

            /**
             * @var \DDD\Dao\Finance\Expense\ExpenseItemSubCategories $subCategoryDao
             */
            $subCategoryDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_sub_categories');

            $subCategoryDao->save(
                ['is_active' => $postData['action']],
                ['id' => $postData['subcategory_id']]
            );

            $result = [
                'status'    => 'success',
                'msg'       => TextConstants::SUCCESS_UPDATE
            ];
        } catch (\Exception $e) {
            $this->gr2logException($e);

            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    public function ajaxAddSubCategoryAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isPost() || !$request->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $request->getPost();

            /**
             * @var \DDD\Dao\Finance\Expense\ExpenseItemSubCategories $subCategoryDao
             */
            $subCategoryDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_sub_categories');

            $result = $subCategoryDao->insert([
                'category_id'   => $postData['category_id'],
                'name'          => $postData['subcategory_title'],
                'is_active'     => 1
            ]);

            if ($result) {
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
}
