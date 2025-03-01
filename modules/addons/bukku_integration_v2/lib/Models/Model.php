<?php

namespace GBNetwork\BukkuIntegration\Models;

abstract class Model
{
    protected array $attributes = [];
    
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        
        return $this;
    }
    
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        
        return $this;
    }
    
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
    
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    public function __set(string $key, $value)
    {
        $this->setAttribute($key, $value);
    }
    
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    
    public function toArray(): array
    {
        return $this->attributes;
    }
}