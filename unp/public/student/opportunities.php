<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Student');
$pdo = db();
$user = current_user();

$filters = [
    'type' => $_GET['type'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
];

$query = "SELECT o.*, u.name AS poster_name, u.department AS poster_department
          FROM opportunities o
          JOIN users u ON u.id = o.posted_by";
$conditions = [];
$params = [];

if ($filters['type'] && in_array($filters['type'], ['job', 'internship', 'research'], true)) {
    $conditions[] = 'o.type = :type';
    $params['type'] = $filters['type'];
}

if ($filters['search'] !== '') {
    $conditions[] = '(o.title LIKE :term OR o.description LIKE :term OR u.name LIKE :term)';
    $params['term'] = '%' . $filters['search'] . '%';
}

if ($conditions) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}

$query .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$opportunities = $stmt->fetchAll();

$pageTitle = 'Opportunities';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label">Opportunity Type</label>
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    <option value="job" <?= $filters['type'] === 'job' ? 'selected' : ''; ?>>Job</option>
                    <option value="internship" <?= $filters['type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                    <option value="research" <?= $filters['type'] === 'research' ? 'selected' : ''; ?>>Research</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Keyword</label>
                <input class="form-control" name="search" placeholder="Title, description, mentor" value="<?= e($filters['search']); ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i> Search</button>
                <a class="btn btn-outline-light" href="<?= BASE_URL; ?>/student/opportunities.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$opportunities): ?>
    <div class="alert alert-info">No opportunities found. Try adjusting your filters.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($opportunities as $opp): ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card h-100 opportunity-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary-subtle text-primary"><?= opportunity_label($opp['type']); ?></span>
                            <small class="text-muted"><?= date('M d, Y', strtotime($opp['created_at'])); ?></small>
                        </div>
                        <h5 class="card-title"><?= e($opp['title']); ?></h5>
                        <p class="card-text flex-grow-1"><?= e(substr($opp['description'], 0, 160)); ?><?= strlen($opp['description']) > 160 ? '…' : ''; ?></p>
                        <div class="mt-3">
                            <div class="small text-muted">
                                Posted by <?= e($opp['poster_name']); ?>
                                <?php if ($opp['poster_department']): ?>
                                    · <?= e($opp['poster_department']); ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($opp['location']): ?>
                                <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= e($opp['location']); ?></div>
                            <?php endif; ?>
                            <?php if ($opp['deadline']): ?>
                                <div class="small text-muted"><i class="bi bi-calendar-event"></i> Apply by <?= date('M d, Y', strtotime($opp['deadline'])); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <?php if ($opp['apply_link']): ?>
                                <a class="btn btn-primary btn-sm flex-grow-1" href="<?= e($opp['apply_link']); ?>" target="_blank" rel="noopener">Apply Now</a>
                            <?php endif; ?>
                            <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#oppModal-<?= (int)$opp['id']; ?>">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="oppModal-<?= (int)$opp['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= e($opp['title']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-2">
                                <span class="badge bg-primary-subtle text-primary"><?= opportunity_label($opp['type']); ?></span>
                                &middot; Posted <?= date('M d, Y', strtotime($opp['created_at'])); ?>
                            </p>
                            <p><?= nl2br(e($opp['description'])); ?></p>
                            <hr>
                            <p class="small text-muted mb-0">
                                <i class="bi bi-person-circle me-1"></i> <?= e($opp['poster_name']); ?>
                                <?php if ($opp['poster_department']): ?>
                                    &middot; <?= e($opp['poster_department']); ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($opp['location']): ?>
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-geo-alt me-1"></i> <?= e($opp['location']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($opp['deadline']): ?>
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-calendar-event me-1"></i> Deadline <?= date('M d, Y', strtotime($opp['deadline'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <?php if ($opp['apply_link']): ?>
                                <a class="btn btn-primary" href="<?= e($opp['apply_link']); ?>" target="_blank" rel="noopener">Apply Now</a>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
