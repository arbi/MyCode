<?php
namespace Backoffice\Controller;

use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Library\Constants\Roles;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Json\Expr;

use Backoffice\Form\SearchTranslationForm;
use Backoffice\Form\Translation as TranslationForm;
use Backoffice\Form\InputFilter\TranslationFilter as TranslationFilter;

use DDD\Service\Translation as TranslationService;

class TranslationController extends ControllerBase
{
    protected $_traslationSservice = null;

    public function indexAction()
    {
        $permission = [];
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        if ($auth->hasRole(Roles::ROLE_CONTENT_EDITOR_PRODUCT)) {
            $permission[] = 3;
        }
        if ($auth->hasRole(Roles::ROLE_CONTENT_EDITOR_TEXTLINE)) {
            $permission[] = 1;
        }
        if ($auth->hasRole(Roles::ROLE_CONTENT_EDITOR_LOCATION)) {
            $permission[] = 2;
        }
        if (empty($permission)) {
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'home']);
        }

        $router = $this->getEvent()->getRouter();
        $ajaxSourceUrl = $router->assemble(
            [
                'controller' => 'translation',
                'action'     => 'get-translation-json'
            ],
            ['name' => 'backoffice/default']
        );

        /**
         * @var \DDD\Service\Translation
         */
        $service = $this->getTranslationService();

        $pageTypesList = $service->getUniversalPages();
        sort($permission);

	    $form = new SearchTranslationForm('search-translation', $pageTypesList, $permission);

        $viewStatstics     = 'no';
        $viewStatsticsLang = '';
        $viewStatsticsType = '';

        $params = $this->params()->fromRoute('id', '');

        if ($params != '') {
           $param               = explode('-', $params);
           $translationType     = (isset($param[0]) && $param[0] != '') ? (int)$param[0] : '';
           $translationStatus   = (isset($param[1]) && $param[1] != '') ? $param[1] : '';
           $translationLang     = (isset($param[2]) && $param[2] != '') ? $param[2] : '';

           if ($translationType != '') {
              $category = $form->get('category');
              $category->setValue($translationType);
           }

           if ($translationStatus != '') {
              $status = $form->get('status');
              $status->setValue($translationStatus);
           }

           $viewStatstics     = 'yes';
           $viewStatsticsLang = $translationLang;
           $viewStatsticsType = $translationType;
        }


        $formTemplate  = 'form-templates/search-translation';
        //Source code
        $viewModelForm = new ViewModel();
	    $viewModelForm->setVariables(['form' => $form]);
	    $viewModelForm->setTemplate($formTemplate);

        $viewModel = new ViewModel([
            'ajaxSourceUrl'     => $ajaxSourceUrl,
            'viewStatstics'     => $viewStatstics,
            'viewStatsticsLang' => $viewStatsticsLang,
            'viewStatsticsType' => $viewStatsticsType,
            'permission'        => (in_array(1, $permission) ? 1 : 2),
            'hasCreatorRole'    => $auth->hasRole(Roles::ROLE_UNIVERSAL_TEXTLINE_CREATOR)
    	]);

    	$viewModel->addChild($viewModelForm, 'formOutput');
    	$viewModel->setTemplate('backoffice/translation/index');
    	return $viewModel;
    }

    public function getTranslationJsonAction() {
    	/**
		 * @var \DDD\Service\Booking
         * @var \DDD\Service\Translation $service
    	 */
    	$service = $this->getTranslationService();

    	// get query parameters
    	$queryParams = $this->params()->fromQuery();
    	$textlines = $service->getTranslationBasicInfo($queryParams);
        $aaData = $textlines['result'];
    	$count  = $textlines['count'];

    	$responseArray = [
            'iTotalRecords'        => $count,
            'iTotalDisplayRecords' => $count,
            "aaData"               => $aaData
    	];

    	return new JsonModel(
    		$responseArray
    	);
    }

    protected function getForm($params, $getParams)
    {
        $service       = $this->getTranslationService();
        $data          = '';
        $title         = '';
        $options       = [];
        $selectedPages = [];

        if (isset($params['id']) && $params['id'] > 0) {
            $response = $service->forTranslation($params);

            if (!$response) {
                return false;
            }

            $selectedPages = $service->getUniversalTextlinePages($params['id']);
            $data          = $response['result'];
            $title         = $data->getType();
        } else {
            $options['pages'] = $service->getUniversalPages();
        }

        $allPageTypes = [];
        $allPageTypesList = $service->getUniversalPages();
        foreach ($allPageTypesList as $row) {
            $allPageTypes[$row->getId()] = $row->getName();
        }

        $form = new TranslationForm('translation_form', $data, $options, $getParams, $allPageTypes, $selectedPages);
        return [
            'form'          => $form,
            'title'         => $title,
            'data'          => $data,
            'pageTypes'     => $allPageTypes,
            'selectedPages' => $selectedPages
        ];
    }

    public function viewAction()
    {
        $paramAll = $this->params()->fromRoute('id', '');

        $params = [
            'permisson_module' => ''
        ];

        $locationView    = '';
        $locationOptions = '';
        $locationType    = '';
        $language        = '';
        $error           = ['error' => 'error'];
        $editMode        = true;

        $param          = explode('-', $paramAll);
        $params['type'] = (isset($param[0]) && $param[0] != '') ? $param[0] : '';
        $params['id']   = (isset($param[1]) && $param[1] > 0) ? $param[1] : 0;
        $params['lng']  = (isset($param[2])) ? $param[2] : '';

        if (!isset($params['type'])) {
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'translation']);
        }

        $service = $this->getTranslationService();
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');

        if ($params['type'] == 'p' && $auth->hasRole(Roles::ROLE_CONTENT_EDITOR_PRODUCT)) {
            $params['permisson_module'][] = 'p';
        }
        if ($params['type'] == 'u' && $auth->hasRole(Roles::ROLE_CONTENT_EDITOR_TEXTLINE)) {
            $params['permisson_module'][] = 'u';
        }
        if ($params['type'] == 'l' && $auth->hasRole(Roles::ROLE_CONTENT_EDITOR_LOCATION)) {
            $params['permisson_module'][] = 'l';
        }

        if (!isset($params['permisson_module']) || empty($params['permisson_module'])) {
            $editMode = false;
        }

        if ($params['type'] == 'l') {
            $params['locationOptions'] = (isset($param[3])) ? $param[3] : '';
            $locationOptions           = $params['locationOptions'];
            $params['locationType']    = (isset($param[4])) ? $param[4] : '';
            $locationType              = $params['locationType'];
            if(!in_array($params['locationOptions'], TranslationService::$LOCATION_OPTION)) {
               return $error;
            }

            $locationView = ' ' .
                (($params['locationType'] == 'poi') ? strtoupper($params['locationType']) :  ucfirst($params['locationType'])) . ' - ' .
                (($params['locationOptions'] == 'info') ? 'Information' : 'Name');
        }

        $getForm = $this->getForm(
            $params,
            [
                'language'         => $params['lng'],
                'type_translation' => $params['type'],
                'locationOption'   => $locationOptions,
                'locationType'     => $locationType
            ]
        );

        if (!$getForm) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'translation']);
        }

        switch ($params['type']) {
            case 'l':
                $textlineTitle = $getForm['title'] . ' ' . $locationView . ' (' . $params['id'] . ')';
                break;
            case 'p':
                $prodType = $service->getDescriptionType($getForm['data']->getOther());
                $textlineTitle = $getForm['title'] . ' - ' . $prodType . ' (' . $params['id'] . ')';
                break;

            case 'u':
                $textlineTitle = 'Textline (' . $params['id'] . ')';
                break;
        }

        $typeTranslataion = ($params['type'] == 'l') ? 'Location': (($params['type'] == 'p') ? 'Product' : 'Universal');
        $prodType = '';

        return [
            'translationForm'  => $getForm['form'],
            'title'            => $getForm['title'],
            'pageTypes'        => $getForm['pageTypes'],
            'typeTranslataion' => $typeTranslataion,
            'locationView'     => $locationView,
            'edit'             => $params['id'],
            'permisson_module' => $params['permisson_module'],
            'prodType'         => $prodType,
            'language'         => $language,
            'textlineTitle'    => $textlineTitle,
            'editMode'         => $editMode
         ];
    }

    public function addAction()
    {
        $service = $this->getTranslationService();
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        if (!$auth->hasRole(Roles::ROLE_UNIVERSAL_TEXTLINE_CREATOR)) {
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'translation']);
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $id = $service->addTextline($postData);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
            return $this->redirect()->toRoute('backoffice/default',
                ['controller' => 'translation',
                    'action'  => 'view',
                    'id'      => 'u-' . $id . '-en'
                ]
            );
        }

        $pageTypesList  = $service->getUniversalPages();
        $pageTypes      = [];
        $pageTypesArray = [];
        foreach ($pageTypesList as $row) {
            $pageTypes['id']   = $row->getId();
            $pageTypes['name'] = $row->getName();
            array_push($pageTypesArray, $pageTypes);
        }

        return new ViewModel(
            ['pageTypes' => json_encode($pageTypesArray)]
        );
    }

    /**
     * Edit key Entry textline for OLD Website
     */
    public function editkeyAction()
    {
        $id = $this->params()->fromRoute('id', 0);

        if ($id > 0) {
            $service = $this->getTranslationService();
            $id = $service->getKeyInstIdByProdId($id);

            if($id) {
                $this->redirect()->toRoute(
                    'backoffice/default',
                    [
                        'controller' => 'translation',
                        'action' => 'view',
                        'id' => $id
                    ]
                );
            } else {
                $this->redirect()->toRoute(
                   'backoffice/default',
                   ['controller' => 'home']
                );
            }
        } else {
            $this->redirect()->toRoute(
                'backoffice/default',
                ['controller' => 'home']
            );
        }
    }

    /**
     * Edit KI Direct Entry Type textline for NEW Website
     */
    public function editKiDirectEntryAction()
    {
        $id = $this->params()->fromRoute('id', 0);

        if ($id > 0){
            $service = $this->getTranslationService();
            $id = $service->getKiDirectTypeIdByApartmentId($id);

            if ($id) {
                $this->redirect()->toRoute(
                    'backoffice/default',
                    [
                        'controller' => 'translation',
                        'action' => 'view',
                        'id' => $id
                    ]
                );
            } else {
                $this->redirect()->toRoute(
                   'backoffice/default',
                   ['controller' => 'home']
                );
            }
        } else {
            $this->redirect()->toRoute(
                'backoffice/default',
                ['controller' => 'home']
            );
        }
    }

    public function ajaxsaveAction()
    {
        $result = [
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

        $request = $this->getRequest();

        try{
            if ($request->isXmlHttpRequest()) {
                $id = (int)$request->getPost('edit_id', 0);

                if(!$id) {

                }

                $params['tipe_id']         = $request->getPost('textline-type');
                $params['lng']             = strip_tags(trim($request->getPost('lang_code')));
                $params['type']            = strip_tags(trim($request->getPost('type_translation')));
                $params['locationOptions'] = strip_tags(trim($request->getPost('location_option')));
                $params['locationType']    = strip_tags(trim($request->getPost('locationType')));
                $params['id']              = (int)$request->getPost('edit_id');

               $getForm = $this->getForm(
                    $params,
                    [
                        'language'         => $params['lng'],
                        'type_translation' => $params['type'],
                        'locationOption'   => $params['locationOptions'],
                        'locationType'     => $params['locationType']
                    ]
                );

               $form = $getForm['form'];
               $messages = '';
               $form->setInputFilter(new TranslationFilter());
               if ($request->isPost()) {
                   $service = $this->getTranslationService();
                   $filter  = $form->getInputFilter();
                   $form->setInputFilter($filter);
                   $data = $request->getPost();
                   $form->setData($data);

                   if ($form->isValid()) {
                       $vData      = $form->getData();
                       $respons_db = $service->translationSave($vData);
                       if($id == 0){
                            $result['id'] = $respons_db['id'];
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                       } else {
                            // RE-CACHE FOR WEBSITE
                            if ($params['type'] === 'u') {
                                $cache = $this->getServiceLocator()->get('service_website_cache');

                                $dataFromDb = $this->getForm($params, [
                                    'language'         => $params['lng'],
                                    'type_translation' => $params['type'],
                                    'locationOption'   => $params['locationOptions'],
                                    'locationType'     => $params['locationType']
                                ]);

                                $cache->set($id.'-'.$params['lng'], $dataFromDb['data']->getContent());
                            }
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_PUBLISH]);
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


    public function searchAutocomplateAction()
    {
        $result = [
            'result' => [],
            'status' => 'success',
            'msg'    => ''
        ];

        $request = $this->getRequest();

        try{
            if($request->isXmlHttpRequest()) {
               $txt              = strip_tags(trim($request->getPost('txt')));
               $type             = (int)$request->getPost('type');
               $service          = $this->getTranslationService();
               $response         = $service->getAutocomplateList($txt, $type);
               $result['result'] = $response;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    /**
     * @return \DDD\Service\Translation
     */
    private function getTranslationService() {
        if ($this->_traslationSservice === null) {
            $this->_traslationSservice = $this->getServiceLocator()->get('service_translation');
        }
        return $this->_traslationSservice;
    }
}
