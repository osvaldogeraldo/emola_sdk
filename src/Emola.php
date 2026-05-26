<?php

namespace BrilliantMind\Sdk;

use BrilliantMind\Sdk\Contracts\EmolaStaticContract;
use BrilliantMind\Sdk\Config\Config;

class Emola extends Config implements EmolaStaticContract {

    /**
     * @var bool $test
     */
    protected static bool $fake = false;

    /**
     * @var string
     */
    protected static string $status = "";

    /**
     * @var string
     */
    protected static string $responseCode = '0';

    /**
     * @param string $responseCode
     * @param string $status
     */
    public static function fake(string $responseCode = '0', string $status = ""): void
    {
        self::$fake = true;
        self::$status = $status;
        self::$responseCode = $responseCode;
    }

    /**
     * @param string $status
     */
    public static function setStatus(string $status): void
    {
        self::$status = $status;
    }

    /**
     * @param int $code
     */
    public static function setResponseCode(int $code): void
    {
        self::$responseCode = $code;
    }
 
    /**
     * Initiates a customer to business (c2b) transaction on the eMola API.
     *
     * @param float $amount
     * @param string $msisdn
     * @param string $transactionID
     * @param string $transactionReference
     * @param string $smsContent
     * @return Transaction
     */
    public static function c2b( float $amount, string $msisdn, string $transactionID, string $transactionReference, string $smsContent ) {
        return (new static())->client()->c2b( $amount, $msisdn, $transactionID, $transactionReference, $smsContent );
    }

    /**
     * Initiates a business to customer (b2c) disbursement on the eMola API.
     *
     * @param float $amount
     * @param string $msisdn
     * @param string $transactionID
     * @param string $smsContent
     * @return Transaction
     */
    public static function b2c(float $amount, string $msisdn, string $transactionID, string $smsContent) {
        return (new static())->client()->b2c($amount, $msisdn, $transactionID, $smsContent);
    }

    /**
     * Query the status of a transaction on the eMola API.
     *
     * @param string $transactionID
     * @param string $transactionType
     * @return mixed
     */
    public static function transaction( string $transactionID, string $transactionType) {
        return (new static())->client()->transaction($transactionID, $transactionType);
    }

    /**
     * Query the beneficiary name on the eMola API.
     *
     * @param string $msisdn
     * @param string $transactionID
     * @return mixed
     */
    public static function beneficiary( string $msisdn, string $transactionID) {
        return (new static())->client()->beneficiary($msisdn, $transactionID);
    }

    /**
     * Query the partner account balance on the eMola API.
     *
     * @param string $transactionID
     * @return mixed
     */
    public static function accountBalance( string $transactionID) {
        return (new static())->client()->accountBalance($transactionID );
    }


    protected function client(): Request
    {
        $request = new Request(self::getHost(), self::getUsername(), self::getPassword(), self::getKey(), self::getPartnerCode(), self::getLanguage());
        return $request->setFake(self::$fake, self::$responseCode, self::$status);
    }
}