<?php

namespace Vluebridge\SmsSuppliers\Semaphore;

use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;
use Vluebridge\Exceptions\InvalidMessageException;
use Vluebridge\Exceptions\InvalidMobileNumberException;
use Vluebridge\Exceptions\MaxSmsRecipientReachedException;
use Vluebridge\Utils\MobileNormalizer;

class SemaphoreClient
{
    const API_BASE = 'http://beta.semaphore.co/api/v4/';
    const DEFAULT_SENDER_NAME = "SEMAPHORE";

    public $apikey;
    protected $client;

    /**
     * SemaphoreClient constructor.
     *
     * @param       $apikey
     */
    public function __construct($apikey) {
        $this->apikey = $apikey;
        $this->client = new Client(['base_uri' => SemaphoreClient::API_BASE, 'query' => ['apikey' => $this->apikey]]);
    }

    /**
     * Check the balance of your account
     *
     * @return StreamInterface
     */
    public function balance() {
        $response = $this->client->get('account');

        return $response->getBody();
    }

    /**
     * Send SMS message(s)
     *
     * @param      $recipients
     * @param      $message - The message you want to send
     * @param null $sendername
     *
     * @return StreamInterface
     * @throws \Exception
     * @internal param $number - The recipient phone number(s)
     * @internal param null $senderId - Optional Sender ID (defaults to initialized value or SEMAPHORE)
     * @internal param bool|false $bulk - Optional send as bulk
     */
    public function send($recipients, $message, $sendername = null) {
        $recipients = is_array($recipients) ? implode(",", $recipients) : $recipients;
        $this->_validate($recipients, $message, $sendername);

        $params = [
            'form_params' => [
                'apikey' => $this->apikey,
                'message' => $message,
                'number' => $recipients,
                'sendername' => $sendername != null ? $sendername : self::DEFAULT_SENDER_NAME,
            ]
        ];

        $response = $this->client->post('messages', $params);

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
    }

    /**
     * Retrieves data about a specific message
     *
     * @param $messageId - The encoded ID of the message
     *
     * @return StreamInterface
     */
    public function message($messageId) {
        $params = [
            'query' => [
                'apikey' => $this->apikey,
            ]
        ];
        $response = $this->client->get('messages/' . $messageId, $params);

        return $response->getBody();
    }

    /**
     * Retrieves up to 100 messages, offset by page
     *
     * @param array $options ( e.g. limit, page, startDate, endDate, status, network, sendername )
     *
     * @return StreamInterface
     * @internal param null $page - Optional page for results past the initial 100
     */
    public function messages($options) {

        $params = [
            'query' => [
                'apikey' => $this->apikey,
                'limit' => 100,
                'page' => 1
            ]
        ];

        //Set optional parameters
        if (array_key_exists('limit', $options)) {
            $params['query']['limit'] = $options['limit'];
        }

        if (array_key_exists('page', $options)) {
            $params['query']['page'] = $options['page'];
        }

        if (array_key_exists('startDate', $options)) {
            $params['query']['startDate'] = $options['startDate'];
        }

        if (array_key_exists('endDate', $options)) {
            $params['query']['endDate'] = $options['endDate'];
        }

        if (array_key_exists('status', $options)) {
            $params['query']['status'] = $options['status'];
        }

        if (array_key_exists('network', $options)) {
            $params['query']['network'] = $options['network'];
        }

        if (array_key_exists('sendername', $options)) {
            $params['query']['sendername'] = $options['sendername'];
        }

        $response = $this->client->get('messages', $params);

        return $response->getBody();
    }

    /**
     * Get account details
     *
     * @return StreamInterface
     */
    public function account() {
        $response = $this->client->get('account');

        return $response->getBody();

    }

    /**
     * Get users associated with the account
     *
     * @return StreamInterface
     */
    public function users() {
        $response = $this->client->get('account/users');

        return $response->getBody();

    }

    /**
     * Get sender names associated with the account
     *
     * @return StreamInterface
     */
    public function sendernames() {
        $response = $this->client->get('account/sendernames');

        return $response->getBody();

    }

    /**
     * Get transactions associated with the account
     *
     * @return StreamInterface
     */
    public function transactions() {
        $response = $this->client->get('account/transactions');

        return $response->getBody();
    }
}
