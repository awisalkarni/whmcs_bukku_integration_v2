<?php

namespace GBNetwork\BukkuIntegration\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use WHMCS\Module\Addon\Setting;

class BaseApi
{
    protected Client $client;
    protected string $baseUrl = 'https://api.bukku.my';
    protected string $apiToken;
    protected string $companySubdomain;
    
    public function __construct()
    {
        // Get module settings
        $this->apiToken = $this->getSetting('api_token');
        $this->companySubdomain = $this->getSetting('company_subdomain');
        
        // Initialize HTTP client
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => true,
        ]);
    }
    
    /**
     * Get a module setting
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting(string $key, $default = '')
    {
        try {
            $setting = Setting::where('module', 'bukku_integration')
                ->where('setting', $key)
                ->first();
            
            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
    
    /**
     * Make a GET request to the API
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    protected function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $params,
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'status' => 'success',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }
    
    /**
     * Make a POST request to the API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    protected function post(string $endpoint, array $data): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data,
            ]);
            
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            return [
                'status' => 'success',
                'data' => $responseData,
            ];
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }
    
    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    protected function put(string $endpoint, array $data): array
    {
        try {
            $response = $this->client->put($endpoint, [
                'json' => $data,
            ]);
            
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            return [
                'status' => 'success',
                'data' => $responseData,
            ];
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }
    
    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint
     * @return array
     */
    protected function delete(string $endpoint): array
    {
        try {
            $response = $this->client->delete($endpoint);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'status' => 'success',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }
    
    /**
     * Handle request exceptions
     *
     * @param RequestException $e
     * @return array
     */
    protected function handleRequestException(RequestException $e): array
    {
        $response = $e->getResponse();
        $message = $e->getMessage();
        
        if ($response) {
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            $message = $responseBody['message'] ?? "HTTP Error: {$statusCode}";
        }
        
        // Log the error if debug mode is enabled
        if ($this->getSetting('debug_mode') === 'on') {
            logActivity("Bukku API Error: {$message}");
        }
        
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }
}