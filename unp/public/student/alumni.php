<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Student', 'Moderator');
$pdo = db();

$filters = [
    'department' => trim($_GET['department'] ?? ''),
    'year' => trim($_GET['year'] ?? ''),
    'profession' => trim($_GET['profession'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];

$query = 'SELECT * FROM alumni';
$conditions = [];
$params = [];

if ($filters['department'] !== '') {
    $conditions[] = 'department LIKE :department';
    $params['department'] = $filters['department'] . '%';
}
if ($filters['year'] !== '') {
    $conditions[] = 'graduation_year = :year';
    $params['year'] = (int)$filters['year'];
}
if ($filters['profession'] !== '') {
    $conditions[] = 'profession LIKE :profession';
    $params['profession'] = '%' . $filters['profession'] . '%';
}
if ($filters['search'] !== '') {
    $conditions[] = '(name LIKE :search OR company LIKE :search OR bio LIKE :search)';
    $params['search'] = '%' . $filters['search'] . '%';
}

if ($conditions) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}

$query .= ' ORDER BY graduation_year DESC, name ASC LIMIT 150';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

$departments = $pdo->query('SELECT DISTINCT department FROM alumni ORDER BY department ASC')->fetchAll(PDO::FETCH_COLUMN);
$professions = $pdo->query('SELECT DISTINCT profession FROM alumni ORDER BY profession ASC')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Alumni Directory';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="get">
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= e($department); ?>" <?= $filters['department'] === $department ? 'selected' : ''; ?>>
                            <?= e($department); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Graduation Year</label>
                <input class="form-control" name="year" type="number" min="1950" max="<?= date('Y'); ?>" value="<?= e($filters['year']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Profession</label>
                <select class="form-select" name="profession">
                    <option value="">All Professions</option>
                    <?php foreach ($professions as $profession): ?>
                        <option value="<?= e($profession); ?>" <?= $filters['profession'] === $profession ? 'selected' : ''; ?>>
                            <?= e($profession); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Keyword</label>
                <input class="form-control" name="search" placeholder="Name, company, bio" value="<?= e($filters['search']); ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                <a class="btn btn-outline-light" href="<?= BASE_URL; ?>/student/alumni.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$alumni): ?>
    <div class="alert alert-info">No alumni records match your filters.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($alumni as $alum): ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card h-100 alumni-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= e($alum['name']); ?></h5>
                            <span class="badge bg-student-subtle text-student"><?= e($alum['graduation_year']); ?></span>
                        </div>
                        <p class="mb-1 text-muted"><?= e($alum['profession']); ?><?php if ($alum['company']): ?> · <?= e($alum['company']); ?><?php endif; ?></p>
                        <p class="small text-muted mb-2"><?= e($alum['department']); ?> Department</p>
                        <p class="card-text flex-grow-1"><?= e(substr($alum['bio'] ?? 'No bio provided.', 0, 160)); ?><?= isset($alum['bio']) && strlen($alum['bio']) > 160 ? '…' : ''; ?></p>
                        <div class="mt-3">
                            <?php if ($alum['contact_email']): ?>
                                <div class="small"><i class="bi bi-envelope me-1"></i> <a href="mailto:<?= e($alum['contact_email']); ?>"><?= e($alum['contact_email']); ?></a></div>
                            <?php endif; ?>
                            <?php if ($alum['linkedin_url']): ?>
                                <div class="small"><i class="bi bi-linkedin me-1"></i> <a href="<?= e($alum['linkedin_url']); ?>" target="_blank" rel="noopener">LinkedIn</a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
