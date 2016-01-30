<?php

namespace FileManager\Base\DirectoryStructure\EntityAttachment;

/**
 * Class Apartment
 * @package FileManager\Base\DirectoryStructure\EntityAttachment
 *
 * @author Tigran Petrosyan
 */
class Apartment extends EntityAttachmentBase {

    /**
     * @var int
     */
    private $countryId;

    /**
     * @var int
     */
    private $provinceId;

    /**
     * @var int
     */
    private $cityId;

    function __construct()
    {
        $this->entityBaseFolder = 'apartment';
    }

    /**
     * @param int $cityId
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;
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
            '/' . $this->cityId . '/'.
            '/' . $this->entityId . '/'.
            $this->filename;
    }
} 