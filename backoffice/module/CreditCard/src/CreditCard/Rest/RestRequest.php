<?php

namespace CreditCard\Rest;

class RestRequest
{
	protected $url;
	protected $port;
    protected $apiKey;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;
	
	public function __construct($verb = 'GET', $requestBody = null)
	{
		$this->url				= null;
		$this->port				= null;
        $this->apiKey			= null;
		$this->verb				= $verb;
		$this->requestBody		= $requestBody;
		$this->requestLength	= 0;
		$this->acceptType		= 'application/json';
		$this->responseBody		= null;
		$this->responseInfo		= null;
		
		if ($this->requestBody !== null)
		{
			$this->buildPostBody();
		}
	}
	
	public function flush()
	{
		$this->requestBody = null;
		$this->requestLength = 0;
		$this->verb	= 'GET';
		$this->responseBody = null;
		$this->responseInfo = null;
	}
	
	public function execute()
	{
		$ch = curl_init();
		
		try
		{
			switch (strtoupper($this->verb))
			{
				case 'GET':
					$this->executeGet($ch);
					break;
                case 'PUT':
                    $this->executePut($ch);
                    break;
                case 'POST':
                    $this->executePost($ch);
                    break;
				default:
					throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
			}
		}
		catch (InvalidArgumentException $e)
		{
			curl_close($ch);
			throw $e;
		}
		catch (Exception $e)
		{
			curl_close($ch);
			throw $e;
		}
	}
	
	public function buildPostBody($data = null)
	{
		$data = ($data !== null) ? $data : $this->requestBody;
		
		if (!is_array($data))
		{
			throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
		}
		
		$data = http_build_query($data, '', '&');
		$this->requestBody = $data;
	}
	
	protected function executeGet($ch)
	{		
		$this->doExecute($ch);	
	}
	
	protected function executePut($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		
		$this->requestLength = strlen($this->requestBody);
		
		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);
		
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);
		
		$this->doExecute($ch);
		
		fclose($fh);
	}

    protected function executePost($ch)
    {
        if (!is_string($this->requestBody))
        {
            $this->buildPostBody();
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);


        $this->doExecute($ch);
    }
	
	protected function doExecute(&$curlHandle)
	{
		$this->setCurlOpts($curlHandle);
		$this->responseBody = curl_exec($curlHandle);
		$this->responseInfo	= curl_getinfo($curlHandle);
		
		curl_close($curlHandle);
	}
	
	protected function setCurlOpts(&$curlHandle)
	{
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
        if (!is_null($this->port) && $this->port != '') {
            curl_setopt($curlHandle, CURLOPT_PORT, $this->port);
        }

		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(
            $curlHandle,
            CURLOPT_HTTPHEADER,
            [
                'Accept: ' . $this->acceptType,
                'X-Sifter-Token: ' . $this->apiKey
            ]
        );
	}

    /**
     * @param null $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param null $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }


    public function setUrl($url)
    {
        $this->url = 'https://' . $url;
    }

    public function getUrl()
    {
        return $this->url;
    }


	public function getAcceptType()
	{
		return $this->acceptType;
	} 
	
	public function setAcceptType($acceptType)
	{
		$this->acceptType = $acceptType;
	}
	
	public function getResponseBody()
	{
		return $this->responseBody;
	} 
	
	public function getResponseInfo()
	{
		return $this->responseInfo;
	}
	
	public function getVerb()
	{
		return $this->verb;
	} 
	
	public function setVerb($verb)
	{
		$this->verb = $verb;
	}
}
