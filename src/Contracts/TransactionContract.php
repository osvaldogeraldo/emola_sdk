<?php

namespace BrilliantMind\Sdk\Contracts;

interface TransactionContract {

    /**
     * Construct transaction from the eMola API response.
     *
     * @param object|string $response
     */
    public function __construct($response);

    /**
     * Convert the model instance to an array
     * 
     * @return array
     */
    public function toArray(): array;

    
}