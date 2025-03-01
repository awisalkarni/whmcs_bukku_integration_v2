<?php
use WHMCS\Module\Addon\Setting;

// Get current settings
$apiToken = Setting::where('module', 'bukku_integration')->where('setting', 'api_token')->first()->value ?? '';
$companySubdomain = Setting::where('module', 'bukku_integration')->where('setting', 'company_subdomain')->first()->value ?? '';
$syncFrequency = Setting::where('module', 'bukku_integration')->where('setting', 'sync_frequency')->first()->value ?? 'daily';
$debugMode = Setting::where('module', 'bukku_integration')->where('setting', 'debug_mode')->first()->value ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings
    $apiToken = $_POST['api_token'] ?? '';
    $companySubdomain = $_POST['company_subdomain'] ?? '';
    $syncFrequency = $_POST['sync_frequency'] ?? 'daily';
    $debugMode = isset($_POST['debug_mode']) ? 'on' : '';
    
    // Save settings
    Setting::updateOrCreate(
        ['module' => 'bukku_integration', 'setting' => 'api_token'],
        ['value' => $apiToken]
    );
    
    Setting::updateOrCreate(
        ['module' => 'bukku_integration', 'setting' => 'company_subdomain'],
        ['value' => $companySubdomain]
    );
    
    Setting::updateOrCreate(
        ['module' => 'bukku_integration', 'setting' => 'sync_frequency'],
        ['value' => $syncFrequency]
    );
    
    Setting::updateOrCreate(
        ['module' => 'bukku_integration', 'setting' => 'debug_mode'],
        ['value' => $debugMode]
    );
    
    $successMessage = 'Settings saved successfully.';
}
?>

<div class="bukku-admin-container">
    <h2>Bukku e-Invoice Integration Settings</h2>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">API Configuration</h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="api_token">API Token</label>
                    <input type="password" class="form-control" id="api_token" name="api_token" value="<?php echo htmlspecialchars($apiToken); ?>" required>
                    <p class="help-block">Your Bukku API Bearer Token</p>
                </div>
                
                <div class="form-group">
                    <label for="company_subdomain">Company Subdomain</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="company_subdomain" name="company_subdomain" value="<?php echo htmlspecialchars($companySubdomain); ?>" required>
                        <span class="input-group-addon">.bukku.my</span>
                    </div>
                    <p class="help-block">Your Bukku company subdomain</p>
                </div>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Sync Settings</h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="sync_frequency">Sync Frequency</label>
                    <select class="form-control" id="sync_frequency" name="sync_frequency">
                        <option value="hourly" <?php echo $syncFrequency === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                        <option value="daily" <?php echo $syncFrequency === 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo $syncFrequency === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    </select>
                    <p class="help-block">How often to sync data with Bukku</p>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="debug_mode" <?php echo $debugMode === 'on' ? 'checked' : ''; ?>> Enable Debug Mode
                        </label>
                        <p class="help-block">Enable detailed logging for troubleshooting</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Test Connection</h3>
        </div>
        <div class="panel-body">
            <p>Test your connection to the Bukku API.</p>
            <button id="test-connection" class="btn btn-info">Test Connection</button>
            <div id="test-result" class="mt-3" style="display: none;"></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#test-connection').click(function() {
        var $button = $(this);
        var $result = $('#test-result');
        
        $button.html('<i class="fa fa-spinner fa-spin"></i> Testing...').prop('disabled', true);
        $result.hide();
        
        $.ajax({
            url: 'addonmodules.php?module=bukku_integration&action=test_connection',
            type: 'POST',
            dataType: 'json',
            <!-- Continuing from where we left off -->
            success: function(response) {
                if (response.status === 'success') {
                    $result.html('<div class="alert alert-success">Connection successful! API version: ' + response.data.version + '</div>').show();
                } else {
                    $result.html('<div class="alert alert-danger">Connection failed: ' + response.message + '</div>').show();
                }
                $button.html('Test Connection').prop('disabled', false);
            },
            error: function() {
                $result.html('<div class="alert alert-danger">Connection failed: Could not connect to the server.</div>').show();
                $button.html('Test Connection').prop('disabled', false);
            }
        });
    });
});
</script>