<?php

namespace Backoffice\Controller;

use \Zend\Mvc\Controller\AbstractActionController;

class CloudFlareController extends AbstractActionController
{

    public function indexAction()
    {

    }

    public function downloadAction()
    {
        try {
            $fileName = 'problematic_file.pdf';
            $filePath = '/ginosi/uploads/product/documents/553/2015_1421387867_Hollywood_Boulevard_Studio_Insurance_Contract_719.pdf';

            $fhandle   = finfo_open(FILEINFO_MIME);
            $mime_type = finfo_file($fhandle, $filePath);

            header('Content-Type: ' . $mime_type);
            //header("Content-Length: " . filesize($filePath));
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: no-cache');
            header('Accept-Ranges: bytes');

            // expence file download way
//            if (file_exists($filePath)) {
//                readfile($filePath);
//                return true;
//            }
            // apartment docs file download way
            echo file_get_contents($filePath, true);
            return;
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

}
