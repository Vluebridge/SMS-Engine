<?php

namespace Vluebridge\SmsSuppliers\Semaphore;

use GuzzleHttp\Exception\ServerException;
use Vluebridge\Contracts\SmsSupplierInterface;
use Vluebridge\Exceptions\InsufficientCreditsException;
use Vluebridge\Exceptions\InvalidApiKeyException;
use Vluebridge\Exceptions\InvalidMessageException;
use Vluebridge\Exceptions\InvalidMobileNumberException;
use Vluebridge\Exceptions\InvalidSenderNameException;
use Vluebridge\Exceptions\SmsSendingException;

class SemaphoreAdapter implements SmsSupplierInterface
{
    /**
     * @var SemaphoreClient
     */
    protected $client;

    public function __construct($api_key) {
        if (is_array($api_key)) {
            if (!isset($api_key['api_key'])) {
                throw new \InvalidArgumentException("Missing `api_key` value in array");
            }

            $api_key = $api_key['api_key'];
        }

        $this->client = new SemaphoreClient($api_key);
    }

    public function send($recipients, $message, $sender_name = null) {
        try{
            $result = $this->client->send($recipients, $message, $sender_name);
            $result = json_decode($result->getContents(), true);
        } catch (ServerException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            if(substr($error,0, 25) === "[\"Your current balance of") {
                throw new InsufficientCreditsException($error);
            }
            throw new SmsSendingException($error);
        }

        if(isset($result[0])) {
            return $result;
        }

        if(isset($result['number'])) {
            throw new InvalidMobileNumberException(json_encode($result));
        }
        if(isset($result['message'])) {
            throw new InvalidMessageException(json_encode($result));
        }
        if(isset($result['sendername'])) {
             throw new InvalidSenderNameException(json_encode($result));
        }
        if(isset($result['apikey'])) {
            throw new InvalidApiKeyException(json_encode($result));
        }

        return $result;
    }
}
