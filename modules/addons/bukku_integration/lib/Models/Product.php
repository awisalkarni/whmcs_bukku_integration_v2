<?php

namespace GBNetwork\BukkuIntegration\Models;

class Product extends Model
{
    /**
     * Create a new product instance
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // Set default values
        $defaults = [
            'type' => 'SERVICE',
            'currency_code' => 'MYR',
            'is_active' => true,
            'tax_rate' => 0
        ];
        
        parent::__construct(array_merge($defaults, $attributes));
    }
    
    /**
     * Set the product name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->setAttribute('name', $name);
        
        return $this;
    }
    
    /**
     * Set the product description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->setAttribute('description', $description);
        
        return $this;
    }
    
    /**
     * Set the product SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self
    {
        $this->setAttribute('sku', $sku);
        
        return $this;
    }
    
    /**
     * Set the product type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->setAttribute('type', $type);
        
        return $this;
    }
    
    /**
     * Set the product unit price
     *
     * @param float $price
     * @return $this
     */
    public function setUnitPrice(float $price): self
    {
        $this->setAttribute('unit_price', $price);
        
        return $this;
    }
    
    /**
     * Set the product tax rate
     *
     * @param float $taxRate
     * @return $this
     */
    public function setTaxRate(float $taxRate): self
    {
        $this->setAttribute('tax_rate', $taxRate);
        
        return $this;
    }
    
    /**
     * Set whether the product is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        $this->setAttribute('is_active', $isActive);
        
        return $this;
    }
}