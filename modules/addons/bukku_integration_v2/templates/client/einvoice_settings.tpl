<div class="bukku-client-container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">e-Invoice Settings</h3>
        </div>
        <div class="panel-body">
            <div class="alert alert-info">
                <p>
                    <strong>e-Invoice Integration</strong><br>
                    We are now integrated with Bukku for e-Invoice compliance. Your invoices will be automatically submitted to the tax authority as required by law.
                </p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="index.php?m=bukku_integration&action=save_settings">
                <div class="form-group">
                    <label for="tax_id">Business Registration Number / Tax ID</label>
                    <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?php echo htmlspecialchars($tax_id); ?>" placeholder="e.g., 123456789012">
                    <p class="help-block">For businesses, please enter your company registration number or tax ID.</p>
                </div>
                
                <div class="form-group">
                    <label for="email_preference">e-Invoice Email Preference</label>
                    <select class="form-control" id="email_preference" name="email_preference">
                        <option value="default" <?php echo $email_preference === 'default' ? 'selected' : ''; ?>>Use my account email</option>
                        <option value="custom" <?php echo $email_preference === 'custom' ? 'selected' : ''; ?>>Use a different email</option>
                    </select>
                </div>
                
                <div class="form-group custom-email-group" style="<?php echo $email_preference === 'custom' ? '' : 'display: none;'; ?>">
                    <label for="custom_email">Custom Email for e-Invoices</label>
                    <input type="email" class="form-control" id="custom_email" name="custom_email" value="<?php echo htmlspecialchars($custom_email); ?>" placeholder="finance@yourcompany.com">
                    <p class="help-block">Enter the email address where you want to receive e-Invoices.</p>
                </div>
                
                <div class="form-group">
                    <label for="invoice_language">Preferred Invoice Language</label>
                    <select class="form-control" id="invoice_language" name="invoice_language">
                        <option value="en" <?php echo $invoice_language === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="ms" <?php echo $invoice_language === 'ms' ? 'selected' : ''; ?>>Bahasa Malaysia</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
            
            <hr>
            
            <h4>Recent e-Invoices</h4>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No e-Invoices found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo $invoice['number']; ?></td>
                                    <td><?php echo $invoice['date']; ?></td>
                                    <td><?php echo $invoice['currency_symbol'] . ' ' . $invoice['amount']; ?></td>
                                    <td>
                                        <?php if ($invoice['myinvois_document_status'] === 'VALIDATED'): ?>
                                            <span class="label label-success">Validated</span>
                                        <?php elseif ($invoice['myinvois_document_status'] === 'REJECTED'): ?>
                                            <span class="label label-danger">Rejected</span>
                                        <?php elseif ($invoice['myinvois_document_status'] === 'PENDING'): ?>
                                            <span class="label label-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="label label-default">Not Submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($invoice['short_link']): ?>
                                            <a href="<?php echo $invoice['short_link']; ?>" target="_blank" class="btn btn-sm btn-default">View</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($invoice['pdf_url']): ?>
                                            <a href="<?php echo $invoice['pdf_url']; ?>" target="_blank" class="btn btn-sm btn-primary">Download PDF</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#email_preference').change(function() {
        if ($(this).val() === 'custom') {
            $('.custom-email-group').show();
        } else {
            $('.custom-email-group').hide();
        }
    });
});
</script>