<?php

namespace Backoffice\Controller;

use Library\Controller\ControllerBase;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;


use Backoffice\Form\SearchTestResultsForm;

class TestResultsController extends ControllerBase {

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        $unitTestingService = $this->getServiceLocator()->get('service_unit_testing');
        $categoriesArray = $unitTestingService->getAllCategories();
        if ($categoriesArray['status'] == 'error') {
            return new ViewModel($categoriesArray);
        }
        $form = new SearchTestResultsForm('search-test-results', $categoriesArray['categories']);
        return new ViewModel(['form' => $form]);
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetTestResultsAction()
    {
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $unitTestingService = $this->getServiceLocator()->get('service_unit_testing');
                $categories = $request->getPost('categories');
                $statuses = $request->getPost('statuses');
                $testName = $request->getPost('test_name');
                $serviceResult = $unitTestingService->getAllTests($categories, $statuses, $testName);
                if ($serviceResult['status'] == 'error') {
                    return new JsonModel($serviceResult);
                }
                $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                $partialFile ='backoffice/test-results/partial/category';

                $finalResult = ['partials' => [], 'total' => $serviceResult['totalArray'], 'status' => 'success'];
                foreach ($serviceResult['resultCategorizedArray'] as $key => $row) {
                    if ($row['totalCount'] == 0) {
                        continue;
                    }

                    if ($row['failCount']) {
                        $categoryClass = 'pink';
                    } elseif($row['errorCount']) {
                        $categoryClass = 'danger';
                    } elseif($row['warningCount']) {
                        $categoryClass = 'warning';
                    } else {
                        $categoryClass = 'success';
                    }

                  $partialHtml =   $partial($partialFile,['categoryClass' => $categoryClass, 'categoryName' => $key, 'category' => $row]);
                  array_push($finalResult['partials'], $partialHtml);
                }
                return new JsonModel($finalResult);
            } catch (\Exception $ex) {

            }
        } else {

        }

        return new JsonModel($result);
    }
}
