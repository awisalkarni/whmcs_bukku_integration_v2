<?php

namespace GBNetwork\BukkuIntegration\Api;

use WHMCS\Module\Addon\Setting;

class BukkuApi
{
    private $apiToken;
    private $companySubdomain;
    private $baseUrl;
    
    public function __construct()
    {
        // Get module settings
        $this->apiToken = $this->getSetting('api_token');
        $this->companySubdomain = $this->getSetting('company_subdomain');
        $this->baseUrl = "https://{$this->companySubdomain}.bukku.my/api/v1/";
    }
    
    /**
     * Get a module setting
     *
     * @param string $setting
     * @return string
     */
    private function getSetting($setting)
    {
        $value = Setting::where('module', 'bukku_integration_v2')
            ->where('setting', $setting)
            ->value('value');
            
        return $value;
    }
    
    /**
     * Make a GET request to the Bukku API
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function get($endpoint, $params = [])
    {
        return $this->request('GET', $endpoint, $params);
    }
    
    /**
     * Make a POST request to the Bukku API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post($endpoint, $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }
    
    /**
     * Make a PUT request to the Bukku API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put($endpoint, $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }
    
    /**
     * Make a DELETE request to the Bukku API
     *
     * @param string $endpoint
     * @return array
     */
    public function delete($endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }
    
    /**
     * Make a request to the Bukku API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function request($method, $endpoint, $data = [])
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'status' => 'error',
                'message' => "cURL Error: $error"
            ];
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $responseData;
        } else {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown error';
            return [
                'status' => 'error',
                'message' => "API Error ($httpCode): $errorMessage",
                'response' => $responseData
            ];
        }
    }
}