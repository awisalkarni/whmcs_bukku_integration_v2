<?php

namespace GBNetwork\BukkuIntegration\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use WHMCS\Database\Capsule;

class BukkuApiClient
{
    private Client $client;
    private string $apiToken;
    private string $companySubdomain;
    private string $baseUrl = 'https://api.bukku.my';
    
    public function __construct()
    {
        // Get module settings
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'bukku_integration')
            ->get()
            ->pluck('value', 'setting')
            ->toArray();
        
        $this->apiToken = $settings['api_token'] ?? '';
        $this->companySubdomain = $settings['company_subdomain'] ?? '';
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Company-Subdomain' => $this->companySubdomain,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
    
    /**
     * Make a GET request to the Bukku API
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $params,
            ]);
            
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (GuzzleException $e) {
            $this->logError($e, $endpoint, 'GET', $params);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Make a POST request to the Bukku API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data,
            ]);
            
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (GuzzleException $e) {
            $this->logError($e, $endpoint, 'POST', $data);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Make a PUT request to the Bukku API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->put($endpoint, [
                'json' => $data,
            ]);
            
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (GuzzleException $e) {
            $this->logError($e, $endpoint, 'PUT', $data);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Make a DELETE request to the Bukku API
     *
     * @param string $endpoint
     * @return array
     */
    public function delete(string $endpoint): array
    {
        try {
            $response = $this->client->delete($endpoint);
            
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (GuzzleException $e) {
            $this->logError($e, $endpoint, 'DELETE');
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Log API errors
     *
     * @param GuzzleException $exception
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return void
     */
    private function logError(GuzzleException $exception, string $endpoint, string $method, array $data = []): void
    {
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'bukku_integration')
            ->where('setting', 'debug_mode')
            ->first();
        
        $debugMode = $settings ? $settings->value : '0';
        
        if ($debugMode === 'on') {
            $logEntry = [
                'module' => 'bukku_integration',
                'date' => date('Y-m-d H:i:s'),
                'description' => "API Error: {$method} {$endpoint}",
                'message' => $exception->getMessage(),
                'data' => json_encode($data),
            ];
            
            Capsule::table('mod_bukku_integration_logs')->insert($logEntry);
        }
        
        // Always log critical errors
        logActivity("Bukku API Error: {$method} {$endpoint} - {$exception->getMessage()}");
    }
}