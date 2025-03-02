<?php
use GBNetwork\BukkuIntegration\Services\ContactService;

// Initialize services
$contactService = new ContactService();

// Get all synced contacts
$contacts = $contactService->getAllSyncedContacts();
$whmcsContacts = $vars['whmcs_contacts'] ?? [];
$bukkuContacts = $vars['bukku_contacts'] ?? [];

// Create a map of Bukku contacts by email for easy lookup
$bukkuContactsByEmail = [];
foreach ($bukkuContacts as $bukkuContact) {
    if (!empty($bukkuContact['email'])) {
        $bukkuContactsByEmail[strtolower($bukkuContact['email'])] = $bukkuContact;
    }
}

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
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Bukku Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($whmcsContacts)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No WHMCS contacts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($whmcsContacts as $whmcsContact): ?>
                            <?php 
                            // Check if contact is synced in our database
                            $isSynced = false;
                            $syncedContact = null;
                            foreach ($contacts as $contact) {
                                if ($contact->whmcs_id == $whmcsContact->id) {
                                    $isSynced = true;
                                    $syncedContact = $contact;
                                    break;
                                }
                            }
                            
                            // Check if contact exists in Bukku by email
                            $existsInBukku = false;
                            $bukkuContact = null;
                            $whmcsEmail = strtolower($whmcsContact->email);
                            if (isset($bukkuContactsByEmail[$whmcsEmail])) {
                                $existsInBukku = true;
                                $bukkuContact = $bukkuContactsByEmail[$whmcsEmail];
                            }
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_contacts[]" value="<?php echo $whmcsContact->id; ?>"></td>
                                <td><?php echo $whmcsContact->id; ?></td>
                                <td><?php echo htmlspecialchars($whmcsContact->firstname . ' ' . $whmcsContact->lastname); ?></td>
                                <td><?php echo htmlspecialchars($whmcsContact->companyname); ?></td>
                                <td><?php echo htmlspecialchars($whmcsContact->email); ?></td>
                                <td>
                                    <?php if ($isSynced): ?>
                                        <?php if ($syncedContact->sync_status === 'success'): ?>
                                            <span class="label label-success">Synced (ID: <?php echo $syncedContact->bukku_id; ?>)</span>
                                        <?php elseif ($syncedContact->sync_status === 'pending'): ?>
                                            <span class="label label-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="label label-danger" title="<?php echo htmlspecialchars($syncedContact->error_message ?? ''); ?>">Failed</span>
                                        <?php endif; ?>
                                    <?php elseif ($existsInBukku): ?>
                                        <span class="label label-info">Exists in Bukku (ID: <?php echo $bukkuContact['id']; ?>)</span>
                                    <?php else: ?>
                                        <span class="label label-default">Not Synced</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary sync-single" data-id="<?php echo $whmcsContact->id; ?>">Sync</button>
                                    <?php if ($isSynced && $syncedContact->bukku_id): ?>
                                        <a href="addonmodules.php?module=bukku_integration_v2&action=contact_invoices&contact_id=<?php echo $syncedContact->bukku_id; ?>" class="btn btn-sm btn-info">Invoices</a>
                                        <a href="https://<?php echo $vars['company_subdomain']; ?>.bukku.my/contacts/<?php echo $syncedContact->bukku_id; ?>" target="_blank" class="btn btn-sm btn-default">View in Bukku</a>
                                    <?php elseif ($existsInBukku): ?>
                                        <a href="https://<?php echo $vars['company_subdomain']; ?>.bukku.my/contacts/<?php echo $bukkuContact['id']; ?>" target="_blank" class="btn btn-sm btn-default">View in Bukku</a>
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