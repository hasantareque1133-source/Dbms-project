<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireRole('student');

$conn = getDBConnection();
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM opportunities WHERE opportunity_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$opportunity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$opportunity) {
    header('Location: opportunities.php');
    exit();
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opportunity Details - Student</title>
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
                <a href="opportunities.php" class="btn btn-secondary mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Opportunities
                </a>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2><?php echo htmlspecialchars($opportunity['title']); ?></h2>
                                <span class="badge badge-<?php echo $opportunity['type']; ?>"><?php echo ucfirst($opportunity['type']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Details</h5>
                            <hr>
                            <p><strong><i class="bi bi-building"></i> Company/Organization:</strong> <?php echo htmlspecialchars($opportunity['company_organization'] ?? 'Not specified'); ?></p>
                            <?php if ($opportunity['location']): ?>
                                <p><strong><i class="bi bi-geo-alt"></i> Location:</strong> <?php echo htmlspecialchars($opportunity['location']); ?></p>
                            <?php endif; ?>
                            <?php if ($opportunity['deadline']): ?>
                                <p><strong><i class="bi bi-calendar"></i> Deadline:</strong> <?php echo date('M d, Y', strtotime($opportunity['deadline'])); ?></p>
                            <?php endif; ?>
                            <p><strong><i class="bi bi-clock"></i> Posted:</strong> <?php echo date('M d, Y', strtotime($opportunity['created_at'])); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <hr>
                            <p><?php echo nl2br(htmlspecialchars($opportunity['description'])); ?></p>
                        </div>
                        
                        <?php if ($opportunity['requirements']): ?>
                            <div class="mb-4">
                                <h5>Requirements</h5>
                                <hr>
                                <p><?php echo nl2br(htmlspecialchars($opportunity['requirements'])); ?></p>
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
