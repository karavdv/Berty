<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class KrakenApiServicePublic
{
    private string $baseUrl;
    protected $httpClient;

    public function __construct()
    {
        $this->baseUrl = config('services.kraken.base_url');
        $this->httpClient = new Client([
            'timeout' => 10, // Timeout in seconds
        ]);
    }

    public function publicRequest(string $endpoint, array $data= []): array
    {
        $url = $this->baseUrl . '/0/public/' . $endpoint;
        Log::info('KrakenApiService initialized with url:', ['url' => $url, 'data' => $data]);


        try {
            $response = $this->httpClient->getAsync($url, [
                'query' => $data, 
            ])->wait();

            // Decode and log response
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['error']) && !empty($responseBody['error'])) {
                Log::error('Kraken API Error:', ['endpoint' => $endpoint, 'errors' => $responseBody['error']]);
                return ['error' => $responseBody['error']];
            }
            
            Log::info('Public API Response Status:', ['status' => $response->getStatusCode()]);

            return $responseBody;
        } catch (Exception $e) {
            Log::error('Public API Error:', ['message' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

}
