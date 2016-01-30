<?php

namespace DDD\Domain\User\Document;


class Documents
{
    private $id;
    private $userId;
    private $creatorId;
    private $typeId;
    private $type;
    private $dateCreated;
    private $description;
    private $attachment;
    private $url;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->userId = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->creatorId = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->typeId = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->dateCreated = (isset($data['date_created'])) ? $data['date_created'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
        $this->attachment = (isset($data['attachment'])) ? $data['attachment'] : null;
        $this->url = (isset($data['url'])) ? $data['url'] : null;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getCreatorId() {
        return $this->creatorId;
    }

    public function getTypeId() {
        return $this->typeId;
    }

    public function getType() {
        return $this->type;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getAttachment() {
        return $this->attachment;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function setCreatorId($creatorId) {
        $this->creatorId = $creatorId;
    }

    public function setTypeId($typeId) {
        $this->typeId = $typeId;
    }

    public function setDateCreated($dateCreated) {
        $this->dateCreated = $dateCreated;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setAttachment($attachment) {
        $this->attachment = $attachment;
    }

    public function setUrl($url) {
        $this->url = $url;
    }
}
