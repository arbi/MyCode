<?php

namespace DDD\Domain\Finance;

/**
 * Class Customer
 * @package DDD\Domain\Finance
 */
class Customer
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $email;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->email = (isset($data['email'])) ? $data['email'] : null;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
