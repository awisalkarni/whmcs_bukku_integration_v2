<?php
use GBNetwork\BukkuIntegration\Services\ContactService;

// Initialize services
$contactService = new ContactService();

// Get all synced contacts
$contacts = $contactService->getAllSyncedContacts();

// Handle actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'sync_selected' && isset($_POST['selected_contacts'])) {
        $selectedIds = $_POST['selected_contacts'];
        $result = $contactService->syncSelectedContacts($selectedIds);
        $successMessage = "Selected contacts have been synced successfully.";
    } elseif ($_POST['action'] === 'sync_all') {
        $result = $contactService->syncAllContacts();
        $successMessage = "All contacts have been synced successfully.";
    }
}
?>

<div class="bukku-admin-container">
    <h2>Bukku e-Invoice Integration - Contacts</h2>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="bukku-actions-bar">
        <form method="post" action="">
            <input type="hidden" name="action" value="sync_all">
            <button type="submit" class="btn btn-primary">Sync All Contacts</button>
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
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Last Synced</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No contacts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_contacts[]" value="<?php echo $contact->whmcs_id; ?>"></td>
                                <td><?php echo $contact->whmcs_id; ?></td>
                                <td><?php echo $contact->bukku_id ?: '-'; ?></td>
                                <td><?php echo htmlspecialchars($contact->name); ?></td>
                                <td><?php echo htmlspecialchars($contact->email); ?></td>
                                <td><?php echo ucfirst($contact->type); ?></td>
                                <td>
                                    <?php if ($contact->sync_status === 'success'): ?>
                                        <span class="label label-success">Synced</span>
                                    <?php elseif ($contact->sync_status === 'pending'): ?>
                                        <span class="label label-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="label label-danger" title="<?php echo htmlspecialchars($contact->error_message); ?>">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $contact->last_synced; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary sync-single" data-id="<?php echo $contact->whmcs_id; ?>">Sync</button>
                                    <?php if ($contact->bukku_id): ?>
                                        <a href="addonmodules.php?module=bukku_integration_v2&action=contact_invoices&contact_id=<?php echo $contact->bukku_id; ?>" class="btn btn-sm btn-info">Invoices</a>
                                        <a href="https://<?php echo $vars['company_subdomain']; ?>.bukku.my/contacts/<?php echo $contact->bukku_id; ?>" target="_blank" class="btn btn-sm btn-default">View in Bukku</a>
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
        $('input[name="selected_contacts[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Single sync button
    $('.sync-single').click(function() {
        var clientId = $(this).data('id');
        $.ajax({
            url: 'addonmodules.php?module=bukku_integration_v2&action=sync_contact',
            type: 'POST',
            data: { client_id: clientId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Contact synced successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while syncing the contact');
            }
        });
    });
});
</script>