<?php

namespace Library\ChannelManager;

class CivilResponder
{
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR = 'error';
	const STATUS_WARNING = 'warning';

	private $status = '';
	private $code = '';
	private $message = '';
	private $provider = '';
	private $data = '';

	public function __construct($responseArray)
    {
		/** @todo: Fix this part */
		$response = reset($responseArray);

		$this->status = isset($response->status) ? $response->status : '';
		$this->code = isset($response->code) ? $response->code : '';
		$this->message = isset($response->message) ? $response->message : '';
		$this->provider = key($responseArray);
		$this->data = isset($response->data) ? $response->data : null;
	}

	public function getStatus()
    {
		return $this->status;
	}

	public function getCode()
    {
		return $this->code;
	}

	public function getMessage()
    {
		return $this->message;
	}

	public function getProvider()
    {
		return $this->provider;
	}

	public function getData()
    {
		return $this->data;
	}
}
