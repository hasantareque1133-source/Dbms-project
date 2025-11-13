<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user's registered events
$registrations = $conn->query("SELECT er.*, e.*, c.name as club_name
    FROM event_registrations er
    JOIN events e ON er.event_id = e.event_id
    LEFT JOIN clubs c ON e.club_id = c.club_id
    WHERE er.student_id = $user_id
    ORDER BY e.event_date ASC");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registrations - Student</title>
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
                    <h2>My Event Registrations</h2>
                    <p>View and manage your registered events</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-check"></i> Registered Events
                    </div>
                    <div class="card-body">
                        <?php if ($registrations->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Event Title</th>
                                            <th>Club</th>
                                            <th>Date & Time</th>
                                            <th>Venue</th>
                                            <th>Registered At</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($reg = $registrations->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reg['title']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['club_name'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($reg['event_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($reg['venue'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($reg['registered_at'])); ?></td>
                                                <td>
                                                    <?php if (strtotime($reg['event_date']) >= time()): ?>
                                                        <span class="badge bg-success">Upcoming</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Past</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="event_details.php?id=<?php echo $reg['event_id']; ?>" class="btn btn-sm btn-primary">
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
                                <h3>No registrations yet</h3>
                                <p>Browse events and register for ones that interest you</p>
                                <a href="events.php" class="btn btn-primary">Browse Events</a>
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
