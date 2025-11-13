<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['students'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'moderator'");
$stats['moderators'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM opportunities");
$stats['opportunities'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM events");
$stats['events'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM alumni");
$stats['alumni'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM clubs");
$stats['clubs'] = $result->fetch_assoc()['count'];

// Recent activity
$recentActivities = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Networking Portal</title>
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
                    <h2>Admin Dashboard</h2>
                    <p>Manage users, opportunities, events, and alumni</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card admin">
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['students']; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['moderators']; ?></div>
                        <div class="stat-label">Moderators</div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['opportunities']; ?></div>
                        <div class="stat-label">Opportunities</div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['events']; ?></div>
                        <div class="stat-label">Events</div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['alumni']; ?></div>
                        <div class="stat-label">Alumni</div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['clubs']; ?></div>
                        <div class="stat-label">Clubs</div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history"></i> Recent Activity
                    </div>
                    <div class="card-body">
                        <?php if ($recentActivities->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    if ($activity['user_id']) {
                                                        $conn2 = getDBConnection();
                                                        $userStmt = $conn2->prepare("SELECT name FROM users WHERE user_id = ?");
                                                        $userStmt->bind_param("i", $activity['user_id']);
                                                        $userStmt->execute();
                                                        $userResult = $userStmt->get_result();
                                                        $user = $userResult->fetch_assoc();
                                                        echo htmlspecialchars($user['name'] ?? 'System');
                                                        $userStmt->close();
                                                        closeDBConnection($conn2);
                                                    } else {
                                                        echo 'System';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['details'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h3>No recent activity</h3>
                                <p>Activity logs will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
