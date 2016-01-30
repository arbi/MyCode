<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Website\Form\ReviewForm;
use Website\Form\InputFilter\ReviewFilter;
use Library\Validator\ClassicValidator;
use Library\Constants\TextConstants;

class ReviewController extends WebsiteBase
{
    public $reviewHash;

    public function indexAction()
    {
        if(!isset($_SERVER['HTTPS'])) {
            $this->getResponse()->setStatusCode(404);
            $view = new ViewModel();
            return $view->setTemplate('error/404');
        }

        $this->reviewHash = $this->params()->fromQuery('code');

        $bookingData = $this->getBookingData();

        if ($this->reviewHash === NULL
            OR !$bookingData)
        {
            return $this->redirect()->toRoute('home')->setStatusCode('301');
        }

        $form = new ReviewForm;
        $form->prepare();

        $question['questionId'] = 994;
        $question['answersId'] = [995,996,997,998,999];

        $this->layout()->userTrackingInfo = [
            'res_number' => $bookingData->getResNumber(),
            'partner_id' => $bookingData->getPartnerId(),
        ];

        return new ViewModel([
            'reviewData'    => $bookingData,
            'form'          => $form,
            'question'      => $question,
            'reviewHash'    => $this->reviewHash,
            'actionMethod'  => 'ajax-save-review',
            'thankYouUrl'   => '/add-review/thank-you'
        ]);
    }

    public function ajaxSaveReviewAction()
    {
        $result = array('status' => 'success', 'result' => '');
        try {
            $request = $this->getRequest();
            if($request->isXmlHttpRequest()) {
                $form = new ReviewForm;
                $form->setInputFilter(new ReviewFilter());
                if ($request->isPost()) {
                    $filter = $form->getInputFilter();
                    $form->setInputFilter($filter);
                    $data = $request->getPost();
                    $form->setData($data);
                    if ($form->isValid()) {
                        $vData = $form->getData();

                        /**
                         * @var \DDD\Service\Website\Review $reviewService
                         */
                        $reviewService = $this->getServiceLocator()->get('service_website_review');

                        $result = $reviewService->addNewReview($vData);
                    } else {
                        $messages = '';
                        $errors = $form->getMessages();
                        foreach ($errors as $key => $row) {
                            if (!empty($row)) {
                                $messages .= ucfirst($key) . ' ';
                                $messages_sub = '';
                                foreach ($row as $keyer => $rower) {
                                    $messages_sub .= $rower;
                                }
                                $messages .= $messages_sub . '<br>';
                            }
                        }
                        $error = $messages;
                        $result['status'] = 'error';
                        $result['result'] = $error;
                    }
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = TextConstants::ERROR;
        }
        return new JsonModel($result);
    }

    public function thankYouAction()
    {

        return new ViewModel();
    }

    private function getBookingData()
    {
        if (!$this->reviewHash || !ClassicValidator::validateAlnum($this->reviewHash)) {
            return FALSE;
        }

        /**
         * @var \DDD\Service\Website\Review $reviewService
         */
        $reviewService = $this->getServiceLocator()->get('service_website_review');

        return $reviewService->getBookingData($this->reviewHash);
    }
}
