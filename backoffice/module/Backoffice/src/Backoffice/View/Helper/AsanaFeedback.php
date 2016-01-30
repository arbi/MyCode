<?php

namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use DDD\Service\Asana\Feedback;
use Library\Constants\Roles;

/**
 * Class GoogleAnalytics
 * @package Backoffice\View\Helper
 */
class AsanaFeedback extends AbstractHelper
{
    protected $serviceLocator;

    public function __invoke($options = array())
    {
        return
            '<script>' .
            $this->createJSVariables() .
            '</script>' .
            '
                    <div class="hidden-print feedback-widget" id="widgets">
                        <a href="#" class="feedback-bug" data-toggle="tooltip" data-placement="left" title="Close Feedback"></a>
                        <div class="feedback-content" data-html2canvas-ignore>
                            <h3>Submit Feedback</h3>
                            <form name="feedback" class="form" id="feedback-form" data-key="' .  mt_rand(19999, 99999) . '
                                ">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            ' . $this->selectElementForBaseTypes() . '
                                        </div>
                                         <div id="asana-feedback-dynamic-part">
                                         </div>
                                         <div id="feedback-account-management-operation-type">
                                         </div>
                                        <div class="form-group dropzone-container">
                                            <div class="well dropzone" id="feedback-dropzone">
                                            </div>
                                        </div>
                                         <div class="form-group asana-feedback-buttons-area">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <button id="feedback-submit" class="btn btn-block btn-primary" data-loading-text="Processing...">Submit</button>
                                                </div>
                                                <div class="col-sm-8">
                                                    <button id="feedback-submit-with-screenshot" class="btn btn-block btn-primary" data-loading-text="Processing...">
                                                    <i class="glyphicon glyphicon-camera"></i>
                                                    Submit with Screenshot </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
        ';
    }

    protected function selectElementForBaseTypes()
    {
        $auth = $this->serviceLocator->get('library_backoffice_auth');
        $hasHrRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR);
        $feedBackBaseTypes = Feedback::getBaseFeedbackTypesForSelect();
        $selectHtml = '<select class="form-control" id="asana-feedback-base-types" name="asana-feedback-base-types" >';
        foreach ($feedBackBaseTypes as $value => $text) {
            if (!$hasHrRole && $value == Feedback::FEEDBACK_TYPE_ACCOUNT_MANAGEMENT_VALUE) {
                continue;
            }
            $selectHtml .= '<option value="' . $value . '" >' . $text . '</option>';
        }
        $selectHtml .= '</select>' ;
        return $selectHtml;
    }

    protected function createJSVariables()
    {
        return
        'var GLOBAL_FEEDBACK_TYPE_SOFTWARE_FEEDBACK = ' . Feedback::FEEDBACK_TYPE_SOFTWARE_FEEDBACK_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_TYPE_ACCOUNT_MANAGEMENT = ' . Feedback::FEEDBACK_TYPE_ACCOUNT_MANAGEMENT_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_TYPE_TRAINING_REQUEST = ' . Feedback::FEEDBACK_TYPE_TRAINING_REQUEST_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_TYPE_ELECTRONICS_REQUEST = ' . Feedback::FEEDBACK_TYPE_ELECTRONICS_REQUEST_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_TYPE_MARKETING_IDEA = ' . Feedback::FEEDBACK_TYPE_MARKETING_IDEA_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_TYPE_CONTENT_IDEA = ' . Feedback::FEEDBACK_TYPE_CONTENT_IDEA_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT = ' . Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT = ' . Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT = ' . Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT = ' . Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_MAILING_LISTS = ' . Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_MAILING_LISTS_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CALL_CENTER = ' . Feedback::FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CALL_CENTER_VALUE . '; ' .
        'var GLOBAL_FEEDBACK_APPLICATION_TYPE_BACKOFFICE = "' . Feedback::FEEDBACK_APPLICATION_TYPE_BACKOFFICE . '"; ' .
        'var GLOBAL_FEEDBACK_APPLICATION_TYPE_MOBILE_APPLICATION = "' . Feedback::FEEDBACK_APPLICATION_TYPE_MOBILE_APPLICATION . '"; ' ;

    }



    public function setServiceLocator(ServiceManager $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
