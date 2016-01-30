<?php

namespace Apartment\Controller;

use DDD\Service\Apartment\ReviewCategory as ReviewCategoryService;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;

use Apartment\Form\ApartmentReviewCategory as ApartmentReviewCategory;
use Apartment\Form\InputFilter\ApartmentReviewCategoryFilter;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class ApartmentReviewCategoryController extends ControllerBase
{
    public function indexAction()
    {
        $service = $this->getApartmentReviewCategoryService();
        $data = $service->getDatatableData();
	    return new ViewModel([
            'aaData'  => json_encode($data)
        ]);
    }

	public function editAction()
    {
		$service = $this->getApartmentReviewCategoryService();
		$request = $this->getRequest();
		$categoryId = $this->params()->fromRoute('id', 0);

		$form = new ApartmentReviewCategory($categoryId);
		$form->setInputFilter(new ApartmentReviewCategoryFilter());
		$form->prepare();

		if ($request->isPost()) {
			$postData = $request->getPost();
			$form->setData($postData);

			if ($form->isValid()) {
				if ($redirectId = $service->saveReviewCategory($postData, $categoryId)) {
					Helper::setFlashMessage(['success' => ($categoryId > 0) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
					$this->redirect()->toRoute('apartment_review_category', ['controller' => 'apartment-review-category']);
				} else {
					Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
				}
			} else {
				Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
			}

			$form->populateValues($postData);
		} else {
			if ($categoryId) {
				$categoryData = $service->getReviewCategoryData($categoryId);

				if ($categoryData) {
					$form->populateValues($categoryData);
				} else {
					$this->redirect()->toRoute('apartment_review_category', ['controller' => 'apartment-review-category']);
				}
			}
		}

		return new ViewModel([
			'form' => $form,
			'id' => $categoryId,
			'status' => (isset($pspData) ? (int)$pspData['active'] : false)
		]);
	}

    public function deleteAction()
    {
        $request = $this->getRequest();

        $response = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

        if ($request->isXmlHttpRequest()) {
            $categoryId = (int)$request->getPost('id');

            /**
             * @var ReviewCategoryService $reviewCategoryService
             */
            $reviewCategoryService = $this->getServiceLocator()->get('service_apartment_review_category');

            $result = $reviewCategoryService->delete($categoryId);

            if ($result) {
                Helper::setFlashMessage(['success' => 'Review Category Was Successfully Removed.']);
            } else {
                $response['status'] = 'error';
                $response['msg']    = 'Problem Caused While Trying To Remove.';
            }
        } else {
            $response['status'] = 'error';
            $response['msg']    = 'Problem Caused While Trying To Remove.';
        }

        return new JsonModel($response);
    }

	/**
	 * @return \DDD\Service\Apartment\ReviewCategory
	 */
	private function getApartmentReviewCategoryService()
    {
        return $this->getServiceLocator()->get('service_apartment_review_category');
    }
}
