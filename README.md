# EMOLA SDK

PHP SDK for consuming the Movitel **e-Mola** USSD Push WebService (SOAP).

Supports: **C2B** (`pushUssdMessage`), **B2C** (`pushUssdDisbursementB2C`),
transaction status, beneficiary name and account balance queries.

## Requirements
- PHP 8.0 or higher
- `ext-soap` enabled

## Installation
```shell
composer require brilliant_mind/emola-sdk
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
> contract is signed**, together with the server IP whitelist. Never commit
> your `.env` — add it to `.gitignore`.

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

If your credentials change per request (multi-tenant apps, sandbox and live in
the same process, queue workers), build a `Request` yourself instead: the
static configuration is shared by the whole PHP process.

```php
use BrilliantMind\Sdk\Request;

$emola = new Request($wsdl, $username, $password, $key, $partnerCode, 'pt');
$response = $emola->b2c(1500, '861234567', $transactionID, 'Payout');
```

### Amounts

Pass the amount in meticais as a number (`1500`, `19.99`). The SDK sends it as
a string with **exactly two decimals** (`"1500.00"`, `"19.99"`): the gateway
rejects round amounts without decimals with *"Invalid amount"*.

To avoid floating point errors, build the amount from integer cents:

```php
$amount = (float) sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
```

### MSISDN

Nine digits, without the country code, prefix `86` or `87`: `861234567`.

### Transaction ID

`transactionID` must be **unique per request** (max 50 characters). Store it
before calling: it is the only way to query a transaction whose answer never
arrived. Reusing one returns code `14`.

### C2B - charge a customer (`pushUssdMessage`)

The customer receives a USSD prompt and confirms with the PIN. By default the
call is **synchronous**: it only returns after the customer answers, or after
60 seconds with code `11` ("customer did not enter PIN"). Set your PHP
`default_socket_timeout` / HTTP timeout above that (90-120 s).

```php
$transactionID        = strtoupper(bin2hex(random_bytes(8)));  // unique per request
$transactionReference = strtoupper(bin2hex(random_bytes(8)));  // shown to the customer

$response = Emola::c2b(
    10,            // amount (MZN)
    '877777777',   // msisdn (prefix 86/87, without 258)
    $transactionID,
    $transactionReference,
    'SMS content shown to the customer (without amount)'  // max 150 chars
);

if ($response->successful()) {
    // paid
}
```

### B2C - pay a customer (`pushUssdDisbursementB2C`)

Sends money **from your partner account** to the customer's e-Mola wallet. The
customer is not asked for a PIN and the answer is synchronous: `errorCode` `0`
means the money was sent.

> ⚠️ B2C must be **enabled for your partner code** by Movitel. Otherwise the
> gateway answers `24` ("The function is not supported"). Your partner account
> must also have balance - check it with `accountBalance()`.

```php
$transactionID = strtoupper(bin2hex(random_bytes(8)));  // unique per request - store it

$response = Emola::b2c(
    1500,          // amount (MZN) - goes out as "1500.00"
    '861234567',   // msisdn (prefix 86/87, without 258)
    $transactionID,
    'Payout'       // remark shown to the customer, max 100 chars
);

if ($response->successful()) {
    // money sent; keep $response->requestId() and gatewayTransactionId()
} elseif ($response->errorCode() === '14') {
    // transId already used: do NOT resend - query the status instead
} else {
    // refused: $response->errorCode(), $response->message(), $response->description()
}
```

**Never resend a B2C whose result you do not know** (timeout, network error,
exception): the money may have left. Query the status with the same
`transactionID` and `B2C`:

```php
try {
    $response = Emola::b2c(1500, '861234567', $transactionID, 'Payout');
} catch (\Exception $e) {
    // network/SOAP failure: unknown result - query, do not resend
    $status = new \BrilliantMind\Sdk\Transaction(Emola::transaction($transactionID, 'B2C'));
}
```

### Transaction status (`pushUssdQueryTrans`)

```php
$raw = Emola::transaction($transactionID, 'B2C');  // or 'C2B'
$status = new \BrilliantMind\Sdk\Transaction($raw);

$status->errorCode();                    // '0' = the query worked
$status->fields()['orgResponseCode'];    // '01' = original transaction succeeded, '00' = failed
$status->fields()['orgResponseMessage'];
```

`errorCode` tells whether the **query** worked; `orgResponseCode` tells how the
**original transaction** ended.

### Beneficiary name and account balance

```php
// Name of the wallet owner (masked, e.g. "A*** B***") - useful before a B2C
$raw = Emola::beneficiary('861234567', $transactionID);
(new \BrilliantMind\Sdk\Transaction($raw))->message();

// Partner account balance
$raw = Emola::accountBalance($transactionID);
(new \BrilliantMind\Sdk\Transaction($raw))->fields()['balance'] ?? null;
```

`transaction()`, `beneficiary()` and `accountBalance()` return the raw
`SoapClient` object (or the error message string when the call failed); wrap
it in a `Transaction` to read it.

### Fake transactions (no network call)

For local development / tests you can stub the gateway. `fake('0')` returns a
success response; any other code returns a gateway failure (`6003`).
```php
Emola::fake();            // success by default
// Emola::fake('1');      // simulate a failure

$response = Emola::b2c(10, '877777777', $transactionID, 'Payout');
var_dump($response->successful());
```

## Response

`c2b()` and `b2c()` return a `Transaction`. Parsing never throws on a
malformed or non-XML response - the gateway reason is kept instead. It throws
`\Exception` only when the call itself failed (network, WSDL, SOAP fault).

| Method | Returns |
|---|---|
| `successful()` | `true` only when the gateway **and** the wallet answered `0` |
| `gatewayError()` | gateway code (`0` = accepted; see the table below) |
| `description()` | gateway description of that code |
| `text()` | `original` content that was not XML (e.g. an error text) |
| `errorCode()` | wallet (business) code, `null` if the wallet was never reached |
| `message()` | wallet message |
| `requestId()` | id generated by e-Mola (`reqeustId`) |
| `gatewayTransactionId()` | `gwtransid` |
| `fields()` | every field inside `<return>` (`orgResponseCode`, `balance`...) |
| `toArray()` | everything above as an array |

```php
$response->toArray();
[
    'error'       => 0,                 // gateway code (0 = accepted)
    'description' => null,              // gateway reason, when error != 0
    'text'        => null,              // non-XML `original`, if any
    'original'    => [
        'errorCode' => '0',             // business code (0 = success)
        'message'   => 'Successfully',
        'requestId' => '00204020210425143336',
    ],
    'gwtransid'   => '20210324093455711185',
]
```

On a gateway-level error, `original` is empty and `error` / `description`
explain why.

## Error codes

### Gateway (`error`)

| Code | Meaning |
|---|---|
| `0` | Success |
| `6001` | Login failed: check username, password and your IP address |
| `6003` / `6004` | Webservice error / error loading the response |
| `6005` | IP not allowed |
| `9002` | Too many requests |
| `2001` / `4001` | Web service inactive - contact Movitel |

### Wallet (`original.errorCode`)

| Code | Meaning |
|---|---|
| `0` | Successfully |
| `02` | Login failed: client IP not valid |
| `03` | Transaction has failed |
| `05` | Invalid partner code |
| `06` | Invalid amount |
| `07` | Invalid MSISDN |
| `08` | Text must be up to 150 characters |
| `09` | Transaction ID must be up to 50 characters |
| `10` | MSISDN not in white list |
| `11` | Customer did not enter PIN (C2B) |
| `12` | Customer does not have an e-Mola account |
| `13` | Cannot find original request id |
| `14` | TransID already exists - **query, do not resend** |
| `15` | Request does not exist |
| `20` | The MSISDN is in another process |
| `22` | Push message done (asynchronous mode) |
| `24` | Function not supported (e.g. B2C not enabled for your partner) |
| `27` | Payment request not found |
| `28` | Transaction has been processed |
| `98` | Login failed: invalid user |
| `99` | Error while processing |

Source: *Movitel USSD Push API Specification v1.5*.

## Changelog

### 1.1.0
- **B2C and C2B amounts are sent with two decimals** (`"1500.00"`). Round
  amounts were sent without decimals and rejected with "Invalid amount".
- `Transaction` no longer throws on a non-XML or malformed `original`; the
  reason is kept in `description()` / `text()`.
- `Transaction::toArray()` returns the **real** gateway `error` (1.0 always
  returned `0` when there was an envelope), plus `description` and `text`.
- New `Transaction` helpers: `successful()`, `gatewayError()`, `description()`,
  `text()`, `errorCode()`, `message()`, `requestId()`,
  `gatewayTransactionId()`, `fields()`.
- `Request` can be used without calling `setFake()` first.
- New `Request::formatAmount()`.
- Fixed the package name in the installation instructions.

### 1.0.0
- C2B, B2C, transaction status, beneficiary name and account balance.
