<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('student');

$pdo = Database::getConnection();
$user = current_user();
$studentId = $user['id'];
$pageTitle = 'Events & Workshops';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($eventId <= 0) {
        $errors[] = 'Invalid event selection.';
    } else {
        $eventStmt = $pdo->prepare('
            SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations
            FROM events e
            WHERE e.id = :id
        ');
        $eventStmt->execute(['id' => $eventId]);
        $event = $eventStmt->fetch();

        if (!$event) {
            $errors[] = 'Event not found.';
        } else {
            if ($action === 'register') {
                $alreadyStmt = $pdo->prepare('SELECT COUNT(*) FROM event_registrations WHERE event_id = :event_id AND student_id = :student_id');
                $alreadyStmt->execute(['event_id' => $eventId, 'student_id' => $studentId]);

                if ($alreadyStmt->fetchColumn() > 0) {
                    $errors[] = 'You are already registered for this event.';
                } elseif (!empty($event['capacity']) && (int) $event['registrations'] >= (int) $event['capacity']) {
                    $errors[] = 'This event has reached maximum capacity.';
                } else {
                    try {
                        $insert = $pdo->prepare('INSERT INTO event_registrations (event_id, student_id) VALUES (:event_id, :student_id)');
                        $insert->execute(['event_id' => $eventId, 'student_id' => $studentId]);
                        set_flash('success', 'You\'re registered! See you at ' . $event['title'] . '.');
                        redirect('/public/student/events.php');
                    } catch (PDOException $e) {
                        $errors[] = 'Unable to register at this time. Try again later.';
                    }
                }
            } elseif ($action === 'unregister') {
                try {
                    $delete = $pdo->prepare('DELETE FROM event_registrations WHERE event_id = :event_id AND student_id = :student_id');
                    $delete->execute(['event_id' => $eventId, 'student_id' => $studentId]);
                    set_flash('success', 'You have been removed from ' . $event['title'] . '.');
                    redirect('/public/student/events.php');
                } catch (PDOException $e) {
                    $errors[] = 'Unable to unregister at this time.';
                }
            }
        }
    }
}

$eventsStmt = $pdo->prepare('
    SELECT
        e.*,
        c.name AS club_name,
        m.name AS moderator_name,
        (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations,
        EXISTS(
            SELECT 1 FROM event_registrations r WHERE r.event_id = e.id AND r.student_id = :student_id
        ) AS is_registered
    FROM events e
    LEFT JOIN clubs c ON c.id = e.club_id
    LEFT JOIN users m ON m.id = e.moderator_id
    WHERE e.start_at IS NULL OR e.start_at >= NOW()
    ORDER BY e.start_at ASC
');
$eventsStmt->execute(['student_id' => $studentId]);
$events = $eventsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Events & Workshops</h1>
        <p class="section-subtitle mb-0">Reserve your seat at the latest campus happenings hosted by clubs, moderators, and alumni.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/public/student/dashboard.php">Back to dashboard</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlentities($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($events)): ?>
    <div class="empty-state">
        <p class="fw-semibold mb-1">No upcoming events are scheduled.</p>
        <p class="small mb-0">Check back soon or reach out to club moderators for the next event drop.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($events as $event): ?>
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h2 class="h5 fw-semibold mb-1"><?= htmlentities($event['title']) ?></h2>
                                <div class="text-muted small">
                                    <?= htmlentities($event['club_name'] ?? 'Campus Programming') ?>
                                    <?php if (!empty($event['moderator_name'])): ?>
                                        · Hosted by <?= htmlentities($event['moderator_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($event['capacity'])): ?>
                                <span class="badge bg-warning-subtle text-warning"><?= (int) $event['registrations'] ?> / <?= (int) $event['capacity'] ?> seats</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary"><?= (int) $event['registrations'] ?> attending</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3 text-muted small">
                            <i class="bi bi-calendar-week me-1"></i><?= $event['start_at'] ? htmlentities((new DateTime($event['start_at']))->format('l, M d · h:i A')) : 'Schedule TBA' ?>
                            <div><i class="bi bi-geo-alt me-1"></i><?= htmlentities($event['location'] ?? 'Location TBA') ?></div>
                        </div>
                        <p class="text-muted flex-grow-1"><?= nl2br(htmlentities(substr($event['description'] ?? 'No description provided yet.', 0, 260))) ?><?= strlen((string) $event['description']) > 260 ? '…' : '' ?></p>
                        <div class="mt-3 d-flex gap-2">
                            <?php if ((int) $event['is_registered'] === 1): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                    <input type="hidden" name="action" value="unregister">
                                    <button type="submit" class="btn btn-outline-danger">Cancel Registration</button>
                                </form>
                            <?php else: ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                    <input type="hidden" name="action" value="register">
                                    <button type="submit" class="btn btn-primary" <?= (!empty($event['capacity']) && (int) $event['registrations'] >= (int) $event['capacity']) ? 'disabled' : '' ?>>
                                        <?= (!empty($event['capacity']) && (int) $event['registrations'] >= (int) $event['capacity']) ? 'Waitlist Full' : 'Register' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($event['description'])): ?>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#event-details-<?= (int) $event['id'] ?>">
                                    View Details
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                            <div class="collapse mt-3" id="event-details-<?= (int) $event['id'] ?>">
                                <div class="bg-light p-3 rounded small">
                                    <?= nl2br(htmlentities($event['description'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
