<?php

namespace GBNetwork\BukkuIntegration\Services;

use GBNetwork\BukkuIntegration\Api\ProductsApi;
use GBNetwork\BukkuIntegration\Models\Product;
use WHMCS\Database\Capsule;
use WHMCS\Product\Product as WhmcsProduct;

class ProductService
{
    private ProductsApi $productsApi;
    
    public function __construct()
    {
        $this->productsApi = new ProductsApi();
    }
    
    /**
     * Sync all WHMCS products to Bukku
     *
     * @return array
     */
    public function syncAllProducts(): array
    {
        try {
            $products = WhmcsProduct::all();
            $results = [
                'total' => count($products),
                'success' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            foreach ($products as $product) {
                $result = $this->syncProduct($product->id);
                
                if ($result['status'] === 'success') {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
                
                $results['details'][] = $result;
            }
            
            return [
                'status' => 'success',
                'message' => "Synced {$results['success']} products, {$results['failed']} failed",
                'data' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to sync products: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync a specific WHMCS product to Bukku
     *
     * @param int $productId
     * @return array
     */
    public function syncProduct(int $productId): array
    {
        try {
            // Get product from WHMCS
            $product = WhmcsProduct::find($productId);
            
            if (!$product) {
                return [
                    'status' => 'error',
                    'message' => "Product not found: {$productId}"
                ];
            }
            
            // Check if product already exists in Bukku by name
            $existingProduct = $this->productsApi->findProductByName($product->name);
            
            // Prepare product data
            $productData = $this->prepareProductData($product);
            
            // Create or update product in Bukku
            if ($existingProduct['exists']) {
                $bukkuProductId = $existingProduct['product']['id'];
                $productModel = new Product($productData);
                $result = $this->productsApi->updateProduct($bukkuProductId, $productModel);
                $action = 'updated';
            } else {
                $productModel = new Product($productData);
                $result = $this->productsApi->createProduct($productModel);
                $action = 'created';
            }
            
            // Update sync status in database
            if ($result['status'] === 'success') {
                $bukkuProductId = $result['product']['id'] ?? $existingProduct['product']['id'];
                
                $this->updateSyncStatus($productId, $bukkuProductId, 'success');
                
                return [
                    'status' => 'success',
                    'message' => "Product {$action} successfully",
                    'whmcs_id' => $productId,
                    'bukku_id' => $bukkuProductId
                ];
            } else {
                $this->updateSyncStatus($productId, null, 'failed', $result['message'] ?? 'Unknown error');
                
                return [
                    'status' => 'error',
                    'message' => "Failed to {$action} product: " . ($result['message'] ?? 'Unknown error'),
                    'whmcs_id' => $productId
                ];
            }
        } catch (\Exception $e) {
            $this->updateSyncStatus($productId, null, 'failed', $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'whmcs_id' => $productId
            ];
        }
    }
    
    /**
     * Get all synced products
     *
     * @return array
     */
    public function getAllSyncedProducts(): array
    {
        try {
            return Capsule::table('mod_bukku_integration_products')
                ->orderBy('last_synced', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Sync selected products
     *
     * @param array $productIds
     * @return array
     */
    public function syncSelectedProducts(array $productIds): array
    {
        $results = [
            'total' => count($productIds),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($productIds as $productId) {
            $result = $this->syncProduct((int)$productId);
            
            if ($result['status'] === 'success') {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = $result;
        }
        
        return [
            'status' => 'success',
            'message' => "Synced {$results['success']} products, {$results['failed']} failed",
            'data' => $results
        ];
    }
    
    /**
     * Update sync status in database
     *
     * @param int $productId
     * @param int|null $bukkuId
     * @param string $status
     * @param string|null $errorMessage
     * @return void
     */
    private function updateSyncStatus(int $productId, ?int $bukkuId, string $status, ?string $errorMessage = null): void
    {
        $product = WhmcsProduct::find($productId);
        
        if (!$product) {
            return;
        }
        
        $data = [
            'whmcs_id' => $productId,
            'name' => $product->name,
            'type' => $product->type,
            'price' => $product->pricing->monthly(),
            'sync_status' => $status,
            'last_synced' => date('Y-m-d H:i:s'),
            'error_message' => $errorMessage
        ];
        
        if ($bukkuId) {
            $data['bukku_id'] = $bukkuId;
        }
        
        // Check if record exists
        $exists = Capsule::table('mod_bukku_integration_products')
            ->where('whmcs_id', $productId)
            ->exists();
        
        if ($exists) {
            Capsule::table('mod_bukku_integration_products')
                ->where('whmcs_id', $productId)
                ->update($data);
        } else {
            Capsule::table('mod_bukku_integration_products')->insert($data);
        }
    }
    
    /**
     * Prepare product data from WHMCS product
     *
     * @param WhmcsProduct $product
     * @return array
     */
    private function prepareProductData(WhmcsProduct $product): array
    {
        // Get product pricing
        $pricing = $product->pricing;
        
        return [
            'name' => $product->name,
            'description' => $product->description,
            'sku' => "WHMCS-{$product->id}",
            'type' => 'SERVICE',
            'unit_price' => $pricing->monthly(),
            'currency_code' => 'MYR',
            'is_active' => $product->hidden ? false : true,
            'tax_rate' => 0, // Default to 0 if not specified
            'notes' => "Imported from WHMCS - {$product->name}"
        ];
    }
    
    /**
     * Get product details by Bukku ID
     *
     * @param int $bukkuId
     * @return array
     */
    public function getProductDetails(int $bukkuId): array
    {
        try {
            $result = $this->productsApi->getProduct($bukkuId);
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get WHMCS product by Bukku ID
     *
     * @param int $bukkuId
     * @return WhmcsProduct|null
     */
    public function getWhmcsProductByBukkuId(int $bukkuId): ?WhmcsProduct
    {
        try {
            $mapping = Capsule::table('mod_bukku_integration_products')
                ->where('bukku_id', $bukkuId)
                ->first();
            
            if (!$mapping) {
                return null;
            }
            
            return WhmcsProduct::find($mapping->whmcs_id);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get Bukku product by WHMCS ID
     *
     * @param int $whmcsId
     * @return array|null
     */
    public function getBukkuProductByWhmcsId(int $whmcsId): ?array
    {
        try {
            $mapping = Capsule::table('mod_bukku_integration_products')
                ->where('whmcs_id', $whmcsId)
                ->first();
            
            if (!$mapping || !$mapping->bukku_id) {
                return null;
            }
            
            return $this->getProductDetails($mapping->bukku_id);
        } catch (\Exception $e) {
            return null;
        }
    }
}