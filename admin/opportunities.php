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
        $title = trim($_POST['title']);
        $type = $_POST['type'];
        $description = trim($_POST['description']);
        $company = trim($_POST['company_organization'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        $requirements = trim($_POST['requirements'] ?? '');
        $posted_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO opportunities (title, type, description, company_organization, location, posted_by, deadline, requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiss", $title, $type, $description, $company, $location, $posted_by, $deadline, $requirements);
        
        if ($stmt->execute()) {
            $message = 'Opportunity added successfully!';
            $messageType = 'success';
            logActivity($conn, 'Opportunity created', 'opportunities', $conn->insert_id, "Created: $title");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'edit') {
        $id = $_POST['opportunity_id'];
        $title = trim($_POST['title']);
        $type = $_POST['type'];
        $description = trim($_POST['description']);
        $company = trim($_POST['company_organization'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        $requirements = trim($_POST['requirements'] ?? '');
        
        $stmt = $conn->prepare("UPDATE opportunities SET title = ?, type = ?, description = ?, company_organization = ?, location = ?, deadline = ?, requirements = ? WHERE opportunity_id = ?");
        $stmt->bind_param("sssssssi", $title, $type, $description, $company, $location, $deadline, $requirements, $id);
        
        if ($stmt->execute()) {
            $message = 'Opportunity updated successfully!';
            $messageType = 'success';
            logActivity($conn, 'Opportunity updated', 'opportunities', $id, "Updated: $title");
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = $_POST['opportunity_id'];
        
        $stmt = $conn->prepare("SELECT title FROM opportunities WHERE opportunity_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $opp = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM opportunities WHERE opportunity_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Opportunity deleted successfully!';
            $messageType = 'success';
            logActivity($conn, 'Opportunity deleted', 'opportunities', $id, "Deleted: " . $opp['title']);
        } else {
            $message = 'Error: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Get all opportunities
$typeFilter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT o.*, u.name as posted_by_name FROM opportunities o LEFT JOIN users u ON o.posted_by = u.user_id WHERE 1=1";
$params = [];
$types = "";

if ($typeFilter) {
    $query .= " AND o.type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

if ($search) {
    $query .= " AND (o.title LIKE ? OR o.description LIKE ? OR o.company_organization LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$opportunities = $stmt->get_result();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opportunities - Admin</title>
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
                    <h2>Manage Opportunities</h2>
                    <p>Post and manage job, internship, and research opportunities</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-briefcase"></i> All Opportunities</span>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOpportunityModal">
                            <i class="bi bi-plus-circle"></i> Add Opportunity
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="search-filter-bar">
                            <form method="GET" class="d-flex gap-2 flex-grow-1">
                                <input type="text" class="form-control" name="search" placeholder="Search opportunities..." value="<?php echo htmlspecialchars($search); ?>">
                                <select class="form-select" name="type" style="max-width: 200px;">
                                    <option value="">All Types</option>
                                    <option value="job" <?php echo $typeFilter === 'job' ? 'selected' : ''; ?>>Job</option>
                                    <option value="internship" <?php echo $typeFilter === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    <option value="research" <?php echo $typeFilter === 'research' ? 'selected' : ''; ?>>Research</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="opportunities.php" class="btn btn-secondary">Clear</a>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Company/Org</th>
                                        <th>Location</th>
                                        <th>Deadline</th>
                                        <th>Posted By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($opportunities->num_rows > 0): ?>
                                        <?php while ($opp = $opportunities->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($opp['title']); ?></td>
                                                <td><span class="badge badge-<?php echo $opp['type']; ?>"><?php echo ucfirst($opp['type']); ?></span></td>
                                                <td><?php echo htmlspecialchars($opp['company_organization'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($opp['location'] ?? '-'); ?></td>
                                                <td><?php echo $opp['deadline'] ? date('M d, Y', strtotime($opp['deadline'])) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($opp['posted_by_name']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editOpportunity(<?php echo htmlspecialchars(json_encode($opp)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this opportunity?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="opportunity_id" value="<?php echo $opp['opportunity_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No opportunities found</td>
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
    
    <!-- Add Opportunity Modal -->
    <div class="modal fade" id="addOpportunityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Opportunity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type *</label>
                                <select class="form-select" name="type" required>
                                    <option value="job">Job</option>
                                    <option value="internship">Internship</option>
                                    <option value="research">Research</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company/Organization</label>
                                <input type="text" class="form-control" name="company_organization">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline</label>
                                <input type="date" class="form-control" name="deadline">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Requirements</label>
                            <textarea class="form-control" name="requirements" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Opportunity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Opportunity Modal -->
    <div class="modal fade" id="editOpportunityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Opportunity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="opportunity_id" id="edit_opportunity_id">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type *</label>
                                <select class="form-select" name="type" id="edit_type" required>
                                    <option value="job">Job</option>
                                    <option value="internship">Internship</option>
                                    <option value="research">Research</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company/Organization</label>
                                <input type="text" class="form-control" name="company_organization" id="edit_company">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" id="edit_location">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline</label>
                                <input type="date" class="form-control" name="deadline" id="edit_deadline">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Requirements</label>
                            <textarea class="form-control" name="requirements" id="edit_requirements" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Opportunity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editOpportunity(opp) {
            document.getElementById('edit_opportunity_id').value = opp.opportunity_id;
            document.getElementById('edit_title').value = opp.title;
            document.getElementById('edit_type').value = opp.type;
            document.getElementById('edit_description').value = opp.description;
            document.getElementById('edit_company').value = opp.company_organization || '';
            document.getElementById('edit_location').value = opp.location || '';
            document.getElementById('edit_deadline').value = opp.deadline || '';
            document.getElementById('edit_requirements').value = opp.requirements || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editOpportunityModal'));
            editModal.show();
        }
    </script>
</body>
</html>
