<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Student');
$pdo = db();
$user = current_user();

if (is_post()) {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Invalid session token.');
        redirect('/student/profile.php');
    }

    try {
        if ($_POST['action'] === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');

            if ($name === '') {
                throw new RuntimeException('Name cannot be empty.');
            }

            $stmt = $pdo->prepare('UPDATE users SET name = :name, department = :department, academic_year = :academic_year WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'department' => $department ?: null,
                'academic_year' => $academicYear ?: null,
                'id' => $user['id'],
            ]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['department'] = $department ?: null;
            $_SESSION['user']['academic_year'] = $academicYear ?: null;

            log_activity($user['id'], 'profile_update', 'Updated profile information');
            set_flash('success', 'Profile updated successfully.');
        } elseif ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword === '' || strlen($newPassword) < 8) {
                throw new RuntimeException('New password must be at least 8 characters.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('New password and confirmation do not match.');
            }

            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
            $stmt->execute(['id' => $user['id']]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($currentPassword, $hash)) {
                throw new RuntimeException('Current password is incorrect.');
            }

            $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $update->execute([
                'hash' => password_hash($newPassword, PASSWORD_HASH_ALGO),
                'id' => $user['id'],
            ]);

            log_activity($user['id'], 'password_change', 'Updated account password');
            set_flash('success', 'Password updated successfully.');
        }
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }

    redirect('/student/profile.php');
}

$stmt = $pdo->prepare('SELECT name, email, department, academic_year, created_at FROM users WHERE id = :id');
$stmt->execute(['id' => $user['id']]);
$profile = $stmt->fetch();

$pageTitle = 'My Profile';
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
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-gradient-student text-white">Profile</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="name" value="<?= e($profile['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email (read-only)</label>
                        <input class="form-control" value="<?= e($profile['email']); ?>" disabled>
                        <div class="form-text">Contact your administrator to update institutional email.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input class="form-control" name="department" value="<?= e($profile['department'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <input class="form-control" name="academic_year" value="<?= e($profile['academic_year'] ?? ''); ?>">
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-gradient-student text-white">Change Password</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input class="form-control" name="current_password" type="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input class="form-control" name="new_password" type="password" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input class="form-control" name="confirm_password" type="password" minlength="8" required>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-outline-light" type="submit">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="fw-semibold mb-1">Account Created</h6>
                <p class="mb-0 text-muted"><?= date('M d, Y', strtotime($profile['created_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
