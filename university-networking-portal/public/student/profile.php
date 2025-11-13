<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('student');

$pdo = Database::getConnection();
$user = current_user();
$studentId = $user['id'];
$pageTitle = 'My Profile';
$errors = [];
$successMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['form'] ?? '') === 'profile') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $graduationYear = sanitize($_POST['graduation_year'] ?? '');

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if ($graduationYear !== '' && !preg_match('/^\d{4}$/', $graduationYear)) {
            $errors[] = 'Graduation year must be a four-digit number.';
        }

        if (empty($errors)) {
            try {
                $duplicateStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (email = :email OR institutional_id = :institutional_id) AND id != :id');
                $duplicateStmt->execute([
                    'email' => $email,
                    'institutional_id' => $user['institutional_id'],
                    'id' => $studentId,
                ]);

                if ($duplicateStmt->fetchColumn() > 0) {
                    $errors[] = 'Another account already uses that email address.';
                } else {
                    $update = $pdo->prepare('UPDATE users SET name = :name, email = :email, department = :department, graduation_year = :graduation_year WHERE id = :id');
                    $update->execute([
                        'name' => $name,
                        'email' => $email,
                        'department' => $department ?: null,
                        'graduation_year' => $graduationYear ?: null,
                        'id' => $studentId,
                    ]);

                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['department'] = $department;
                    $_SESSION['user']['graduation_year'] = $graduationYear;

                    $successMessages[] = 'Profile updated successfully.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Unable to update profile at this time.';
            }
        }
    } elseif (($_POST['form'] ?? '') === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'All password fields are required.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password confirmation does not match.';
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
            $stmt->execute(['id' => $studentId]);
            $record = $stmt->fetch();

            if (!$record || !password_verify($currentPassword, $record['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                try {
                    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                    $update->execute([
                        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'id' => $studentId,
                    ]);
                    $successMessages[] = 'Password updated successfully.';
                } catch (PDOException $e) {
                    $errors[] = 'Unable to update password right now.';
                }
            }
        }
    }
}

$profileStmt = $pdo->prepare('SELECT name, email, institutional_id, department, graduation_year FROM users WHERE id = :id');
$profileStmt->execute(['id' => $studentId]);
$profile = $profileStmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-heading mb-3">My Profile</h1>
<p class="section-subtitle mb-4">Keep your contact details and academic information up to date so moderators and alumni can reach out effectively.</p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlentities($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php foreach ($successMessages as $message): ?>
    <div class="alert alert-success">
        <?= htmlentities($message) ?>
    </div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Contact & Academic Details</h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="form" value="profile">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlentities($profile['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institutional Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlentities($profile['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institutional ID</label>
                        <input type="text" class="form-control" value="<?= htmlentities($profile['institutional_id'] ?? '') ?>" disabled>
                        <div class="form-text">Contact your administrator if your institutional ID is incorrect.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" value="<?= htmlentities($profile['department'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Graduation Year</label>
                        <input type="number" class="form-control" name="graduation_year" min="2024" max="2100" value="<?= htmlentities($profile['graduation_year'] ?? '') ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Security</h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="form" value="password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <div class="form-text">Use at least 8 characters with a blend of letters, numbers, and symbols.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
