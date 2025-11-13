<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get all upcoming events
$events = $conn->query("SELECT e.*, c.name as club_name,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registrations_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND student_id = $user_id) as is_registered
    FROM events e 
    LEFT JOIN clubs c ON e.club_id = c.club_id 
    WHERE e.event_date >= NOW() 
    ORDER BY e.event_date ASC");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events & Workshops - Student</title>
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
                    <h2>Events & Workshops</h2>
                    <p>Browse and register for upcoming events</p>
                </div>
                
                <div class="row">
                    <?php if ($events->num_rows > 0): ?>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                            <?php if ($event['is_registered']): ?>
                                                <span class="badge bg-success">Registered</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Available</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($event['club_name']): ?>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-collection"></i> <?php echo htmlspecialchars($event['club_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-calendar"></i> <?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?>
                                        </p>
                                        <?php if ($event['venue']): ?>
                                            <p class="text-muted mb-3">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 150)) . '...'; ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">
                                                <i class="bi bi-people"></i> <?php echo $event['registrations_count']; ?> registered
                                                <?php if ($event['capacity'] > 0): ?>
                                                    / <?php echo $event['capacity']; ?> capacity
                                                <?php endif; ?>
                                            </span>
                                            <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-eye"></i> View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h3>No upcoming events</h3>
                                <p>Check back later for new events</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
