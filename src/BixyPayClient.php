<?php

namespace BixyPay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class BixyPayClient
{
    private $client;
    private $baseUrl;
    private $apiKey;
    private $jwtToken;

    public function __construct(string $baseUrl, ?string $apiKey = null, ?string $jwtToken = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->jwtToken = $jwtToken;
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30.0,
        ]);
    }

    private function getHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if ($this->apiKey) {
            $headers['X-API-Key'] = $this->apiKey;
        }

        if ($this->jwtToken) {
            $headers['Authorization'] = 'Bearer ' . $this->jwtToken;
        }

        return $headers;
    }

    private function request(string $method, string $path, ?array $data = null, ?array $params = null): array
    {
        try {
            $options = [
                'headers' => $this->getHeaders(),
            ];

            if ($data !== null) {
                $options['json'] = $data;
            }

            if ($params !== null) {
                $options['query'] = $params;
            }

            $response = $this->client->request($method, $path, $options);
            $body = $response->getBody()->getContents();

            return [
                'data' => $body ? json_decode($body, true) : null,
                'error' => null,
            ];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $error = $response
                ? json_decode($response->getBody()->getContents(), true)
                : ['message' => $e->getMessage()];

            return [
                'data' => null,
                'error' => $error,
            ];
        }
    }

    public function auth(): AuthResource
    {
        return new AuthResource($this);
    }

    public function merchants(): MerchantsResource
    {
        return new MerchantsResource($this);
    }

    public function invoices(): InvoicesResource
    {
        return new InvoicesResource($this);
    }

    public function webhooks(): WebhooksResource
    {
        return new WebhooksResource($this);
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setJwtToken(string $token): void
    {
        $this->jwtToken = $token;
    }
}

class AuthResource
{
    private $client;

    public function __construct(BixyPayClient $client)
    {
        $this->client = $client;
    }

    public function register(string $email, string $password, string $businessName, ?string $businessAddress = null): array
    {
        return $this->client->request('POST', '/api/v1/auth/register', [
            'email' => $email,
            'password' => $password,
            'businessName' => $businessName,
            'businessAddress' => $businessAddress,
        ]);
    }

    public function login(string $email, string $password): array
    {
        $response = $this->client->request('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($response['data'] && isset($response['data']['access_token'])) {
            $this->client->setJwtToken($response['data']['access_token']);
        }

        return $response;
    }

    public function createApiKey(string $name, ?array $scopes = null): array
    {
        return $this->client->request('POST', '/api/v1/auth/api-keys', [
            'name' => $name,
            'scopes' => $scopes ?? [],
        ]);
    }

    public function listApiKeys(): array
    {
        return $this->client->request('GET', '/api/v1/auth/api-keys');
    }

    public function revokeApiKey(string $keyId): array
    {
        return $this->client->request('DELETE', "/api/v1/auth/api-keys/{$keyId}");
    }
}

class MerchantsResource
{
    private $client;

    public function __construct(BixyPayClient $client)
    {
        $this->client = $client;
    }

    public function getProfile(): array
    {
        return $this->client->request('GET', '/api/v1/merchants/profile');
    }

    public function getBalance(): array
    {
        return $this->client->request('GET', '/api/v1/merchants/balance');
    }

    public function updateKycStatus(string $status): array
    {
        return $this->client->request('PATCH', '/api/v1/merchants/kyc', [
            'status' => $status,
        ]);
    }
}

class InvoicesResource
{
    private $client;

    public function __construct(BixyPayClient $client)
    {
        $this->client = $client;
    }

    public function create(array $invoiceData): array
    {
        return $this->client->request('POST', '/api/v1/transactions/invoices', $invoiceData);
    }

    public function get(string $invoiceId): array
    {
        return $this->client->request('GET', "/api/v1/transactions/invoices/{$invoiceId}");
    }

    public function list(?array $params = null): array
    {
        return $this->client->request('GET', '/api/v1/transactions/invoices', null, $params);
    }

    public function updateStatus(string $invoiceId, string $status, ?string $txHash = null): array
    {
        return $this->client->request('PATCH', "/api/v1/transactions/invoices/{$invoiceId}/status", [
            'status' => $status,
            'txHash' => $txHash,
        ]);
    }
}

class WebhooksResource
{
    private $client;

    public function __construct(BixyPayClient $client)
    {
        $this->client = $client;
    }

    public function create(string $url, array $events): array
    {
        return $this->client->request('POST', '/api/v1/webhooks', [
            'url' => $url,
            'events' => $events,
        ]);
    }

    public function list(): array
    {
        return $this->client->request('GET', '/api/v1/webhooks');
    }

    public function delete(string $webhookId): array
    {
        return $this->client->request('DELETE', "/api/v1/webhooks/{$webhookId}");
    }
}
