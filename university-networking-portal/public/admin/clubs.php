<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$pdo = Database::getConnection();
$pageTitle = 'Manage Clubs | Admin';
$errors = [];
$editingClub = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $moderatorId = (int) ($_POST['moderator_id'] ?? 0);

        if ($name === '') {
            $errors[] = 'Club name is required.';
        }

        if (empty($errors)) {
            try {
                $insert = $pdo->prepare('INSERT INTO clubs (name, description, moderator_id) VALUES (:name, :description, :moderator_id)');
                $insert->execute([
                    'name' => $name,
                    'description' => $description ?: null,
                    'moderator_id' => $moderatorId > 0 ? $moderatorId : null,
                ]);
                set_flash('success', 'Club created successfully.');
                redirect('/public/admin/clubs.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to create club. Please try again.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $moderatorId = (int) ($_POST['moderator_id'] ?? 0);

        if ($id <= 0 || $name === '') {
            $errors[] = 'Invalid club details provided.';
        }

        if (empty($errors)) {
            try {
                $update = $pdo->prepare('UPDATE clubs SET name = :name, description = :description, moderator_id = :moderator_id WHERE id = :id');
                $update->execute([
                    'name' => $name,
                    'description' => $description ?: null,
                    'moderator_id' => $moderatorId > 0 ? $moderatorId : null,
                    'id' => $id,
                ]);
                set_flash('success', 'Club updated successfully.');
                redirect('/public/admin/clubs.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to update club.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid club selected.';
        } else {
            try {
                $delete = $pdo->prepare('DELETE FROM clubs WHERE id = :id');
                $delete->execute(['id' => $id]);
                set_flash('success', 'Club deleted successfully.');
                redirect('/public/admin/clubs.php');
            } catch (PDOException $e) {
                $errors[] = 'Unable to delete club. Ensure there are no linked events.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, name, description, moderator_id FROM clubs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $editingClub = $stmt->fetch();
        if (!$editingClub) {
            set_flash('warning', 'The selected club could not be found.');
            redirect('/public/admin/clubs.php');
        }
    }
}

$clubs = $pdo->query('SELECT c.id, c.name, c.description, c.created_at, u.name AS moderator_name FROM clubs c LEFT JOIN users u ON c.moderator_id = u.id ORDER BY c.name')->fetchAll();
$moderators = $pdo->query("SELECT id, name FROM users WHERE role = 'moderator' ORDER BY name")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Club Directory</h1>
        <p class="section-subtitle mb-0">Assign moderators to clubs and curate experiences for the student community.</p>
    </div>
    <a href="/public/admin/clubs.php" class="btn btn-outline-secondary">Reset</a>
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
                <h2 class="h5 mb-0">Active Clubs</h2>
                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($clubs) ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Club</th>
                        <th>Moderator</th>
                        <th>Description</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clubs as $club): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlentities($club['name']) ?></td>
                            <td><?= htmlentities($club['moderator_name'] ?? 'Unassigned') ?></td>
                            <td><?= $club['description'] ? htmlentities($club['description']) : '—' ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/public/admin/clubs.php?edit=<?= (int) $club['id'] ?>">Edit</a>
                                <form class="d-inline" method="post" action="/public/admin/clubs.php" onsubmit="return confirm('Delete this club? Any associated events will need reassignment.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $club['id'] ?>">
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
                <h2 class="h5 mb-0"><?= $editingClub ? 'Update Club' : 'Create a Club' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="action" value="<?= $editingClub ? 'update' : 'create' ?>">
                    <?php if ($editingClub): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingClub['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Club Name</label>
                        <input type="text" class="form-control" name="name" required value="<?= htmlentities($editingClub['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Moderator</label>
                        <select class="form-select" name="moderator_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($moderators as $moderator): ?>
                                <option value="<?= (int) $moderator['id'] ?>" <?= isset($editingClub['moderator_id']) && (int) $editingClub['moderator_id'] === (int) $moderator['id'] ? 'selected' : '' ?>>
                                    <?= htmlentities($moderator['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only users with the moderator role are displayed here.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="description" placeholder="Describe the club's mission"><?= htmlentities($editingClub['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $editingClub ? 'Update Club' : 'Create Club' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
