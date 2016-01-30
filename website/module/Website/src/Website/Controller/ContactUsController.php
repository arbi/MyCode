<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Website\Form\ContactForm;
use Website\Form\InputFilter\ContactFilter;

use Zend\Validator\EmailAddress;


class ContactUsController extends WebsiteBase
{
    public function indexAction()
    {
        $form = new ContactForm;
        $form->prepare();
        $form->setInputFilter(new ContactFilter());
        
        return new ViewModel([
            'form' => $form,
            'actionMethod' => 'ajax-send-email'
        ]);
    }
    
//    public function ajaxSendEmailAction()
//    {
//        try {
//            if (!$this->getRequest()->isXmlHttpRequest()) {
//                return $this->redirect()->toRoute('home')->setStatusCode('301');
//            }
//            
//            $params['name'] = str_replace(["'","\""], ["",""], $this->params()->fromPost('name'));
//            
//            $params['email'] = $this->params()->fromPost('email');
//            
//            $params['remarks'] = preg_replace('/[\n\r]/', '<br>', $this->params()->fromPost('remarks'));
//            $params['remarks'] = str_replace(["'","\""], ["",""], $params['remarks']);
//            
//            $emailValidator = new EmailAddress;
//            
//            $return = [];
//            if (!$emailValidator->isValid($params['email'])) {
//                 $return['status'] = 'error';
//                 $return['message'] = 'Email address format is invalid';
//            } else {
//                
//                $cmd = "ginosole contact-us send --email='".$params['email']."' --name='".$params['name']."' --remarks='".$params['remarks']."' > /dev/null &";
//                $output = shell_exec($cmd);
//                
//                $return['status'] = 'success';
//                $return['message'] = 'Thank You!';
//                
//                if (strstr($output, 'error')) {
//                    $return['status'] = 'error';
//                    $return['message'] = 'Thank You!';
//                }
//            }
//            
//            return new JsonModel($return);
//            
//        } catch (\Exception $e) {
//            
//        }
//    }
}
