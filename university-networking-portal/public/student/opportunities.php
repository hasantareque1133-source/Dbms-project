<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('student');

$pdo = Database::getConnection();
$pageTitle = 'Opportunity Board';

$typeFilter = sanitize($_GET['type'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$validTypes = ['job', 'internship', 'research'];

$conditions = [];
$params = [];

if ($typeFilter !== '' && in_array($typeFilter, $validTypes, true)) {
    $conditions[] = 'type = :type';
    $params['type'] = $typeFilter;
}

if ($search !== '') {
    $conditions[] = '(title LIKE :search OR organization LIKE :search OR description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

$stmt = $pdo->prepare("
    SELECT o.*, u.name AS author
    FROM opportunities o
    LEFT JOIN users u ON u.id = o.posted_by
    $whereClause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$opportunities = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Opportunity Board</h1>
        <p class="section-subtitle mb-0">Explore internships, research roles, and full-time positions curated by the university.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/public/student/opportunities.php">Clear Filters</a>
</div>

<form class="card shadow-sm border-0 mb-4" method="get">
    <div class="card-body row g-3 align-items-center">
        <div class="col-lg-4">
            <label class="form-label fw-semibold">Keyword Search</label>
            <input type="text" class="form-control search-bar" name="search" placeholder="Search by title, organization, or keyword" value="<?= htmlentities($search) ?>">
        </div>
        <div class="col-lg-3">
            <label class="form-label fw-semibold">Opportunity Type</label>
            <select class="form-select" name="type">
                <option value="">All types</option>
                <?php foreach ($validTypes as $type): ?>
                    <option value="<?= $type ?>" <?= $type === $typeFilter ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 d-flex align-items-end offset-lg-1">
            <button type="submit" class="btn btn-primary w-100">Filter Results</button>
        </div>
    </div>
</form>

<?php if (!empty($typeFilter) || !empty($search)): ?>
    <div class="mb-3">
        <div class="filters-chip">
            <span>•</span>
            Filtering results
            <?php if ($typeFilter): ?>
                <span class="badge bg-dark-subtle text-dark text-uppercase"><?= htmlentities($typeFilter) ?></span>
            <?php endif; ?>
            <?php if ($search): ?>
                <span class="badge bg-dark-subtle text-dark"><?= htmlentities($search) ?></span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($opportunities)): ?>
    <div class="empty-state">
        <p class="fw-semibold mb-1">No opportunities match your filters.</p>
        <p class="small mb-0">Try clearing filters or check back later as new postings arrive.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($opportunities as $opportunity): ?>
            <div class="col-lg-6" id="opportunity-<?= (int) $opportunity['id'] ?>">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h2 class="h5 fw-semibold mb-0"><?= htmlentities($opportunity['title']) ?></h2>
                            <?php
                            $type = $opportunity['type'];
                            $badgeClass = match ($type) {
                                'job' => 'badge-opportunity-job',
                                'internship' => 'badge-opportunity-internship',
                                default => 'badge-opportunity-research',
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?> text-uppercase"><?= htmlentities($type) ?></span>
                        </div>
                        <div class="mb-3 text-muted small">
                            <?= htmlentities($opportunity['organization'] ?? 'Organization Confidential') ?>
                            <?php if (!empty($opportunity['location'])): ?>
                                · <?= htmlentities($opportunity['location']) ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted flex-grow-1"><?= nl2br(htmlentities(substr($opportunity['description'], 0, 300))) ?><?= strlen($opportunity['description']) > 300 ? '…' : '' ?></p>
                        <div class="mt-3">
                            <?php if (!empty($opportunity['deadline'])): ?>
                                <div class="mb-2 small">
                                    <i class="bi bi-hourglass-split me-1"></i> Apply by <?= htmlentities((new DateTime($opportunity['deadline']))->format('M d, Y')) ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2">
                                <?php if (!empty($opportunity['apply_link'])): ?>
                                    <a class="btn btn-sm btn-primary" href="<?= htmlentities($opportunity['apply_link']) ?>" target="_blank" rel="noopener">
                                        Apply Now
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#opportunity-details-<?= (int) $opportunity['id'] ?>" aria-expanded="false">
                                    View Details
                                </button>
                            </div>
                        </div>
                        <div class="collapse mt-3" id="opportunity-details-<?= (int) $opportunity['id'] ?>">
                            <div class="bg-light rounded p-3 small">
                                <?= nl2br(htmlentities($opportunity['description'])) ?>
                                <div class="mt-3 text-muted">
                                    Posted by <?= htmlentities($opportunity['author'] ?? 'UNP Admin') ?> · <?= htmlentities((new DateTime($opportunity['created_at']))->format('M d, Y')) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
