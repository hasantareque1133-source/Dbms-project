<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('moderator');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $club_id = $_POST['club_id'] ?? null;
        $event_date = $_POST['event_date'];
        $venue = trim($_POST['venue'] ?? '');
        $capacity = $_POST['capacity'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO events (title, description, club_id, event_date, venue, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissii", $title, $description, $club_id, $event_date, $venue, $capacity, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Event created successfully!';
            $messageType = 'success';
            logActivity($conn, 'Event created', 'events', $conn->insert_id, "Created: $title");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'edit') {
        $id = $_POST['event_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $club_id = $_POST['club_id'] ?? null;
        $event_date = $_POST['event_date'];
        $venue = trim($_POST['venue'] ?? '');
        $capacity = $_POST['capacity'] ?? 0;
        
        // Verify ownership
        $checkStmt = $conn->prepare("SELECT created_by FROM events WHERE event_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $event = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($event && $event['created_by'] == $user_id) {
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, club_id = ?, event_date = ?, venue = ?, capacity = ? WHERE event_id = ?");
            $stmt->bind_param("ssissii", $title, $description, $club_id, $event_date, $venue, $capacity, $id);
            
            if ($stmt->execute()) {
                $message = 'Event updated successfully!';
                $messageType = 'success';
                logActivity($conn, 'Event updated', 'events', $id, "Updated: $title");
            } else {
                $message = 'Error: ' . $conn->error;
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Unauthorized action!';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['event_id'];
        
        // Verify ownership
        $checkStmt = $conn->prepare("SELECT created_by, title FROM events WHERE event_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $event = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($event && $event['created_by'] == $user_id) {
            $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Event deleted successfully!';
                $messageType = 'success';
                logActivity($conn, 'Event deleted', 'events', $id, "Deleted: " . $event['title']);
            } else {
                $message = 'Error: ' . $conn->error;
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Unauthorized action!';
            $messageType = 'danger';
        }
    }
}

// Get moderator's clubs
$clubsResult = $conn->query("SELECT * FROM clubs WHERE moderator_id = $user_id");
$clubsList = [];
while ($club = $clubsResult->fetch_assoc()) {
    $clubsList[] = $club;
}

// Get all events created by this moderator
$events = $conn->query("SELECT e.*, c.name as club_name,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registrations_count
    FROM events e 
    LEFT JOIN clubs c ON e.club_id = c.club_id 
    WHERE e.created_by = $user_id 
    ORDER BY e.event_date DESC");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - Moderator</title>
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
                    <h2>Manage Events</h2>
                    <p>Create and manage club events and workshops</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-event"></i> My Events</span>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="bi bi-plus-circle"></i> Create Event
                        </button>
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
                                                <td><span class="badge bg-primary"><?php echo $event['registrations_count']; ?></span></td>
                                                <td>
                                                    <a href="event_registrations.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-people"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-warning" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this event?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No events found. Create your first event!</td>
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
    
    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Event Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Club</label>
                                <select class="form-select" name="club_id">
                                    <option value="">No club</option>
                                    <?php foreach ($clubsList as $club): ?>
                                        <option value="<?php echo $club['club_id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date & Time *</label>
                                <input type="datetime-local" class="form-control" name="event_date" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Venue</label>
                                <input type="text" class="form-control" name="venue">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacity (0 for unlimited)</label>
                                <input type="number" class="form-control" name="capacity" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="event_id" id="edit_event_id">
                        <div class="mb-3">
                            <label class="form-label">Event Title *</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Club</label>
                                <select class="form-select" name="club_id" id="edit_club_id">
                                    <option value="">No club</option>
                                    <?php foreach ($clubsList as $club): ?>
                                        <option value="<?php echo $club['club_id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date & Time *</label>
                                <input type="datetime-local" class="form-control" name="event_date" id="edit_event_date" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Venue</label>
                                <input type="text" class="form-control" name="venue" id="edit_venue">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacity (0 for unlimited)</label>
                                <input type="number" class="form-control" name="capacity" id="edit_capacity" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEvent(event) {
            document.getElementById('edit_event_id').value = event.event_id;
            document.getElementById('edit_title').value = event.title;
            document.getElementById('edit_description').value = event.description || '';
            document.getElementById('edit_club_id').value = event.club_id || '';
            
            // Format datetime for input
            const eventDate = new Date(event.event_date);
            const formattedDate = eventDate.toISOString().slice(0, 16);
            document.getElementById('edit_event_date').value = formattedDate;
            
            document.getElementById('edit_venue').value = event.venue || '';
            document.getElementById('edit_capacity').value = event.capacity || 0;
            
            const editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
            editModal.show();
        }
    </script>
</body>
</html>
