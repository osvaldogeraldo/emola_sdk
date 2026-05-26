<?php

namespace BrilliantMind\Sdk\Config;

class Config {

    /**
     * @var string $host eMola SOAP gateway WSDL endpoint.
     */
    private static string $host;

    /**
     * @var string $username Encrypted username assigned by eMola.
     */
    private static string $username;

    /**
     * @var string $password Encrypted password assigned by eMola.
     */
    private static string $password;

    /**
     * @var string $key Private key provided by eMola.
     */
    private static string $key;

    /**
     * @var string $partnerCode Partner code provided by eMola (e.g. "00204").
     */
    private static string $partnerCode;

    /**
     * @var string $language Message language: pt (Portuguese) or en (English).
     */
    private static string $language = 'pt';


    public static function config(
        string $host,
        string $username,
        string $password,
        string $key,
        string $partnerCode,
        string $language = 'pt'
    ) {
        self::$host = $host;
        self::$username = $username;
        self::$password = $password;
        self::$key = $key;
        self::$partnerCode = $partnerCode;
        self::$language = $language;
    }

    /**
     * @return string
     */
    public static function getHost(): string
    {
        return self::$host;
    }

    /**
     * @return string
     */
    public static function getUsername(): string
    {
        return self::$username;
    }

    /**
     * @return string
     */
    public static function getPassword(): string
    {
        return self::$password;
    }

    /**
     * @return string
     */
    public static function getKey(): string
    {
        return self::$key;
    }

    /**
     * @return string
     */
    public static function getPartnerCode(): string
    {
        return self::$partnerCode;
    }

    /**
     * @return string
     */
    public static function getLanguage(): string
    {
        return self::$language;
    }
}
