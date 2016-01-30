<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\Debug\Debug;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use Library\Validator\ClassicValidator;
use DDD\Service\Website\Apartel;

class ApartelController extends WebsiteBase
{
    public function indexAction()
    {
        try {
            if (!($pageSlug = $this->params()->fromRoute('apartel-route'))  || !ClassicValidator::checkApartmentTitle($pageSlug)) {
                $viewModel = new ViewModel();
                $this->getResponse()->setStatusCode(404);
                return $viewModel->setTemplate('error/404');
            }

            $showReviews = $this->params()->fromQuery('reviews', '');

            /**
             * @var \DDD\Service\Website\Apartel $apartelService
             */
            $apartelService = $this->getServiceLocator()->get('service_website_apartel');
            $apartelData = $apartelService->getApartel($pageSlug);

            if (!$apartelData) {
                $this->getResponse()->setStatusCode(404);
                $viewModel = new ViewModel();

                return $viewModel->setTemplate('error/404');
            }

            return [
                'data'         => $apartelData['data'],
                'options'      => $apartelData['options'],
                'reviews'      => $apartelData['reviews']['result'],
                'reviewCount'  => $apartelData['reviews']['total'],
                'roomTypes'    => $apartelData['roomTypes'],
                'reviewsScore' => $apartelData['reviewsScore'],
                'showReviews'  => $showReviews
            ];
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Website: Apartel Page Failed');
            return $this->redirect()->toRoute('home');
        }
    }

    public function getMoreReviewsAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $this->getRequest()->getPost();

            /**
             * @var \DDD\Service\Website\Apartel $apartelService
             */
            $apartelService = $this->getServiceLocator()->get('service_website_apartel');
            $reviewsData = $apartelService->getReviews(
                $postData['apartel_id'],
                Apartel::DEFAULT_REVIEWS_COUNT,
                $postData['current_count']
            );

            $result = [
                'status'    => 'success',
                'the_end'   => false
            ];

            if ($reviewsData && count($reviewsData) > 0) {
                $apartelDetails = $apartelService->getApartelGeneralData($postData['apartel_id']);

                $partialHelper = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                $partialResponse = $partialHelper('website/apartel/partial/reviews.phtml', [
                    'reviews' => $reviewsData['result'],
                    'apartel' => $apartelDetails
                ]);

                $result['result'] = $partialResponse;
                $result['reviews_count'] = $postData['current_count'] + count($reviewsData['result']);

                if ($result['reviews_count'] == $reviewsData['total']) {
                    $result['the_end'] = true;
                }
            } else {
                $result = [
                    'status'    => 'error',
                    'msg'       => TextConstants::SERVER_ERROR,
                ];
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Website: Cannot get more reviews for apartel');

            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR,
            ];
        }

        return new JsonModel($result);
    }
}
