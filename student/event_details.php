<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$event_id = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

// Handle registration/unregistration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        // Check if already registered
        $checkStmt = $conn->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $checkStmt->bind_param("ii", $event_id, $user_id);
        $checkStmt->execute();
        $existing = $checkStmt->get_result();
        $checkStmt->close();
        
        if ($existing->num_rows > 0) {
            $message = 'You are already registered for this event!';
            $messageType = 'warning';
        } else {
            // Check capacity
            $eventStmt = $conn->prepare("SELECT capacity, (SELECT COUNT(*) FROM event_registrations WHERE event_id = ?) as current_count FROM events WHERE event_id = ?");
            $eventStmt->bind_param("ii", $event_id, $event_id);
            $eventStmt->execute();
            $eventData = $eventStmt->get_result()->fetch_assoc();
            $eventStmt->close();
            
            if ($eventData['capacity'] > 0 && $eventData['current_count'] >= $eventData['capacity']) {
                $message = 'Event is full!';
                $messageType = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, student_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $event_id, $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Successfully registered for the event!';
                    $messageType = 'success';
                } else {
                    $message = 'Error registering: ' . $conn->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'unregister') {
        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $event_id, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Successfully unregistered from the event!';
            $messageType = 'success';
        } else {
            $message = 'Error unregistering: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Get event details
$stmt = $conn->prepare("SELECT e.*, c.name as club_name,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registrations_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND student_id = ?) as is_registered
    FROM events e 
    LEFT JOIN clubs c ON e.club_id = c.club_id 
    WHERE e.event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header('Location: events.php');
    exit();
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - Student</title>
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
                <a href="events.php" class="btn btn-secondary mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Events
                </a>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                                <?php if ($event['club_name']): ?>
                                    <p class="text-muted">
                                        <i class="bi bi-collection"></i> <?php echo htmlspecialchars($event['club_name']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($event['is_registered']): ?>
                                <span class="badge bg-success">Registered</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Event Details</h5>
                            <hr>
                            <p><strong><i class="bi bi-calendar"></i> Date & Time:</strong> <?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></p>
                            <?php if ($event['venue']): ?>
                                <p><strong><i class="bi bi-geo-alt"></i> Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
                            <?php endif; ?>
                            <p><strong><i class="bi bi-people"></i> Registrations:</strong> <?php echo $event['registrations_count']; ?>
                                <?php if ($event['capacity'] > 0): ?>
                                    / <?php echo $event['capacity']; ?> capacity
                                <?php else: ?>
                                    (Unlimited capacity)
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if ($event['description']): ?>
                            <div class="mb-4">
                                <h5>Description</h5>
                                <hr>
                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (strtotime($event['event_date']) >= time()): ?>
                            <div class="mt-4">
                                <?php if ($event['is_registered']): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to unregister from this event?');">
                                        <input type="hidden" name="action" value="unregister">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-x-circle"></i> Unregister
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($event['capacity'] > 0 && $event['registrations_count'] >= $event['capacity']): ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="bi bi-x-circle"></i> Event Full
                                        </button>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="register">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle"></i> Register for Event
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> This event has already passed.
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
