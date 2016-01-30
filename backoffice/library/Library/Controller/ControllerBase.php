<?php

namespace Library\Controller;

use Zend\Http\Request;
use \Zend\Mvc\Controller\AbstractActionController;
use ZF2Graylog2\Traits\Logger;

/**
 * @method Request getRequest()
 */
abstract class ControllerBase extends AbstractActionController
{
    use Logger;

    const EMERG  = 0;
    const ALERT  = 1;
    const CRIT   = 2;
    const ERR    = 3;
    const WARN   = 4;
    const NOTICE = 5;
    const INFO   = 6;
    const DEBUG  = 7;

    /**
     * @param string $subject Subject of mail message.
     * @param string $message Message of mail. Can contain HTML tags and spec symbols, for example "\n"
     * @param array|list|string $to If string and more one email, delimit by ',' (without spaces)
     * @return boolean
     */
    public function email($subject, $message, $to = 'notify@ginosi.com')
    {
        $this->emailPlugin()->email($subject, $message, $to, $this->getServiceLocator());
    }

    /**
     * Check whether current/session user has permission to selected dashboard
     *
     * @param int $dashboardId
     * @param bool|false|integer $roleId
     * @return boolean
     */
    protected function checkUniversalDashboardPermission($dashboardId, $roleId = false)
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasDashboard($dashboardId)) {
            return false;
        }

        if ($roleId && !$auth->hasRole($roleId)) {
            return false;
        }

        return true;
    }
}
