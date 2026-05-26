<?php

namespace BrilliantMind\Sdk\Contracts;

interface EmolaContract {

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $key
     * @param string $partnerCode
     * @param string $language
     */
    public function __construct(
        string $host,
        string $username,
        string $password,
        string $key,
        string $partnerCode,
        string $language
    );

    public function c2b(
        float $amount,
        string $msisdn,
        string $transactionID,
        string $transactionReference,
        string $smsContent
    );

    public function b2c(
        float $amount,
        string $msisdn,
        string $transactionID,
        string $smsContent
    );

    public function transaction(
        string $transactionID,
        string $transactionType,
    );

    public function beneficiary(
        string $msisdn,
        string $transactionID
    );

    public function accountBalance(
        string $transactionID
    );
}