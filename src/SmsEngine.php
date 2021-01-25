<?php

namespace Vluebridge;

use InvalidArgumentException;
use Vluebridge\Contracts\SmsSupplierInterface;
use Vluebridge\SmsSuppliers\MyBusyBee\MyBusyBeeAdapter;
use Vluebridge\SmsSuppliers\Semaphore\SemaphoreAdapter;

class SmsEngine {
    protected static $suppliers = [
        'semaphore' => SemaphoreAdapter::class,
        'mybusybee' => MyBusyBeeAdapter::class,
    ];

    public static function getSuppliers() {
        return self::$suppliers;
    }

    public static function init($supplier, $api) {
        $class = self::getSuppliers()[$supplier];
        return new $class($api);
    }

    public static function registerSupplier($name, $class) {
        if(! $class instanceof SmsSupplierInterface) {
            throw new InvalidArgumentException("{$class} must implement ". SmsSupplierInterface::class);
        }

        self::$suppliers[$name] = $class;
    }
}
