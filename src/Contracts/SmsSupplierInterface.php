<?php

namespace Vluebridge\Contracts;

use Vluebridge\Exceptions\InsufficientCreditsException;
use Vluebridge\Exceptions\InvalidApiKeyException;
use Vluebridge\Exceptions\InvalidMessageException;
use Vluebridge\Exceptions\InvalidMobileNumberException;
use Vluebridge\Exceptions\InvalidSenderNameException;
use Vluebridge\Exceptions\MaxSmsRecipientReachedException;
use Vluebridge\Exceptions\SmsSendingException;

interface SmsSupplierInterface
{
    public function __construct($api_key);

    /**
     * @param string|array $recipients
     * @param string $message
     * @param string $sender_name
     *
     * @return mixed
     *
     * @throws InvalidSenderNameException
     * @throws InvalidMobileNumberException
     * @throws InvalidMessageException
     * @throws InsufficientCreditsException
     * @throws InvalidApiKeyException
     * @throws MaxSmsRecipientReachedException
     * @throws SmsSendingException
     */
    public function send($recipients, $message, $sender_name = null);
}
