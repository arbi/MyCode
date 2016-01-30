<?php
namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FlashMessenger extends AbstractHelper
{
    protected $serviceLocator;   
    
    protected static $_flashMessenger;
    
    
    
    public function __invoke ()
    {
        $flashMessenger = $this->_getFlashMessenger();
        //get messages from previous requests
        $messages = $flashMessenger->getMessages();
        //add any messages from this request
        if ($flashMessenger->hasCurrentMessages()){
            $messages = array_merge($messages, $flashMessenger->getCurrentMessages());
            //we don't need to display them twice.
            $flashMessenger->clearCurrentMessages();
        }
        
        return $messages;
    }
    
    public function hasCurrentMessages()
    {
        if ($this->getSessionManager()->sessionExists()) {
            $container = $this->getContainer();
            $namespace = $this->getNamespace();

            return isset($container->{$namespace});
        }

        return false;
    }
    
    public function _getFlashMessenger(){
        if (!self::$_flashMessenger) {

            self::$_flashMessenger = new \Zend\Mvc\Controller\Plugin\FlashMessenger;
        }

        return self::$_flashMessenger;  
    }
}