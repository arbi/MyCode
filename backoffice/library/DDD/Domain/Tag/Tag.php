<?php

namespace DDD\Domain\Tag;

class Tag
{
    protected $id;
    protected $name;
    protected $style;
    protected $tagId;
    protected $usedCount;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id']))? $data['id']: null;
        $this->name                 = (isset($data['name']))? $data['name']: null;
        $this->style                = (isset($data['style']))? $data['style']: null;
        $this->tagId                = (isset($data['tag_id']))? $data['tag_id']: null;
        $this->usedCount            = (isset($data['used_count']))? $data['used_count']: null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStyle()
    {
        return $this->style;
    }

    public function getTagId()
    {
        return $this->tagId;
    }

    public function getUsedCount()
    {
        return $this->usedCount;
    }

}