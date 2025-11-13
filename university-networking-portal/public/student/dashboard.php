<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('student');

$pdo = Database::getConnection();
$user = current_user();
$studentId = $user['id'];
$pageTitle = 'Student Dashboard';

$stats = [
    'registrations' => 0,
    'opportunities' => 0,
];

$countRegistrationsStmt = $pdo->prepare('SELECT COUNT(*) FROM event_registrations WHERE student_id = :student_id');
$countRegistrationsStmt->execute(['student_id' => $studentId]);
$stats['registrations'] = (int) $countRegistrationsStmt->fetchColumn();

$countOpportunitiesStmt = $pdo->prepare('SELECT COUNT(*) FROM opportunities WHERE deadline IS NULL OR deadline >= CURDATE()');
$countOpportunitiesStmt->execute();
$stats['opportunities'] = (int) $countOpportunitiesStmt->fetchColumn();

$myEventsStmt = $pdo->prepare('
    SELECT e.id, e.title, e.start_at, e.location, c.name AS club_name
    FROM event_registrations r
    INNER JOIN events e ON e.id = r.event_id
    LEFT JOIN clubs c ON c.id = e.club_id
    WHERE r.student_id = :student_id
    ORDER BY e.start_at ASC
    LIMIT 5
');
$myEventsStmt->execute(['student_id' => $studentId]);
$myEvents = $myEventsStmt->fetchAll();

$suggestedEvents = $pdo->query('
    SELECT e.id, e.title, e.start_at, e.location, c.name AS club_name,
           (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations,
           e.capacity
    FROM events e
    LEFT JOIN clubs c ON c.id = e.club_id
    WHERE e.start_at >= NOW()
    ORDER BY e.start_at ASC
    LIMIT 6
')->fetchAll();

$latestOpportunities = $pdo->query('
    SELECT id, title, type, organization, deadline
    FROM opportunities
    ORDER BY created_at DESC
    LIMIT 6
')->fetchAll();

$featuredAlumni = $pdo->query('
    SELECT name, profession, company, department, graduation_year, linkedin_url
    FROM alumni
    ORDER BY RAND()
    LIMIT 3
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-heading mb-3">Welcome back, <?= htmlentities($user['name']) ?>!</h1>
<p class="section-subtitle mb-4">Discover opportunities, register for upcoming events, and connect with alumni mentors.</p>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['registrations'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">My Event Registrations</p>
                </div>
                <div class="icon bg-warning text-dark">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['opportunities'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Active Opportunities</p>
                </div>
                <div class="icon bg-success">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card table-card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">My Upcoming Events</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/student/events.php">View all events</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php if (empty($myEvents)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <p class="fw-semibold mb-1">You haven’t registered for any events yet.</p>
                                    <p class="small mb-0">Explore the events hub to grab a seat before they fill up.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myEvents as $event): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlentities($event['title']) ?></div>
                                    <div class="text-muted small"><?= htmlentities($event['club_name'] ?? 'Campus Event') ?></div>
                                </td>
                                <td>
                                    <?= $event['start_at'] ? htmlentities((new DateTime($event['start_at']))->format('M d, Y · h:i A')) : '—' ?>
                                    <div class="text-muted small"><?= htmlentities($event['location'] ?? 'Location TBA') ?></div>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="/public/student/events.php">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Suggested Events</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/student/events.php">Register now</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php foreach ($suggestedEvents as $event): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlentities($event['title']) ?></div>
                                <div class="text-muted small"><?= htmlentities($event['club_name'] ?? 'Campus Event') ?></div>
                            </td>
                            <td>
                                <?= $event['start_at'] ? htmlentities((new DateTime($event['start_at']))->format('M d, Y · h:i A')) : '—' ?>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-primary-subtle text-primary">
                                    <?= (int) $event['registrations'] ?><?= $event['capacity'] ? ' / ' . (int) $event['capacity'] : '' ?> attending
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($suggestedEvents)): ?>
                        <tr><td colspan="3"><div class="empty-state"><p class="fw-semibold mb-1">No events scheduled.</p><p class="small mb-0">Check back soon for new workshops and mixers.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card table-card mb-4">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Latest Opportunities</h2>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($latestOpportunities as $opportunity): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="/public/student/opportunities.php#opportunity-<?= (int) $opportunity['id'] ?>">
                        <div>
                            <div class="fw-semibold"><?= htmlentities($opportunity['title']) ?></div>
                            <div class="text-muted small"><?= htmlentities($opportunity['organization'] ?? 'Organization Confidential') ?></div>
                        </div>
                        <span class="badge bg-dark-subtle text-dark text-uppercase"><?= htmlentities($opportunity['type']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($latestOpportunities)): ?>
                    <div class="list-group-item">
                        <div class="empty-state">
                            <p class="fw-semibold mb-1">No opportunities posted yet.</p>
                            <p class="small mb-0">Check again soon — admins are curating new openings.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Featured Alumni Mentors</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/student/alumni.php">Explore directory</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($featuredAlumni as $alumnus): ?>
                    <div class="list-group-item">
                        <div class="fw-semibold"><?= htmlentities($alumnus['name']) ?></div>
                        <div class="text-muted small"><?= htmlentities($alumnus['profession'] ?? 'Professional') ?> @ <?= htmlentities($alumnus['company'] ?? '—') ?></div>
                        <div class="small"><?= htmlentities($alumnus['department'] ?? 'Department') ?> · Class of <?= htmlentities($alumnus['graduation_year'] ?? '—') ?></div>
                        <?php if (!empty($alumnus['linkedin_url'])): ?>
                            <a class="small fw-semibold" href="<?= htmlentities($alumnus['linkedin_url']) ?>" target="_blank" rel="noopener">LinkedIn Profile</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($featuredAlumni)): ?>
                    <div class="list-group-item">
                        <div class="empty-state">
                            <p class="fw-semibold mb-1">No alumni profiles available.</p>
                            <p class="small mb-0">Admins are updating the directory — check back shortly.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
