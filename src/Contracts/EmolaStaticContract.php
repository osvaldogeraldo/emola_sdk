<?php

namespace BrilliantMind\Sdk\Contracts;

interface EmolaStaticContract {

    /**
     * @param float $amount
     * @param string $msisdn
     * @param string $transactionID
     * @param string $transactionReference
     * @param string $smsContent
     */
    public static function c2b(
        float $amount,
        string $msisdn,
        string $transactionID,
        string $transactionReference,
        string $smsContent
    );
}