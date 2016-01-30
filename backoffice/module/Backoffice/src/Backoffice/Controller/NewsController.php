<?php
namespace Backoffice\Controller;

use Library\Constants\DbTables;
use Library\Controller\ControllerBase;
use Library\Constants\DomainConstants;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Constants\Constants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Json\Expr;

use Backoffice\Form\News as NewsForm;
use Backoffice\Form\InputFilter\NewsFilter as NewsFilter;

class NewsController extends ControllerBase
{
    protected $_newsService = NULL;

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
    	$router = $this->getEvent()->getRouter();
		$ajaxSourceUrl = $router
            ->assemble([
                'controller' => 'news',
                'action'     => 'get-json'
            ], ['name' => 'backoffice/default']
        );


    	return new ViewModel([
            'ajaxSourceUrl'    => $ajaxSourceUrl
        ]);
    }

    /**
     * @return JsonModel
     */
    public function getJsonAction()
    {

    	// get query parameters
    	$queryParams = $this->params()->fromQuery();

    	// get products data
    	$productService = $this->getNewsService();
    	$newsList = $productService->getNewsResult($queryParams);

    	// prepare products array
    	$filteredArray = [];
    	$router = $this->getEvent()->getRouter();
    	foreach ($newsList as $news){
    		$editUrl = $router->assemble(                [
                'controller' => 'news',
                'action'     => 'edit',
                'id'         => $news->getId()
            ], ['name' => 'backoffice/default']);

    		$result = [
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($news->getDate())),
                $news->getEn_title(),
                '<a class="btn btn-xs btn-info" target="_blank" href="//'.DomainConstants::WS_DOMAIN_NAME . '/news/' . Helper::urlForSite($news->getEn_title()) . '">View</a>',
                '<a class="btn btn-xs btn-primary" href="'.$editUrl.'" data-html-content="Edit"></a>'
            ];

    		$filteredArray[] = $result;
    	}

    	// build response
    	$responseArray = [
            "aaData" => $filteredArray
    	];

    	return new JsonModel($responseArray);
    }


    protected function getForm($id)
    {
        $service = $this->getNewsService();
        $news_data = '';
        if ($id > 0) {
            $news_data = $service->getNewsById((int)$id);
        }

        $form = new NewsForm('news', $news_data);
        return $form;
    }

    public function editAction()
    {
        $id      = $this->params()->fromRoute('id', 0);
        $form    = $this->getForm($id);
        $url = '';

        if ($id > 0) {
            if (!$this->getServiceLocator()->get('dao_geolocation_details')->checkRowExist(DbTables::TBL_NEWS, 'id', $id)) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('backoffice/default', ['controller' => 'news']);
            }
            $url = '//'.DomainConstants::WS_DOMAIN_NAME . '/news/' . Helper::urlForSite($form->get('title')->getValue());
        }

        return [
            'newsForm' => $form,
            'edit'     => $id,
            'urlSie'   => $url
        ];
    }

    public function ajaxsaveAction()
    {
        $result = [
            'result' => [],
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                $id       = (int)$request->getPost('edit_id');
                $form     = $this->getForm($id);
                $messages = '';
                $form->setInputFilter(new NewsFilter());

               if ($request->isPost()) {
                   $service = $this->getNewsService();
                   $filter  = $form->getInputFilter();
                   $form->setInputFilter($filter);
                   $data = $request->getPost();

                   if (preg_match('/\./', $data['title'])) {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::SERVER_ERROR;
                        return new JsonModel($result);
                   }

                   $form->setData($data);

                   if ($form->isValid()) {
                       $vData = $form->getData();
                       $newsId = $service->newsSave($vData, $id);

                       if ($newsId) {
                            $result['id'] = $id;
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);

                         if (!$id) {
                            $result['id'] = $newsId;
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                        }
                    } else {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::SERVER_ERROR;
                    }

                   } else {
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

                       $result['status'] = 'error';
                       $result['msg']    = $messages;
                   }
               }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxdeleteAction()
    {
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

        try {
            $request = $this->getRequest();
            $id      = (int)$request->getPost('id');

            if ($request->isXmlHttpRequest() && $id > 0 ) {
                $service  = $this->getNewsService();
                $response = $service->deleteNews($id);
                if ($response) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
                } else {
                   $result['status'] = 'error';
                   $result['msg']    = TextConstants::SERVER_ERROR;
                }
            } else {
                throw new \Exception("Bad data");
            }
        } catch (\Exception $e){
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    private function getNewsService()
    {
        if (!$this->_newsService) {
            $this->_newsService =
                $this->getServiceLocator()->get('service_news');
        }

        return $this->_newsService;
    }
}
