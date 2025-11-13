<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Admin');
$pdo = db();
$currentUser = current_user();

$roles = $pdo->query('SELECT id, name FROM roles ORDER BY name ASC')->fetchAll();

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Invalid or expired security token.');
        redirect('/admin/users.php');
    }

    try {
        if ($action === 'create_user') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');
            $roleId = (int)($_POST['role_id'] ?? 0);
            $password = $_POST['password'] ?? '';

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                throw new RuntimeException('Provide a valid name, email, and password (min 8 characters).');
            }

            $stmt = $pdo->prepare('INSERT INTO users (role_id, name, email, password_hash, department, academic_year) VALUES (:role_id, :name, :email, :password_hash, :department, :academic_year)');
            $stmt->execute([
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_HASH_ALGO),
                'department' => $department ?: null,
                'academic_year' => $academicYear ?: null,
            ]);

            $newUserId = (int)$pdo->lastInsertId();
            log_activity($currentUser['id'], 'user_create', "Created user {$name} ({$email})");
            set_flash('success', 'User account created successfully.');
        } elseif ($action === 'update_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');
            $roleId = (int)($_POST['role_id'] ?? 0);
            $password = $_POST['password'] ?? '';

            if ($userId <= 0 || $name === '') {
                throw new RuntimeException('Invalid user data supplied.');
            }

            $pdo->beginTransaction();

            $update = $pdo->prepare('UPDATE users SET name = :name, department = :department, academic_year = :academic_year, role_id = :role_id WHERE id = :id');
            $update->execute([
                'name' => $name,
                'department' => $department ?: null,
                'academic_year' => $academicYear ?: null,
                'role_id' => $roleId,
                'id' => $userId,
            ]);

            if ($password !== '') {
                if (strlen($password) < 8) {
                    throw new RuntimeException('New password must be at least 8 characters.');
                }
                $pwd = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $pwd->execute([
                    'hash' => password_hash($password, PASSWORD_HASH_ALGO),
                    'id' => $userId,
                ]);
            }

            $pdo->commit();
            log_activity($currentUser['id'], 'user_update', "Updated user #{$userId}");
            set_flash('success', 'User details updated.');
        } elseif ($action === 'delete_user') {
            $userId = (int)($_POST['user_id'] ?? 0);

            if ($userId === $currentUser['id']) {
                throw new RuntimeException('You cannot delete your own account.');
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);

            log_activity($currentUser['id'], 'user_delete', "Deleted user #{$userId}");
            set_flash('success', 'User removed successfully.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('danger', $e->getMessage());
    }

    redirect('/admin/users.php');
}

$users = $pdo->query(
    "SELECT u.*, r.name AS role_name
     FROM users u
     JOIN roles r ON r.id = u.role_id
     ORDER BY u.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../templates/header.php';
$flashes = get_flashes();
$csrfToken = csrf_token();
?>

<?php foreach ($flashes as $type => $messages): ?>
    <div class="alert alert-<?= e($type); ?> alert-dismissible fade show" role="alert">
        <?php foreach ($messages as $message): ?>
            <div><?= e($message); ?></div>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-gradient-admin text-white">Create User</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <input type="hidden" name="action" value="create_user">
                    <div class="mb-3">
                        <label class="form-label" for="name">Full Name</label>
                        <input class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Temporary Password</label>
                        <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                        <div class="form-text">Share with user; they can update later.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role_id">Role</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int)$role['id']; ?>"><?= e($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col">
                            <label class="form-label">Department</label>
                            <input class="form-control" name="department">
                        </div>
                        <div class="col">
                            <label class="form-label">Academic Year</label>
                            <input class="form-control" name="academic_year">
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button class="btn btn-primary" type="submit">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-gradient-admin text-white">Users Directory</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($u['name']); ?></td>
                                    <td><?= e($u['email']); ?></td>
                                    <td><?= e($u['department'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= e($u['role_name']); ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= date('M d, Y', strtotime($u['updated_at'])); ?></small></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#user-<?= (int)$u['id']; ?>">
                                            Manage
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse-row">
                                    <td colspan="6" class="p-0">
                                        <div class="collapse" id="user-<?= (int)$u['id']; ?>">
                                            <div class="border-top p-4 bg-light-subtle">
                                                <div class="row g-3">
                                                    <div class="col-lg-9">
                                                        <form class="row g-3" method="post">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="update_user">
                                                            <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Full Name</label>
                                                                <input class="form-control" name="name" value="<?= e($u['name']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Department</label>
                                                                <input class="form-control" name="department" value="<?= e($u['department'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Academic Year</label>
                                                                <input class="form-control" name="academic_year" value="<?= e($u['academic_year'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Role</label>
                                                                <select class="form-select" name="role_id">
                                                                    <?php foreach ($roles as $role): ?>
                                                                        <option value="<?= (int)$role['id']; ?>" <?= (int)$role['id'] === (int)$u['role_id'] ? 'selected' : ''; ?>>
                                                                            <?= e($role['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Reset Password</label>
                                                                <input class="form-control" name="password" type="password" placeholder="Leave blank to keep current">
                                                            </div>
                                                            <div class="col-12 d-flex gap-2">
                                                                <button class="btn btn-primary" type="submit">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <form method="post" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                                            <button class="btn btn-outline-danger w-100" type="submit" <?= (int)$u['id'] === (int)$currentUser['id'] ? 'disabled' : ''; ?>>
                                                                Delete User
                                                            </button>
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
