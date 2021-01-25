# SMS Engine

SMS Engine is a PHP Library that wraps the implementation of SMS gateways ([Semaphore](https://semaphore.co/), [MyBusyBee](https://www.mybusybee.net/)) so you can send SMS using two simple lines of code:

```php
$engine = SmsEngine::init('semaphore', 'xxxxxxx');

$engine->send("09xxxxxxxxx", "Your message", "Sender Name");
// Your `Sender Name` must be purchased & approved, or it'll throw InvalidSenderNameException
```

# Table of Contents
 - [Supported SMS Suppliers](#supported-sms-suppliers)
 - [Installation](#installation)
 - [How To Use](#how-to-use)
    - [For Any PHP Project](#for-any-php-project)
    - [For Laravel](#for-laravel)
 - [Exception Handling](#exception-handling)
 - [Registering your own SMS gateway](#registering-your-own-sms-gateway)

## Supported SMS Suppliers
Right now, these are the only two SMS suppliers (or SMS Gateways) that are supported in this package:
* [Semaphore](https://semaphore.co/)
* [MyBusyBee](https://www.mybusybee.net/)

If you wish you support an SMS gateway of your choice, feel free to submit a pull request.

## Installation

```sh
composer require vluebridge/sms-engine
```

## How To Use
It's important to note that you need to have a registered account from the [supported SMS Suppliers](#supported-sms-suppliers) above. You'll get your own API key when you login on their application.

> For performance reasons, I highly recommend that you send SMS through a [job](https://laravel.com/docs/8.x/queues#creating-jobs), especially if you're gonna send 20+ SMS in one HTTP request.
>
> Your server will communicate with the SMS Supplier's API server and you could potentially hit your [max_execution_time](https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time) especially if you put this code in a loop.


#### For Any PHP Project
Just write the following lines of code to wherever you want to start sending SMS. 

```php
<?php
require_once('vendor/autoload.php');

use Vluebridge\SmsEngine;

$engine = SmsEngine::init('semaphore', '{API KEY}');
$engine->send("09xxxxxxxxx", "Hello po :)", "{SENDER NAME}");
// You can send the same message in multiple numbers by adding a comma in mobile number string.
// For example:
// $engine->send("09xxxxxxxxx,09xxxxxxxxx,09xxxxxxxxx", "You all the best", "{SENDER NAME}");
```

#### For Laravel
1. Create a config file in `config/sms-engine.php` with the following contents:
    ```php
    <?php
    return [
        'suppliers' => [
            'semaphore' => [
                'api_key' => env('SEMAPHORE_API_KEY')
            ],
    
            'mybusybee' => [
                'api_key' => env('MYBUSYBEE_API_KEY')
            ],
        ]
    ];
    ```
2. Add these variables in your `.env` file:
    ```dotenv
    MYBUSYBEE_API_KEY={YOUR API KEY}
    SEMAPHORE_API_KEY={YOUR API KEY}
    ```
3. Write this code somewhere in your Laravel app:
    ```php
    $supplier = 'semaphore';
    $engine = SmsEngine::init($supplier, config("sms-engine.suppliers.{$supplier}"));
    $engine->send("09xxxxxxxxx", "Hello po :)", "{SENDER NAME}");
    ```
I created a small Laravel command to make this even simpler. Just download this [SendSmsCommand class](https://gist.github.com/vernard/c10442313425f82dedc1f0cdb6366908) and put it in your `app/Console/Commands`directory.

Once installed, you will be able to send sms through this command:

```bash
php artisan sms:send semaphore 09xxxxxxxxx "Hello darkness my old friend" --sender="Sender Name"
```

## Exception Handling
There are a lot of potential problems when you send an SMS. If you don't want your application to crash when it faces an error, it's a good idea to wrap it around a try-catch and catch the following exceptions:
* `InsufficientCreditsException`
* `InvalidApiKeyException`
* `InvalidMessageException`
* `InvalidMobileNumberException`
    > If used with bulk mobile number, this exception is thrown if any one mobile number is invalid.
* `InvalidSenderNameException`
    > Thrown if you use a sender name that's not valid for your account. Usually, you have to buy this from your SMS supplier and wait for it to be approved.
* `MaxSmsRecipientReachedException`
    > Only 1,000 recipients limit per api call. For performance reasons.
* `SmsSendingException`
    > Occurs if some unknown error happens in sending SMS.

All these exceptions returns the SMS Supplier's API response body when you run `$exception->getMessage()`. Use it to figure out what's wrong, especially for `SmsSendingException`.

## Registering your own SMS gateway

You may wish to support your own SMS supplier (like Nexmo, Twilio, etc.) in your own project. This is how you'll do it without touching the code base of this package:

1. Create an adapter class and implement the `SmsSupplierInterface` contract. Example:
    ```php
    <?php
    
    use Vluebridge\Contracts\SmsSupplierInterface;
    
    class TwilioAdapter implements SmsSupplierInterface {
        protected $client;
        
        public function __construct($api_key) {
            // Support argument of single string of API Key
            // and an array of configuration in case you need more info like `api_secret`
            if(is_array($api_key)) {
                if(! isset($api_key['api_key'])) {
                    throw new \InvalidArgumentException("Missing `api_key` value in array");
                }
    
                $api_key = $api_key['api_key'];
            }
            
            // Your SMS Supplier usually provides their own PHP package so you don't have to
            // make one from scratch. You can use that here and create the $client field
            // and get the API key from this $conf variable.
            $this->client = new WhateverApiPackage($api_key);
        }
    
        public function send($recipients, $message, $sender_name = null) {
            // Insert code here how they implement their own SMS sending feature.
            // Usually, it's something like this:
            $this->client->send($recipients, $message, $sender_name);
        }
     }
    ```

2. Make sure to implement the [exception handling](#exception-handling) depending on the API server's response.

3. Register your adapter class by executing `SmsEngine::registerSupplier('twilio', TwilioAdapter::class)`

4. Your SMS gateway should now be supported.

5. (Optional) Make a pull request and share your implementation with us! Sharing is caring 😊
