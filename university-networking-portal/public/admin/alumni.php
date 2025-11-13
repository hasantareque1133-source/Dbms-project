<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$pdo = Database::getConnection();
$pageTitle = 'Alumni Directory Management | Admin';
$errors = [];
$editingAlumnus = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        $errors[] = 'Unknown action requested.';
    }

    if ($action === 'create' || $action === 'update') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $graduationYear = sanitize($_POST['graduation_year'] ?? '');
        $profession = sanitize($_POST['profession'] ?? '');
        $company = sanitize($_POST['company'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $linkedin = sanitize($_POST['linkedin'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if ($name === '') {
            $errors[] = 'Alumnus name is required.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if ($linkedin !== '' && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
            $errors[] = 'LinkedIn URL must be valid.';
        }

        if ($graduationYear !== '' && !preg_match('/^\d{4}$/', $graduationYear)) {
            $errors[] = 'Graduation year must be a four-digit number.';
        }

        if ($action === 'update' && (int) ($_POST['id'] ?? 0) <= 0) {
            $errors[] = 'Invalid alumni record selected for update.';
        }

        if (empty($errors)) {
            try {
                if ($action === 'create') {
                    $insert = $pdo->prepare('INSERT INTO alumni (name, email, department, graduation_year, profession, company, location, linkedin_url, bio, created_by) VALUES (:name, :email, :department, :graduation_year, :profession, :company, :location, :linkedin_url, :bio, :created_by)');
                    $insert->execute([
                        'name' => $name,
                        'email' => $email ?: null,
                        'department' => $department ?: null,
                        'graduation_year' => $graduationYear ?: null,
                        'profession' => $profession ?: null,
                        'company' => $company ?: null,
                        'location' => $location ?: null,
                        'linkedin_url' => $linkedin ?: null,
                        'bio' => $bio ?: null,
                        'created_by' => current_user()['id'],
                    ]);
                    set_flash('success', 'Alumni profile created successfully.');
                } else {
                    $update = $pdo->prepare('UPDATE alumni SET name = :name, email = :email, department = :department, graduation_year = :graduation_year, profession = :profession, company = :company, location = :location, linkedin_url = :linkedin_url, bio = :bio WHERE id = :id');
                    $update->execute([
                        'name' => $name,
                        'email' => $email ?: null,
                        'department' => $department ?: null,
                        'graduation_year' => $graduationYear ?: null,
                        'profession' => $profession ?: null,
                        'company' => $company ?: null,
                        'location' => $location ?: null,
                        'linkedin_url' => $linkedin ?: null,
                        'bio' => $bio ?: null,
                        'id' => (int) $_POST['id'],
                    ]);
                    set_flash('success', 'Alumni profile updated successfully.');
                }
                redirect('/public/admin/alumni.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to save alumni profile.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid alumni record selected.';
        } else {
            try {
                $delete = $pdo->prepare('DELETE FROM alumni WHERE id = :id');
                $delete->execute(['id' => $id]);
                set_flash('success', 'Alumni profile removed successfully.');
                redirect('/public/admin/alumni.php');
            } catch (PDOException $e) {
                $errors[] = 'Unable to delete alumni record.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM alumni WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $editingAlumnus = $stmt->fetch();
        if (!$editingAlumnus) {
            set_flash('warning', 'Alumni record not found.');
            redirect('/public/admin/alumni.php');
        }
    }
}

$alumni = $pdo->query('SELECT * FROM alumni ORDER BY graduation_year DESC, name')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Alumni Network</h1>
        <p class="section-subtitle mb-0">Maintain a searchable alumni directory to unlock mentorship and career pathways for students.</p>
    </div>
    <a href="/public/admin/alumni.php" class="btn btn-outline-secondary">Reset</a>
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
                <h2 class="h5 mb-0">Directory Overview</h2>
                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($alumni) ?> profiles</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Graduation</th>
                        <th>Profession</th>
                        <th>Location</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($alumni as $alumnus): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlentities($alumnus['name']) ?></div>
                                <?php if ($alumnus['email']): ?>
                                    <div class="small text-muted"><?= htmlentities($alumnus['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlentities($alumnus['department'] ?? '—') ?></td>
                            <td><?= htmlentities($alumnus['graduation_year'] ?? '—') ?></td>
                            <td><?= htmlentities($alumnus['profession'] ?? '—') ?></td>
                            <td><?= htmlentities($alumnus['location'] ?? '—') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/public/admin/alumni.php?edit=<?= (int) $alumnus['id'] ?>">Edit</a>
                                <form method="post" action="/public/admin/alumni.php" class="d-inline" onsubmit="return confirm('Remove this alumni profile?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $alumnus['id'] ?>">
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
                <h2 class="h5 mb-0"><?= $editingAlumnus ? 'Update Profile' : 'Add Alumni Profile' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="action" value="<?= $editingAlumnus ? 'update' : 'create' ?>">
                    <?php if ($editingAlumnus): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingAlumnus['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" required value="<?= htmlentities($editingAlumnus['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlentities($editingAlumnus['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" value="<?= htmlentities($editingAlumnus['department'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Graduation Year</label>
                        <input type="number" class="form-control" name="graduation_year" value="<?= htmlentities($editingAlumnus['graduation_year'] ?? '') ?>" min="1950" max="2100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profession</label>
                        <input type="text" class="form-control" name="profession" value="<?= htmlentities($editingAlumnus['profession'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company / Organization</label>
                        <input type="text" class="form-control" name="company" value="<?= htmlentities($editingAlumnus['company'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" value="<?= htmlentities($editingAlumnus['location'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="url" class="form-control" name="linkedin" value="<?= htmlentities($editingAlumnus['linkedin_url'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bio / Mentorship Focus</label>
                        <textarea class="form-control" rows="4" name="bio" placeholder="Highlight expertise, mentoring interests, or achievements"><?= htmlentities($editingAlumnus['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $editingAlumnus ? 'Update Profile' : 'Create Profile' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
