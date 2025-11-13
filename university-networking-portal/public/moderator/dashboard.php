<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('moderator');

$pdo = Database::getConnection();
$user = current_user();
$pageTitle = 'Moderator Hub';
$moderatorId = $user['id'];

$stats = [
    'events' => 0,
    'registrations' => 0,
];

$countEventsStmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE moderator_id = :moderator_id');
$countEventsStmt->execute(['moderator_id' => $moderatorId]);
$stats['events'] = (int) $countEventsStmt->fetchColumn();

$countRegistrationsStmt = $pdo->prepare('
    SELECT COUNT(*)
    FROM event_registrations r
    INNER JOIN events e ON e.id = r.event_id
    WHERE e.moderator_id = :moderator_id
');
$countRegistrationsStmt->execute(['moderator_id' => $moderatorId]);
$stats['registrations'] = (int) $countRegistrationsStmt->fetchColumn();

$upcomingEventsStmt = $pdo->prepare('
    SELECT e.id, e.title, e.start_at, e.location,
           (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS attendees,
           e.capacity
    FROM events e
    WHERE e.moderator_id = :moderator_id AND e.start_at >= NOW()
    ORDER BY e.start_at ASC
    LIMIT 5
');
$upcomingEventsStmt->execute(['moderator_id' => $moderatorId]);
$upcomingEvents = $upcomingEventsStmt->fetchAll();

$recentRegistrationsStmt = $pdo->prepare('
    SELECT e.title, u.name, u.department, r.created_at
    FROM event_registrations r
    INNER JOIN events e ON e.id = r.event_id
    INNER JOIN users u ON u.id = r.student_id
    WHERE e.moderator_id = :moderator_id
    ORDER BY r.created_at DESC
    LIMIT 6
');
$recentRegistrationsStmt->execute(['moderator_id' => $moderatorId]);
$recentRegistrations = $recentRegistrationsStmt->fetchAll();

$clubsStmt = $pdo->prepare('SELECT id, name, description FROM clubs WHERE moderator_id = :moderator_id');
$clubsStmt->execute(['moderator_id' => $moderatorId]);
$clubs = $clubsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-heading mb-3">Moderator Hub</h1>
<p class="section-subtitle mb-4">Plan unforgettable events, track registrations in real time, and keep your club community engaged.</p>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['events'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Events Managed</p>
                </div>
                <div class="icon bg-success">
                    <i class="bi bi-calendar2-event-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stats-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1"><?= $stats['registrations'] ?></h2>
                    <p class="text-muted mb-0 small text-uppercase">Total Registrations</p>
                </div>
                <div class="icon bg-primary">
                    <i class="bi bi-people-fill"></i>
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
                <a class="btn btn-sm btn-outline-primary" href="/public/moderator/events.php">Manage events</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php if (empty($upcomingEvents)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <p class="fw-semibold mb-1">No upcoming events yet.</p>
                                    <p class="small mb-0">Launch a workshop or meetup to energize your club members.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlentities($event['title']) ?></div>
                                    <div class="text-muted small"><?= htmlentities($event['location'] ?? 'Location TBA') ?></div>
                                </td>
                                <td>
                                    <?= $event['start_at'] ? htmlentities((new DateTime($event['start_at']))->format('M d, Y · h:i A')) : '—' ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success-subtle text-success"><?= (int) $event['attendees'] ?><?= $event['capacity'] ? ' / ' . (int) $event['capacity'] : '' ?> registered</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card table-card">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Newest Registrations</h2>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php if (empty($recentRegistrations)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <p class="fw-semibold mb-1">No registrations yet.</p>
                                    <p class="small mb-0">Share your event link or feature it on the student dashboard.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentRegistrations as $registration): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlentities($registration['name']) ?></div>
                                    <div class="small text-muted"><?= htmlentities($registration['department'] ?? '—') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary"><?= htmlentities($registration['title']) ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="text-muted small"><?= htmlentities((new DateTime($registration['created_at']))->format('M d, Y h:i A')) ?></span>
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

<?php if (!empty($clubs)): ?>
    <div class="card table-card mt-4">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0">Your Clubs</h2>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($clubs as $club): ?>
                <div class="list-group-item">
                    <div class="fw-semibold"><?= htmlentities($club['name']) ?></div>
                    <div class="text-muted small"><?= htmlentities($club['description'] ?? 'No description provided.') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
