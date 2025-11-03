<?php

require '../vendor/autoload.php';

use BixyPay\BixyPayClient;

function main() {
    $baseUrl = 'https://api.bixypay.com';
    
    // Step 1: Register a new merchant
    echo "1. Registering new merchant...\n";
    $client = new BixyPayClient(baseUrl: $baseUrl);
    
    $response = $client->auth()->register(
        email: 'test@example.com',
        password: 'SecurePassword123!',
        businessName: 'Test Merchant Corp',
        businessAddress: '123 Test Street'
    );
    
    if ($response['error']) {
        echo "Registration error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    echo "✓ Merchant registered: {$response['data']['id']}\n";
    
    // Step 2: Login and get JWT token
    echo "\n2. Logging in...\n";
    $response = $client->auth()->login(
        email: 'test@example.com',
        password: 'SecurePassword123!'
    );
    
    if ($response['error']) {
        echo "Login error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    echo "✓ Logged in successfully\n";
    
    // Step 3: Create API key
    echo "\n3. Creating API key...\n";
    $response = $client->auth()->createApiKey(
        name: 'Production API Key',
        scopes: []
    );
    
    if ($response['error']) {
        echo "API key error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    $apiKey = $response['data']['key'];
    echo "✓ API Key created: " . substr($apiKey, 0, 20) . "...\n";
    
    // Step 4: Switch to API key authentication
    $client->setApiKey($apiKey);
    
    // Step 5: Get merchant balance
    echo "\n4. Getting merchant balance...\n";
    $response = $client->merchants()->getBalance();
    
    if ($response['error']) {
        echo "Balance error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    $balance = $response['data'];
    echo "✓ Balance: {$balance['balance']} {$balance['currency']}\n";
    
    // Step 6: Create an invoice
    echo "\n5. Creating invoice...\n";
    $response = $client->invoices()->create([
        'amount' => 99.99,
        'currency' => 'USD',
        'description' => 'Premium Subscription - Monthly',
        'metadata' => [
            'customer_id' => 'cust_12345',
            'plan' => 'premium'
        ],
        'callbackUrl' => 'https://example.com/webhook'
    ]);
    
    if ($response['error']) {
        echo "Invoice error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    $invoice = $response['data'];
    echo "✓ Invoice created: {$invoice['invoiceId']}\n";
    echo "  Amount: \${$invoice['amount']} {$invoice['currency']}\n";
    echo "  Status: {$invoice['status']}\n";
    
    // Step 7: List invoices
    echo "\n6. Listing invoices...\n";
    $response = $client->invoices()->list(['page' => 1, 'limit' => 10]);
    
    if ($response['error']) {
        echo "List error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    $invoices = $response['data'];
    echo "✓ Found " . count($invoices) . " invoice(s)\n";
    
    // Step 8: Create webhook
    echo "\n7. Creating webhook...\n";
    $response = $client->webhooks()->create(
        url: 'https://example.com/webhooks/bixypay',
        events: ['invoice.created', 'invoice.completed']
    );
    
    if ($response['error']) {
        echo "Webhook error: " . json_encode($response['error']) . "\n";
        return;
    }
    
    $webhook = $response['data'];
    echo "✓ Webhook created: {$webhook['id']}\n";
    
    echo "\n✅ All operations completed successfully!\n";
}

main();
