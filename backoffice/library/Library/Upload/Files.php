<?php

namespace Library\Upload;

use Library\Constants\TextConstants;
use FileManager\Constant\DirectoryStructure;

class Files
{
    protected $files;
    protected $errors = FALSE;

    const FILE_TYPE_ALL      = 1;
    const FILE_TYPE_IMAGE    = 2;
    const FILE_TYPE_DOCUMENT = 3;

    public static function getAttachmentTypes()
    {
        return  [
            self::FILE_TYPE_ALL      => 'All',
            self::FILE_TYPE_IMAGE    => 'Image',
            self::FILE_TYPE_DOCUMENT => 'Document',
        ];
    }

    public function __construct(Array $files = null)
    {
        foreach ($files as $file){

            if ($file === null){
                $this->errors = TextConstants::FILE_TYPE_NOT_TRUE;
            }

            if ($file['size'] > DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE){
                $this->errors = TextConstants::FILE_SIZE_EXCEEDED.' ('. DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE/1024/1024 .'Mb)';
            }

            if ($file['error']) {
                $this->errors = 'File uploaded with errors';
            }
        }

        if (!$this->errors)
            $this->files = $files;
    }

    /*
     * Check the maximum allowable image file size
     */
    public function checkFileSize($fileSize)
    {
        if($fileSize > DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE) {
            return true;
        }

        return false;
    }

    public function checkFiles()
    {
        foreach ($this->files as $file){
            if(!$this->checkFileSize($file['size']))
                $this->errors[][] = 'big';

            if($file['error'] !== 0)
                $this->errors[][] = $file['error'];
        }

        if($this->errors)
            return $this->errors;

        return false;
    }

    public function saveFiles($path = null, $allowedTypes = FALSE, $between = FALSE, $removeOriginalFromTemp = TRUE, $myTime = FALSE)
    {
        if (is_null($path)) {
            $path = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_TMP;
        }

        if (!$this->files) {
            return false;
        }
        $filenames = [];
        try{
            $currentTime = ($myTime) ? $myTime : time();
            $time = [];
            if(!is_dir($path)) {
                self::createdir($path);
            }
            foreach ($this->files as $iterator => $file) {
                $time[$iterator] = $currentTime.'_'.$iterator;
                $type = $this->getFileType($file['name']);

                if (!$allowedTypes OR in_array($type, $allowedTypes)) {
                    $filename = $path.$time[$iterator].(($between) ? '_'.$between : '').'.'.$type;

                    if ($removeOriginalFromTemp) {
                        if (!move_uploaded_file($file['tmp_name'], $filename)) {
                            rename($file['tmp_name'], $filename);
                        }
                        @chmod($filename, 0775);
                    }

                }

                $filenames[$iterator] = $time[$iterator].(($between) ? '_'.$between : '').'.'.$type;
            }

            return $filenames;
        }
        catch (Exception $e){
            throw new \Exception($e);
        }
    }

    public function getFileType($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    public static function createDir($path)
    {
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $temp_path = '/';

        foreach($parts as $part) {
            $temp_path .= $part . '/';
            if(!is_dir($temp_path)) {
                @mkdir($temp_path);
                @chmod($temp_path, 0775);
            }
        }
    }

    /**
     * @param string $file
     * @param string $destination
     */
    public static function moveFile($file, $destination)
    {
        $destinationFolder = pathinfo($destination, PATHINFO_DIRNAME);
        self::createDir($destinationFolder);

        if (!file_exists($file)) {
            return false;
        }

        rename($file, $destination);

        @chmod($destination, 0775);

        return true;
    }

    /**
     * @param string $file
     * @param string $destination
     */
    public static function copyFile($file, $destination)
    {
        $destinationFolder = pathinfo($destination, PATHINFO_DIRNAME);
        self::createDir($destinationFolder);

        copy($file, $destination);
        @chmod($destination, 0775);
    }

    /**
     *
     * @param array $file File information from $request->getFiles()
     * @return string|boolean Return moved file full path or false
     */
    public static function moveToTemp($file)
    {
        $uploadTo = substr(DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_UPLOADS_ROOT
            . DirectoryStructure::FS_UPLOADS_TMP, 0, -1);

        if (!is_dir($uploadTo)) {
            mkdir($uploadTo, 0775, true);
        }

        $tempPath = $uploadTo . '/' . time() . '_' . $file['name'];

        if (move_uploaded_file($file['tmp_name'], $tempPath)) {
            return $tempPath;
        }

        return false;
    }
}
