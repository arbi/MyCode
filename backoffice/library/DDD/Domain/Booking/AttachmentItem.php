<?php

namespace DDD\Domain\Booking;

/**
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class AttachmentItem
{
    /**
     * @access private
     * @var int
     */
    private $id;

    /**
     * @access private
     * @var string
     */
    private $attachment;

    /**
     * @access private
     * @var string
     */
    private $docId;

    /**
     * @access private
     * @var int
     */
    private $reservationId;

    /**
     * @access private
     * @var date
     */
    private $createdDate;

    /**
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->reservationId = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->attachment    = (isset($data['attachment'])) ? $data['attachment'] : null;
        $this->docId         = (isset($data['doc_id'])) ? $data['doc_id'] : null;
        $this->createdDate   = (isset($data['created_date'])) ? $data['created_date'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * @param $reservationId
     * @return $this
     */
    public function setReservationId($reservationId)
    {
        $this->reservationId = $reservationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocId()
    {
        return $this->docId;
    }

    /**
     * @param $docId
     * @return $this
     */
    public function setDocId($docId)
    {
        $this->docId = $docId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param $attachment
     * @return $this
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
        return $this;
    }

    /**
     * @return date
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }
}
