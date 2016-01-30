<?php

namespace DDD\Domain\User;


class Devices
{
    protected $id;
    protected $userId;
    protected $dateAdded;
    protected $hash;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->userId       = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->dateAdded    = (isset($data['date_added'])) ? $data['date_added'] : null;
        $this->hash         = (isset($data['hash'])) ? $data['hash'] : null;
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }
}
