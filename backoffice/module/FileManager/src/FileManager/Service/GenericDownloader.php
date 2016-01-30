<?php

namespace FileManager\Service;

use DDD\Service\ServiceBase;
use FileManager\Constant\Messages;
use FileManager\Constant\DirectoryStructure;
use Zend\ServiceManager\ServiceManager;

/**
 * Class GenericDownloader
 * @package FileManager\src\FileManager
 *
 * @author Tigran Petrosyan
 */
class GenericDownloader extends ServiceBase
{
    /**
     * @var \Zend\ServiceManager\ServiceManager $serviceManager
     */
    private $serviceManager         = null;

    /**
     * @var \League\Flysystem\Filesystem $fileSystem
     */
    private $fileSystem             = null;

    private $currentFileSystemMode  = null;
    private $defaultFileSystemMode  = self::FS_MODE_UPLOADS;

    private $currentPath            = null;

    const FS_MODE_UPLOADS   = 1;
    const FS_MODE_IMAGES    = 2;
    const FS_MODE_DB_BACKUP = 3;

    private static $fileSystemModes = [
        self::FS_MODE_UPLOADS   => 'uploads',
        self::FS_MODE_IMAGES    => 'images',
        self::FS_MODE_DB_BACKUP => 'db_backup',
    ];

    private $hasError   = false;
    private $errors     = [];

    const ERROR_ATTACHMENT_FILE_DOES_NOT_EXIST = 1;
    const ERROR_ATTACHMENT_DOES_NOT_EXIST = 2;

    public static $errorMessages = [
        self::ERROR_ATTACHMENT_DOES_NOT_EXIST => Messages::ATTACHMENT_NAME_IS_EMPTY,
        self::ERROR_ATTACHMENT_FILE_DOES_NOT_EXIST => Messages::FILE_DOES_NOT_EXIST,
    ];

    /**
     * @param ServiceManager $sm
     */
    public function __construct(ServiceManager $sm)
    {
        $this->serviceManager = $sm;

        $this->currentFileSystemMode = $this->defaultFileSystemMode;
        $this->setFileSystemMode($this->currentFileSystemMode);

    }

    /**
     * @param int $mode
     * @return bool
     */
    public function setFileSystemMode($mode)
    {

        if (array_key_exists($mode, self::$fileSystemModes)) {
            $fileSystemMode = self::$fileSystemModes[$mode];

            $this->fileSystem = $this->serviceManager->get('BsbFlysystemManager')->get($fileSystemMode);
            $this->currentFileSystemMode = $mode;

            switch ($this->currentFileSystemMode) {
                case self::FS_MODE_UPLOADS:
                    $this->currentPath = DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_UPLOADS_ROOT;
                    break;
                case self::FS_MODE_IMAGES:
                    $this->currentPath = DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_IMAGES_ROOT;
                    break;
                case self::FS_MODE_DB_BACKUP;
                    $this->currentPath = DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_DATABASE_BACKUP;
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * @param $filePath
     * @param $toBeDownloadedFileName
     *
     * @return int
     */
    public function downloadAttachment($filePath, $toBeDownloadedFileName = '')
    {
        if (!is_readable($this->currentPath . $filePath)) {
            $this->setError(self::ERROR_ATTACHMENT_FILE_DOES_NOT_EXIST);
        }

        if (empty($toBeDownloadedFileName)) {
            $toBeDownloadedFileName = basename($filePath);

            if (empty($toBeDownloadedFileName)) {
                $this->setError(self::ERROR_ATTACHMENT_DOES_NOT_EXIST);
            }
        }

        if ($this->hasError()) {
            return $this->errors;
        }

        $this->setHeadersForAttachmentDownload($toBeDownloadedFileName);

        if ($this->fileSystem->getSize($filePath) > DirectoryStructure::FS_DOWNLOAD_STREAM_AFTER_SIZE) {
            header('Content-Length: ' . $this->fileSystem->getSize($filePath));

            $stream = $this->fileSystem->readStream($filePath);

            while (!feof($stream)) {
                print fgets($stream, 1024);
                flush();
            }

            fclose($stream);
            exit;

        } else {
            ob_start();
            ob_start("ob_gzhandler");

            echo $this->fileSystem->get($filePath)->read();

            ob_end_flush();

            $gzippedContent = ob_get_contents(); // store gzipped content to get size

            header('Content-Length: ' . strlen($gzippedContent));

            ob_end_flush();
            exit;
        }
    }

    /**
     * @param $toBeDownloadedFileName
     * @return bool
     */
    private function setHeadersForAttachmentDownload($toBeDownloadedFileName)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($toBeDownloadedFileName));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        return true;
    }

    /**
     * @param $filePath
     * @return bool
     */
    public function removeAttachment($filePath)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->hasError;
    }

    /**
     * @param int $errorConstant
     * @return array
     */
    private function setError($errorConstant)
    {
        $this->errors[] = $errorConstant;
        $this->hasError = true;

        return $this->errors;
    }

    /**
     * @return array
     */
    private function clearErrors()
    {
        $this->errors = [];
        $this->hasError = false;

        return $this->errors;
    }

    /**
     * @param bool|false $parseToSingleString
     * @return array|bool|string
     */
    public function getErrorMessages($parseToSingleString = false)
    {
        try {
            if ($this->hasError()) {
                $result = '';

                if ($parseToSingleString) {
                    $result .= '<ul>';

                    foreach ($this->errors as $error) {
                        $result .= '<li>' . self::$errorMessages[$error] . '</li>';
                    }

                    $result .= '</ul>';
                } else {
                    $result = $this->errors;
                }

                return $result;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Generic Downloader error message failed');
        }

        return false;
    }

}
