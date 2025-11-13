<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$conn = getDBConnection();
$event_id = $_GET['id'] ?? 0;

// Get event details
$eventStmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$eventStmt->bind_param("i", $event_id);
$eventStmt->execute();
$event = $eventStmt->get_result()->fetch_assoc();
$eventStmt->close();

if (!$event) {
    header('Location: events.php');
    exit();
}

// Get registrations
$registrations = $conn->query("SELECT er.*, u.name, u.email, u.institutional_id, u.department, u.year 
    FROM event_registrations er 
    JOIN users u ON er.student_id = u.user_id 
    WHERE er.event_id = $event_id 
    ORDER BY er.registered_at DESC");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registrations - Admin</title>
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
                    <h2>Event Registrations</h2>
                    <p><?php echo htmlspecialchars($event['title']); ?></p>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Event Details</h5>
                        <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></p>
                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue'] ?? '-'); ?></p>
                        <p><strong>Capacity:</strong> <?php echo $event['capacity'] > 0 ? $event['capacity'] : 'Unlimited'; ?></p>
                        <p><strong>Registrations:</strong> <?php echo $registrations->num_rows; ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-people"></i> Registered Students
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Institutional ID</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Registered At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($registrations->num_rows > 0): ?>
                                        <?php while ($reg = $registrations->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['institutional_id']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['department'] ?? '-'); ?></td>
                                                <td><?php echo $reg['year'] ?? '-'; ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($reg['registered_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No registrations yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <a href="events.php" class="btn btn-secondary mt-3">
                    <i class="bi bi-arrow-left"></i> Back to Events
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
