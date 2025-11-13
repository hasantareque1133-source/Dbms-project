<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('moderator');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get moderator's clubs
$clubs = $conn->query("SELECT * FROM clubs WHERE moderator_id = $user_id");

// Get events created by this moderator
$events = $conn->query("SELECT e.*, c.name as club_name,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registrations_count
    FROM events e 
    LEFT JOIN clubs c ON e.club_id = c.club_id 
    WHERE e.created_by = $user_id 
    ORDER BY e.event_date DESC 
    LIMIT 5");

// Get statistics
$totalEvents = $conn->query("SELECT COUNT(*) as count FROM events WHERE created_by = $user_id")->fetch_assoc()['count'];
$totalRegistrations = $conn->query("SELECT COUNT(*) as count FROM event_registrations er 
    JOIN events e ON er.event_id = e.event_id 
    WHERE e.created_by = $user_id")->fetch_assoc()['count'];

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard - University Networking Portal</title>
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
                    <h2>Moderator Dashboard</h2>
                    <p>Manage your club events and workshops</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card moderator">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalEvents; ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                    
                    <div class="stat-card moderator">
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalRegistrations; ?></div>
                        <div class="stat-label">Total Registrations</div>
                    </div>
                    
                    <div class="stat-card moderator">
                        <div class="stat-icon">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-value"><?php echo $clubs->num_rows; ?></div>
                        <div class="stat-label">My Clubs</div>
                    </div>
                </div>
                
                <!-- Recent Events -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-event"></i> Recent Events</span>
                        <a href="events.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($events->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Club</th>
                                            <th>Date & Time</th>
                                            <th>Venue</th>
                                            <th>Registrations</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $events->data_seek(0);
                                        while ($event = $events->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                <td><?php echo htmlspecialchars($event['club_name'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($event['venue'] ?? '-'); ?></td>
                                                <td><span class="badge bg-primary"><?php echo $event['registrations_count']; ?></span></td>
                                                <td>
                                                    <a href="event_registrations.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-people"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h3>No events yet</h3>
                                <p>Create your first event to get started</p>
                                <a href="events.php" class="btn btn-primary">Create Event</a>
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
