<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Library\Constants\TextConstants;
use Library\Utility\Helper;


/**
 *
 * @author developer
 *
 */
class Review extends ApartmentBaseController
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Apartment\Review $service
         */
        $service = $this->getServiceLocator()->get( 'service_apartment_review' );
        $options = $service->getOptions($this->apartmentId);
        $data    = $service->getDatableData($this->apartmentId, $this->url());

		return new ViewModel (
            [
                'aaData'          => json_encode($data),
                'apartmentId'     => $this->apartmentId,
                'apartmentStatus' => $this->apartmentStatus,
                'options'         => $options,
            ]
        );
	}

    public function deleteAction()
    {
		$reviewId = (int)$this->params()->fromRoute('review_id', 0);

        if ($reviewId > 0) {
            $service = $this->getServiceLocator()->get('service_apartment_review');
            $service->deleteReview($reviewId, $this->apartmentId);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);

        } else {
            Helper::setFlashMessage(['success' => TextConstants::SERVER_ERROR]);
        }

		return $this->redirect()->toRoute('apartment/review', ['apartment_id' => $this->apartmentId]);
	}

    public function statusAction()
    {
		$reviewID = (int) $this->params()->fromRoute('review_id', 0);
		$status   = $this->params()->fromRoute('status', '0');
        if ($reviewID > 0) {
            $service = $this->getServiceLocator()->get( 'service_apartment_review' );

            $service->updateReview($reviewID, $status, $this->apartmentId);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);

        } else {
            Helper::setFlashMessage(['success' => TextConstants::SERVER_ERROR]);
        }
		return $this->redirect()->toRoute('apartment/review', ['apartment_id' => $this->apartmentId]);
	}

    public function ajaxSaveReviewCategoryAction()
    {
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR
        ];

        $request = $this->getRequest();

        try{
            if ($request->isXmlHttpRequest()) {
                $reviewId   = $request->getPost('reviewId', 0);
                $selectData = json_decode($request->getPost('selectData', ''));

                if ($reviewId > 0) {
                    $service = $this->getServiceLocator()->get('service_apartment_review');

                    if ($service->saveReviewCategory($reviewId, $selectData)) {
                        $result['status'] = 'success';
                        $result['msg']    = TextConstants::SUCCESS_UPDATE;
                    }
                }
            }
        } catch (\Exception $e) {

        }
        return new JsonModel($result);
    }
}
