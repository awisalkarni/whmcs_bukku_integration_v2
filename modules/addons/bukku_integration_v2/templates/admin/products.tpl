<?php
use GBNetwork\BukkuIntegration\Services\ProductService;

// Initialize services
$productService = new ProductService();

// Get all synced products
$syncedProducts = $productService->getAllSyncedProducts();

// Handle actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'sync_selected' && isset($_POST['selected_products'])) {
        $selectedIds = $_POST['selected_products'];
        $result = $productService->syncSelectedProducts($selectedIds);
        $successMessage = "Selected products have been synced successfully.";
    } elseif ($_POST['action'] === 'sync_all') {
        $result = $productService->syncAllProducts();
        $successMessage = "All products have been synced successfully.";
    }
}
?>

<div class="bukku-admin-container">
    <h2>Bukku Products</h2>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="bukku-actions-bar">
        <form method="post" action="">
            <input type="hidden" name="action" value="sync_all">
            <button type="submit" class="btn btn-primary">Sync All Products</button>
        </form>
    </div>
    
    <form method="post" action="">
        <input type="hidden" name="action" value="sync_selected">
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>WHMCS ID</th>
                        <th>Bukku ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Last Synced</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($syncedProducts)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No products have been synced yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($syncedProducts as $product): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_products[]" value="<?php echo $product['whmcs_id']; ?>"></td>
                                <td><?php echo $product['whmcs_id']; ?></td>
                                <td><?php echo $product['bukku_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['type']; ?></td>
                                <td><?php echo $product['price']; ?></td>
                                <td>
                                    <?php if ($product['sync_status'] === 'success'): ?>
                                        <span class="label label-success">Synced</span>
                                    <?php elseif ($product['sync_status'] === 'pending'): ?>
                                        <span class="label label-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="label label-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['last_synced']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary sync-single" data-id="<?php echo $product['whmcs_id']; ?>">Sync</button>
                                    <?php if ($product['bukku_id']): ?>
                                        <a href="#" class="btn btn-sm btn-info view-product" data-id="<?php echo $product['bukku_id']; ?>">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bukku-actions-bar">
            <button type="submit" class="btn btn-primary">Sync Selected</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Select all checkbox
    $('#select-all').change(function() {
        $('input[name="selected_products[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Single sync button
    $('.sync-single').click(function() {
        var productId = $(this).data('id');
        $.ajax({
            url: 'addonmodules.php?module=bukku_integration&action=sync_product',
            type: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Product synced successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while syncing the product');
            }
        });
    });
    
    // View product button
    $('.view-product').click(function(e) {
        e.preventDefault();
        var productId = $(this).data('id');
        window.open('https://<?php echo $vars['company_subdomain'] ?? ''; ?>.bukku.my/products/' + productId, '_blank');
    });
});
</script>