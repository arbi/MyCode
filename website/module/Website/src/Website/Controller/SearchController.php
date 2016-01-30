<?php

namespace Website\Controller;

use DDD\Service\Website\Search;
use Library\Controller\WebsiteBase;
use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use Library\Validator\ClassicValidator;

class SearchController extends WebsiteBase
{
    public function indexAction()
    {
        /**
         * @var Request $request
         */
        $error         = $viewAllApartment = false;
        $apartelList   = $allApartment = $options = [];
        $searchService = $this->getSearchService();
        $request       = $this->getRequest();
        $data          = $request->getQuery();
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $data[$key] = strip_tags($value);
            }
        }

        try {
            if (!empty($data) && ((isset($data['city']) && $data['city']) || (isset($data['apartel']) && $data['apartel']))) {
                $options = $searchService->getOptions($data);

                if ($options['status'] != 'success') {
                    $error = $options['msg'];
                } else if (isset($data['show']) && $data['show'] == 'all') {
                    $apartmentResult = $searchService->searchApartmentList($data, true);

                    if ($apartmentResult['status'] != 'success') {
                        $error = $apartmentResult['msg'];
                    } else {
                        $viewAllApartment = true;
                        $allApartment['list'] = $apartmentResult['apartelList'];
                        $allApartment['options'] = $apartmentResult['options'];
                    }
                }
            } else {
                $viewModel = new ViewModel();
                $this->getResponse()->setStatusCode(404);
                return $viewModel->setTemplate('error/404');
            }
        } catch (\Exception $exc) {
            $error = TextConstants::ERROR;
        }

        $this->layout()->setVariable('view_currency', 'yes');

        return new ViewModel([
            'apartels'         => $apartelList,
            'error'            => $error,
            'options'          => $options,
            'allApartment'     => $allApartment,
            'viewAllApartment' => $viewAllApartment
        ]);
    }

    public function ajaxSearchAction()
    {
        /**
         * @var Request $request
         * @var Search $searchService
         */
        $request = $this->getRequest();
        $result = [
            'status'     => 'success',
            'result'     => '',
            'totalPages' => 1
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $data = $request->getQuery()->toArray();
                $validateTags = ClassicValidator::checkScriptTags($data);

                if (!$validateTags) {
                    return  new JsonModel([
                        'status' => 'error',
                        'msg'    => TextConstants::ERROR,
                    ]);
                }

                array_walk($data, 'strip_tags');

                $searchService = $this->getSearchService();
                $searchResponse = $searchService->searchApartmentList($data);
                $hasDate       = true;

                if ($searchResponse['status'] != 'success') {
                     $result['status'] = 'error';
                     $result['result'] = $searchResponse['msg'];
                } else {
                    if (empty($data['arrival']) && empty($data['departure'])) {
                        $hasDate = false;
                    }
                    $apartelList = $searchResponse['apartelList'];
                    $visitorLoc  = $searchResponse['visitorLoc'];
                    $totalPages  = $searchResponse['totalPages'];
                    $partial     = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');

                    $response = $partial('partial/search.phtml', [
                        'apartelList'   => $apartelList,
                        'options'       => $searchResponse['options'],
                        'visitorLoc'    => $visitorLoc,
                        'hasPagination' => true,
                        'sl'            => $this->getServiceLocator(),
                        'hasDate'       => $hasDate
                    ]);

                    $result['result']        = $response;
                    $result['totalPages']    = $totalPages;
                    $result['paginatinView'] = $searchResponse['paginatinView'];
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR . $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function ajaxAutocompleteSearchAction()
    {
        /**
         * @var Request $request
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'result' => [],
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $txt = $request->getPost('txt');

                if (!ClassicValidator::validateAutocomplateSearch($txt)) {
                    return new JsonModel($result);
                }

                $searchService = $this->getSearchService();
                $searchRespons = $searchService->autocompleteSearch($txt);
                $result['result'] = $searchRespons;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = [];
        }

        return new JsonModel($result);
    }

    /**
     *
     * @return \DDD\Service\Website\Search
     */
    public function getSearchService()
    {
    	return $this->getServiceLocator()->get('service_website_search');
    }
}
