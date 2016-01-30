<?php
/**
 * A PHP class that acts as wrapper for MailChimp API.
 * @Author Tigran Ghabuzyan.
 */

namespace MailChimp\Service;

/**
 * Class MailChimp
 */
final class MailChimp
{
    private $apiKey;
    private $apiVersion = '3.0';
    private $apiUrl     = 'https://{{prefix}}.api.mailchimp.com/';

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
    }

    /**
     * @param string $uri
     * @param string $method [GET, POST, PUT, PATCH, DELETE]
     * @param array $data
     * @throws \Exception
     * @return Object
     */
    private function request($uri, $method, $data = [])
    {
        $url    = $this->getApiUrl() . $this->getApiVersion() . $uri;
        $headers = [];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERPWD, 'ginosi:' . $this->getApiKey());

        if (!empty($data))
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($curl);
        curl_close($curl);

        if (!$result) {
            throw new \Exception('curl request failed');
        } else {
            $decodedResult = json_decode($result);
            return $decodedResult;
        }
    }

    /**
     * @param string $listId
     * @return Object | bool
     * @throws \Exception
     */
    public function getListMemebers($listId)
    {
        $uri = '/lists/' . $listId . '/members/';
        $method = 'GET';

        $response =  $this->request($uri, $method);

        if (!empty($response->members)) {
            return $response->members;
        } else {
            return false;
        }
    }


    public function getListMember($listId, $email)
    {
        $emailHash = md5(strtolower($email));
        $uri = '/lists/' . $listId . '/members/' . $emailHash;
        $method = 'GET';

        $result = $this->request($uri, $method);

        if ($result->status == 404) {
            return false;
        } else {
            return $result;
        }
    }

    /**
     * @param string $listId
     * @param Object $member
     * @param string $status ['pending', 'subscribed', 'unsubscribed']
     * @return Object
     * @throws \Exception
     */
    public function addMemberToList($listId, $member, $status = 'pending')
    {
        $uri = '/lists/' . $listId . '/members/';
        $method = 'POST';

        $data = [
            'email_address' => $member->email,
            'status' => $status,
            'merge_fields' => [
                'FNAME' => $member->firstName,
                'LNAME' => $member->lastName
            ],
        ];

        return $this->request($uri, $method, $data);
    }

    public function updateMemberDataInList($listId, $member)
    {
        $emailHash = md5(strtolower($member->email));
        $uri = '/lists/' . $listId . '/members/' . $emailHash;
        $method = 'PATCH';

        $data = [
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => $member->firstName,
                'LNAME' => $member->lastName
            ],
        ];

        return $this->request($uri, $method, $data);
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @throws \Exception
     */
    public function setApiKey($apiKey)
    {
        $splittedKey = explode('-', $apiKey);
        if (!empty($splittedKey[1])) {
            $this->apiUrl = str_replace('{{prefix}}', $splittedKey[1], $this->apiUrl);
        } else {
            throw new \Exception('Invalid API Key');
        }

        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     */
    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }
}
