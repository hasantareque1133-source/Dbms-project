<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

ensure_session();

if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token($token)) {
        $errors[] = 'Invalid or expired session token. Please try again.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid institutional email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors) && !authenticate($email, $password)) {
        $errors[] = 'Incorrect email or password.';
    }

    if (empty($errors)) {
        redirect('/dashboard.php');
    }
}

$pageTitle = 'Log In';
require_once __DIR__ . '/../templates/header.php';
$csrfToken = csrf_token();
?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card glass-panel">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 fw-bold text-center mb-4">Welcome back to UNP</h1>
                <p class="text-center text-muted mb-4">Log in with your institutional credentials to access tailored opportunities.</p>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Institutional Email</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group input-group-lg">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                        <a href="<?= BASE_URL; ?>/index.php" class="btn btn-outline-light btn-lg">Back to Home</a>
                    </div>
                </form>
                <p class="text-center text-muted small mt-4 mb-0">Need access? Contact your administrator for assistance.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
