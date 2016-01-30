<?php

namespace Warehouse\Controller;

use Library\Constants\DbTables;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\ActionLogger\Logger;

use Warehouse\Form\Category as CategoryForm;
use Warehouse\Form\InputFilter\Category as CategoryFilter;
use Zend\Validator\Db\NoRecordExists;
use Zend\View\Model\JsonModel;

class Category extends ControllerBase
{
    public function indexAction()
    {
        return [
            'ajaxSourceUrl' => '/warehouse/category/get-json',
        ];
	}

    public function getJsonAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Category $service
         */
        $request = $this->params();
        $service = $this->getServiceLocator()->get('service_warehouse_category');
        $result = $service->getDatatableData(
            $request->fromQuery('iDisplayStart'),
            $request->fromQuery('iDisplayLength'),
            $request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        return new JsonModel([
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $result['total'],
            'iTotalDisplayRecords' => $result['total'],
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => $request->fromQuery('iDisplayLength'),
            'aaData'               => $result['data'],
        ]);
    }

    public function editAction()
    {
        /** @var \DDD\Service\Warehouse\Category $service */
        $service      = $this->getServiceLocator()->get('service_warehouse_category');
        $request      = $this->getRequest();
        $categoryId   = $this->params()->fromRoute('id', 0);
        $redirectId   = 0;
        $status       = 0;
        $form         = new CategoryForm($categoryId);
        $categories   = [];
        $actionLogs   = [];
        $categoryType = 0;
        $error        = false;

        $form->prepare();

        if ($request->isPost()) {
            $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

            $inputFilter = new CategoryFilter();
            $form->setInputFilter($inputFilter);
            $postData = $request->getPost();

            $form->setData($postData);
            $uniqueValidator = new NoRecordExists(
                array(
                    'adapter' => $dbAdapter,
                    'table'   => DbTables::TBL_ASSET_CATEGORIES,
                    'field'   => 'name',
                    'exclude' => array(
                        'field' => 'id',
                        'value' => $categoryId
                    )
                )
            );

            if ($form->isValid()) {
                try {
                    if ($uniqueValidator->isValid($postData['name'])) {
                        $output = $service->saveCategory($postData, $categoryId);

                        if ($output instanceof \Exception) {
                            $error = $output->getMessage();
                        } else {
                            $redirectId = $output;
                            Helper::setFlashMessage(['success' => ($categoryId > 0) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
                        }
                    } else {
                        $error = 'Category with same name already exists.';
                    }
                } catch (\RuntimeException $ex) {
                    $error = $ex->getMessage();
                } catch (\Exception $ex) {
                    $error = TextConstants::SERVER_ERROR;
                }

                if ($redirectId) {
                    $this->redirect()->toRoute('warehouse/category', ['controller' => 'category', 'action' => 'edit', 'id' => $redirectId]);
                }
            } else {
                $error = TextConstants::SERVER_ERROR;
            }

            $form->populateValues($postData);
        } else {
            if ($categoryId) {
                $categoryData = $service->getCategoryData($categoryId);

                if ($categoryData) {
                    $form->populateValues($categoryData);
                    $status = $categoryData['inactive'];

                    $categories = $service->getCategoryNames([$categoryData['type']], $categoryId);

                    $categoryType = $categoryData['type'];

                    $loggerService = $this->getServiceLocator()->get('ActionLogger');
                    $actionLogs    = $loggerService->getDatatableData(Logger::MODULE_ASSET_CATEGORY, $categoryId);

                } else {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    $this->redirect()->toRoute('warehouse/category', ['controller' => 'category']);
                }

            }
        }

        $aliases = [];
        if ($categoryId) {
            /** @var \DDD\Dao\Warehouse\Alias $assetCategoryAliasesDao */
            $assetCategoryAliasesDao = $this->getServiceLocator()->get('dao_warehouse_alias');
            $aliases = $assetCategoryAliasesDao->fetchAll(['asset_category_id' => $categoryId]);
        }


        return [
            'form'         => $form,
            'id'           => $categoryId,
            'status'       => $status,
            'error'        => $error,
            'skuList'      => $service->getCategroySKUList($categoryId),
            'aliases'      => $aliases,
            'categories'   => $categories,
            'historyData'  => json_encode($actionLogs),
            'categoryType' => $categoryType
        ];
    }

    public function changeStatusAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Category $service
         */
        $service    = $this->getServiceLocator()->get('service_warehouse_category');
        $categoryId = $this->params()->fromRoute('id', 0);
        $status     = $this->params()->fromRoute('param', 0);

        if ($categoryId > 0) {
            $service->changeStatus($categoryId, $status);
            Helper::setFlashMessage(['success' => $status ? TextConstants::SUCCESS_DEACTIVATE : TextConstants::SUCCESS_ACTIVATE]);
        } else {
            Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('warehouse/category', ['controller' => 'category', 'action' => 'edit', 'id' => $categoryId]);
    }

    public function checkAliasUniquenessAction()
    {
        $result = [
            'status' => 'success',
        ];
        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $aliasId = $request->getPost('id', 0);
            $aliasName = $request->getPost('name', '');
            if ($aliasName) {
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

                $uniqueValidator = new NoRecordExists(
                    array(
                        'adapter' => $dbAdapter,
                        'table'   => DbTables::TBL_ASSET_CATEGORY_ALIASES,
                        'field'   => 'name',
                        'exclude' => array(
                            'field' => 'id',
                            'value' => $aliasId
                        )
                    )
                );

                if (!($uniqueValidator->isValid($aliasName))) {
                    $result = [
                        'status' => 'error',
                        'msg' => 'Alias with name ' . $aliasName . ' already exists'
                    ];
                }
            }
        }

        return new JsonModel($result);
    }

    public function archiveCategoryAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Category $service
         */
        $service    = $this->getServiceLocator()->get('service_warehouse_category');
        $categoryId = $this->params()->fromRoute('id', 0);

        $service->archiveCategory($categoryId);

        return new JsonModel([
            'status' => 'success',
            'msg'    => 'Category successfully archived'
        ]);
    }

    public function mergeCategoryAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Category $service
         */
        $service = $this->getServiceLocator()->get('service_warehouse_category');

        $result  = [ 'status' => 'error', 'msg' => TextConstants::SERVER_ERROR];
        $request = $this->getRequest();

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $currentCategoryId = $request->getPost('current_category_id', 0);
                $mergeCategoryId   = $request->getPost('merge_category_id', 0);
                $aliasName         = $request->getPost('name', '');
                $type              = $request->getPost('type', 0);

                $response = $service->mergeCategory($currentCategoryId, $mergeCategoryId, $aliasName, $type);

                if ($response) {
                    $result['status'] = 'reload';
                    $result['msg']    = TextConstants::CATEGORY_MERGED;

                    Helper::setFlashMessage(['success' => TextConstants::CATEGORY_MERGED]);
                }
            }
        } catch (\Exception $e) {
            return new JsonModel($result);
        }

        return new JsonModel($result);
    }
}
