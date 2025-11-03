# BixyPay Fintech API - PHP SDK

Official PHP SDK for the BixyPay Fintech API Platform. This SDK provides a simple and intuitive interface for integrating crypto-fiat payment processing into your PHP applications.

## Requirements

- PHP 7.4 or higher
- Composer
- Guzzle HTTP client

## Installation

Install via Composer:

```bash
composer require bixypay/fintech-sdk
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use BixyPay\BixyPayClient;

// Initialize with API key
$client = new BixyPayClient(
    baseUrl: 'https://api.bixypay.com',
    apiKey: 'sk_live_your_api_key_here'
);

// Create an invoice
$response = $client->invoices()->create([
    'amount' => 100.50,
    'currency' => 'USD',
    'description' => 'Payment for Product XYZ'
]);

if ($response['error']) {
    echo "Error: " . json_encode($response['error']);
} else {
    echo "Invoice created: " . $response['data']['invoiceId'];
}
```

## Authentication

The SDK supports two authentication methods:

### 1. API Key Authentication (Recommended for server-side)

```php
$client = new BixyPayClient(
    baseUrl: 'https://api.bixypay.com',
    apiKey: 'sk_live_your_api_key'
);
```

### 2. JWT Token Authentication

```php
$client = new BixyPayClient(
    baseUrl: 'https://api.bixypay.com',
    jwtToken: 'your_jwt_token'
);
```

## Usage Examples

### Authentication & Registration

#### Register a New Merchant

```php
$response = $client->auth()->register(
    email: 'merchant@example.com',
    password: 'SecurePassword123!',
    businessName: 'My Business LLC',
    businessAddress: '123 Business Street'
);

if (!$response['error']) {
    echo "Merchant ID: " . $response['data']['id'];
}
```

#### Login and Get JWT Token

```php
$response = $client->auth()->login(
    email: 'merchant@example.com',
    password: 'SecurePassword123!'
);

if (!$response['error']) {
    // Token is automatically stored in client
    $accessToken = $response['data']['access_token'];
    echo "Logged in! Token: $accessToken";
}
```

#### Create API Key

```php
$client = new BixyPayClient(
    baseUrl: 'https://api.bixypay.com',
    jwtToken: $yourJwtToken
);

$response = $client->auth()->createApiKey(
    name: 'Production API Key',
    scopes: ['invoices:read', 'invoices:write']
);

if (!$response['error']) {
    $apiKey = $response['data']['key'];
    echo "API Key created: $apiKey";
}
```

### Merchant Operations

#### Get Merchant Profile

```php
$response = $client->merchants()->getProfile();

if (!$response['error']) {
    $profile = $response['data'];
    echo "Business: {$profile['businessName']}\n";
    echo "KYC Status: {$profile['kycStatus']}\n";
}
```

#### Get Account Balance

```php
$response = $client->merchants()->getBalance();

if (!$response['error']) {
    $balance = $response['data'];
    echo "Balance: {$balance['balance']} {$balance['currency']}";
}
```

#### Update KYC Status

```php
$response = $client->merchants()->updateKycStatus('approved');

if (!$response['error']) {
    echo "KYC status updated successfully";
}
```

### Invoice Management

#### Create an Invoice

```php
$response = $client->invoices()->create([
    'amount' => 99.99,
    'currency' => 'USD',
    'description' => 'Premium Subscription - Monthly',
    'metadata' => [
        'customer_id' => 'cust_12345',
        'plan' => 'premium'
    ],
    'callbackUrl' => 'https://yourapp.com/webhooks/payment'
]);

if (!$response['error']) {
    $invoice = $response['data'];
    echo "Invoice ID: {$invoice['invoiceId']}\n";  // Use invoiceId for subsequent API calls
    echo "Payment URL: {$invoice['paymentUrl']}\n";
}
```

#### Get Invoice by ID

```php
$response = $client->invoices()->get('invoice-id-here');

if (!$response['error']) {
    $invoice = $response['data'];
    echo "Status: {$invoice['status']}\n";
    echo "Amount: {$invoice['amount']} {$invoice['currency']}\n";
}
```

#### List Invoices

```php
$response = $client->invoices()->list([
    'page' => 1,
    'limit' => 20
]);

if (!$response['error']) {
    $invoices = $response['data'];
    foreach ($invoices as $invoice) {
        echo "{$invoice['invoiceId']} - {$invoice['status']} - \${$invoice['amount']}\n";
    }
}
```

#### Update Invoice Status

```php
$response = $client->invoices()->updateStatus(
    invoiceId: 'invoice-id-here',
    status: 'completed',
    txHash: '0x1234567890abcdef'  // Optional: blockchain transaction hash
);

if (!$response['error']) {
    echo "Invoice status updated successfully";
}
```

### Webhook Management

#### Create a Webhook

```php
$response = $client->webhooks()->create(
    url: 'https://yourapp.com/webhooks/bixypay',
    events: ['invoice.created', 'invoice.completed', 'invoice.failed']
);

if (!$response['error']) {
    $webhook = $response['data'];
    echo "Webhook ID: {$webhook['id']}";
}
```

#### List Webhooks

```php
$response = $client->webhooks()->list();

if (!$response['error']) {
    $webhooks = $response['data'];
    foreach ($webhooks as $webhook) {
        echo "{$webhook['id']} - {$webhook['url']}\n";
    }
}
```

#### Delete a Webhook

```php
$response = $client->webhooks()->delete('webhook-id-here');

if (!$response['error']) {
    echo "Webhook deleted successfully";
}
```

## Error Handling

All SDK methods return an associative array with `data` and `error` keys:

```php
$response = $client->invoices()->create([
    'amount' => 100,
    'currency' => 'USD'
]);

if ($response['error']) {
    $error = $response['error'];
    echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
    
    if (isset($error['statusCode'])) {
        echo "HTTP Status: {$error['statusCode']}\n";
    }
} else {
    // Success
    $invoice = $response['data'];
    echo "Invoice created: {$invoice['invoiceId']}";
}
```

## Dynamic Token Management

You can update authentication tokens dynamically:

```php
$client = new BixyPayClient(baseUrl: 'https://api.bixypay.com');

// Login and get JWT token
$loginResponse = $client->auth()->login('user@example.com', 'password');

// Create API key using JWT
$apiKeyResponse = $client->auth()->createApiKey('My API Key');

// Switch to API key authentication
$client->setApiKey($apiKeyResponse['data']['key']);

// Now use API key for subsequent requests
$balance = $client->merchants()->getBalance();
```

## Laravel Integration

### Service Provider

```php
// config/services.php
return [
    'bixypay' => [
        'base_url' => env('BIXYPAY_BASE_URL', 'https://api.bixypay.com'),
        'api_key' => env('BIXYPAY_API_KEY'),
    ],
];

// app/Providers/AppServiceProvider.php
use BixyPay\BixyPayClient;

public function register()
{
    $this->app->singleton(BixyPayClient::class, function ($app) {
        return new BixyPayClient(
            baseUrl: config('services.bixypay.base_url'),
            apiKey: config('services.bixypay.api_key')
        );
    });
}
```

### Usage in Controller

```php
use BixyPay\BixyPayClient;

class PaymentController extends Controller
{
    public function createInvoice(BixyPayClient $bixypay)
    {
        $response = $bixypay->invoices()->create([
            'amount' => 100.00,
            'currency' => 'USD',
            'description' => 'Order #12345'
        ]);

        if ($response['error']) {
            return response()->json($response['error'], 400);
        }

        return response()->json($response['data']);
    }
}
```

## Support

- **Documentation**: [https://docs.bixypay.com](https://docs.bixypay.com)
- **API Reference**: [https://api.bixypay.com/docs](https://api.bixypay.com/docs)
- **Issues**: [https://github.com/bixypay/fintech-sdk-php/issues](https://github.com/bixypay/fintech-sdk-php/issues)

## License

MIT License - see LICENSE file for details
