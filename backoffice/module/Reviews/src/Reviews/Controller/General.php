<?php

namespace Reviews\Controller;

use League\Flysystem\Exception;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;

use Reviews\Form\SearchForm;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class General extends ControllerBase
{

    public function indexAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         * @var \DDD\Service\ApartmentGroup\Main $apartmentGroupService
         */

        $apartmentGroupService = $this->getServiceLocator()->get( 'service_apartment_group_main' );
        $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );

        $options = [];

        $options['allApartmentGroups'] = $apartmentGroupService->getAllGroupNamesButApartelsAtFirst();
        $options['tags'] = $reviewService->getAllReviewCategories();

        $form = new SearchForm('search-form', $options);
        $form->prepare();
        return new ViewModel (
            [
                'form'    => $form,
            ]
        );
    }

    public function getDatatableDataAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         */
        $queryParams    = $this->params()->fromQuery();
        $iDisplayStart  = $queryParams["iDisplayStart"];
        $iDisplayLength = $queryParams["iDisplayLength"];
        $sortCol        = (int)$queryParams['iSortCol_0'];
        $sortDir        = $queryParams['sSortDir_0'];


        $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );
        $result = $reviewService->getSearchResult(
            $iDisplayStart,
            $iDisplayLength,
            $queryParams,
            $sortCol,
            $sortDir
        );
        $responseArray = [
            'iTotalRecords'        => $result['count'],
            'iTotalDisplayRecords' => $result['count'],
            'iDisplayStart'        => $iDisplayStart,
            'iDisplayLength'       => (int)$iDisplayLength,
            "aaData"               => $result['result']
        ];
        return new JsonModel(
            $responseArray
        );
    }

    public function getChartInfoAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         */

        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {

            try {
                $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );
                $post = $request->getPost();
                $result = $reviewService->getChartInfo($post);
                $result = [
                    'status' => 'success',
                    'data'   => $result
                ];
            } catch (\Exception $ex) {

            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getCategoriesInfoAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         */

        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {

            try {
                $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );
                $post = $request->getPost();
                $result = $reviewService->getCategoriesInfo($post);
                $result = [
                    'status' => 'success',
                    'data'   => $result
                ];
            } catch (\Exception $ex) {

            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function changeReviewCategoriesAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         */

        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {

            try {
                $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );
                $post = $request->getPost();
                $reviewService->changeReviewCategoriesInfo($post);
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_UPDATE
                ];
            } catch (\Exception $ex) {

            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function changeStatusAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         */

        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {

            try {
                $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );
                $post = $request->getPost();
                $glyphicon = $reviewService->changeStatus($post['review_id'], $post['status']);
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_UPDATE,
                    'glyphicon' => $glyphicon
                ];
            } catch (\Exception $ex) {

            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function deleteAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $reviewService
         */
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];
        $request = $this->getRequest();
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $reviewService = $this->getServiceLocator()->get( 'service_apartment_review' );
                $post = $request->getPost();
                $reviewService->delete($post['review_id']);
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_UPDATE . '</br>Refreshing data...',
                ];
            } catch (\Exception $ex) {
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }
}
