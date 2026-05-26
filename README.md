# EMOLA SDK

PHP SDK for consuming the Movitel **e-Mola** USSD Push WebService (SOAP).

Supports: **C2B** (`pushUssdMessage`), **B2C** (`pushUssdDisbursementB2C`),
transaction status, beneficiary name and account balance queries.

## Requirements
- PHP 8.0 or higher
- `ext-soap` enabled

## Installation
```shell
composer require brilliantmind/emola-sdk
```

## Configuration

This SDK does **not** read environment variables by itself — you pass the
credentials to `Emola::config()`. The recommended approach is to keep them in a
`.env` file in your project and feed them into the config call.

### `.env` variables

| Variable             | Required | Description                                                                 | Example                                              |
|----------------------|----------|-----------------------------------------------------------------------------|------------------------------------------------------|
| `EMOLA_WSDL`         | yes      | SOAP gateway WSDL endpoint. Provided by e-Mola after the contract is signed | `https://1.2.3.4:8080/BCCSGateway/BCCSGateway?wsdl`  |
| `EMOLA_USERNAME`     | yes      | Encrypted username assigned by e-Mola                                       | `d609baa5ba374a7e89f74f99c33ad761`                   |
| `EMOLA_PASSWORD`     | yes      | Encrypted password assigned by e-Mola                                       | `09671efad19a4d85f2960fde2812339e`                   |
| `EMOLA_KEY`          | yes      | Private key provided by e-Mola                                              | `your-private-key`                                   |
| `EMOLA_PARTNER_CODE` | yes      | Partner code provided by e-Mola (keep as string — may have leading zeros)   | `00204`                                              |
| `EMOLA_LANGUAGE`     | no       | Message language: `pt` (default) or `en`                                    | `pt`                                                 |

> ⚠️ `EMOLA_WSDL` and all credentials are delivered by e-Mola **only after a
> contract is signed**. Never commit your `.env` — add it to `.gitignore`.

### Example `.env`
```dotenv
EMOLA_WSDL=https://1.2.3.4:8080/BCCSGateway/BCCSGateway?wsdl
EMOLA_USERNAME=d609baa5ba374a7e89f74f99c33ad761
EMOLA_PASSWORD=09671efad19a4d85f2960fde2812339e
EMOLA_KEY=your-private-key
EMOLA_PARTNER_CODE=00204
EMOLA_LANGUAGE=pt
```

### Loading the `.env`

**Plain PHP** (using [`vlucas/phpdotenv`](https://github.com/vlucas/phpdotenv)):
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use BrilliantMind\Sdk\Emola;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

Emola::config(
    $_ENV['EMOLA_WSDL'],
    $_ENV['EMOLA_USERNAME'],
    $_ENV['EMOLA_PASSWORD'],
    $_ENV['EMOLA_KEY'],
    $_ENV['EMOLA_PARTNER_CODE'],
    $_ENV['EMOLA_LANGUAGE'] ?? 'pt'
);
```

**Laravel** (`.env` is already loaded; use `config()`/`env()`):
```php
use BrilliantMind\Sdk\Emola;

Emola::config(
    config('services.emola.wsdl'),
    config('services.emola.username'),
    config('services.emola.password'),
    config('services.emola.key'),
    config('services.emola.partner_code'),
    config('services.emola.language', 'pt')
);
```
```php
// config/services.php
'emola' => [
    'wsdl'         => env('EMOLA_WSDL'),
    'username'     => env('EMOLA_USERNAME'),
    'password'     => env('EMOLA_PASSWORD'),
    'key'          => env('EMOLA_KEY'),
    'partner_code' => env('EMOLA_PARTNER_CODE'),
    'language'     => env('EMOLA_LANGUAGE', 'pt'),
],
```

## Usage

After calling `Emola::config(...)` once, use the static methods.

### C2B (charge a customer — `pushUssdMessage`)
```php
$transactionID        = strtoupper(bin2hex(random_bytes(8)));
$transactionReference = strtoupper(bin2hex(random_bytes(8)));

$response = Emola::c2b(
    10,            // amount
    '877777777',   // msisdn (prefix 86/87)
    $transactionID,
    $transactionReference,
    'SMS content shown to the customer (without amount)'
);

print_r($response->toArray());
```

### B2C (disburse to a customer — `pushUssdDisbursementB2C`)
```php
$transactionID = strtoupper(bin2hex(random_bytes(8)));

$response = Emola::b2c(
    10,            // amount
    '877777777',   // msisdn (prefix 86/87)
    $transactionID,
    'Disbursement remark'
);

print_r($response->toArray());
```

### Other queries
```php
// Transaction status. transType: C2B | B2C | QUERY_TXN | QUERY_CUS | QUERY_BEN
Emola::transaction($transactionID, 'C2B');

// Beneficiary name
Emola::beneficiary('877777777', $transactionID);

// Partner account balance
Emola::accountBalance($transactionID);
```

### Fake transactions (no network call)

For local development / tests you can stub the gateway. `fake('0')` returns a
success response; any other code returns a failure (`6003`).
```php
Emola::fake();            // success by default
// Emola::fake('1');      // simulate a failure

$response = Emola::c2b(10, '877777777', $transactionID, $transactionReference, 'SMS CONTENT');
print_r($response->toArray());
```

## Response

`c2b()` and `b2c()` return a `Transaction`. Call `->toArray()`:
```php
[
    'error'     => 0,                  // gateway error code (0 = success)
    'original'  => [
        'errorCode' => 0,              // business error code
        'message'   => 'Successfully',
        'requestId' => '00204020210425143336',
    ],
    'gwtransid' => '20210324093455711185',
]
```
On a gateway-level error, `original` is empty and `error` holds the code.
See the e-Mola API specification for the full list of business error codes
(e.g. `11` = customer did not enter PIN, `12` = no e-Mola account).
