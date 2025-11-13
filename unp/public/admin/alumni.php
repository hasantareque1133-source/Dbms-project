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
        redirect('/admin/alumni.php');
    }

    try {
        if ($action === 'create') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'graduation_year' => (int)($_POST['graduation_year'] ?? 0),
                'profession' => trim($_POST['profession'] ?? ''),
                'company' => trim($_POST['company'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
            ];

            if ($data['name'] === '' || $data['department'] === '' || $data['graduation_year'] === 0 || $data['profession'] === '') {
                throw new RuntimeException('Name, department, graduation year, and profession are required.');
            }

            if ($data['contact_email'] !== '' && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Provide a valid email address.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO alumni (name, department, graduation_year, profession, company, contact_email, linkedin_url, bio)
                 VALUES (:name, :department, :graduation_year, :profession, :company, :contact_email, :linkedin_url, :bio)'
            );
            $stmt->execute([
                'name' => $data['name'],
                'department' => $data['department'],
                'graduation_year' => $data['graduation_year'],
                'profession' => $data['profession'],
                'company' => $data['company'] ?: null,
                'contact_email' => $data['contact_email'] ?: null,
                'linkedin_url' => $data['linkedin_url'] ?: null,
                'bio' => $data['bio'] ?: null,
            ]);

            log_activity($currentUser['id'], 'alumni_create', "Created alumni {$data['name']}");
            set_flash('success', 'Alumni profile added.');
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Invalid alumni record.');
            }

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'graduation_year' => (int)($_POST['graduation_year'] ?? 0),
                'profession' => trim($_POST['profession'] ?? ''),
                'company' => trim($_POST['company'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
            ];

            if ($data['name'] === '' || $data['department'] === '' || $data['graduation_year'] === 0 || $data['profession'] === '') {
                throw new RuntimeException('Name, department, graduation year, and profession are required.');
            }

            if ($data['contact_email'] !== '' && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Provide a valid email address.');
            }

            $stmt = $pdo->prepare(
                'UPDATE alumni
                 SET name = :name, department = :department, graduation_year = :graduation_year,
                     profession = :profession, company = :company, contact_email = :contact_email,
                     linkedin_url = :linkedin_url, bio = :bio
                 WHERE id = :id'
            );
            $stmt->execute([
                'name' => $data['name'],
                'department' => $data['department'],
                'graduation_year' => $data['graduation_year'],
                'profession' => $data['profession'],
                'company' => $data['company'] ?: null,
                'contact_email' => $data['contact_email'] ?: null,
                'linkedin_url' => $data['linkedin_url'] ?: null,
                'bio' => $data['bio'] ?: null,
                'id' => $id,
            ]);

            log_activity($currentUser['id'], 'alumni_update', "Updated alumni #{$id}");
            set_flash('success', 'Alumni profile updated.');
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM alumni WHERE id = :id');
            $stmt->execute(['id' => $id]);
            log_activity($currentUser['id'], 'alumni_delete', "Deleted alumni #{$id}");
            set_flash('success', 'Alumni profile removed.');
        }
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }

    redirect('/admin/alumni.php');
}

$alumni = $pdo->query('SELECT * FROM alumni ORDER BY graduation_year DESC, name ASC')->fetchAll();

$pageTitle = 'Alumni Directory';
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
            <div class="card-header bg-gradient-admin text-white">Add Alumni</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input class="form-control" name="department" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Graduation Year</label>
                        <input class="form-control" name="graduation_year" type="number" min="1950" max="<?= date('Y'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profession</label>
                        <input class="form-control" name="profession" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input class="form-control" name="company">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input class="form-control" name="contact_email" type="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">LinkedIn URL</label>
                        <input class="form-control" name="linkedin_url" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bio / Notes</label>
                        <textarea class="form-control" name="bio" rows="3"></textarea>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Add Alumni</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-gradient-admin text-white">Directory</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Graduation</th>
                            <th>Profession</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$alumni): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No alumni records yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($alumni as $alum): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($alum['name']); ?></td>
                                    <td><?= e($alum['department']); ?></td>
                                    <td><?= e($alum['graduation_year']); ?></td>
                                    <td><?= e($alum['profession']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#alumni-<?= (int)$alum['id']; ?>">Manage</button>
                                    </td>
                                </tr>
                                <tr class="collapse-row">
                                    <td colspan="5" class="p-0">
                                        <div class="collapse" id="alumni-<?= (int)$alum['id']; ?>">
                                            <div class="border-top p-4 bg-light-subtle">
                                                <form method="post" class="row g-3">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?= (int)$alum['id']; ?>">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Full Name</label>
                                                        <input class="form-control" name="name" value="<?= e($alum['name']); ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Department</label>
                                                        <input class="form-control" name="department" value="<?= e($alum['department']); ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Graduation Year</label>
                                                        <input class="form-control" name="graduation_year" type="number" min="1950" max="<?= date('Y'); ?>" value="<?= e($alum['graduation_year']); ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Profession</label>
                                                        <input class="form-control" name="profession" value="<?= e($alum['profession']); ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Company</label>
                                                        <input class="form-control" name="company" value="<?= e($alum['company'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Contact Email</label>
                                                        <input class="form-control" name="contact_email" type="email" value="<?= e($alum['contact_email'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">LinkedIn URL</label>
                                                        <input class="form-control" name="linkedin_url" value="<?= e($alum['linkedin_url'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Bio / Notes</label>
                                                        <textarea class="form-control" name="bio" rows="3"><?= e($alum['bio'] ?? ''); ?></textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-primary" type="submit">Save Changes</button>
                                                    </div>
                                                </form>
                                                <form method="post" class="mt-3" onsubmit="return confirm('Delete this alumni profile?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$alum['id']; ?>">
                                                    <button class="btn btn-outline-danger" type="submit">Delete Profile</button>
                                                </form>
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
