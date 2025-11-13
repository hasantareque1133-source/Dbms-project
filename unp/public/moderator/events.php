<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Moderator');
$pdo = db();
$user = current_user();

$clubsStmt = $pdo->prepare('SELECT id, name FROM clubs WHERE moderator_id = :id ORDER BY name ASC');
$clubsStmt->execute(['id' => $user['id']]);
$clubs = $clubsStmt->fetchAll();
$clubIds = array_column($clubs, 'id');

if (is_post()) {
    $token = $_POST['csrf_token'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Invalid session token.');
        redirect('/moderator/events.php');
    }

    try {
        if ($action === 'create_event') {
            $title = trim($_POST['title'] ?? '');
            $clubId = (int)($_POST['club_id'] ?? 0) ?: null;
            $description = trim($_POST['description'] ?? '');
            $eventDate = $_POST['event_date'] ?? '';
            $venue = trim($_POST['venue'] ?? '');
            $capacity = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;

            if ($title === '' || $eventDate === '' || $venue === '') {
                throw new RuntimeException('Title, date, and venue are required.');
            }

            if ($clubId !== null && !in_array($clubId, $clubIds, true)) {
                throw new RuntimeException('You can only schedule events for your assigned clubs.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO events (title, club_id, description, event_date, venue, capacity, created_by)
                 VALUES (:title, :club_id, :description, :event_date, :venue, :capacity, :created_by)'
            );
            $stmt->execute([
                'title' => $title,
                'club_id' => $clubId,
                'description' => $description ?: null,
                'event_date' => $eventDate,
                'venue' => $venue,
                'capacity' => $capacity,
                'created_by' => $user['id'],
            ]);

            log_activity($user['id'], 'event_create', "Moderator created event {$title}");
            set_flash('success', 'Event created successfully.');
        } elseif (in_array($action, ['update_event', 'delete_event'], true)) {
            $eventId = (int)($_POST['event_id'] ?? 0);

            $eventStmt = $pdo->prepare(
                "SELECT e.*, c.moderator_id
                 FROM events e
                 LEFT JOIN clubs c ON c.id = e.club_id
                 WHERE e.id = :id"
            );
            $eventStmt->execute(['id' => $eventId]);
            $event = $eventStmt->fetch();

            if (!$event) {
                throw new RuntimeException('Event not found.');
            }

            if ($event['created_by'] !== $user['id'] && (int)($event['moderator_id'] ?? 0) !== $user['id']) {
                throw new RuntimeException('You are not authorized to modify this event.');
            }

            if ($action === 'update_event') {
                $title = trim($_POST['title'] ?? '');
                $clubId = (int)($_POST['club_id'] ?? 0) ?: null;
                $description = trim($_POST['description'] ?? '');
                $eventDate = $_POST['event_date'] ?? '';
                $venue = trim($_POST['venue'] ?? '');
                $capacity = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;

                if ($title === '' || $eventDate === '' || $venue === '') {
                    throw new RuntimeException('Title, date, and venue are required.');
                }

                if ($clubId !== null && !in_array($clubId, $clubIds, true)) {
                    throw new RuntimeException('You can only associate events with your clubs.');
                }

                $update = $pdo->prepare(
                    'UPDATE events
                     SET title = :title, club_id = :club_id, description = :description,
                         event_date = :event_date, venue = :venue, capacity = :capacity
                     WHERE id = :id'
                );
                $update->execute([
                    'title' => $title,
                    'club_id' => $clubId,
                    'description' => $description ?: null,
                    'event_date' => $eventDate,
                    'venue' => $venue,
                    'capacity' => $capacity,
                    'id' => $eventId,
                ]);

                log_activity($user['id'], 'event_update', "Moderator updated event #{$eventId}");
                set_flash('success', 'Event updated.');
            } elseif ($action === 'delete_event') {
                $delete = $pdo->prepare('DELETE FROM events WHERE id = :id');
                $delete->execute(['id' => $eventId]);
                log_activity($user['id'], 'event_delete', "Moderator deleted event #{$eventId}");
                set_flash('success', 'Event deleted.');
            }
        }
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }

    redirect('/moderator/events.php');
}

$eventsStmt = $pdo->prepare(
    "SELECT e.*, c.name AS club_name,
            (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registrations
     FROM events e
     LEFT JOIN clubs c ON c.id = e.club_id
     WHERE e.created_by = :id OR c.moderator_id = :id
     ORDER BY e.event_date DESC"
);
$eventsStmt->execute(['id' => $user['id']]);
$events = $eventsStmt->fetchAll();

$pageTitle = 'Manage Events';
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

<?php if (!$clubs): ?>
    <div class="alert alert-warning">You are not yet assigned to a club. Contact an administrator to gain access.</div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-header bg-gradient-moderator text-white">Create Event</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                <input type="hidden" name="action" value="create_event">
                <div class="col-md-4">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="title" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Club</label>
                    <select class="form-select" name="club_id">
                        <option value="">Independent</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= (int)$club['id']; ?>"><?= e($club['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date &amp; Time</label>
                    <input class="form-control" type="datetime-local" name="event_date" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capacity</label>
                    <input class="form-control" type="number" name="capacity" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Venue</label>
                    <input class="form-control" name="venue" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Publish Event</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-gradient-moderator text-white">Your Events</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Club</th>
                    <th>Registrations</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$events): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No events yet. Create your first event above.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($event['title']); ?></td>
                            <td><?= date('M d, Y g:i A', strtotime($event['event_date'])); ?></td>
                            <td><?= e($event['venue']); ?></td>
                            <td><?= e($event['club_name'] ?? 'Independent'); ?></td>
                            <td><span class="badge bg-info text-dark"><?= (int)$event['registrations']; ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#event-<?= (int)$event['id']; ?>">Manage</button>
                            </td>
                        </tr>
                        <?php
                        $participants = $pdo->prepare(
                            "SELECT er.registered_at, u.name, u.email, u.department
                             FROM event_registrations er
                             JOIN users u ON u.id = er.student_id
                             WHERE er.event_id = :event
                             ORDER BY er.registered_at ASC"
                        );
                        $participants->execute(['event' => $event['id']]);
                        $participants = $participants->fetchAll();
                        ?>
                        <tr class="collapse-row">
                            <td colspan="6" class="p-0">
                                <div class="collapse" id="event-<?= (int)$event['id']; ?>">
                                    <div class="border-top p-4 bg-light-subtle">
                                        <form method="post" class="row g-3">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                            <input type="hidden" name="action" value="update_event">
                                            <input type="hidden" name="event_id" value="<?= (int)$event['id']; ?>">
                                            <div class="col-md-4">
                                                <label class="form-label">Title</label>
                                                <input class="form-control" name="title" value="<?= e($event['title']); ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Club</label>
                                                <select class="form-select" name="club_id">
                                                    <option value="">Independent</option>
                                                    <?php foreach ($clubs as $club): ?>
                                                        <option value="<?= (int)$club['id']; ?>" <?= (int)$club['id'] === (int)$event['club_id'] ? 'selected' : ''; ?>>
                                                            <?= e($club['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date &amp; Time</label>
                                                <input class="form-control" type="datetime-local" name="event_date" value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Capacity</label>
                                                <input class="form-control" type="number" name="capacity" value="<?= e((string)$event['capacity']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Venue</label>
                                                <input class="form-control" name="venue" value="<?= e($event['venue']); ?>" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3"><?= e($event['description'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary" type="submit">Save Changes</button>
                                            </div>
                                        </form>
                                        <form method="post" class="mt-3" onsubmit="return confirm('Delete this event?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= (int)$event['id']; ?>">
                                            <button class="btn btn-outline-danger" type="submit">Delete Event</button>
                                        </form>
                                        <div class="mt-4">
                                            <h6 class="fw-semibold mb-2">Registered Participants</h6>
                                            <?php if (!$participants): ?>
                                                <p class="text-muted mb-0">No registrations yet.</p>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-striped mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Email</th>
                                                                <th>Department</th>
                                                                <th>Registered</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($participants as $participant): ?>
                                                                <tr>
                                                                    <td><?= e($participant['name']); ?></td>
                                                                    <td><?= e($participant['email']); ?></td>
                                                                    <td><?= e($participant['department'] ?? '-'); ?></td>
                                                                    <td><?= date('M d, Y H:i', strtotime($participant['registered_at'])); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
