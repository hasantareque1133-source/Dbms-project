<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $moderator_id = $_POST['moderator_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO clubs (name, description, moderator_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $description, $moderator_id);
        
        if ($stmt->execute()) {
            $message = 'Club added successfully!';
            $messageType = 'success';
            logActivity($conn, 'Club created', 'clubs', $conn->insert_id, "Created: $name");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'edit') {
        $id = $_POST['club_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $moderator_id = $_POST['moderator_id'] ?? null;
        
        $stmt = $conn->prepare("UPDATE clubs SET name = ?, description = ?, moderator_id = ? WHERE club_id = ?");
        $stmt->bind_param("ssii", $name, $description, $moderator_id, $id);
        
        if ($stmt->execute()) {
            $message = 'Club updated successfully!';
            $messageType = 'success';
            logActivity($conn, 'Club updated', 'clubs', $id, "Updated: $name");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = $_POST['club_id'];
        
        $stmt = $conn->prepare("SELECT name FROM clubs WHERE club_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $club = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM clubs WHERE club_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Club deleted successfully!';
            $messageType = 'success';
            logActivity($conn, 'Club deleted', 'clubs', $id, "Deleted: " . $club['name']);
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Get all clubs with moderator info
$clubs = $conn->query("SELECT c.*, u.name as moderator_name FROM clubs c LEFT JOIN users u ON c.moderator_id = u.user_id ORDER BY c.name");

// Get all moderators for dropdown
$moderators = $conn->query("SELECT user_id, name FROM users WHERE role = 'moderator' ORDER BY name");
$moderatorsList = [];
while ($mod = $moderators->fetch_assoc()) {
    $moderatorsList[] = $mod;
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubs - Admin</title>
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
                    <h2>Manage Clubs</h2>
                    <p>Create and manage student clubs and assign moderators</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-collection"></i> All Clubs</span>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClubModal">
                            <i class="bi bi-plus-circle"></i> Add Club
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Moderator</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($clubs->num_rows > 0): ?>
                                        <?php while ($club = $clubs->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($club['name']); ?></td>
                                                <td><?php echo htmlspecialchars($club['description'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($club['moderator_name'] ?? 'Not assigned'); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editClub(<?php echo htmlspecialchars(json_encode($club)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this club?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="club_id" value="<?php echo $club['club_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No clubs found</td>
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
    
    <!-- Add Club Modal -->
    <div class="modal fade" id="addClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Club</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Club Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Moderator</label>
                                <select class="form-select" name="moderator_id">
                                    <option value="">Not assigned</option>
                                    <?php foreach ($moderatorsList as $mod): ?>
                                        <option value="<?php echo $mod['user_id']; ?>"><?php echo htmlspecialchars($mod['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Club</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Club Modal -->
    <div class="modal fade" id="editClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Club</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="club_id" id="edit_club_id">
                        <div class="mb-3">
                            <label class="form-label">Club Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Moderator</label>
                            <select class="form-select" name="moderator_id" id="edit_moderator_id">
                                <option value="">Not assigned</option>
                                <?php foreach ($moderatorsList as $mod): ?>
                                    <option value="<?php echo $mod['user_id']; ?>"><?php echo htmlspecialchars($mod['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Club</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editClub(club) {
            document.getElementById('edit_club_id').value = club.club_id;
            document.getElementById('edit_name').value = club.name;
            document.getElementById('edit_description').value = club.description || '';
            document.getElementById('edit_moderator_id').value = club.moderator_id || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editClubModal'));
            editModal.show();
        }
    </script>
</body>
</html>
