<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

start_session();

if (is_logged_in()) {
    $user = current_user();
    redirect('/public/' . $user['role'] . '/dashboard.php');
}

$pageTitle = 'Student Registration | University Networking Portal';
$form = [
    'name' => '',
    'email' => '',
    'institutional_id' => '',
    'department' => '',
    'graduation_year' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = sanitize($_POST['name'] ?? '');
    $form['email'] = sanitize($_POST['email'] ?? '');
    $form['institutional_id'] = sanitize($_POST['institutional_id'] ?? '');
    $form['department'] = sanitize($_POST['department'] ?? '');
    $form['graduation_year'] = sanitize($_POST['graduation_year'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($form['name'] === '') {
        $errors[] = 'Full name is required.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid institutional email is required.';
    }

    if ($form['institutional_id'] === '') {
        $errors[] = 'Institutional ID is required.';
    }

    if ($password === '' || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email OR institutional_id = :institutional_id');
            $stmt->execute([
                'email' => $form['email'],
                'institutional_id' => $form['institutional_id'],
            ]);

            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'An account already exists with the provided email or institutional ID.';
            } else {
                $insert = $pdo->prepare('INSERT INTO users (name, email, institutional_id, password_hash, role, department, graduation_year) VALUES (:name, :email, :institutional_id, :password_hash, :role, :department, :graduation_year)');
                $insert->execute([
                    'name' => $form['name'],
                    'email' => $form['email'],
                    'institutional_id' => $form['institutional_id'],
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'student',
                    'department' => $form['department'] ?: null,
                    'graduation_year' => $form['graduation_year'] ?: null,
                ]);

                set_flash('success', 'Registration successful! Please sign in to continue.');
                redirect('/public/login.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed due to a server error. Please try again later.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-lg border-0">
            <div class="card-body p-5">
                <h1 class="h3 mb-4 fw-bold text-center text-gradient">Create Your Student Account</h1>
                <p class="text-muted text-center mb-4">Gain access to curated opportunities, events, and alumni mentors tailored to your academic journey.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlentities($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" value="<?= htmlentities($form['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="institutional_id" class="form-label">Institutional ID</label>
                            <input type="text" class="form-control form-control-lg" id="institutional_id" name="institutional_id" value="<?= htmlentities($form['institutional_id']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Institutional Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= htmlentities($form['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control form-control-lg" id="department" name="department" value="<?= htmlentities($form['department']) ?>" placeholder="e.g. Computer Science">
                        </div>
                        <div class="col-md-6">
                            <label for="graduation_year" class="form-label">Expected Graduation Year</label>
                            <input type="number" class="form-control form-control-lg" id="graduation_year" name="graduation_year" value="<?= htmlentities($form['graduation_year']) ?>" min="2024" max="2100">
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required minlength="8">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">Register Now</button>
                    </div>
                    <div class="text-center mt-3">
                        <span class="text-muted small">Already registered?</span>
                        <a href="/public/login.php" class="small fw-semibold">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
