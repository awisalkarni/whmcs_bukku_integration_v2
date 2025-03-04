<?php
use GBNetwork\BukkuIntegration\Services\ContactService;
use GBNetwork\BukkuIntegration\Services\ProductService;
use GBNetwork\BukkuIntegration\Services\InvoiceService;
use GBNetwork\BukkuIntegration\Helpers\Logger;

// Initialize services
$contactService = new ContactService();
$productService = new ProductService();
$invoiceService = new InvoiceService();

// Get stats
$contacts = $contactService->getAllSyncedContacts();
$products = $productService->getAllSyncedProducts();
$invoices = $invoiceService->getAllSyncedInvoices();
$logs = Logger::getLogs(5);

// Calculate stats
$contactStats = [
    'total' => count($contacts),
    'synced' => count(array_filter($contacts, function($contact) {
        return $contact->sync_status === 'success';
    })),
    'failed' => count(array_filter($contacts, function($contact) {
        return $contact->sync_status === 'failed';
    })),
];

$productStats = [
    'total' => count($products),
    'synced' => count(array_filter($products, function($product) {
        return $product->sync_status === 'success';
    })),
    'failed' => count(array_filter($products, function($product) {
        return $product->sync_status === 'failed';
    })),
];

$invoiceStats = [
    'total' => count($invoices),
    'synced' => count(array_filter($invoices, function($invoice) {
        return $invoice->sync_status === 'success';
    })),
    'failed' => count(array_filter($invoices, function($invoice) {
        return $invoice->sync_status === 'failed';
    })),
];
?>

<div class="bukku-admin-container">
    <h2>Bukku e-Invoice Integration Dashboard</h2>
    
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Contacts</h3>
                </div>
                <div class="panel-body">
                    <div class="stats-container">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $contactStats['total']; ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $contactStats['synced']; ?></span>
                            <span class="stat-label">Synced</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $contactStats['failed']; ?></span>
                            <span class="stat-label">Failed</span>
                        </div>
                    </div>
                    <div class="panel-actions">
                        <a href="addonmodules.php?module=bukku_integration_v2&action=contacts" class="btn btn-default btn-sm">View All</a>
                        <button class="btn btn-primary btn-sm sync-all-contacts">Sync All</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Products</h3>
                </div>
                <div class="panel-body">
                    <div class="stats-container">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $productStats['total']; ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $productStats['synced']; ?></span>
                            <span class="stat-label">Synced</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $productStats['failed']; ?></span>
                            <span class="stat-label">Failed</span>
                        </div>
                    </div>
                    <div class="panel-actions">
                        <a href="addonmodules.php?module=bukku_integration_v2&action=products" class="btn btn-default btn-sm">View All</a>
                        <button class="btn btn-primary btn-sm sync-all-products">Sync All</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Invoices</h3>
                </div>
                <div class="panel-body">
                    <div class="stats-container">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $invoiceStats['total']; ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $invoiceStats['synced']; ?></span>
                            <span class="stat-label">Synced</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $invoiceStats['failed']; ?></span>
                            <span class="stat-label">Failed</span>
                        </div>
                    </div>
                    <div class="panel-actions">
                        <a href="addonmodules.php?module=bukku_integration_v2&action=invoices" class="btn btn-default btn-sm">View All</a>
                        <button class="btn btn-primary btn-sm sync-all-invoices">Sync All</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Recent Activity</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Level</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No recent activity</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log->created_at; ?></td>
                                        <td>
                                            <span class="label label-<?php echo $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($log->level); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $log->message; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="text-right">
                        <a href="addonmodules.php?module=bukku_integration_v2&action=logs" class="btn btn-default btn-sm">View All Logs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.sync-all-contacts').click(function() {
        syncAll('contacts');
    });
    
    $('.sync-all-products').click(function() {
        syncAll('products');
    });
    
    $('.sync-all-invoices').click(function() {
        syncAll('invoices');
    });
    
    function syncAll(type) {
        var $button = $('.sync-all-' + type);
        $button.html('<i class="fa fa-spinner fa-spin"></i> Syncing...').prop('disabled', true);
        
        $.ajax({
            url: 'addonmodules.php?module=bukku_integration&action=sync_' + type,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Sync completed: ' + response.message);
                } else {
                    alert('Error: ' + response.message);
                }
                location.reload();
            },
            error: function() {
                alert('An error occurred during the sync process.');
                $button.html('Sync All').prop('disabled', false);
            }
        });
    }
});
</script>