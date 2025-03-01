<?php
use GBNetwork\BukkuIntegration\Helpers\Logger;

// Get logs
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$level = isset($_GET['level']) ? $_GET['level'] : null;

$logs = Logger::getLogs($limit, $offset, $level);
$totalLogs = count(Logger::getLogs(1000000, 0, $level)); // Get approximate count for pagination
$totalPages = ceil($totalLogs / $limit);

// Handle clear logs action
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    $clearLevel = $_POST['clear_level'] ?? null;
    $clearDays = isset($_POST['clear_days']) ? (int)$_POST['clear_days'] : null;
    
    Logger::clearLogs($clearLevel, $clearDays);
    
    $successMessage = "Logs have been cleared successfully.";
    
    // Refresh logs
    $logs = Logger::getLogs($limit, $offset, $level);
    $totalLogs = count(Logger::getLogs(1000000, 0, $level));
    $totalPages = ceil($totalLogs / $limit);
}
?>

<div class="bukku-admin-container">
    <h2>Bukku e-Invoice Integration - Logs</h2>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Log Entries</h3>
                </div>
                <div class="panel-body">
                    <div class="filter-bar">
                        <form method="get" action="" class="form-inline">
                            <input type="hidden" name="module" value="bukku_integration">
                            <input type="hidden" name="action" value="logs">
                            
                            <div class="form-group">
                                <label for="level">Filter by Level:</label>
                                <select name="level" id="level" class="form-control">
                                    <option value="">All Levels</option>
                                    <option value="info" <?php echo $level === 'info' ? 'selected' : ''; ?>>Info</option>
                                    <option value="warning" <?php echo $level === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                    <option value="error" <?php echo $level === 'error' ? 'selected' : ''; ?>>Error</option>
                                    <option value="debug" <?php echo $level === 'debug' ? 'selected' : ''; ?>>Debug</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Level</th>
                                    <th>Message</th>
                                    <th>Context</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No logs found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log->created_at; ?></td>
                                            <td>
                                                <span class="label label-<?php echo $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : ($log->level === 'debug' ? 'default' : 'info')); ?>">
                                                    <?php echo ucfirst($log->level); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log->message); ?></td>
                                            <td>
                                                <?php if ($log->context): ?>
                                                    <button type="button" class="btn btn-xs btn-default view-context" data-context='<?php echo htmlspecialchars($log->context); ?>'>View</button>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-container">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="?module=bukku_integration&action=logs&page=<?php echo $page - 1; ?>&level=<?php echo $level; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                                        <a href="?module=bukku_integration&action=logs&page=<?php echo $i; ?>&level=<?php echo $level; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li>
                                        <a href="?module=bukku_integration&action=logs&page=<?php echo $page + 1; ?>&level=<?php echo $level; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Clear Logs</h3>
                </div>
                <div class="panel-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="clear_logs">
                        
                        <div class="form-group">
                            <label for="clear_level">Level to Clear:</label>
                            <select name="clear_level" id="clear_level" class="form-control">
                                <option value="">All Levels</option>
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="clear_days">Clear logs older than:</label>
                            <select name="clear_days" id="clear_days" class="form-control">
                                <option value="">All logs</option>
                                <option value="7">7 days</option>
                                <option value="30">30 days</option>
                                <option value="90">90 days</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear these logs? This action cannot be undone.');">Clear Logs</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Context Modal -->
<div class="modal fade" id="contextModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Continuing from where we left off -->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Log Context</h4>
            </div>
            <div class="modal-body">
                <pre id="contextContent"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.view-context').click(function() {
        var context = $(this).data('context');
        try {
            // Try to parse and pretty-print JSON
            var jsonObj = JSON.parse(context);
            var prettyJson = JSON.stringify(jsonObj, null, 4);
            $('#contextContent').text(prettyJson);
        } catch (e) {
            // If not valid JSON, just display as is
            $('#contextContent').text(context);
        }
        $('#contextModal').modal('show');
    });
});
</script>