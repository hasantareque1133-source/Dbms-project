<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Admin');
$pdo = db();

$users = $pdo->query('SELECT id, name FROM users ORDER BY name ASC')->fetchAll();

$filters = [
    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
    'action' => trim($_GET['action'] ?? ''),
];

$query = "SELECT a.*, u.name, r.name AS role_name
          FROM activity_log a
          JOIN users u ON u.id = a.user_id
          JOIN roles r ON r.id = u.role_id";
$conditions = [];
$params = [];

if ($filters['user_id']) {
    $conditions[] = 'a.user_id = :user_id';
    $params['user_id'] = $filters['user_id'];
}
if ($filters['action'] !== '') {
    $conditions[] = 'a.action LIKE :action';
    $params['action'] = '%' . $filters['action'] . '%';
}

if ($conditions) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}

$query .= ' ORDER BY a.created_at DESC LIMIT 100';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

$pageTitle = 'Activity Log';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="card">
    <div class="card-header bg-gradient-admin text-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <span>System Activity</span>
        <form class="row row-cols-lg-auto g-2 align-items-center" method="get">
            <div class="col-12">
                <select class="form-select form-select-sm" name="user_id">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int)$user['id']; ?>" <?= $filters['user_id'] === (int)$user['id'] ? 'selected' : ''; ?>>
                            <?= e($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <input class="form-control form-control-sm" name="action" placeholder="Action keyword" value="<?= e($filters['action']); ?>">
            </div>
            <div class="col-12">
                <button class="btn btn-light btn-sm" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-12">
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/admin/activity.php">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body">
        <?php if (!$activities): ?>
            <p class="text-muted mb-0">No activity found for the selected filters.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($activities as $activity): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1">
                                        <?= e($activity['name']); ?>
                                        <span class="badge bg-light text-dark"><?= e($activity['role_name']); ?></span>
                                    </h6>
                                    <span class="badge bg-secondary"><?= e($activity['action']); ?></span>
                                    <?php if ($activity['details']): ?>
                                        <p class="small text-muted mb-0"><?= e($activity['details']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= date('M d, Y g:i A', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
