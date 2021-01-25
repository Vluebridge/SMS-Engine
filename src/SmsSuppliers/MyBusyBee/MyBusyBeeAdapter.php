<?php

namespace Vluebridge\SmsSuppliers\MyBusyBee;

use Vluebridge\Contracts\SmsSupplierInterface;
use Vluebridge\Exceptions\InsufficientCreditsException;
use Vluebridge\Exceptions\InvalidApiKeyException;
use Vluebridge\Exceptions\InvalidMessageException;
use Vluebridge\Exceptions\InvalidMobileNumberException;
use Vluebridge\Exceptions\InvalidSenderNameException;
use Vluebridge\Exceptions\SmsSendingException;

class MyBusyBeeAdapter implements SmsSupplierInterface
{
    /**
     * @var MyBusyBeeClient
     */
    protected $client;

    public function __construct($api_key) {
        if(is_array($api_key)) {
            if(! isset($api_key['api_key'])) {
                throw new \InvalidArgumentException("Missing `api_key` value in array");
            }

            $api_key = $api_key['api_key'];
        }
        $this->client = new MyBusyBeeClient($api_key);
    }

    public function send($recipients, $message, $sender_name = null) {
        $result = $this->client->send($recipients, $message, $sender_name);

        switch(substr($result, 0, 4)) {
            case "1001":
                throw new InvalidApiKeyException($result);
            case "1003":
            case "1004":
                throw new InvalidSenderNameException($result);
            case "1005":
                throw new InvalidMessageException($result);
            case "1008":
                throw new InvalidMobileNumberException($result);
            case "1009":
                throw new InsufficientCreditsException($result);
            case "api_":
                break;
            default:
                throw new SmsSendingException($result);
        }

        return $result;
    }


}
