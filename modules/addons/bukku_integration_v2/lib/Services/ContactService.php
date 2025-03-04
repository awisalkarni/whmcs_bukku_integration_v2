<?php

namespace GBNetwork\BukkuIntegration\Services;

use GBNetwork\BukkuIntegration\Api\ContactsApi;
use GBNetwork\BukkuIntegration\Models\Contact;
use WHMCS\Database\Capsule;
use WHMCS\User\Client;

class ContactService
{
    private ContactsApi $contactsApi;
    
    public function __construct()
    {
        $this->contactsApi = new ContactsApi();
    }
    
    /**
     * Get all contacts from WHMCS
     *
     * @return array
     */
    public function getContactsFromWHMCS(): array
    {
        try {
            return Client::orderBy('id', 'desc')->get()->toArray();
        } catch (\Exception $e) {
            // Log error
            logActivity("Error fetching WHMCS contacts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all contacts from Bukku API
     *
     * @return array
     */
    public function getContactsFromBukku(): array
    {
        try {
            $result = $this->contactsApi->getAllContacts();
            
            if ($result['status'] === 'success') {
                return $result['contacts'];
            }
            
            return [];
        } catch (\Exception $e) {
            // Log error
            logActivity("Error fetching Bukku contacts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync all WHMCS clients to Bukku contacts
     *
     * @return array
     */
    public function syncAllContacts(): array
    {
        try {
            $clients = Client::all();
            $results = [
                'total' => count($clients),
                'success' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            foreach ($clients as $client) {
                $result = $this->syncContact($client->id);
                
                if ($result['status'] === 'success') {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
                
                $results['details'][] = $result;
            }
            
            return [
                'status' => 'success',
                'message' => "Synced {$results['success']} contacts, {$results['failed']} failed",
                'data' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to sync contacts: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync a specific WHMCS client to Bukku
     *
     * @param int $clientId
     * @return array
     */
    public function syncContact(int $clientId): array
    {
        try {
            // Get client from WHMCS
            $client = Client::find($clientId);
            
            if (!$client) {
                return [
                    'status' => 'error',
                    'message' => "Client not found: {$clientId}"
                ];
            }
            
            // Check if contact already exists in Bukku by email
            $existingContact = $this->contactsApi->findContactByEmail($client->email);
            
            // Prepare contact data
            $contactData = $this->prepareContactData($client);
            
            // Create or update contact in Bukku
            if ($existingContact['exists']) {
                $bukkuContactId = $existingContact['contact']['id'];
                $contactModel = new Contact($contactData);
                $result = $this->contactsApi->updateContact($bukkuContactId, $contactModel);
                $action = 'updated';
            } else {
                // Determine if business or individual
                if (!empty($client->companyname)) {
                    $result = $this->contactsApi->createMalaysianBusiness(
                        $client->companyname,
                        $client->email,
                        $client->tax_id,
                        null,
                        null,
                        $client->phonenumber,
                        $this->formatAddress($client)
                    );
                } else {
                    $result = $this->contactsApi->createMalaysianIndividual(
                        $client->firstname . ' ' . $client->lastname,
                        $client->email,
                        $client->phonenumber,
                        $this->formatAddress($client),
                        null
                    );
                }
                $action = 'created';
            }
            
            // Update sync status in database
            if ($result['status'] === 'success') {
                $bukkuContactId = $result['contact']['id'] ?? $existingContact['contact']['id'];
                
                $this->updateSyncStatus($clientId, $bukkuContactId, 'success');
                
                return [
                    'status' => 'success',
                    'message' => "Contact {$action} successfully",
                    'whmcs_id' => $clientId,
                    'bukku_id' => $bukkuContactId
                ];
            } else {
                $this->updateSyncStatus($clientId, null, 'failed', $result['message'] ?? 'Unknown error');
                
                return [
                    'status' => 'error',
                    'message' => "Failed to {$action} contact: " . ($result['message'] ?? 'Unknown error'),
                    'whmcs_id' => $clientId
                ];
            }
        } catch (\Exception $e) {
            $this->updateSyncStatus($clientId, null, 'failed', $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'whmcs_id' => $clientId
            ];
        }
    }
    
    /**
     * Get all synced contacts
     *
     * @return array
     */
    public function getAllSyncedContacts(): array
    {
        try {
            return Capsule::table('mod_bukku_integration_v2_contacts')
                ->orderBy('last_synced', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get contact details by Bukku ID
     *
     * @param int $bukkuId
     * @return array
     */
    public function getContactDetails(int $bukkuId): array
    {
        try {
            $result = $this->contactsApi->getContact($bukkuId);
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Sync selected contacts
     *
     * @param array $clientIds
     * @return array
     */
    public function syncSelectedContacts(array $clientIds): array
    {
        $results = [
            'total' => count($clientIds),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($clientIds as $clientId) {
            $result = $this->syncContact((int)$clientId);
            
            if ($result['status'] === 'success') {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = $result;
        }
        
        return [
            'status' => 'success',
            'message' => "Synced {$results['success']} contacts, {$results['failed']} failed",
            'data' => $results
        ];
    }
    
    /**
     * Update sync status in database
     *
     * @param int $clientId
     * @param int|null $bukkuId
     * @param string $status
     * @param string|null $errorMessage
     * @return void
     */
    private function updateSyncStatus(int $clientId, ?int $bukkuId, string $status, ?string $errorMessage = null): void
    {
        $client = Client::find($clientId);
        
        if (!$client) {
            return;
        }
        
        $data = [
            'whmcs_id' => $clientId,
            'name' => $client->firstname . ' ' . $client->lastname,
            'email' => $client->email,
            'type' => !empty($client->companyname) ? 'business' : 'individual',
            'sync_status' => $status,
            'last_synced' => date('Y-m-d H:i:s'),
            'error_message' => $errorMessage
        ];
        
        if ($bukkuId) {
            $data['bukku_id'] = $bukkuId;
        }
        
        // Check if record exists
        $exists = Capsule::table('mod_bukku_integration_contacts')
            ->where('whmcs_id', $clientId)
            ->exists();
        
        if ($exists) {
            Capsule::table('mod_bukku_integration_contacts')
                ->where('whmcs_id', $clientId)
                ->update($data);
        } else {
            Capsule::table('mod_bukku_integration_contacts')->insert($data);
        }
    }
    
    /**
     * Format client address for Bukku
     *
     * @param Client $client
     * @return string
     */
    private function formatAddress(Client $client): string
    {
        $address = [];
        
        if (!empty($client->address1)) {
            $address[] = $client->address1;
        }
        
        if (!empty($client->address2)) {
            $address[] = $client->address2;
        }
        
        $cityStatePostcode = [];
        
        if (!empty($client->city)) {
            $cityStatePostcode[] = $client->city;
        }
        
        if (!empty($client->state)) {
            $cityStatePostcode[] = $client->state;
        }
        
        if (!empty($client->postcode)) {
            $cityStatePostcode[] = $client->postcode;
        }
        
        if (!empty($cityStatePostcode)) {
            $address[] = implode(', ', $cityStatePostcode);
        }
        
        if (!empty($client->country)) {
            $address[] = $client->country;
        }
        
        return implode(', ', $address);
    }
    
    /**
     * Prepare contact data from WHMCS client
     *
     * @param Client $client
     * @return array
     */
    private function prepareContactData(Client $client): array
    {
        $data = [
            'email' => $client->email,
            'phone' => $client->phonenumber,
            'address' => $this->formatAddress($client),
            'default_currency_code' => 'MYR'
        ];
        
        if (!empty($client->companyname)) {
            $data['legal_name'] = $client->companyname;
            $data['entity_type'] = 'MALAYSIAN_COMPANY';
            
            if (!empty($client->tax_id)) {
                $data['reg_no'] = $client->tax_id;
            }
        } else {
            $data['legal_name'] = $client->firstname . ' ' . $client->lastname;
            $data['entity_type'] = 'MALAYSIAN_INDIVIDUAL';
        }
        
        $data['types'] = ['customer'];
        
        return $data;
    }
}