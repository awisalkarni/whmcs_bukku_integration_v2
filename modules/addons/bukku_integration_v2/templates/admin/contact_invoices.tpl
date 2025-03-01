<?php
use GBNetwork\BukkuIntegration\Services\InvoiceService;
use GBNetwork\BukkuIntegration\Services\ContactService;

// Initialize services
$invoiceService = new InvoiceService();
$contactService = new ContactService();

// Get contact ID from request
$contactId = $vars['contact_id'] ?? 0;

// Get contact details
$contact = $contactService->getContactDetails($contactId);

// Get all invoices for this contact
$invoices = $invoiceService->getInvoicesByContact($contactId);

// Handle actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'sync_selected' && isset($_POST['selected_invoices'])) {
        $selectedIds = $_POST['selected_invoices'];
        $result = $invoiceService->syncSelectedInvoices($selectedIds);
        $successMessage = "Selected invoices have been synced successfully.";
    } elseif ($_POST['action'] === 'sync_all') {
        $result = $invoiceService->syncAllInvoicesForContact($contactId);
        $successMessage = "All invoices have been synced successfully.";
    }
}
?>

<div class="bukku-admin-container">
    <h2>Invoices for <?php echo htmlspecialchars($contact['legal_name'] ?? 'Unknown Contact'); ?></h2>
    
    <div class="bukku-breadcrumb">
        <a href="addonmodules.php?module=bukku_integration&action=contacts">← Back to Contacts</a>
    </div>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="bukku-actions-bar">
        <form method="post" action="">
            <input type="hidden" name="action" value="sync_all">
            <button type="submit" class="btn btn-primary">Sync All Invoices</button>
        </form>
    </div>
    
    <form method="post" action="">
        <input type="hidden" name="action" value="sync_selected">
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>WHMCS Invoice #</th>
                        <th>Bukku Invoice #</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Last Synced</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No invoices found for this contact.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_invoices[]" value="<?php echo $invoice['whmcs_id']; ?>"></td>
                                <td><?php echo $invoice['invoicenum']; ?></td>
                                <td><?php echo $invoice['bukku_id'] ? 'IV-' . str_pad($invoice['bukku_id'], 5, '0', STR_PAD_LEFT) : '-'; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($invoice['date'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($invoice['duedate'])); ?></td>
                                <td><?php echo $invoice['currency_symbol'] ?? 'RM'; ?> <?php echo number_format($invoice['total'], 2); ?></td>
                                <td>
                                    <?php if ($invoice['sync_status'] === 'success'): ?>
                                        <span class="label label-success">Synced</span>
                                    <?php elseif ($invoice['sync_status'] === 'pending'): ?>
                                        <span class="label label-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="label label-danger">Failed</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($invoice['whmcs_status']): ?>
                                        <span class="label label-<?php echo strtolower($invoice['whmcs_status']) === 'paid' ? 'success' : 'default'; ?>">
                                            <?php echo $invoice['whmcs_status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $invoice['last_synced']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary sync-single" data-id="<?php echo $invoice['whmcs_id']; ?>">Sync</button>
                                    <?php if ($invoice['bukku_id']): ?>
                                        <a href="https://<?php echo $vars['company_subdomain']; ?>.bukku.my/sales/invoices/<?php echo $invoice['bukku_id']; ?>" target="_blank" class="btn btn-sm btn-info">View</a>
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
        $('input[name="selected_invoices[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Single sync button
    $('.sync-single').click(function() {
        var invoiceId = $(this).data('id');
        $.ajax({
            url: 'addonmodules.php?module=bukku_integration&action=sync_invoice',
            type: 'POST',
            data: { invoice_id: invoiceId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Invoice synced successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while syncing the invoice');
            }
        });
    });
});
</script>