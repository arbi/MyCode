<?php

namespace BackofficeUser\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Model\ViewModel;
use BackofficeUser\Form\Login;
use BackofficeUser\Form\LoginFilter;

/**
 * Render the dialog box with login form to dynamically sign in 
 *
 * @package backoffice_user
 * @subpackage backoffice_user_view_helpers
 * @author Tigran Petrosyan
 */
class DynamicLoginForm extends AbstractHelper {
    
    /**
     * __invoke
     *
     * @access public
     * @param array $options array of options
     * @return string
     */
    public function __invoke() {
    	$loginForm = new Login();
    	$loginForm->setInputFilter(new LoginFilter());
    	
    	$viewTemplate = 'backoffice-user/authentication/dynamic-login';
    	
        $viewModel = new ViewModel(
        	array(
            	'loginForm' => $loginForm,
        	)
        );
        $viewModel->setTemplate($viewTemplate);
        
        return $this->getView()->render($viewModel);
    }
}
