<?php

namespace FileManager\Base\DirectoryStructure\EntityAttachment;


/**
 * Class Apartment
 * @package FileManager\Base\DirectoryStructure\EntityAttachmentBase
 *
 * @author Tigran Petrosyan
 */
abstract class EntityAttachmentBase {

    /**
     * @var string
     */
    protected $entityBaseFolder;

    /**
     * @var int
     */
    protected $entityId;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @param int $entityId
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
} 