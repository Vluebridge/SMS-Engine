<?php

namespace Vluebridge\SmsSuppliers\MyBusyBee;

use GuzzleHttp\Client;
use Vluebridge\Exceptions\InvalidMessageException;
use Vluebridge\Exceptions\InvalidMobileNumberException;
use Vluebridge\Exceptions\InvalidSenderNameException;
use Vluebridge\Exceptions\MaxSmsRecipientReachedException;
use Vluebridge\Utils\MobileNormalizer;

class MyBusyBeeClient
{
    const API_BASE = "http://cloud.mybusybee.net/app/smsapi/";

    protected $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function send($recipients, $message, $sender_name = null) {
        $recipients = is_array($recipients) ? implode(",", $recipients) : $recipients;
        $this->_validate($recipients, $message, $sender_name);

        $query = [
            'key' => $this->apiKey,
            'contacts' => $recipients,
            'senderid' => $sender_name,
            'msg' => $message,
        ];

        $client = new Client(['base_uri' => self::API_BASE, 'query' => $query]);
        $response = $client->post('index.php');

        return $response->getBody();
    }

    protected function _validate($recipients, $message, $sender_name) {
        $recipients = explode(',', $recipients);
        if (count($recipients) > 1000) {
            throw new MaxSmsRecipientReachedException('API is limited to sending to 1000 recipients at a time');
        }

        foreach($recipients as $recipient) {
            if(MobileNormalizer::normalize($recipient) === false) {
                throw new InvalidMobileNumberException("Invalid mobile number: `{$recipient}`");
            }
        }

        if(trim($message) == "") {
            throw new InvalidMessageException("Message can't be blank");
        }

        if($sender_name === null) {
            throw new InvalidSenderNameException("Sender ID is required for MyBusyBee.\n\nUse an approved Sender ID in your account in this link: https://cloud.mybusybee.net/app/senderID");
        }
    }
}
