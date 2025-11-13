<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $institutional_id = trim($_POST['institutional_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $department = trim($_POST['department'] ?? '');
        $year = $_POST['year'] ?? null;
        $phone = trim($_POST['phone'] ?? '');
        
        $stmt = $conn->prepare("INSERT INTO users (institutional_id, name, email, password, role, department, year, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssis", $institutional_id, $name, $email, $password, $role, $department, $year, $phone);
        
        if ($stmt->execute()) {
            $message = 'User added successfully!';
            $messageType = 'success';
            logActivity($conn, 'User created', 'users', $conn->insert_id, "Created user: $name");
        } else {
            $message = 'Error adding user: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'edit') {
        $user_id = $_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $department = trim($_POST['department'] ?? '');
        $year = $_POST['year'] ?? null;
        $phone = trim($_POST['phone'] ?? '');
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, department = ?, year = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssssisi", $name, $email, $role, $department, $year, $phone, $user_id);
        
        if ($stmt->execute()) {
            $message = 'User updated successfully!';
            $messageType = 'success';
            logActivity($conn, 'User updated', 'users', $user_id, "Updated user: $name");
        } else {
            $message = 'Error updating user: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $message = 'User deleted successfully!';
            $messageType = 'success';
            logActivity($conn, 'User deleted', 'users', $user_id, "Deleted user: " . $user['name']);
        } else {
            $message = 'Error deleting user: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Get all users
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR institutional_id LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if ($roleFilter) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                    <h2>Manage Users</h2>
                    <p>Add, edit, or remove users from the system</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people"></i> All Users</span>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus-circle"></i> Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Search and Filter -->
                        <div class="search-filter-bar">
                            <form method="GET" class="d-flex gap-2 flex-grow-1">
                                <input type="text" class="form-control" name="search" placeholder="Search by name, email, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                                <select class="form-select" name="role" style="max-width: 200px;">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="moderator" <?php echo $roleFilter === 'moderator' ? 'selected' : ''; ?>>Moderator</option>
                                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="users.php" class="btn btn-secondary">Clear</a>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Institutional ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($users->num_rows > 0): ?>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $user['user_id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['institutional_id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                                <td><?php echo $user['year'] ?? '-'; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No users found</td>
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
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Institutional ID *</label>
                            <input type="text" class="form-control" name="institutional_id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" required>
                                <option value="student">Student</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="1" max="5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Institutional ID</label>
                            <input type="text" class="form-control" id="edit_institutional_id" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="student">Student</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" id="edit_department">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" id="edit_year" min="1" max="5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_institutional_id').value = user.institutional_id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_department').value = user.department || '';
            document.getElementById('edit_year').value = user.year || '';
            document.getElementById('edit_phone').value = user.phone || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        }
    </script>
</body>
</html>
