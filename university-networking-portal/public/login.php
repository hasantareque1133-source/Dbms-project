<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

start_session();

if (is_logged_in()) {
    $user = current_user();
    redirect('/public/' . $user['role'] . '/dashboard.php');
}

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Institutional ID or email and password are required.';
    } else {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT id, name, email, institutional_id, password_hash, role, department, graduation_year FROM users WHERE email = :identifier OR institutional_id = :identifier LIMIT 1');
            $stmt->execute(['identifier' => $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'institutional_id' => $user['institutional_id'],
                    'role' => $user['role'],
                    'department' => $user['department'],
                    'graduation_year' => $user['graduation_year'],
                ];

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect('/public/' . $user['role'] . '/dashboard.php');
            } else {
                $errors[] = 'Invalid credentials. Please try again.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to process login right now. Please try again later.';
        }
    }
}

$pageTitle = 'Sign In | University Networking Portal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card shadow-lg border-0 login-card">
            <div class="card-body p-5">
                <h1 class="h3 mb-3 fw-bold text-center text-gradient">Welcome to UNP</h1>
                <p class="text-muted text-center mb-4">Sign in to access personalized opportunities, events, and alumni connections.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlentities($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Institutional ID or Email</label>
                        <input type="text" class="form-control form-control-lg" id="identifier" name="identifier" value="<?= htmlentities($identifier) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                    </div>
                    <div class="text-center">
                        <span class="text-muted small">Need an account?</span>
                        <a href="/public/register.php" class="small fw-semibold">Student Registration</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
