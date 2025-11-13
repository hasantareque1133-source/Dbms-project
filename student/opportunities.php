<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();

// Get filters
$typeFilter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM opportunities WHERE 1=1";
$params = [];
$types = "";

if ($typeFilter) {
    $query .= " AND type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR company_organization LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY created_at DESC";

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
    <title>Opportunities - Student</title>
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
                    <h2>Opportunities</h2>
                    <p>Browse jobs, internships, and research opportunities</p>
                </div>
                
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
                
                <div class="row">
                    <?php if ($opportunities->num_rows > 0): ?>
                        <?php while ($opp = $opportunities->fetch_assoc()): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?php echo htmlspecialchars($opp['title']); ?></h5>
                                            <span class="badge badge-<?php echo $opp['type']; ?>"><?php echo ucfirst($opp['type']); ?></span>
                                        </div>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($opp['company_organization'] ?? 'Not specified'); ?>
                                        </p>
                                        <?php if ($opp['location']): ?>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($opp['location']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($opp['deadline']): ?>
                                            <p class="text-muted mb-3">
                                                <i class="bi bi-calendar"></i> Deadline: <?php echo date('M d, Y', strtotime($opp['deadline'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($opp['description'], 0, 150)) . '...'; ?></p>
                                        <a href="opportunity_details.php?id=<?php echo $opp['opportunity_id']; ?>" class="btn btn-primary">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="bi bi-briefcase"></i>
                                <h3>No opportunities found</h3>
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
