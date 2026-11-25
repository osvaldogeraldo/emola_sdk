<?php

namespace BrilliantMind\Sdk;

use BrilliantMind\Sdk\Contracts\EmolaContract;
use Exception;
use SoapClient;

class Request implements EmolaContract
{

    /**
     * @var string $host
     */
    protected string $host;

    /**
     * @var bool $fake
     */
    protected bool $fake = false;

    /**
     * @var int $code
     */
    protected int $errorCode = 0;

    /**
     * @var string $status
     */
    protected string $responseStatus = '';

    /**
     * @var string $partnerCode
     */
    protected string $partnerCode;

    /**
     * @var string $language
     */
    protected string $language;

    /**
     * @var string $key
     */
    protected string $key;

    /**
     * @var string $username
     */
    protected string $username;

    /**
     * @var string $password
     */
    protected string $password;


    public function __construct(string $host, string $username, string $password, string $key, string $partnerCode, string $language = 'pt')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->key = $key;
        $this->partnerCode = $partnerCode;
        $this->language = $language;
    }

    /**
     * @param bool $fake
     * @param int $code
     * @param string $status
     * @return EmolaContract
     */
    public function setFake(bool $fake, int $code, string $status): EmolaContract
    {
        $this->fake = $fake;
        $this->errorCode = $code;
        $this->responseStatus = $status;
        return $this;
    }

    public function c2b(float $amount, string $msisdn, string $transactionID, string $transactionReference, string $smsContent)
    {
        $data = [
            'Input' => [
                'username' => $this->username,
                'password' => $this->password,
                'wscode' => 'pushUssdMessage',
                "param" => [
                    ['name' => 'partnerCode', 'value' => $this->partnerCode],
                    ['name' => 'msisdn', 'value' => $msisdn],
                    ['name' => 'smsContent', 'value' => $smsContent],
                    ['name' => 'transAmount', 'value' => self::formatAmount($amount)],
                    ['name' => 'transId', 'value' => $transactionID],
                    ['name' => 'language', 'value' => $this->language],
                    ['name' => 'refNo', 'value' => $transactionReference],
                    ['name' => 'key', 'value' => $this->key]
                ],
                'rawData' => '?'
            ]
        ];

        try {
            $client = $this->request($this->host, $data);
            $response = $client->__soapCall('gwOperation', $data);
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return new Transaction($response);
    }

    /**
     * Initiates a business to customer (b2c) disbursement on the eMola API.
     *
     * @param float $amount
     * @param string $msisdn
     * @param string $transactionID
     * @param string $smsContent
     * @return Transaction
     * @throws Exception
     */
    public function b2c(float $amount, string $msisdn, string $transactionID, string $smsContent)
    {

        $data = [
            'Input' => [
                'username' => $this->username,
                'password' => $this->password,
                'wscode' => 'pushUssdDisbursementB2C',
                "param" => [
                    ['name' => 'partnerCode', 'value' => $this->partnerCode],
                    ['name' => 'msisdn', 'value' => $msisdn],
                    ['name' => 'smsContent', 'value' => $smsContent],
                    ['name' => 'transAmount', 'value' => self::formatAmount($amount)],
                    ['name' => 'transId', 'value' => $transactionID],
                    ['name' => 'key', 'value' => $this->key]
                ],
                'rawData' => '?'
            ]
        ];

        try {
            $client = $this->request($this->host, $data);
            $response = $client->__soapCall('gwOperation', $data);
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return new Transaction($response);
    }

    /**
     * Query the status of a transaction on the eMola API.
     *
     * @param string $transactionID
     * @param string $transactionType
     * @return mixed
     * @throws Exception
     */
    public function transaction(string $transactionID, string $transactionType)
    {

        $data = [
            'Input' => [
                'username' => $this->username,
                'password' => $this->password,
                'wscode' => 'pushUssdQueryTrans',
                "param" => [
                    ['name' => 'partnerCode', 'value' => $this->partnerCode],
                    ['name' => 'transId', 'value' => $transactionID],
                    ['name' => 'key', 'value' => $this->key],
                    ['name' => 'transType', 'value' => $transactionType]
                ],
                'rawData' => '?'
            ]
        ];

        try {
            $client = $this->request($this->host, $data);
            $response = $client->__soapCall('gwOperation', $data);
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;

    }

    /**
     * Summary of beneficiary
     * @param string $msisdn
     * @param string $transactionID
     * @return mixed
     */
    public function beneficiary(string $msisdn, string $transactionID)
    {
        $data = [
            'Input' => [
                'username' => $this->username,
                'password' => $this->password,
                'wscode' => 'queryBeneficiaryName',
                "param" => [
                    ['name' => 'transId', 'value' => $transactionID],
                    ['name' => 'partnerCode', 'value' => $this->partnerCode],
                    ['name' => 'msisdn', 'value' => $msisdn],
                    ['name' => 'key', 'value' => $this->key],
                ],
                'rawData' => '?'
            ]
        ];

        try {
            $client = $this->request($this->host, $data);
            $response = $client->__soapCall('gwOperation', $data);
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }

    /**
     * Summary of accountBalance
     * @param string $transactionID
     * @return mixed
     */
    public function accountBalance(string $transactionID)
    {

        $data = [
            'Input' => [
                'username' => $this->username,
                'password' => $this->password,
                'wscode' => 'queryAccountBalance',
                "param" => [
                    ['name' => 'transId', 'value' => $transactionID],
                    ['name' => 'partnerCode', 'value' => $this->partnerCode],
                    ['name' => 'key', 'value' => $this->key],
                ],
                'rawData' => '?'
            ]
        ];

        try {
            $client = $this->request($this->host, $data);
            $response = $client->__soapCall('gwOperation', $data);
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }


    /**
     * The amount as the gateway expects it: a string with exactly two decimals.
     *
     * A PHP float whose decimal part is zero is serialised by SOAP without
     * decimals (`1500.0` goes out as `1500`), and the gateway rejects it with
     * "Invalid amount". Amounts with cents (`19.99`) happened to work, so round
     * amounts - the most common ones - always failed.
     */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Summary of request
     * @param string $wsdl
     * @return \SoapClient
     */
    protected function request(string $wsdl, array $body): SoapClient
    {
        return $this->fake ? $this->developmentRequest($body) : $this->productionRequest($wsdl);
    }

    /**
     * @param string $wsdl
     * @return SoapClient
     */
    protected function productionRequest(string $wsdl): SoapClient
    {
        $client = new SoapClient($wsdl, [
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        ]);

        return $client;
    }

    protected function developmentRequest(array $data): SoapClient
    {
        $client = new class ($this->errorCode, $this->responseStatus, $data) extends SoapClient {
            protected $errorCode;
            protected $responseStatus;
            protected $fakeData;

            public function __construct($errorCode, $responseStatus, $fakeData)
            {
                $this->errorCode = $errorCode;
                $this->responseStatus = $responseStatus;
                $this->fakeData = $fakeData;
            }

            #[\ReturnTypeWillChange]
            public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null)
            {
                $requestId = random_int(100000000000000, 999999999999999);
                $code = $this->errorCode == '0' ? '0' : '6003';
                
                $response = new \stdClass;
                $response->error = $code;
                $response->original = Response::generateResponse($code, $requestId, $arguments['Input']['wscode']);
                $response->gwtransid = random_int(100000000000000, 999999999999999);
                return $response;
            }
        };

        return $client;
    }


}