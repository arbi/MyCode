<?php

namespace DDD\Domain\Apartel\Details;

class Details
{
    protected $id;
    protected $name;
    protected $apartelId;
    protected $contentTextlineId;
    protected $motoTextlineId;
    protected $metaDescriptionTextlineId;
    protected $bgImage;
    protected $defaultAvailability;

    public function exchangeArray($data)
    {
        $this->id                           = (isset($data['id'])) ? $data['id'] : null;
        $this->name                         = (isset($data['name'])) ? $data['name'] : null;
        $this->apartelId                    = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->contentTextlineId            = (isset($data['content_textline_id'])) ? $data['content_textline_id'] : null;
        $this->motoTextlineId               = (isset($data['moto_textline_id'])) ? $data['moto_textline_id'] : null;
        $this->metaDescriptionTextlineId    = (isset($data['meta_description_textline_id'])) ? $data['meta_description_textline_id'] : null;
        $this->bgImage                      = (isset($data['bg_image'])) ? $data['bg_image'] : null;
        $this->defaultAvailability          = (isset($data['default_availability'])) ? $data['default_availability'] : null;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getApartelId()
    {
        return $this->apartelId;
    }

    /**
     * @return mixed
     */
    public function getContentTextlineId()
    {
        return $this->contentTextlineId;
    }

    /**
     * @return mixed
     */
    public function getMotoTextlineId()
    {
        return $this->motoTextlineId;
    }

    /**
     * @return mixed
     */
    public function getMetaDescriptionTextlineId()
    {
        return $this->metaDescriptionTextlineId;
    }

    /**
     * @return mixed
     */
    public function getBgImage()
    {
        return $this->bgImage;
    }

    /**
     * @return mixed
     */
    public function getDefaultAvailability()
    {
        return $this->defaultAvailability;
    }



}
