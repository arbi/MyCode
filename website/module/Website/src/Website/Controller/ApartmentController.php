<?php

namespace Website\Controller;

use DDD\Service\Website\Apartment;
use Library\Controller\WebsiteBase;
use Zend\Debug\Debug;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use Library\Validator\ClassicValidator;
use Library\Utility\Helper;
use Library\Constants\DomainConstants;

class ApartmentController extends WebsiteBase
{
    public function indexAction()
    {
        /**
         * @var Apartment $apartmentService
         */
        try {
            if (   !($pageSlug = $this->params()->fromRoute('apartmentTitle'))
                || !ClassicValidator::checkApartmentTitle($pageSlug)
            ) {
                $viewModel = new ViewModel();
                $this->getResponse()->setStatusCode(404);
                return $viewModel->setTemplate('error/404');
            }

            /* @var $apartmentService \DDD\Service\Website\Apartment */
            $apartmentService = $this->getApartmentService();
            $apartment        = $apartmentService->getApartment($pageSlug);

            if (!$apartment) {
                $this->getResponse()->setStatusCode(404);
                $viewModel = new ViewModel();

                return $viewModel->setTemplate('error/404');
            }

            $request      = $this->getRequest();
            $data         = $request->getQuery();

            $data['slug'] = $pageSlug;
            $filtreData   = $apartmentService->filterQueryData($data);
            $reviewCount  = false;

            if ($filtreData) {
                $apartment['otherParams']['arrival']   = Helper::dateForSearch($data['arrival']);
                $apartment['otherParams']['departure'] = Helper::dateForSearch($data['departure']);

            }

            if (isset($apartment['general']['aprtment_id'])) {
                $reviewCount = $apartmentService->apartmentReviewCount($apartment['general']['aprtment_id']);
            }

            $show_reviews = false;
            $reviews = [];
            $apartment['otherParams']['guest'] = (int)$data['guest'];

            if (   isset($data['show']) && $data['show'] == 'reviews'
                && isset($apartment['general']['aprtment_id'])
                && $apartment['general']['aprtment_id'] > 0
            ) {
                $show_reviews  = true;
                $reviewsResult = $apartmentService->apartmentReviewList(['apartment_id' => $apartment['general']['aprtment_id']], true);

                if ($reviewsResult && $reviewsResult['result']->count() > 0) {
                    $reviews = $reviewsResult['result'];
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Website: Apartment Page Failed');
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setVariable('view_currency', 'yes');

        return new ViewModel([
            'general'             => $apartment['general'],
            'amenities'           => $apartment['amenities'],
            'facilities'          => $apartment['facilities'],
            'otherParams'         => $apartment['otherParams'],
            'secure_url_booking'  => 'https://'.DomainConstants::WS_SECURE_DOMAIN_NAME.'/booking',
            'show_reviews'        => $show_reviews,
            'reviews'             => $reviews,
            'reviewCount'         => $reviewCount,
            'sl'                  => $this->getServiceLocator(),
            'apartelId'           => (int)$data['apartel_id'] > 0 ? (int)$data['apartel_id'] : 0,
        ]);
    }

    public function apartmentSearchAction()
    {
        $result  = [
            'status' => 'success',
            'result' => ''
        ];

        $request = $this->getRequest();

        try{
            if ($request->isXmlHttpRequest()) {
                $data          = $request->getPost();
                $searchService = $this->getApartmentService();
                $searchRespons = $searchService->apartmentSearch($data);

                if ($searchRespons['status'] == 'success' && $searchRespons['result']) {
                    $result['result'] = $searchRespons['result'];
                } else {
                    $forLink = $searchRespons['result'];

                    $urlRedirect = $this->url()->fromRoute(
                        'search',
                        ['controller' =>  'search', 'action' => 'index'],
                        [
                            'query' => [
                                'city'      => $forLink['city'],
                                'guest'     => $forLink['guest'],
                                'arrival'   => $forLink['arrival'],
                                'departure' => $forLink['departure']
                            ]
                        ]
                    );
                    $result['status'] = 'error';
                    $result['result'] = $urlRedirect;
              }
           } else {
                return $this->redirect()->toRoute('home');
           }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxApartmentReviewAction()
    {
        $result = [
            'status'     => 'success',
            'result'     => '',
            'totalPages' => 1,
            'total'      => 0
        ];

        $request = $this->getRequest();
        try{
            if($request->isXmlHttpRequest()) {
                $data             = $request->getQuery();
                $apartmentService = $this->getApartmentService();
                $respons          = $apartmentService->apartmentReviewList($data);
                if(!$respons || !$respons['result']->count()) {
                     $result['status'] = 'error';
                } else {
                    $reviewList           = $respons['result'];
                    $totalPages           = $respons['totalPages'];
                    $partial              = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                    $reviewes             = $partial('partial/review.phtml', array('reviewList' => $reviewList));
                    $result['result']     = $reviewes;
                    $result['totalPages'] = $totalPages;
                    $result['total']      = $respons['total'];
                }
            } else {
                return $this->redirect()->toRoute('home');
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR;
        }
        return new JsonModel($result);
    }

    private function getApartmentService(){
        return $this->getServiceLocator()->get('service_website_apartment');
    }

}
