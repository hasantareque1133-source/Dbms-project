<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$pdo = Database::getConnection();
$pageTitle = 'Event Oversight | Admin';
$errors = [];
$editingEvent = null;
$selectedEvent = null;
$participants = [];
$clubs = $pdo->query('SELECT id, name FROM clubs ORDER BY name')->fetchAll();
$moderators = $pdo->query("SELECT id, name FROM users WHERE role = 'moderator' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = sanitize($_POST['title'] ?? '');
        $clubId = (int) ($_POST['club_id'] ?? 0);
        $moderatorId = (int) ($_POST['moderator_id'] ?? 0);
        $location = sanitize($_POST['location'] ?? '');
        $startAt = sanitize($_POST['start_at'] ?? '');
        $capacity = (int) ($_POST['capacity'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            $errors[] = 'Event title is required.';
        }

        if ($startAt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $startAt)) {
            $errors[] = 'Please provide a valid start date and time.';
        }

        if ($capacity < 0) {
            $errors[] = 'Capacity cannot be negative.';
        }

        $startDateTime = null;
        if ($startAt !== '') {
            try {
                $startDateTime = new DateTime($startAt);
            } catch (Exception $e) {
                $errors[] = 'Invalid start date format.';
            }
        }

        if ($action === 'update' && (int) ($_POST['id'] ?? 0) <= 0) {
            $errors[] = 'Invalid event selected for update.';
        }

        if (empty($errors)) {
            try {
                if ($action === 'create') {
                    $insert = $pdo->prepare('INSERT INTO events (title, club_id, moderator_id, location, start_at, capacity, description, created_by) VALUES (:title, :club_id, :moderator_id, :location, :start_at, :capacity, :description, :created_by)');
                    $insert->execute([
                        'title' => $title,
                        'club_id' => $clubId > 0 ? $clubId : null,
                        'moderator_id' => $moderatorId > 0 ? $moderatorId : null,
                        'location' => $location ?: null,
                        'start_at' => $startDateTime?->format('Y-m-d H:i:s'),
                        'capacity' => $capacity > 0 ? $capacity : null,
                        'description' => $description ?: null,
                        'created_by' => current_user()['id'],
                    ]);
                    set_flash('success', 'Event created successfully.');
                } else {
                    $update = $pdo->prepare('UPDATE events SET title = :title, club_id = :club_id, moderator_id = :moderator_id, location = :location, start_at = :start_at, capacity = :capacity, description = :description WHERE id = :id');
                    $update->execute([
                        'title' => $title,
                        'club_id' => $clubId > 0 ? $clubId : null,
                        'moderator_id' => $moderatorId > 0 ? $moderatorId : null,
                        'location' => $location ?: null,
                        'start_at' => $startDateTime?->format('Y-m-d H:i:s'),
                        'capacity' => $capacity > 0 ? $capacity : null,
                        'description' => $description ?: null,
                        'id' => (int) $_POST['id'],
                    ]);
                    set_flash('success', 'Event updated successfully.');
                }
                redirect('/public/admin/events.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to save event details.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid event selected.';
        } else {
            try {
                $delete = $pdo->prepare('DELETE FROM events WHERE id = :id');
                $delete->execute(['id' => $id]);
                set_flash('success', 'Event deleted successfully.');
                redirect('/public/admin/events.php');
            } catch (PDOException $e) {
                $errors[] = 'Unable to delete event. Remove registrations first if necessary.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $editingEvent = $stmt->fetch();
        if (!$editingEvent) {
            set_flash('warning', 'Selected event not found.');
            redirect('/public/admin/events.php');
        }
    }
}

if (isset($_GET['view'])) {
    $eventId = (int) $_GET['view'];
    if ($eventId > 0) {
        $stmt = $pdo->prepare('SELECT e.*, c.name AS club_name FROM events e LEFT JOIN clubs c ON e.club_id = c.id WHERE e.id = :id');
        $stmt->execute(['id' => $eventId]);
        $selectedEvent = $stmt->fetch();

        if ($selectedEvent) {
            $participantStmt = $pdo->prepare('
                SELECT r.created_at, u.name, u.email, u.institutional_id, u.department
                FROM event_registrations r
                INNER JOIN users u ON r.student_id = u.id
                WHERE r.event_id = :event_id
                ORDER BY r.created_at DESC
            ');
            $participantStmt->execute(['event_id' => $eventId]);
            $participants = $participantStmt->fetchAll();
        } else {
            set_flash('warning', 'Event not found.');
            redirect('/public/admin/events.php');
        }
    }
}

$events = $pdo->query('
    SELECT e.*, c.name AS club_name, u.name AS creator,
           (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations,
           m.name AS moderator_name
    FROM events e
    LEFT JOIN clubs c ON e.club_id = c.id
    LEFT JOIN users u ON e.created_by = u.id
    LEFT JOIN users m ON e.moderator_id = m.id
    ORDER BY e.start_at DESC
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Workshops & Events</h1>
        <p class="section-subtitle mb-0">Keep track of campus programming, assign moderators, and monitor attendance in real time.</p>
    </div>
    <a href="/public/admin/events.php" class="btn btn-outline-secondary">Reset</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlentities($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card table-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Scheduled Events</h2>
                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($events) ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Club</th>
                        <th>Moderator</th>
                        <th>When</th>
                        <th>Registrations</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlentities($event['title']) ?></div>
                                <div class="small text-muted">Created by <?= htmlentities($event['creator'] ?? 'System') ?></div>
                            </td>
                            <td><?= htmlentities($event['club_name'] ?? 'General') ?></td>
                            <td><?= htmlentities($event['moderator_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <?php if ($event['start_at']): ?>
                                    <?= htmlentities((new DateTime($event['start_at']))->format('M d, Y · h:i A')) ?>
                                    <div class="text-muted small"><?= htmlentities($event['location'] ?? 'Location TBA') ?></div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int) $event['registrations'] ?><?= $event['capacity'] ? ' / ' . (int) $event['capacity'] : '' ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/public/admin/events.php?edit=<?= (int) $event['id'] ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-secondary" href="/public/admin/events.php?view=<?= (int) $event['id'] ?>">Participants</a>
                                <form method="post" action="/public/admin/events.php" class="d-inline" onsubmit="return confirm('Delete this event? Registrations will also be removed.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0"><?= $editingEvent ? 'Update Event' : 'Create Event' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="action" value="<?= $editingEvent ? 'update' : 'create' ?>">
                    <?php if ($editingEvent): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingEvent['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Event Title</label>
                        <input type="text" class="form-control" name="title" required value="<?= htmlentities($editingEvent['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Club</label>
                        <select class="form-select" name="club_id">
                            <option value="">General Programming</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int) $club['id'] ?>" <?= isset($editingEvent['club_id']) && (int) $editingEvent['club_id'] === (int) $club['id'] ? 'selected' : '' ?>>
                                    <?= htmlentities($club['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Moderator</label>
                        <select class="form-select" name="moderator_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($moderators as $moderator): ?>
                                <option value="<?= (int) $moderator['id'] ?>" <?= isset($editingEvent['moderator_id']) && (int) $editingEvent['moderator_id'] === (int) $moderator['id'] ? 'selected' : '' ?>>
                                    <?= htmlentities($moderator['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only accounts with the moderator role are listed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date &amp; Time</label>
                        <input type="datetime-local" class="form-control" name="start_at" required value="<?= isset($editingEvent['start_at']) && $editingEvent['start_at'] ? (new DateTime($editingEvent['start_at']))->format('Y-m-d\TH:i') : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" value="<?= htmlentities($editingEvent['location'] ?? '') ?>" placeholder="Innovation Hall, Room 203">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" name="capacity" min="0" value="<?= htmlentities($editingEvent['capacity'] ?? '') ?>" placeholder="Leave blank for unlimited">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="4" name="description" placeholder="Overview, speakers, and outcomes"><?= htmlentities($editingEvent['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $editingEvent ? 'Update Event' : 'Create Event' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedEvent): ?>
    <div class="card mt-4 table-card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 mb-0"><?= htmlentities($selectedEvent['title']) ?> · Participants</h2>
                <p class="text-muted small mb-0"><?= htmlentities($selectedEvent['club_name'] ?? 'General Programming') ?> · <?= htmlentities($selectedEvent['location'] ?? 'Location TBA') ?></p>
            </div>
            <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($participants) ?> registrations</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Registered On</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($participants)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <p class="fw-semibold mb-1">No registrations yet.</p>
                                <p class="small mb-0">Share the event with students or feature it on the dashboard to drive sign-ups.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlentities($participant['name']) ?></td>
                            <td><?= htmlentities($participant['email']) ?></td>
                            <td><?= htmlentities($participant['department'] ?? '—') ?></td>
                            <td><?= htmlentities((new DateTime($participant['created_at']))->format('M d, Y h:i A')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
