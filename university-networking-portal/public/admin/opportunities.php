<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$pdo = Database::getConnection();
$pageTitle = 'Manage Opportunities | Admin';
$errors = [];
$editingOpportunity = null;

$validTypes = ['job', 'internship', 'research'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = sanitize($_POST['title'] ?? '');
        $type = strtolower(sanitize($_POST['type'] ?? ''));
        $organization = sanitize($_POST['organization'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $applyLink = sanitize($_POST['apply_link'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            $errors[] = 'Opportunity title is required.';
        }

        if (!in_array($type, $validTypes, true)) {
            $errors[] = 'Please select a valid opportunity type.';
        }

        if ($description === '') {
            $errors[] = 'Description is required.';
        }

        if ($deadline !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
            $errors[] = 'Deadline must be in YYYY-MM-DD format.';
        }

        if ($applyLink !== '' && !filter_var($applyLink, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please provide a valid application link.';
        }

        if (empty($errors)) {
            try {
                $insert = $pdo->prepare('INSERT INTO opportunities (title, type, organization, location, description, deadline, apply_link, posted_by) VALUES (:title, :type, :organization, :location, :description, :deadline, :apply_link, :posted_by)');
                $insert->execute([
                    'title' => $title,
                    'type' => $type,
                    'organization' => $organization ?: null,
                    'location' => $location ?: null,
                    'description' => $description,
                    'deadline' => $deadline ?: null,
                    'apply_link' => $applyLink ?: null,
                    'posted_by' => current_user()['id'],
                ]);
                set_flash('success', 'Opportunity posted successfully.');
                redirect('/public/admin/opportunities.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to create opportunity.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $type = strtolower(sanitize($_POST['type'] ?? ''));
        $organization = sanitize($_POST['organization'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $applyLink = sanitize($_POST['apply_link'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0) {
            $errors[] = 'Invalid opportunity selected.';
        }

        if ($title === '' || !in_array($type, $validTypes, true) || $description === '') {
            $errors[] = 'Title, valid type, and description are required.';
        }

        if ($deadline !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
            $errors[] = 'Deadline must be in YYYY-MM-DD format.';
        }

        if ($applyLink !== '' && !filter_var($applyLink, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please provide a valid application link.';
        }

        if (empty($errors)) {
            try {
                $update = $pdo->prepare('UPDATE opportunities SET title = :title, type = :type, organization = :organization, location = :location, description = :description, deadline = :deadline, apply_link = :apply_link WHERE id = :id');
                $update->execute([
                    'title' => $title,
                    'type' => $type,
                    'organization' => $organization ?: null,
                    'location' => $location ?: null,
                    'description' => $description,
                    'deadline' => $deadline ?: null,
                    'apply_link' => $applyLink ?: null,
                    'id' => $id,
                ]);
                set_flash('success', 'Opportunity updated successfully.');
                redirect('/public/admin/opportunities.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to update opportunity.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid opportunity selected.';
        } else {
            try {
                $delete = $pdo->prepare('DELETE FROM opportunities WHERE id = :id');
                $delete->execute(['id' => $id]);
                set_flash('success', 'Opportunity removed successfully.');
                redirect('/public/admin/opportunities.php');
            } catch (PDOException $e) {
                $errors[] = 'Unable to delete opportunity.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM opportunities WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $editingOpportunity = $stmt->fetch();
        if (!$editingOpportunity) {
            set_flash('warning', 'Selected opportunity not found.');
            redirect('/public/admin/opportunities.php');
        }
    }
}

$opportunities = $pdo->query('SELECT o.*, u.name AS author FROM opportunities o LEFT JOIN users u ON o.posted_by = u.id ORDER BY o.created_at DESC')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Opportunity Marketplace</h1>
        <p class="section-subtitle mb-0">Publish internships, jobs, and research programs for students across the university.</p>
    </div>
    <a href="/public/admin/opportunities.php" class="btn btn-outline-secondary">Reset</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlentities($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Posted Opportunities</h2>
                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($opportunities) ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Organization</th>
                        <th>Deadline</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($opportunities as $opportunity): ?>
                        <tr>
                            <td>
                                <strong><?= htmlentities($opportunity['title']) ?></strong>
                                <div class="small text-muted">Posted by <?= htmlentities($opportunity['author'] ?? 'System') ?></div>
                            </td>
                            <td>
                                <?php
                                $type = $opportunity['type'];
                                $badgeClass = match ($type) {
                                    'job' => 'badge-opportunity-job',
                                    'internship' => 'badge-opportunity-internship',
                                    default => 'badge-opportunity-research',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> text-uppercase"><?= htmlentities($type) ?></span>
                            </td>
                            <td><?= htmlentities($opportunity['organization'] ?? '—') ?></td>
                            <td><?= $opportunity['deadline'] ? htmlentities((new DateTime($opportunity['deadline']))->format('M d, Y')) : '—' ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/public/admin/opportunities.php?edit=<?= (int) $opportunity['id'] ?>">Edit</a>
                                <form class="d-inline" method="post" action="/public/admin/opportunities.php" onsubmit="return confirm('Delete this opportunity?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $opportunity['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0"><?= $editingOpportunity ? 'Update Opportunity' : 'Create Opportunity' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="action" value="<?= $editingOpportunity ? 'update' : 'create' ?>">
                    <?php if ($editingOpportunity): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingOpportunity['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required value="<?= htmlentities($editingOpportunity['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <?php foreach ($validTypes as $type): ?>
                                <option value="<?= $type ?>" <?= ($editingOpportunity['type'] ?? '') === $type ? 'selected' : '' ?>>
                                    <?= ucfirst($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Organization</label>
                        <input type="text" class="form-control" name="organization" value="<?= htmlentities($editingOpportunity['organization'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" value="<?= htmlentities($editingOpportunity['location'] ?? '') ?>" placeholder="On-campus / Remote / City">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Application Deadline</label>
                        <input type="date" class="form-control" name="deadline" value="<?= htmlentities($editingOpportunity['deadline'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Application Link</label>
                        <input type="url" class="form-control" name="apply_link" value="<?= htmlentities($editingOpportunity['apply_link'] ?? '') ?>" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="5" name="description" required><?= htmlentities($editingOpportunity['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $editingOpportunity ? 'Update Opportunity' : 'Publish Opportunity' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
