<?php

namespace FileManager\Base\DirectoryStructure\EntityAttachment;

/**
 * Class City
 * @package FileManager\Base\DirectoryStructure\EntityAttachment
 *
 * @author Tigran Petrosyan
 */
class City extends EntityAttachmentBase {

    /**
     * @var int
     */
    private $countryId;

    /**
     * @var int
     */
    private $provinceId;

    function __construct()
    {
        $this->entityBaseFolder = 'city';
    }

    /**
     * @param int $countryId
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
    }

    /**
     * @param int $provinceId
     */
    public function setProvinceId($provinceId)
    {
        $this->provinceId = $provinceId;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return
            '/ginosi/images/' . $this->entityBaseFolder.
            '/' . $this->countryId . '/'.
            '/' . $this->provinceId . '/'.
            '/' . $this->entityId . '/'.
            $this->filename;
    }
} 