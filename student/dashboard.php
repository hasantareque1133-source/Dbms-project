<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get recent opportunities
$opportunities = $conn->query("SELECT * FROM opportunities ORDER BY created_at DESC LIMIT 5");

// Get upcoming events
$events = $conn->query("SELECT e.*, c.name as club_name,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registrations_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND student_id = $user_id) as is_registered
    FROM events e 
    LEFT JOIN clubs c ON e.club_id = c.club_id 
    WHERE e.event_date >= NOW() 
    ORDER BY e.event_date ASC 
    LIMIT 5");

// Get user's registered events count
$registeredCount = $conn->query("SELECT COUNT(*) as count FROM event_registrations WHERE student_id = $user_id")->fetch_assoc()['count'];

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Networking Portal</title>
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
                    <h2>Student Dashboard</h2>
                    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card student">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $registeredCount; ?></div>
                        <div class="stat-label">Registered Events</div>
                    </div>
                </div>
                
                <!-- Recent Opportunities -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-briefcase"></i> Recent Opportunities</span>
                        <a href="opportunities.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($opportunities->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Company/Org</th>
                                            <th>Location</th>
                                            <th>Deadline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $opportunities->data_seek(0);
                                        while ($opp = $opportunities->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($opp['title']); ?></td>
                                                <td><span class="badge badge-<?php echo $opp['type']; ?>"><?php echo ucfirst($opp['type']); ?></span></td>
                                                <td><?php echo htmlspecialchars($opp['company_organization'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($opp['location'] ?? '-'); ?></td>
                                                <td><?php echo $opp['deadline'] ? date('M d, Y', strtotime($opp['deadline'])) : '-'; ?></td>
                                                <td>
                                                    <a href="opportunity_details.php?id=<?php echo $opp['opportunity_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-briefcase"></i>
                                <h3>No opportunities available</h3>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-event"></i> Upcoming Events</span>
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
                                            <th>Status</th>
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
                                                    <?php if ($event['is_registered']): ?>
                                                        <span class="badge bg-success">Registered</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not Registered</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i> View
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
                                <h3>No upcoming events</h3>
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
