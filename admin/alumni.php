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
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department']);
        $graduation_year = $_POST['graduation_year'];
        $profession = trim($_POST['profession'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $contact_info = trim($_POST['contact_info'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $linkedin = trim($_POST['linkedin_url'] ?? '');
        
        $stmt = $conn->prepare("INSERT INTO alumni (name, email, department, graduation_year, profession, company, contact_info, bio, linkedin_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssss", $name, $email, $department, $graduation_year, $profession, $company, $contact_info, $bio, $linkedin);
        
        if ($stmt->execute()) {
            $message = 'Alumni added successfully!';
            $messageType = 'success';
            logActivity($conn, 'Alumni created', 'alumni', $conn->insert_id, "Created: $name");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'edit') {
        $id = $_POST['alumni_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department']);
        $graduation_year = $_POST['graduation_year'];
        $profession = trim($_POST['profession'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $contact_info = trim($_POST['contact_info'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $linkedin = trim($_POST['linkedin_url'] ?? '');
        
        $stmt = $conn->prepare("UPDATE alumni SET name = ?, email = ?, department = ?, graduation_year = ?, profession = ?, company = ?, contact_info = ?, bio = ?, linkedin_url = ? WHERE alumni_id = ?");
        $stmt->bind_param("sssisssssi", $name, $email, $department, $graduation_year, $profession, $company, $contact_info, $bio, $linkedin, $id);
        
        if ($stmt->execute()) {
            $message = 'Alumni updated successfully!';
            $messageType = 'success';
            logActivity($conn, 'Alumni updated', 'alumni', $id, "Updated: $name");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = $_POST['alumni_id'];
        
        $stmt = $conn->prepare("SELECT name FROM alumni WHERE alumni_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $alumni = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM alumni WHERE alumni_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Alumni deleted successfully!';
            $messageType = 'success';
            logActivity($conn, 'Alumni deleted', 'alumni', $id, "Deleted: " . $alumni['name']);
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Get all alumni with filters
$departmentFilter = $_GET['department'] ?? '';
$yearFilter = $_GET['year'] ?? '';
$professionFilter = $_GET['profession'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM alumni WHERE 1=1";
$params = [];
$types = "";

if ($departmentFilter) {
    $query .= " AND department = ?";
    $params[] = $departmentFilter;
    $types .= "s";
}

if ($yearFilter) {
    $query .= " AND graduation_year = ?";
    $params[] = $yearFilter;
    $types .= "i";
}

if ($professionFilter) {
    $query .= " AND profession LIKE ?";
    $params[] = "%$professionFilter%";
    $types .= "s";
}

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR company LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY graduation_year DESC, name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$alumni = $stmt->get_result();

// Get unique departments and years for filters
$deptsResult = $conn->query("SELECT DISTINCT department FROM alumni ORDER BY department");
$yearsResult = $conn->query("SELECT DISTINCT graduation_year FROM alumni ORDER BY graduation_year DESC");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Directory - Admin</title>
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
                    <h2>Alumni Directory</h2>
                    <p>Manage alumni records and networking information</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-badge"></i> All Alumni</span>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAlumniModal">
                            <i class="bi bi-plus-circle"></i> Add Alumni
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="search-filter-bar">
                            <form method="GET" class="d-flex gap-2 flex-wrap flex-grow-1">
                                <input type="text" class="form-control" name="search" placeholder="Search by name, email, or company..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;">
                                <select class="form-select" name="department" style="max-width: 200px;">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = $deptsResult->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $departmentFilter === $dept['department'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <select class="form-select" name="year" style="max-width: 150px;">
                                    <option value="">All Years</option>
                                    <?php while ($year = $yearsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $year['graduation_year']; ?>" <?php echo $yearFilter == $year['graduation_year'] ? 'selected' : ''; ?>>
                                            <?php echo $year['graduation_year']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="text" class="form-control" name="profession" placeholder="Profession..." value="<?php echo htmlspecialchars($professionFilter); ?>" style="max-width: 200px;">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="alumni.php" class="btn btn-secondary">Clear</a>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Graduation Year</th>
                                        <th>Profession</th>
                                        <th>Company</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($alumni->num_rows > 0): ?>
                                        <?php while ($a = $alumni->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($a['name']); ?></td>
                                                <td><?php echo htmlspecialchars($a['department']); ?></td>
                                                <td><?php echo $a['graduation_year']; ?></td>
                                                <td><?php echo htmlspecialchars($a['profession'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($a['company'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($a['email'] ?? '-'); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editAlumni(<?php echo htmlspecialchars(json_encode($a)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this alumni record?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="alumni_id" value="<?php echo $a['alumni_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No alumni found</td>
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
    
    <!-- Add Alumni Modal -->
    <div class="modal fade" id="addAlumniModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Alumni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department *</label>
                                <input type="text" class="form-control" name="department" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Graduation Year *</label>
                                <input type="number" class="form-control" name="graduation_year" min="1950" max="<?php echo date('Y'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profession</label>
                                <input type="text" class="form-control" name="profession">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company</label>
                                <input type="text" class="form-control" name="company">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Info</label>
                            <textarea class="form-control" name="contact_info" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" class="form-control" name="linkedin_url">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Alumni</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Alumni Modal -->
    <div class="modal fade" id="editAlumniModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Alumni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="alumni_id" id="edit_alumni_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department *</label>
                                <input type="text" class="form-control" name="department" id="edit_department" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Graduation Year *</label>
                                <input type="number" class="form-control" name="graduation_year" id="edit_graduation_year" min="1950" max="<?php echo date('Y'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profession</label>
                                <input type="text" class="form-control" name="profession" id="edit_profession">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company</label>
                                <input type="text" class="form-control" name="company" id="edit_company">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Info</label>
                            <textarea class="form-control" name="contact_info" id="edit_contact_info" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" id="edit_bio" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" class="form-control" name="linkedin_url" id="edit_linkedin_url">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Alumni</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAlumni(alumni) {
            document.getElementById('edit_alumni_id').value = alumni.alumni_id;
            document.getElementById('edit_name').value = alumni.name;
            document.getElementById('edit_email').value = alumni.email || '';
            document.getElementById('edit_department').value = alumni.department;
            document.getElementById('edit_graduation_year').value = alumni.graduation_year;
            document.getElementById('edit_profession').value = alumni.profession || '';
            document.getElementById('edit_company').value = alumni.company || '';
            document.getElementById('edit_contact_info').value = alumni.contact_info || '';
            document.getElementById('edit_bio').value = alumni.bio || '';
            document.getElementById('edit_linkedin_url').value = alumni.linkedin_url || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editAlumniModal'));
            editModal.show();
        }
    </script>
</body>
</html>
