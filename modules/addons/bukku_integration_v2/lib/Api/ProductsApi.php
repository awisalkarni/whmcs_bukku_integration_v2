<?php

namespace GBNetwork\BukkuIntegration\Api;

use GBNetwork\BukkuIntegration\Models\Product;

class ProductsApi extends BaseApi
{
    /**
     * Get all products
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllProducts(int $page = 1, int $perPage = 100): array
    {
        return $this->get('/products', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }
    
    /**
     * Get a specific product
     *
     * @param int $productId
     * @return array
     */
    public function getProduct(int $productId): array
    {
        return $this->get("/products/{$productId}");
    }
    
    /**
     * Find a product by name
     *
     * @param string $name
     * @return array
     */
    public function findProductByName(string $name): array
    {
        $result = $this->get('/products', [
            'name' => $name,
        ]);
        
        if ($result['status'] === 'success' && !empty($result['data']['data'])) {
            return [
                'status' => 'success',
                'exists' => true,
                'product' => $result['data']['data'][0],
            ];
        }
        
        return [
            'status' => 'success',
            'exists' => false,
        ];
    }
    
    /**
     * Find a product by SKU
     *
     * @param string $sku
     * @return array
     */
    public function findProductBySku(string $sku): array
    {
        $result = $this->get('/products', [
            'sku' => $sku,
        ]);
        
        if ($result['status'] === 'success' && !empty($result['data']['data'])) {
            return [
                'status' => 'success',
                'exists' => true,
                'product' => $result['data']['data'][0],
            ];
        }
        
        return [
            'status' => 'success',
            'exists' => false,
        ];
    }
    
    /**
     * Create a new product
     *
     * @param Product $product
     * @return array
     */
    public function createProduct(Product $product): array
    {
        $result = $this->post('/products', $product->toArray());
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'product' => $result['data'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Update an existing product
     *
     * @param int $productId
     * @param Product $product
     * @return array
     */
    public function updateProduct(int $productId, Product $product): array
    {
        $result = $this->put("/products/{$productId}", $product->toArray());
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'product' => $result['data'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Delete a product
     *
     * @param int $productId
     * @return array
     */
    public function deleteProduct(int $productId): array
    {
        return $this->delete("/products/{$productId}");
    }
}