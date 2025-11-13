<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();

// Get filters
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
    <title>Alumni Directory - Student</title>
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
                    <p>Connect with alumni and explore networking opportunities</p>
                </div>
                
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
                
                <div class="row">
                    <?php if ($alumni->num_rows > 0): ?>
                        <?php while ($a = $alumni->fetch_assoc()): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($a['name']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($a['department']); ?> - <?php echo $a['graduation_year']; ?>
                                        </p>
                                        <?php if ($a['profession']): ?>
                                            <p class="mb-2">
                                                <strong>Profession:</strong> <?php echo htmlspecialchars($a['profession']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($a['company']): ?>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($a['company']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($a['email']): ?>
                                            <p class="mb-2">
                                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($a['email']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($a['bio']): ?>
                                            <p class="card-text"><?php echo htmlspecialchars(substr($a['bio'], 0, 150)) . '...'; ?></p>
                                        <?php endif; ?>
                                        <?php if ($a['linkedin_url']): ?>
                                            <a href="<?php echo htmlspecialchars($a['linkedin_url']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="bi bi-linkedin"></i> LinkedIn
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="bi bi-person-badge"></i>
                                <h3>No alumni found</h3>
                                <p>Try adjusting your search filters</p>
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
