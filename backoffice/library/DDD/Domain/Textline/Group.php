<?php

namespace DDD\Domain\Textline;

/**
 * Class Group
 * @package DDD\Domain\Textline
 */
class Group
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $text;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->text = (isset($data['en_text'])) ? $data['en_text'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @return string
     */
    public function getEnText()
    {
        return $this->text;
    }
}