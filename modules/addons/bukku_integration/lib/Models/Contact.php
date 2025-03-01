<?php

namespace GBNetwork\BukkuIntegration\Models;

class Contact extends Model
{
    /**
     * Create a new contact instance
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // Set default values
        $defaults = [
            'types' => ['customer'],
            'entity_type' => 'MALAYSIAN_INDIVIDUAL',
            'default_currency_code' => 'MYR'
        ];
        
        parent::__construct(array_merge($defaults, $attributes));
    }
    
    /**
     * Set the contact's legal name
     *
     * @param string $name
     * @return $this
     */
    public function setLegalName(string $name): self
    {
        $this->setAttribute('legal_name', $name);
        
        return $this;
    }
    
    /**
     * Set the contact's email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->setAttribute('email', $email);
        
        return $this;
    }
    
    /**
     * Set the contact's phone number
     *
     * @param string $phone
     * @return $this
     */
    public function setPhone(string $phone): self
    {
        $this->setAttribute('phone', $phone);
        
        return $this;
    }
    
    /**
     * Set the contact's address
     *
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): self
    {
        $this->setAttribute('address', $address);
        
        return $this;
    }
    
    /**
     * Set the contact's entity type
     *
     * @param string $entityType
     * @return $this
     */
    public function setEntityType(string $entityType): self
    {
        $this->setAttribute('entity_type', $entityType);
        
        return $this;
    }
    
    /**
     * Set the contact's types
     *
     * @param array $types
     * @return $this
     */
    public function setTypes(array $types): self
    {
        $this->setAttribute('types', $types);
        
        return $this;
    }
    
    /**
     * Set the contact's other name (company name)
     *
     * @param string $otherName
     * @return $this
     */
    public function setOtherName(string $otherName): self
    {
        $this->setAttribute('other_name', $otherName);
        
        return $this;
    }
    
    /**
     * Set the contact's registration number
     *
     * @param string $regNo
     * @return $this
     */
    public function setRegNo(string $regNo): self
    {
        $this->setAttribute('reg_no', $regNo);
        
        return $this;
    }
}