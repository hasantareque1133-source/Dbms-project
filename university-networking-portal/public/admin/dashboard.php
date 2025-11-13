<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$pdo = Database::getConnection();
$pageTitle = 'Admin Control Center';

$stats = [
    'students' => 0,
    'moderators' => 0,
    'opportunities' => 0,
    'events' => 0,
    'alumni' => 0,
];

try {
    $stats['students'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['moderators'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'moderator'")->fetchColumn();
    $stats['opportunities'] = (int) $pdo->query('SELECT COUNT(*) FROM opportunities')->fetchColumn();
    $stats['events'] = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $stats['alumni'] = (int) $pdo->query('SELECT COUNT(*) FROM alumni')->fetchColumn();
} catch (PDOException $e) {
    set_flash('danger', 'Unable to load dashboard metrics.');
}

$upcomingEvents = $pdo->query('
    SELECT e.id, e.title, e.start_at, e.location, c.name AS club_name,
           (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS attendees
    FROM events e
    LEFT JOIN clubs c ON e.club_id = c.id
    WHERE e.start_at >= NOW()
    ORDER BY e.start_at ASC
    LIMIT 5
')->fetchAll();

$recentOpportunities = $pdo->query('
    SELECT o.id, o.title, o.type, o.organization, o.deadline, u.name AS author
    FROM opportunities o
    LEFT JOIN users u ON o.posted_by = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
')->fetchAll();

$newRegistrations = $pdo->query('
    SELECT name, email, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 6
')->fetchAll();

$activeModerators = $pdo->query('
    SELECT u.id, u.name, COUNT(e.id) AS event_count
    FROM users u
    LEFT JOIN events e ON e.moderator_id = u.id
    WHERE u.role = \'moderator\'
    GROUP BY u.id, u.name
    ORDER BY event_count DESC, u.name
    LIMIT 6
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-heading mb-3">Admin Control Center</h1>
<p class="section-subtitle mb-4">Monitor activity across the portal, highlight upcoming programming, and stay ahead of student engagement.</p>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['students'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Students</p>
                </div>
                <div class="icon bg-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['moderators'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Moderators</p>
                </div>
                <div class="icon bg-success">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['opportunities'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Opportunities</p>
                </div>
                <div class="icon bg-warning text-dark">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['events'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Events</p>
                </div>
                <div class="icon bg-danger">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card table-card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Upcoming Events</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/admin/events.php">Manage events</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php if (empty($upcomingEvents)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <p class="fw-semibold mb-1">No upcoming events scheduled.</p>
                                    <p class="small mb-0">Coordinate with moderators to plan the next wave of workshops or networking meetups.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlentities($event['title']) ?></div>
                                    <div class="small text-muted"><?= htmlentities($event['club_name'] ?? 'General Programming') ?></div>
                                </td>
                                <td>
                                    <?php if ($event['start_at']): ?>
                                        <?= htmlentities((new DateTime($event['start_at']))->format('M d, Y · h:i A')) ?>
                                    <?php endif; ?>
                                    <div class="text-muted small"><?= htmlentities($event['location'] ?? 'Location TBA') ?></div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-primary-subtle text-primary"><?= (int) $event['attendees'] ?> registered</span>
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
                <h2 class="h5 mb-0">Latest Opportunities</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/admin/opportunities.php">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php foreach ($recentOpportunities as $opportunity): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlentities($opportunity['title']) ?></div>
                                <div class="small text-muted"><?= htmlentities($opportunity['author'] ?? 'System') ?> · <?= htmlentities(ucfirst($opportunity['type'])) ?></div>
                            </td>
                            <td><?= htmlentities($opportunity['organization'] ?? '—') ?></td>
                            <td class="text-end">
                                <?php if ($opportunity['deadline']): ?>
                                    <span class="badge bg-warning-subtle text-warning">Deadline <?= htmlentities((new DateTime($opportunity['deadline']))->format('M d')) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOpportunities)): ?>
                        <tr><td colspan="3"><div class="empty-state"><p class="fw-semibold mb-1">No opportunities posted yet.</p><p class="small mb-0">Share internships, jobs, or research roles to engage students.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card table-card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Newest Registrations</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/admin/users.php">Manage users</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php foreach ($newRegistrations as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlentities($user['name']) ?></div>
                                        <div class="text-muted small"><?= htmlentities($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-dark-subtle text-dark text-uppercase"><?= htmlentities($user['role']) ?></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted small"><?= htmlentities((new DateTime($user['created_at']))->format('M d, Y')) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Top Moderators</h2>
                <a class="btn btn-sm btn-outline-primary" href="/public/admin/users.php">Assign roles</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php if (empty($activeModerators)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <p class="fw-semibold mb-1">No moderators assigned yet.</p>
                                    <p class="small mb-0">Elevate engaged students to moderator status to help run events.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeModerators as $moderator): ?>
                            <tr>
                                <td><?= htmlentities($moderator['name']) ?></td>
                                <td class="text-end"><span class="badge bg-success-subtle text-success"><?= (int) $moderator['event_count'] ?> events</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
