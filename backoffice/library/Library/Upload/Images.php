<?php

namespace Library\Upload;

use Library\Constants\TextConstants;
use FileManager\Constant\DirectoryStructure;

class Images
{

    private $images;
    private $resized    = false;
    public $errors      = false;
    public $filenames;




    public function __construct(Array $files = null)
    {
        set_time_limit(1800);

        foreach ($files as $file){

            if ($file === null){
                $this->errors = TextConstants::FILE_TYPE_NOT_TRUE;
            }

            if ($file['size'] > DirectoryStructure::FS_UPLOAD_MAX_IMAGE_SIZE){
                $this->errors = TextConstants::FILE_SIZE_EXCEEDED
                    . ' ('. DirectoryStructure::FS_UPLOAD_MAX_IMAGE_SIZE/1024/1024 .'Mb)';
            }

            $this->getImageType($file['tmp_name']);
        }

        if (!$this->errors)
            $this->images = $files;
    }

    public function moveImages()
    {

    }

    public function resizeToWidth(Array $sizes, $withCrop = false)
    {
        foreach ($this->images as $img){
            foreach ($sizes as $i => $width){
                $ratio = $width / $this->getWidth($img['tmp_name']);
                $height = $this->getHeight($img['tmp_name']) * $ratio;
                $images[][$i][$width] = $this->resizeImage($img['tmp_name'], $width, $height);
            }
        }

        $this->pushToResized($images);
    }

    public function resizeToHeight(Array $sizes)
    {
        try{
            foreach ($this->images as $img){
                foreach ($sizes as $i => $height){
                    $ratio = $height / $this->getHeight($img['tmp_name']);
                    $width = $this->getWidth($img['tmp_name']) * $ratio;
                    $images[][$i][$height] = $this->resizeImage($img['tmp_name'], $width, $height);
                }
            }
            $this->pushToResized($images);

            return true;
        }
        catch (Exception $e){
            throw new \Exception(e);
            return false;
        }
    }

    /*
     *
     */
    public function resizeToSquare(Array $sizes)
    {
        try{
            $i=0;
            foreach ($this->images as $img){
                $imageWidth = $this->getWidth($img['tmp_name']);
                $imageHeight = $this->getHeight($img['tmp_name']);

                $type = $this->getImageType($img['tmp_name']);

                if($type === 'jpeg'){
                    $file_heandler = imagecreatefromjpeg($img['tmp_name']);
                }
                elseif($type === 'png') {
                    $file_heandler = imagecreatefrompng($img['tmp_name']);
                }
                elseif($type === 'gif'){
                     $file_heandler = imagecreatefromgif($img['tmp_name']);
                }

                if($imageWidth <= $imageHeight){
                    foreach ($sizes as $size){
                        $srcStartY = ($imageHeight - $imageWidth)/2;
                        $originalCroppedImage = imagecreatetruecolor($imageWidth, $imageWidth);
                        $backgroundColor = imagecolorallocate($originalCroppedImage, 255, 255, 255);
                        imagefill($originalCroppedImage, 0, 0, $backgroundColor);
                        imagecopy($originalCroppedImage, $file_heandler, 0, 0, 0, $srcStartY, $imageWidth, $imageWidth);

                        $newImage = imagecreatetruecolor($size, $size);
                        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
                        imagefill($newImage, 0, 0, $backgroundColor);
                        imagecopyresampled($newImage, $originalCroppedImage, 0, 0, 0, 0, $size, $size, $imageWidth, $imageWidth);
                        $images[][$i][$size] = $newImage;
                    }
                } else{
                    foreach ($sizes as $size){
                        $srcStartX = ($imageWidth - $imageHeight)/2;
                        $originalCroppedImage = imagecreatetruecolor($imageHeight, $imageHeight);
                        $backgroundColor = imagecolorallocate($originalCroppedImage, 255, 255, 255);
                        imagefill($originalCroppedImage, 0, 0, $backgroundColor);
                        imagecopy($originalCroppedImage, $file_heandler, 0, 0, $srcStartX, 0, $imageHeight, $imageHeight);

                        $newImage = imagecreatetruecolor($size, $size);
                        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
                        imagefill($newImage, 0, 0, $backgroundColor);
                        imagecopyresampled($newImage, $originalCroppedImage, 0, 0, 0, 0, $size, $size, $imageHeight, $imageHeight);
                        $images[][$i][$size] = $newImage;
                    }
                }
                $i++;
            }

            $this->pushToResized($images);

            return true;
        }
        catch (Exception $e){
            throw new \Exception(e);
            return false;
        }
    }

    public function resizeImage($img, $width, $height)
    {
        $type = $this->getImageType($img);

        if($type === 'jpeg'){
            $file_heandler = imagecreatefromjpeg($img);
        }
        elseif($type === 'png') {
            $file_heandler = imagecreatefrompng($img);
        }
        elseif($type === 'gif'){
             $file_heandler = imagecreatefromgif($img);
        }
        $newImage = imagecreatetruecolor($width, $height);

        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $backgroundColor);

        imagecopyresampled($newImage, $file_heandler, 0, 0, 0, 0, $width, $height, $this->getWidth($img), $this->getHeight($img));

        return $newImage;
    }

    /**
     * MAGIC CROP - DON'T TOUCH
     */
    public function cropImages(Array $sizes)
    {
        try{
            $i=0;
            foreach ($this->images as $imageCount => $img){

                $imageWidth = $this->getWidth($img['tmp_name']);
                $imageHeight = $this->getHeight($img['tmp_name']);

                $type = $this->getImageType($img['tmp_name']);

                if($type === 'jpeg'){
                    $file_heandler = imagecreatefromjpeg($img['tmp_name']);
                }
                elseif($type === 'png') {
                    $file_heandler = imagecreatefrompng($img['tmp_name']);
                }
                elseif($type === 'gif'){
                     $file_heandler = imagecreatefromgif($img['tmp_name']);
                }

                $ii=0;
                foreach ($sizes as $sizeCount => $size)
                {
                    $this->resizeToHeight([$size['h']]);

                    if($imageWidth <= $imageHeight OR (int)$size['w'] > $this->getWidth($this->resized[$imageCount][0][$size['h']])) {

                        $this->clearResized();
                        $this->resizeToWidth([$size['w']]);

                        $newImage = imagecreatetruecolor($size['w'], $size['h']);
                        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
                        imagefill($newImage, 0, 0, $backgroundColor);

                        imagecopyresampled(
                                $newImage,
                                $this->resized[$imageCount][0][$size['w']],
                                0, 0,
                                0, ($this->getHeight($this->resized[$imageCount][0][$size['w']])-$size['h'])/2,
                                $size['w'], $size['h'],
                                $size['w'], $size['h']
                            );
                    } else {

                        $newImage = imagecreatetruecolor($size['w'], $size['h']);
                        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
                        imagefill($newImage, 0, 0, $backgroundColor);

                        imagecopyresampled(
                                $newImage,
                                $this->resized[$imageCount][0][$size['h']],
                                0, 0,
                                ($this->getWidth($this->resized[$imageCount][0][$size['h']])-$size['w'])/2, 0,
                                $size['w'], $size['h'],
                                $size['w'], $size['h']
                            );
                    }

                    $images[][$imageCount][$size['w']] = $newImage;

                    $this->clearResized();
                    $ii++;
                }
                $i++;
            }

            $this->pushToResized($images);

            return true;
        }
        catch (Exception $e){
            throw new \Exception(e);
            return false;
        }
    }

    public function createThumbs()
    {

    }

    public function saveImage(
        $path                   = null,
        $compression            = 75,
        $between                = false,
        $removeOriginalFromTemp = true,
        $originalSufix          = 'original',
        $needType               = false,
        $myTime                 = false,
        $whithSuffix            = true,
        $ownerFileName          = null,
        $imagesOwner            = null
    ) {
        try{
            $filename = '';

            if (is_null($path)) {
                $path = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_TEMP_PATH;
            }

            $currentTime = ($myTime) ? $myTime : time();
            $time = [];

            // save resized images
            if($this->resized){
                foreach ($this->resized as $images) {
                    if(!is_dir($path)){
                        mkdir($path, 0775, true);
                    }
                    foreach ($images as $count => $image) {
                        if ($whithSuffix) {
                            $time[$count] = $currentTime.'_'.$count;
                        } else {
                            $time[$count] = $currentTime;
                        }

                        foreach ($image as $key => $img) {
                            $filePath = $path . $time[$count] .
                                (($between) ? '_'.$between : '') .
                                '_' . $key;

                            switch ($needType) {
                                case 'jpg':
                                    $filename = $filePath .'.jpg';
                                    imagejpeg($img, $filename);
                                    break;
                                default:
                                    $filename = $filePath .'.png';
                                    imagepng($img, $filename);
                            }

                            @chmod($filename, 0775);

                            $this->filenames[$count][$key] =
                                $time[$count].(($between) ? '_'.$between : '').'_'.$key.'.png';
                        }
                    }
                }
            }

            // save originals
            foreach ($this->images as $count => $original) {
                if(!is_dir($path)){
                    mkdir($path, 0775, true);
                }
                $time[$count] = $currentTime.'_'.$count;
                $type = $this->getImageType($original['tmp_name']);

                if (!$needType OR in_array($type, ['jpeg','jpg'])) {
                    if ($type === 'jpeg') {
                        $type = 'jpg';
                    }

                    $filename = $path.$time[$count].(($between) ? '_'.$between : '').'_'.$originalSufix.'.'.$type;

                    if ($removeOriginalFromTemp) {
                        if (!move_uploaded_file($original['tmp_name'], $filename)) {
                            rename($original['tmp_name'], $filename);
                        }
                        @chmod($filename, 0775);
                    }

                } elseif($type === 'png' AND $removeOriginalFromTemp){
                    $type = $needType;

                    $filename = $path.$time[$count].(($between) ? '_'.$between : '').'_'.$originalSufix.'.'.$type;

                    $image = imagecreatefrompng($original['tmp_name']);
                    imagejpeg($image, $filename, $compression);
                    imagedestroy($image);
                } elseif ($type === 'gif' AND $removeOriginalFromTemp) {
                    $type = $needType;

                    $filename = $path.$time[$count].(($between) ? '_'.$between : '').'_'.$originalSufix.'.'.$type;

                    $image = imagecreatefromgif($original['tmp_name']);
                    imagejpeg($image, $filename, $compression);
                    imagedestroy($image);
                }

                $this->filenames[$count]['original'] = $time[$count].(($between) ? '_'.$between : '').'_'.$originalSufix.'.'.$type;
            }

            return $filename;
        } catch (\Exception $e){
            throw new \Exception($e);
        }
    }

    public function getWidth($img)
    {
        if(is_string($img)){
            $size = getimagesize($img);
            return $size[0];
        }

        return imagesx($img);
    }

    public function getHeight($img)
    {
        if(is_string($img)){
            $size = getimagesize($img);
            return $size[1];
        }

        return imagesy($img);
    }

    public function getImageType($img)
    {
        if (!empty($img)) {
            $type = getimagesize($img);
            $type = explode('/', $type['mime']);

            $trueImagesTypes = array('jpeg', 'png', 'gif');

            if (($type[0] == '') || !in_array($type[1], $trueImagesTypes)) {
                $this->errors = TextConstants::FILE_TYPE_NOT_TRUE;
                return false;
            } else{
                return $type[1];
            }
        }

        return false;
    }


    /*
     * Check the maximum allowable image file size
     */
    public function checkFileSize($fileSize)
    {
        if($fileSize > DirectoryStructure::FS_UPLOAD_MAX_IMAGE_SIZE)
            return true;

        return false;
    }

    public function checkImages()
    {
        foreach ($this->images as $img) {
            if (!$this->checkFileSize($img['size'])) {
                $this->errors[][] = 'big';
            }

            if ($img['error'] !== 0) {
                $this->errors[][] = $img['error'];
            }
        }

        if ($this->errors) {
            return $this->errors;
        }

        return false;
    }

    private function pushToResized($images)
    {
        if ($this->resized) {
            $this->resized = array_merge($this->resized, $images);
        } else {
            $this->resized = $images;
        }
    }

    private function clearResized()
    {
        $this->resized = false;
        return true;
    }
}
