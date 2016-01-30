<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\Json\Decoder;
use Zend\Http\Client;

class ReceiverController extends WebsiteBase
{
    public function lockstateAction()
    {
        $request = $this->getRequest();
        $headers = $request->getHeaders();

        $message = Decoder::decode($request->getContent(), Json::TYPE_ARRAY);
        $snsMessageType = $headers->get('x-amz-sns-message-type');

        if (!$snsMessageType || !$message) {
            return $this->redirect()->toRoute('home');
        } else {
            if ($snsMessageType->getFieldValue() == 'SubscriptionConfirmation') {
                //ToDo Confirm the subscription -> save signature, save arn
                $topicArn = $message['TopicArn'];
                $subscribeUrl = $message['SubscribeURL'];
                $client = new Client($subscribeUrl, [
                    'adapter' => 'Zend\Http\Client\Adapter\Curl'
                ]);
                $response = $client->send();
            } else {
                //ToDo Check signature, check arn

            }
        }

        $this->gr2info('Notification from Lockstate via Amazon SNS', $message);

        echo 'Notification received.'; exit();
    }
}
