<?php

namespace App\Services;

use GuzzleHttp\Client;
use WebSocket\Client as WebSocketClient;
use Illuminate\Support\Facades\Log;
use Exception;


class KrakenApiServicePrivate
{
    private string $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $httpClient;

    public function __construct()
    {
        $this->baseUrl = config('services.kraken.base_url');
        $this->apiKey = config('services.kraken.api_key');
        $this->apiSecret = config('services.kraken.api_secret');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl, // Standaard API-basispad
            'timeout' => 10, // Stel een timeout in (in seconden)
            'headers' => [
            ],
        ]);

        Log::info('KrakenApiService initialized with API Key:', ['api_key' => $this->apiKey]);
    }


    public function sendRequest(string $endpoint, array $data = [])
    {

        $url = $endpoint;
        $nonce = (string) round(microtime(true) * 1000);
        $data['nonce'] = $nonce;

        $signature = $this->generateSignature($endpoint, $data, $nonce);

        Log::info('Request Headers:', [
            'API-Key' => $this->apiKey,
            'API-Sign' => $signature,
        ]);
        Log::info('Request Payload:', $data);

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'API-Key' => $this->apiKey,
                    'API-Sign' => $signature,
                ],
                'form_params' => $data, 
            ]);

            // Decodeer en log de response
            $responseBody = json_decode($response->getBody(), true);
            Log::info('API Response:', $responseBody);

            return $responseBody;
        } catch (Exception $e) {
            Log::error('API Error:', ['message' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    protected function generateSignature(string $endpoint, array $data, string $nonce): string
    {
        $postData = http_build_query($data, '', '&');
        $hash = hash('sha256', $nonce . $postData, true);
        $hmac = hash_hmac('sha512', $endpoint . $hash, base64_decode($this->apiSecret), true);

        $signature = base64_encode($hmac);

        return $signature;
    }
}
