<?php
namespace Backoffice\Controller;

use Library\Controller\ControllerBase;
use Library\Constants\DomainConstants;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Json\Expr;
use Library\Constants\Objects;
use Backoffice\Form\Blog as BlogForm;
use Backoffice\Form\InputFilter\BlogFilter as BlogFilter;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Constants\Constants;

class BlogController extends ControllerBase
{
    protected $_blogService = NULL;

    public function indexAction() {

        // get products data
        $productService = $this->getBlogService();
        $blogs = $productService->getBlogResult();

        // prepare products array
        $filteredArray = [];
        $router = $this->getEvent()->getRouter();
        foreach ($blogs as $blog){
            $editUrl = $router->assemble(
                [
                    'controller' => 'blog',
                    'action'     => 'edit',
                    'id'         => $blog->getId()
                ],
                ['name' => 'backoffice/default']
            );

            $result = [
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($blog->getDate())),
                $blog->getTitle(),
                '<a class="btn btn-xs btn-info" target="_blank" href="//'.DomainConstants::WS_DOMAIN_NAME . '/blog/' . Helper::urlForSite($blog->getTitle()) . '">View</a>',
                '<a class="btn btn-xs btn-primary" href="' . $editUrl . '" data-html-content="Edit"></a>'
            ];

            $filteredArray[] = $result;
        }

    	return new ViewModel(
    		[
    			'aaData'    => json_encode($filteredArray)
    		]
    	);
    }


    protected function getForm($id)
    {
        $service   = $this->getBlogService();
        $blog_data = '';

        if ($id > 0) {
            $blog_data = $service->getBlogById((int)$id);
        }

        $form = new BlogForm('blog', $blog_data);

        return ['form'=>$form, 'data'=>$blog_data];
    }

    public function editAction()
    {
        $id   = $this->params()->fromRoute('id', 0);
        $form = $this->getForm($id);
        $url  = '';
        if ($id > 0) {
            if (!$form['data']) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('backoffice/default', ['controller' => 'blog']);
            }
            $url  = '//'.DomainConstants::WS_DOMAIN_NAME . '/blog/' . Helper::urlForSite($form['data']->getTitle());
        }
        return [
            'blogForm'  => $form['form'],
            'edit'      => $id,
            'imgDomain' => DomainConstants::IMG_DOMAIN_NAME,
            'urlSie'    => $url
        ];
    }

    public function ajaxUploadImageAction()
    {
        $result = [
            'src'   => '',
            'status'=> 'success',
            'msg'   => TextConstants::SUCCESS_UPDATE
        ];

        try
        {
            $uploadService = $this->getBlogService();
            $request = $this->getRequest();
            if($request->isXmlHttpRequest()) {
                $logoFile = $request->getFiles();
                $tempName = $uploadService
                    ->saveToTemp([$logoFile['file']]);
                $result   = $tempName;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
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
        try{
            if($request->isXmlHttpRequest()) {
               $id   = (int)$request->getPost('edit_id');
               $form = $this->getForm($id);
               $form = $form['form'];
               $messages = '';
               $form->setInputFilter(new BlogFilter());
               if ($request->isPost()) {
                   $service = $this->getBlogService();
                   $filter  = $form->getInputFilter();

                   $form->setInputFilter($filter);
                   $data = $request->getPost();
                   $form->setData($data);
                   if ($form->isValid()) {
                       $vData = $form->getData();

                       $respons_db = $service->blogSave($vData, $id);
                       if (is_array($respons_db)) {
                           if (!$id) {
                                $result['id']  = $respons_db[0];
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
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxremoveAction()
    {
        $result = [
            'src'   => '',
            'status'=> 'success',
            'msg'   => 'Image successfully removed'
        ];

        try
        {
            $request = $this->getRequest();
            $id      = (int)$request->getPost('id');
            if ($request->isXmlHttpRequest() && ($id > 0)) {
                $service = $this->getBlogService();
                $service->removeImage($id);
            } else {
                throw new \Exception("Bad data");
            }
        } catch (Exception $e){
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxdeleteAction()
    {
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_DELETE
        ];

        try
        {
            $request = $this->getRequest();
            $id      = (int)$request->getPost('id');

            if ($request->isXmlHttpRequest() && ($id > 0) ) {
                $service  = $this->getBlogService();
                $response = $service->deleteBlog($id);
                if($response){
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
                } else {
                   $result['status'] = 'error';
                   $result['msg']    = TextConstants::SERVER_ERROR;
                }
            } else {
                throw new \Exception("Bad data");
            }
        } catch (Exception $e){
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    /**
     * @return \DDD\Service\Blog
     */
    private function getBlogService()
    {
        if(!$this->_blogService){
            $this->_blogService =
                $this->getServiceLocator()->get('service_blog');
        }

        return $this->_blogService;
    }
}
