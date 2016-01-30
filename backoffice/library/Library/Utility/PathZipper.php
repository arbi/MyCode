<?php

/**
 * Add a Dir with Files and Subdirs to the archive
 *
 * @author tigran.tadevosyan
 */

namespace Library\Utility;


class PathZipper extends \ZipArchive
{
    
    /**
     * Add a Dir with Files and Subdirs to the archive
     * @param string $location Real Location
     * @param string $name Name in Archive
     * @access private
     **/
    public function addDir($location, $name, $onlyHaveInName = FALSE)
    {
        $this->addEmptyDir($name);
        
        $this->addDirDo($location, $name, $onlyHaveInName);
    }

    /**
     * Add Files & Dirs to archive
     * @param string $location Real Location
     * @param string $name Name in Archive
     * @access private
     **/
    private function addDirDo($location, $name, $onlyHaveInName)
    {
        $needStrInName = ($onlyHaveInName) ? $onlyHaveInName : '.';
        $name .= '/';
        $location .= '/';
        
        // Read all Files in Dir
        $dir = opendir ($location);
        while ($file = readdir($dir))
        {
            if ($file == '.' || $file == '..' || !strpos($file, $needStrInName)) continue;
            // Rekursiv, If dir: FlxZipArchive::addDir(), else ::File();
            $do = (filetype( $location . $file) == 'dir') ? 'addDir' : 'addFile';
            $this->$do($location . $file, $name . $file);
        }
    }
}
