<?php

declare(strict_types=1);

$pageTitle = 'Welcome';
require_once __DIR__ . '/../templates/header.php';
$user = current_user();
?>

<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-5 fw-bold text-gradient mb-4">Connect. Collaborate. Grow.</h1>
                <p class="lead mb-4">
                    The University Networking Portal unifies internships, research projects, events, and alumni mentors in a single, secure place. Designed for campus-wide collaboration and meaningful opportunities.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if ($user): ?>
                        <a class="btn btn-primary btn-lg" href="<?= BASE_URL; ?>/dashboard.php">Go to Dashboard</a>
                    <?php else: ?>
                        <a class="btn btn-primary btn-lg" href="<?= BASE_URL; ?>/login.php">Log In</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-light btn-lg" href="#features">Explore Features</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-illustration shadow-lg">
                    <img src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=900&q=80" class="img-fluid rounded" alt="Students collaborating">
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="feature-icon bg-admin mb-3">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h3>Admin Control</h3>
                    <p>Manage users, opportunities, alumni, and run detailed activity audits with role-based security.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="feature-icon bg-student mb-3">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <h3>Student Growth</h3>
                    <p>Browse curated internships, research roles, and upcoming events tailored to your department and year.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="feature-icon bg-moderator mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3>Moderator Tools</h3>
                    <p>Empower student leaders to manage club events, monitor registrations, and keep the campus buzzing.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-dark text-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="fw-bold mb-3">Ready to build a thriving campus community?</h2>
                <p class="mb-0">Log in to start connecting with opportunities, events, mentors, and future collaborators.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL; ?>/login.php">Access UNP</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
