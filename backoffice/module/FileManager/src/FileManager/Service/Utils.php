<?php

namespace FileManager\Service;


class Utils
{
    public static function deleteDir($path)
    {
        $thisFunction = array(__CLASS__, __FUNCTION__);

        return is_file($path) ?
            @unlink($path) :
            array_map($thisFunction, glob($path.'/*')) == @rmdir($path);
    }
}