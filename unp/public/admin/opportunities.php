<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Admin');
$pdo = db();
$currentUser = current_user();

if (is_post()) {
    $token = $_POST['csrf_token'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Invalid or expired security token.');
        redirect('/admin/opportunities.php');
    }

    try {
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['type'] ?? '';
            $description = trim($_POST['description'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $applyLink = trim($_POST['apply_link'] ?? '');
            $deadline = $_POST['deadline'] ?? null;

            if ($title === '' || $description === '') {
                throw new RuntimeException('Title and description are required.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO opportunities (title, type, description, location, apply_link, deadline, posted_by)
                 VALUES (:title, :type, :description, :location, :apply_link, :deadline, :posted_by)'
            );
            $stmt->execute([
                'title' => $title,
                'type' => $type,
                'description' => $description,
                'location' => $location ?: null,
                'apply_link' => $applyLink ?: null,
                'deadline' => $deadline ?: null,
                'posted_by' => $currentUser['id'],
            ]);

            log_activity($currentUser['id'], 'opportunity_create', "Created opportunity {$title}");
            set_flash('success', 'Opportunity posted successfully.');
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['type'] ?? '';
            $description = trim($_POST['description'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $applyLink = trim($_POST['apply_link'] ?? '');
            $deadline = $_POST['deadline'] ?? null;

            if ($id <= 0 || $title === '') {
                throw new RuntimeException('Invalid opportunity data provided.');
            }

            $stmt = $pdo->prepare(
                'UPDATE opportunities
                 SET title = :title, type = :type, description = :description, location = :location,
                     apply_link = :apply_link, deadline = :deadline
                 WHERE id = :id'
            );
            $stmt->execute([
                'title' => $title,
                'type' => $type,
                'description' => $description,
                'location' => $location ?: null,
                'apply_link' => $applyLink ?: null,
                'deadline' => $deadline ?: null,
                'id' => $id,
            ]);

            log_activity($currentUser['id'], 'opportunity_update', "Updated opportunity #{$id}");
            set_flash('success', 'Opportunity updated.');
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM opportunities WHERE id = :id');
            $stmt->execute(['id' => $id]);
            log_activity($currentUser['id'], 'opportunity_delete', "Deleted opportunity #{$id}");
            set_flash('success', 'Opportunity removed.');
        }
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }

    redirect('/admin/opportunities.php');
}

$opportunities = $pdo->query(
    "SELECT o.*, u.name AS poster
     FROM opportunities o
     JOIN users u ON u.id = o.posted_by
     ORDER BY o.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Opportunities';
require_once __DIR__ . '/../../templates/header.php';
$csrfToken = csrf_token();
$flashes = get_flashes();
?>

<?php foreach ($flashes as $type => $messages): ?>
    <div class="alert alert-<?= e($type); ?> alert-dismissible fade show" role="alert">
        <?php foreach ($messages as $message): ?>
            <div><?= e($message); ?></div>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-gradient-admin text-white">Post Opportunity</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="type">
                            <option value="job">Job</option>
                            <option value="internship">Internship</option>
                            <option value="research">Research</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input class="form-control" name="location">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apply Link</label>
                        <input class="form-control" name="apply_link" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deadline</label>
                        <input class="form-control" type="date" name="deadline">
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Publish</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-gradient-admin text-white">Opportunity Board</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Posted By</th>
                            <th>Deadline</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$opportunities): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No opportunities posted.</td></tr>
                        <?php else: ?>
                            <?php foreach ($opportunities as $opportunity): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($opportunity['title']); ?></div>
                                        <small class="text-muted d-block"><?= e(substr($opportunity['description'], 0, 90)); ?><?= strlen($opportunity['description']) > 90 ? '…' : ''; ?></small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= opportunity_label($opportunity['type']); ?></span></td>
                                    <td><?= e($opportunity['poster']); ?></td>
                                    <td><?= $opportunity['deadline'] ? date('M d, Y', strtotime($opportunity['deadline'])) : 'Open'; ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#opp-<?= (int)$opportunity['id']; ?>">Manage</button>
                                    </td>
                                </tr>
                                <tr class="collapse-row">
                                    <td colspan="5" class="p-0">
                                        <div class="collapse" id="opp-<?= (int)$opportunity['id']; ?>">
                                            <div class="border-top p-4 bg-light-subtle">
                                                <div class="row g-3">
                                                    <div class="col-lg-9">
                                                        <form method="post" class="row g-3">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="id" value="<?= (int)$opportunity['id']; ?>">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Title</label>
                                                                <input class="form-control" name="title" value="<?= e($opportunity['title']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Category</label>
                                                                <select class="form-select" name="type">
                                                                    <?php foreach (['job', 'internship', 'research'] as $type): ?>
                                                                        <option value="<?= $type; ?>" <?= $type === $opportunity['type'] ? 'selected' : ''; ?>>
                                                                            <?= opportunity_label($type); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label">Description</label>
                                                                <textarea class="form-control" name="description" rows="4" required><?= e($opportunity['description']); ?></textarea>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Location</label>
                                                                <input class="form-control" name="location" value="<?= e($opportunity['location'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Apply Link</label>
                                                                <input class="form-control" name="apply_link" value="<?= e($opportunity['apply_link'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Deadline</label>
                                                                <input class="form-control" type="date" name="deadline" value="<?= e($opportunity['deadline'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-12">
                                                                <button class="btn btn-primary" type="submit">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <form method="post" onsubmit="return confirm('Delete this opportunity?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= (int)$opportunity['id']; ?>">
                                                            <button class="btn btn-outline-danger w-100" type="submit">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
