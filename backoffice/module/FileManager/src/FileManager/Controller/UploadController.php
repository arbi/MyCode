<?php

namespace FileManager\Controller;

use Library\Controller\ControllerBase;
use Zend\View\Helper\ViewModel;

/**
 * Class UploadController
 * @package FileManager\Controller
 *
 * @author Tigran Petrosyan
 */
class UploadController extends ControllerBase
{
    public function indexAction()
    {

        return new ViewModel();
    }
}