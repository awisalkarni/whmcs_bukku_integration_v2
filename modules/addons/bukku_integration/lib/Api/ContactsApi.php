<?php

namespace GBNetwork\BukkuIntegration\Api;

use GBNetwork\BukkuIntegration\Models\Contact;

class ContactsApi extends BaseApi
{
    /**
     * Get all contacts
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllContacts(int $page = 1, int $perPage = 100): array
    {
        return $this->get('/contacts', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }
    
    /**
     * Get a specific contact
     *
     * @param int $contactId
     * @return array
     */
    public function getContact(int $contactId): array
    {
        return $this->get("/contacts/{$contactId}");
    }
    
    /**
     * Find a contact by email
     *
     * @param string $email
     * @return array
     */
    public function findContactByEmail(string $email): array
    {
        $result = $this->get('/contacts', [
            'email' => $email,
        ]);
        
        if ($result['status'] === 'success' && !empty($result['data']['data'])) {
            return [
                'status' => 'success',
                'exists' => true,
                'contact' => $result['data']['data'][0],
            ];
        }
        
        return [
            'status' => 'success',
            'exists' => false,
        ];
    }
    
    /**
     * Create a new contact
     *
     * @param Contact $contact
     * @return array
     */
    public function createContact(Contact $contact): array
    {
        $result = $this->post('/contacts', $contact->toArray());
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'contact' => $result['data'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Create a Malaysian individual contact
     *
     * @param string $name
     * @param string $email
     * @param string $phone
     * @param string $address
     * @param string|null $idNumber
     * @return array
     */
    public function createMalaysianIndividual(
        string $name,
        string $email,
        string $phone,
        string $address,
        ?string $idNumber = null
    ): array {
        $data = [
            'legal_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'entity_type' => 'MALAYSIAN_INDIVIDUAL',
            'types' => ['customer'],
            'default_currency_code' => 'MYR',
        ];
        
        if ($idNumber) {
            $data['id_number'] = $idNumber;
        }
        
        $result = $this->post('/contacts', $data);
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'contact' => $result['data'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Create a Malaysian business contact
     *
     * @param string $companyName
     * @param string $email
     * @param string|null $regNo
     * @param string|null $contactPerson
     * @param string|null $website
     * @param string $phone
     * @param string $address
     * @return array
     */
    public function createMalaysianBusiness(
        string $companyName,
        string $email,
        ?string $regNo = null,
        ?string $contactPerson = null,
        ?string $website = null,
        string $phone = '',
        string $address = ''
    ): array {
        $data = [
            'legal_name' => $companyName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'entity_type' => 'MALAYSIAN_COMPANY',
            'types' => ['customer'],
            'default_currency_code' => 'MYR',
        ];
        
        if ($regNo) {
            $data['reg_no'] = $regNo;
        }
        
        if ($contactPerson) {
            $data['contact_person'] = $contactPerson;
        }
        
        if ($website) {
            $data['website'] = $website;
        }
        
        $result = $this->post('/contacts', $data);
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'contact' => $result['data'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Update an existing contact
     *
     * @param int $contactId
     * @param Contact $contact
     * @return array
     */
    public function updateContact(int $contactId, Contact $contact): array
    {
        $result = $this->put("/contacts/{$contactId}", $contact->toArray());
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'contact' => $result['data'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Delete a contact
     *
     * @param int $contactId
     * @return array
     */
    public function deleteContact(int $contactId): array
    {
        return $this->delete("/contacts/{$contactId}");
    }
}