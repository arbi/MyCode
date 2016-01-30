<?php

namespace Warehouse\Controller;

use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Warehouse\Form\Storage as StorageForm;
use Warehouse\Form\InputFilter\Storage as StorageFilter;
use Zend\View\Model\JsonModel;


class Storage extends ControllerBase
{
    public function indexAction()
    {
        return [
            'ajaxSourceUrl' => '/warehouse/storage/get-json',
        ];
    }

    public function getJsonAction()
    {
        /**
         * @var \DDD\Service\Warehouse\Storage $service
         */
        $request = $this->params();
        $service = $this->getServiceLocator()->get('service_warehouse_storage');
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
        /**
         * @var \DDD\Service\Warehouse\Storage $service
         * @var \DDD\Dao\Geolocation\City $daoCity
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $service = $this->getServiceLocator()->get('service_warehouse_storage');
        $daoCity = $this->getServiceLocator()->get('dao_geolocation_city');

        $request = $this->getRequest();
        $storageId = $this->params()->fromRoute('id', 0);
        $thresholdList = $categoryList = [];
        $status = 0;

        // get city list
        $cities = $daoCity->getSearchableCities();

        $form = new StorageForm($storageId, $cities);
        $form->setInputFilter(new StorageFilter());
        $form->prepare();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                if ($redirectId = $service->saveStorage($postData, $storageId)) {
                    Helper::setFlashMessage(['success' => ($storageId > 0) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
                    $this->redirect()->toRoute('warehouse/storage', ['controller' => 'storage', 'action' => 'edit', 'id' => $redirectId]);
                } else {
                    Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
                }
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }

            $form->populateValues($postData);
        } else {
            if ($storageId) {
                $storageData = $service->getStorageData($storageId);

                if ($storageData) {
                    $form->populateValues($storageData);

                    // threshold list
                    $thresholdList = $service->getAllThresholdForStorage($storageId);

                    // get unused category list
                    $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
                    $categoryList = $categoryDao->getUnusedCategories($storageId);

                    $status = $storageData['inactive'];
                } else {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    $this->redirect()->toRoute('warehouse/storage', ['controller' => 'storage']);
                }
            }
        }

        return [
            'form' => $form,
            'id' => $storageId,
            'thresholdList' => json_encode($thresholdList),
            'categoryList' => $categoryList,
            'status' => $status
        ];
    }

    public function changeStatusAction()
    {

        /**
         * @var \DDD\Service\Warehouse\Storage $service
         */
        $service = $this->getServiceLocator()->get('service_warehouse_storage');
        $storageId = $this->params()->fromRoute('id', 0);
        $status = $this->params()->fromRoute('param', 0);

        if ($storageId > 0) {
            try {
                $service->changeStatus($storageId, $status);
            } catch (\Exception $e) {
                Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
                $this->redirect()->toRoute('warehouse/storage', ['controller' => 'storage']);
            }

            Helper::setFlashMessage(['success' => $status ? TextConstants::SUCCESS_DEACTIVATE : TextConstants::SUCCESS_ACTIVATE]);
        } else {
            Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('warehouse/storage', ['controller' => 'storage', 'action' => 'edit', 'id' => $storageId]);
    }

    public function ajaxAddThresholdAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $categoryId = (int)$request->getPost('category_id', 0);
                $threshold = (int)$request->getPost('threshold', 0);
                $storageId = (int)$request->getPost('storage_id', 0);

                if ($categoryId && $threshold && $storageId) {
                    /**
                     * @var \DDD\Service\Warehouse\Storage $service
                     */
                    $service = $this->getServiceLocator()->get('service_warehouse_storage');
                    if ($service->saveThreshold($categoryId, $threshold, $storageId)) {
                        $result['status'] = 'success';
                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                    }
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }


    public function deleteThresholdAction()
    {

        /**
         * @var \DDD\Service\Warehouse\Storage $service
         */
        $service = $this->getServiceLocator()->get('service_warehouse_storage');
        $storageId = $this->params()->fromRoute('id', 0);
        $itemId = $this->params()->fromRoute('param', 0);

        if ($itemId && $storageId) {
            $service->deleteThreshold($itemId);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
        } else {
            Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('warehouse/storage', ['controller' => 'storage', 'action' => 'edit', 'id' => $storageId]);
    }

}
