<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Get all events with details
$events = $conn->query("SELECT e.*, c.name as club_name, u.name as created_by_name, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registrations_count
    FROM events e 
    LEFT JOIN clubs c ON e.club_id = c.club_id 
    LEFT JOIN users u ON e.created_by = u.user_id 
    ORDER BY e.event_date DESC");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Events - Admin</title>
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
                    <h2>All Events</h2>
                    <p>View all events and their registration details</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-event"></i> All Events
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Club</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th>Capacity</th>
                                        <th>Registrations</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($events->num_rows > 0): ?>
                                        <?php while ($event = $events->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                <td><?php echo htmlspecialchars($event['club_name'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($event['venue'] ?? '-'); ?></td>
                                                <td><?php echo $event['capacity'] > 0 ? $event['capacity'] : 'Unlimited'; ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $event['registrations_count']; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($event['created_by_name']); ?></td>
                                                <td>
                                                    <a href="event_registrations.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-people"></i> View Registrations
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No events found</td>
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
