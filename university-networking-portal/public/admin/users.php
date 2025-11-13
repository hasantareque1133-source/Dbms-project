<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$pdo = Database::getConnection();
$pageTitle = 'Manage Users | Admin';
$errors = [];
$editingUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $institutionalId = sanitize($_POST['institutional_id'] ?? '');
        $role = sanitize($_POST['role'] ?? 'student');
        $department = sanitize($_POST['department'] ?? '');
        $graduationYear = sanitize($_POST['graduation_year'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $institutionalId === '' || $password === '') {
            $errors[] = 'Name, email, institutional ID, and password are required.';
        }

        if (!in_array($role, ['admin', 'moderator', 'student'], true)) {
            $errors[] = 'Invalid role specified.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email OR institutional_id = :institutional_id');
                $stmt->execute([
                    'email' => $email,
                    'institutional_id' => $institutionalId,
                ]);

                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'A user already exists with that email or institutional ID.';
                } else {
                    $insert = $pdo->prepare('INSERT INTO users (name, email, institutional_id, password_hash, role, department, graduation_year) VALUES (:name, :email, :institutional_id, :password_hash, :role, :department, :graduation_year)');
                    $insert->execute([
                        'name' => $name,
                        'email' => $email,
                        'institutional_id' => $institutionalId,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'department' => $department ?: null,
                        'graduation_year' => $graduationYear !== '' ? (int) $graduationYear : null,
                    ]);

                    set_flash('success', 'User account created successfully.');
                    redirect('/public/admin/users.php');
                }
            } catch (PDOException $e) {
                $errors[] = 'Failed to create user. Please try again.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $institutionalId = sanitize($_POST['institutional_id'] ?? '');
        $role = sanitize($_POST['role'] ?? 'student');
        $department = sanitize($_POST['department'] ?? '');
        $graduationYear = sanitize($_POST['graduation_year'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($id <= 0) {
            $errors[] = 'Invalid user selected for update.';
        }

        if ($name === '' || $email === '' || $institutionalId === '') {
            $errors[] = 'Name, email, and institutional ID are required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if (!in_array($role, ['admin', 'moderator', 'student'], true)) {
            $errors[] = 'Invalid role specified.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (email = :email OR institutional_id = :institutional_id) AND id != :id');
                $stmt->execute([
                    'email' => $email,
                    'institutional_id' => $institutionalId,
                    'id' => $id,
                ]);

                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Email or institutional ID already in use by another account.';
                } else {
                    $pdo->beginTransaction();

                    $update = $pdo->prepare('UPDATE users SET name = :name, email = :email, institutional_id = :institutional_id, role = :role, department = :department, graduation_year = :graduation_year WHERE id = :id');
                    $update->execute([
                        'name' => $name,
                        'email' => $email,
                        'institutional_id' => $institutionalId,
                        'role' => $role,
                        'department' => $department ?: null,
                        'graduation_year' => $graduationYear !== '' ? (int) $graduationYear : null,
                        'id' => $id,
                    ]);

                    if ($password !== '') {
                        if (strlen($password) < 8) {
                            throw new RuntimeException('Password must be at least 8 characters.');
                        }
                        $passwordUpdate = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                        $passwordUpdate->execute([
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'id' => $id,
                        ]);
                    }

                    $pdo->commit();

                    if ($id === current_user()['id']) {
                        $_SESSION['user']['name'] = $name;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['institutional_id'] = $institutionalId;
                        $_SESSION['user']['role'] = $role;
                        $_SESSION['user']['department'] = $department;
                        $_SESSION['user']['graduation_year'] = $graduationYear;
                    }

                    set_flash('success', 'User details updated successfully.');
                    redirect('/public/admin/users.php');
                }
            } catch (RuntimeException $runtimeException) {
                $pdo->rollBack();
                $errors[] = $runtimeException->getMessage();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to update user. Please try again.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid user selected for deletion.';
        } elseif ($id === current_user()['id']) {
            $errors[] = 'You cannot delete your own account.';
        } else {
            try {
                $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $delete->execute(['id' => $id]);
                set_flash('success', 'User removed successfully.');
                redirect('/public/admin/users.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to delete user. Ensure there are no dependent records.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, name, email, institutional_id, role, department, graduation_year FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $editingUser = $stmt->fetch();
        if (!$editingUser) {
            set_flash('warning', 'The selected user could not be found.');
            redirect('/public/admin/users.php');
        }
    }
}

$users = $pdo->query('SELECT id, name, email, institutional_id, role, department, graduation_year, created_at FROM users ORDER BY role, name')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Team & Community Management</h1>
        <p class="section-subtitle mb-0">Create accounts, assign moderator roles, and keep your user base up to date.</p>
    </div>
    <a href="/public/admin/users.php" class="btn btn-outline-secondary">Reset</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlentities($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Registered Users</h2>
                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($users) ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Graduation</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlentities($user['name']) ?></td>
                            <td>
                                <span class="d-block small"><?= htmlentities($user['email']) ?></span>
                                <span class="d-block text-muted small">ID: <?= htmlentities($user['institutional_id']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : ($user['role'] === 'moderator' ? 'success' : 'warning') ?> text-uppercase">
                                    <?= htmlentities($user['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlentities($user['department'] ?? '—') ?></td>
                            <td><?= htmlentities($user['graduation_year'] ?? '—') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/public/admin/users.php?edit=<?= (int) $user['id'] ?>">Edit</a>
                                <?php if ($user['id'] !== current_user()['id']): ?>
                                    <form action="/public/admin/users.php" method="post" class="d-inline" onsubmit="return confirm('Delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
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
                <h2 class="h5 mb-0"><?= $editingUser ? 'Update User' : 'Create a New User' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="action" value="<?= $editingUser ? 'update' : 'create' ?>">
                    <?php if ($editingUser): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingUser['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" required value="<?= htmlentities($editingUser['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required value="<?= htmlentities($editingUser['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institutional ID</label>
                        <input type="text" class="form-control" name="institutional_id" required value="<?= htmlentities($editingUser['institutional_id'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="student" <?= (($editingUser['role'] ?? '') === 'student') ? 'selected' : '' ?>>Student</option>
                            <option value="moderator" <?= (($editingUser['role'] ?? '') === 'moderator') ? 'selected' : '' ?>>Moderator</option>
                            <option value="admin" <?= (($editingUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" value="<?= htmlentities($editingUser['department'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Graduation Year</label>
                        <input type="number" class="form-control" name="graduation_year" value="<?= htmlentities($editingUser['graduation_year'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $editingUser ? 'New Password (optional)' : 'Password' ?></label>
                        <input type="password" class="form-control" name="password" <?= $editingUser ? '' : 'required minlength="8"' ?>>
                        <?php if ($editingUser): ?>
                            <div class="form-text">Leave blank to keep existing password.</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $editingUser ? 'Update User' : 'Create User' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
