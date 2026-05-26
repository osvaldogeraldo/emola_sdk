<?php

namespace BrilliantMind\Sdk;
use BrilliantMind\Sdk\Contracts\TransactionContract;
use Exception;


/**
 * @property string $responseCode
 * @property string $transactionID
 * @property string $conversationID
 * @property string $transactionStatus
 * @property string $thirdPartyReference
 * @property string $responseDescription
 */
class Transaction implements TransactionContract
{

    /**
     * the model's atributes
     * 
     * @var object
     */
    protected object $attributes;

    /**
     * Summary of xml
     * @var object
     */
    protected $xml;

    /**
     * Construct transaction from the eMola API response.
     *
     * @param object|string $response
     */
    public function __construct($response)
    {

        if (is_string($response)) {
            throw new Exception($response);
        }

        $this->attributes = $response;

        if (!empty($response->original)) {
            $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $response->original);
            $this->xml = simplexml_load_string($cleanXml, "SimpleXMLElement", LIBXML_NOCDATA);
        }
    }


    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {

        if (empty($this->xml))
            return [
                'error' => $this->attributes->error,
                'original' => [],
                'gwtransid' => $this->attributes->gwtransid
            ];

        $array = $this->xmlToArray($this->xml);

        $responseKey = $this->findResponseKey($array['Body']);

        return [
            'error' => 0,
            'original' => [
                'errorCode' => $array['Body'][$responseKey]['return']['errorCode'],
                'message' => $array['Body'][$responseKey]['return']['message'],
                'requestId' => $array['Body'][$responseKey]['return']['reqeustId'],
            ],
            'gwtransid' => $this->attributes->gwtransid
        ];
    }

    protected function xmlToArray($xmlObject): array
    {
        $out = [];
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = (is_object($node) || is_array($node)) ? $this->xmlToArray($node) : $node;
        }
        return $out;
    }

    protected function findResponseKey(array $body): string
    {
        foreach ($body as $key => $value) {
            if (strpos($key, 'Response') !== false) {
                return $key;
            }
        }

        throw new Exception('Key not found');
    }
}