<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Get all activity logs
$logs = $conn->query("SELECT al.*, u.name as user_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.user_id 
    ORDER BY al.created_at DESC 
    LIMIT 100");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/navbar.php'; ?>
            
            <div class="content-area">
                <div class="page-header">
                    <h2>Activity Logs</h2>
                    <p>System activity and user actions</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history"></i> Recent Activity
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Table</th>
                                        <th>Record ID</th>
                                        <th>Details</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs->num_rows > 0): ?>
                                        <?php while ($log = $logs->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                                                <td><?php echo $log['record_id'] ?? '-'; ?></td>
                                                <td><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No activity logs found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
