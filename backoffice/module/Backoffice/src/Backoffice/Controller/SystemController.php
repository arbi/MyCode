<?php
namespace Backoffice\Controller;

use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Library\Service\SitemapGenerator;
use Library\Service\MaxMind;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use FileManager\Service\GenericDownloader;
use FileManager\Constant\DirectoryStructure;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class SystemController extends ControllerBase
{
    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction()
    {
        $dbBackupPath = DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_DATABASE_BACKUP;

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasDevelopmentTestingRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);
        $dbFileList = @array_diff(scandir($dbBackupPath), ['.','..']);
        
        $filesArray = [];
        
        if (!empty($dbFileList)) {
            foreach ($dbFileList as $file) {
                if (strstr($file, 'safe')) {
                    $filesArray[] = [
                        '<button class="btn btn-xs btn-success database-file-download" data-link="'.$file.'"><span class="glyphicon glyphicon-download-alt"></span> '.$file.' </button>',
                        '<button class="btn btn-xs btn-danger database-file-delete" data-link="'.$file.'">Delete</button>'
                    ];
                }
            }
        }

        return new ViewModel([
            'filesAaData' => json_encode($filesArray),
            'hasDevelopmentTesingRole' => $hasDevelopmentTestingRole
        ]);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function generateSitemapAction()
    {
    	$generator = new SitemapGenerator();
    	$generator->setServiceLocator($this->getServiceLocator());
    	
    	$result = $generator->generate(true, true, true, true);
    	
    	return new JsonModel(
    		$result
    	);
    }
    
    public function createNewDatabaseAction()
    {
        try {
            $request = $this->getRequest();
            
            if($request->isXmlHttpRequest()) {
                // Set script excecution time to 3 minute
                set_time_limit(360);

                // Call ginosole commant to create new safe database backup
                $cmd = 'ginosole db safe-backup -v';
                shell_exec($cmd);

                return new JsonModel([
                    'status' => 'success',
                    'msg' => 'Done'
                ]);
            }
            
            return new JsonModel([
                'status' => 'error',
                'msg' => TextConstants::AJAX_NO_POST_ERROR
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'error',
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    public function downloadDatabaseBackupAction()
    {
        try{
            $fileString = $this->params()->fromQuery('file');
            
            if (strstr($fileString, '..')) {
                return new JsonModel([
                    'status' => 'error',
                    'msg' => TextConstants::ERROR
                ]);
            }

            $filePath = DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_DATABASE_BACKUP . $fileString;

            if (file_exists($filePath)) {

                ini_set('memory_limit', '512M');

                /**
                 * @var \FileManager\Service\GenericDownloader $genericDownloader
                 */
                $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');
                $genericDownloader->setFileSystemMode(GenericDownloader::FS_MODE_DB_BACKUP);

                $genericDownloader->downloadAttachment($fileString);

                if ($genericDownloader->hasError()) {
                    Helper::setFlashMessage(['error' => $genericDownloader->getErrorMessages(true)]);

                    if ($this->getRequest()->getHeader('Referer')) {
                        $url = $this->getRequest()->getHeader('Referer')->getUri();
                        $this->redirect()->toUrl($url);
                    }
                }

                return true;
            }
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'error',
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    public function deleteDatabaseBackupAction()
    {
        try{
            $request = $this->getRequest();
            
            if($request->isXmlHttpRequest()) {
                $fileString = $this->params()->fromPost('file');
                
                if (strstr($fileString, '..')) {
                    return new JsonModel([
                        'status' => 'error',
                        'msg' => TextConstants::ERROR
                    ]);
                }
                
                $filePath = DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_DATABASE_BACKUP . $fileString;
                
                if (file_exists($filePath) && is_writable($filePath)) {
                    
                    unlink($filePath);
                    
                    return new JsonModel([
                        'status' => 'success',
                        'msg' => TextConstants::SYSTEM_DATABASE_BACKUP_REMOVED
                    ]);
                } else {
                    return new JsonModel([
                        'status' => 'error',
                        'msg' => TextConstants::SYSTEM_DATABASE_BACKUP_NOT_REMOVED
                    ]);
                }
            }
            
            return new JsonModel([
                'status' => 'error',
                'msg' => TextConstants::AJAX_NO_POST_ERROR
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => 'error',
                'msg' => $e->getMessage()
            ]);
        }
    }
}
