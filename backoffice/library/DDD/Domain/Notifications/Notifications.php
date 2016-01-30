<?php
namespace DDD\Domain\Notifications;

class Notifications
{
    protected $id;
    protected $userId;
    protected $sender;
    protected $senderId;
    protected $message;
    protected $showDate;
    protected $doneDate;
    protected $type;
    protected $url;

    public function exchangeArray($data)
    {
        $this->id         = (isset($data['id']))          ? $data['id']           : null;
        $this->userId     = (isset($data['user_id']))     ? $data['user_id']      : null;
        $this->sender     = (isset($data['sender']))      ? $data['sender']       : null;
        $this->senderId   = (isset($data['sender_id']))   ? $data['sender_id']    : null;
        $this->message    = (isset($data['message']))     ? $data['message']      : null;
        $this->showDate   = (isset($data['show_date']))   ? $data['show_date']    : null;
        $this->doneDate   = (isset($data['done_date']))   ? $data['done_date']    : null;
        $this->type       = (isset($data['type']))        ? $data['type']         : null;
        $this->url        = (isset($data['url']))         ? $data['url']          : null;
    }
    
    public function getId()
    {
		return $this->id;
	}
    
    public function getUserId()
    {
        return $this->userId;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function getMessage()
    {
        return $this->message;
    }


    public function getShowDate()
    {
        return $this->showDate;
    }


    public function getDoneDate()
    {
        return $this->doneDate;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    public function setSenderId($senderId)
    {
        $this->senderId = $senderId;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }


    public function setShowDate($showDate)
    {
        $this->showDate = $showDate;
    }


    public function setDoneDate($doneDate)
    {
        $this->doneDate = $doneDate;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isActive()
    {
        return is_null($this->doneDate);
    }
}