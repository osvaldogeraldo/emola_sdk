<?php

namespace BrilliantMind\Sdk;

class Response
{
    public static array $codes = [
        '0' => [
            'error' => 0,
            'original' => [
                '0' => [
                    'errorCode' => 0,
                    'message' => 'Successfully'
                ]
            ]
        ],
        '6003' => [
            'error' => 6003,
            'message' => 'Login failed. Please check username, password and your IP Address and contact the administrator'
        ]
    ];

    public static function generateResponse(string $code, string $requestId, string $transType): string
    {
        $response = self::$codes[$code] ?? self::$codes['6003'];
        $transType = $transType.'Response';

        $xml = '';
        if ($code == '0') {
            $xml = <<<XML
                <original> 
                    <S:Body>
                        <ns2:{$transType} xmlns:ns2="http://services.wsfw.vas.viettel.com/">
                            <return>
                                <errorCode>{$response['original'][0]['errorCode']}</errorCode>
                                <message>{$response['original'][0]['message']}</message>
                                <reqeustId>{$requestId}</reqeustId>
                            </return>
                        </ns2:{$transType}>
                    </S:Body>
                </original>
            XML;
        }


        return $xml;
    }
}
