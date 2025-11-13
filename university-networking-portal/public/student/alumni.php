<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('student');

$pdo = Database::getConnection();
$pageTitle = 'Alumni Directory';

$department = sanitize($_GET['department'] ?? '');
$graduationYear = sanitize($_GET['graduation_year'] ?? '');
$profession = sanitize($_GET['profession'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$conditions = [];
$params = [];

if ($department !== '') {
    $conditions[] = 'department LIKE :department';
    $params['department'] = '%' . $department . '%';
}

if ($graduationYear !== '' && preg_match('/^\d{4}$/', $graduationYear)) {
    $conditions[] = 'graduation_year = :graduation_year';
    $params['graduation_year'] = $graduationYear;
}

if ($profession !== '') {
    $conditions[] = 'profession LIKE :profession';
    $params['profession'] = '%' . $profession . '%';
}

if ($search !== '') {
    $conditions[] = '(name LIKE :search OR company LIKE :search OR bio LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$whereClause = '';
if ($conditions) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

$stmt = $pdo->prepare("
    SELECT *
    FROM alumni
    $whereClause
    ORDER BY graduation_year DESC, name ASC
");
$stmt->execute($params);
$alumni = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Alumni Connections</h1>
        <p class="section-subtitle mb-0">Search alumni mentors by department, profession, or graduation year to kickstart meaningful conversations.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/public/student/alumni.php">Clear Filters</a>
</div>

<form class="card shadow-sm border-0 mb-4" method="get">
    <div class="card-body row g-3">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Department</label>
            <input type="text" class="form-control" name="department" value="<?= htmlentities($department) ?>" placeholder="e.g. Computer Science">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Grad Year</label>
            <input type="number" class="form-control" name="graduation_year" value="<?= htmlentities($graduationYear) ?>" min="1950" max="2100">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Profession</label>
            <input type="text" class="form-control" name="profession" value="<?= htmlentities($profession) ?>" placeholder="e.g. Data Scientist">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Keyword</label>
            <input type="text" class="form-control" name="search" value="<?= htmlentities($search) ?>" placeholder="Name, company, or skills">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Find</button>
        </div>
    </div>
</form>

<?php if ($alumni): ?>
    <p class="text-muted mb-4"><?= count($alumni) ?> alumni match your filters.</p>
<?php else: ?>
    <div class="empty-state mb-4">
        <p class="fw-semibold mb-1">No alumni found with those filters.</p>
        <p class="small mb-0">Try broadening your search or clearing the filters.</p>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($alumni as $mentor): ?>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle me-3"><?= strtoupper(substr($mentor['name'], 0, 1)) ?></div>
                        <div>
                            <h2 class="h5 fw-semibold mb-0"><?= htmlentities($mentor['name']) ?></h2>
                            <div class="text-muted small"><?= htmlentities($mentor['profession'] ?? 'Experienced Professional') ?></div>
                        </div>
                    </div>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-mortarboard me-1"></i><?= htmlentities($mentor['department'] ?? 'Department not specified') ?> · Class of <?= htmlentities($mentor['graduation_year'] ?? '—') ?>
                    </p>
                    <?php if (!empty($mentor['company']) || !empty($mentor['location'])): ?>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlentities($mentor['company'] ?? 'Company confidential') ?><?= $mentor['location'] ? ' · ' . htmlentities($mentor['location']) : '' ?>
                        </p>
                    <?php endif; ?>
                    <p class="text-muted flex-grow-1"><?= $mentor['bio'] ? nl2br(htmlentities(substr($mentor['bio'], 0, 250))) . (strlen($mentor['bio']) > 250 ? '…' : '') : 'This alumni mentor has not provided a bio yet.' ?></p>
                    <div class="mt-3 small">
                        <?php if (!empty($mentor['email'])): ?>
                            <div class="mb-1">
                                <i class="bi bi-envelope me-1"></i>
                                <a href="mailto:<?= htmlentities($mentor['email']) ?>"><?= htmlentities($mentor['email']) ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($mentor['linkedin_url'])): ?>
                            <div>
                                <i class="bi bi-linkedin me-1"></i>
                                <a href="<?= htmlentities($mentor['linkedin_url']) ?>" target="_blank" rel="noopener">LinkedIn Profile</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
