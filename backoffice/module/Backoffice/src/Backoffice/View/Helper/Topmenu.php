<?php

namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ViewModel;

class Topmenu extends AbstractHelper
{
    protected $serviceLocator;
    protected $viewTemplate;

    public function __invoke($options = [])
    {
        $render = array_key_exists('render', $options) ? $options['render'] : true;
        $userId = 0;

        $auth = $this->serviceLocator->get('library_backoffice_auth');

        if ($auth->hasIdentity()) {
            $userId = $auth->getIdentity()->id;
        }

        $availableGroup = array();

        if ($userId) {
            $service = $this->serviceLocator->get('service_user');
            $availableGroupGroup = $service->getUsersGroup($userId);

            foreach ($availableGroupGroup as $row) {
                $availableGroup[] = $row['group_id'];
            }
        }

        $vm = new ViewModel(['availableGroup' => $availableGroup]);
        $vm->setTemplate($this->viewTemplate);

        return ($render ? $this->getView()->render($vm) : $vm);
    }

    public function setViewTemplate($viewTemplate)
    {
        $this->viewTemplate = $viewTemplate;

        return $this;
    }

    public function setServiceLocator(ServiceManager $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
