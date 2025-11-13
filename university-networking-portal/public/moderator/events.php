<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('moderator');

$pdo = Database::getConnection();
$user = current_user();
$moderatorId = $user['id'];
$pageTitle = 'Manage Events | Moderator';
$errors = [];
$editingEvent = null;
$selectedEvent = null;
$participants = [];

$clubsStmt = $pdo->prepare('SELECT id, name FROM clubs WHERE moderator_id = :moderator_id ORDER BY name');
$clubsStmt->execute(['moderator_id' => $moderatorId]);
$clubs = $clubsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = sanitize($_POST['title'] ?? '');
        $clubId = (int) ($_POST['club_id'] ?? 0);
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
                $errors[] = 'Invalid date format.';
            }
        }

        if ($clubId !== 0) {
            $clubAllowed = array_filter($clubs, static fn($club) => (int) $club['id'] === $clubId);
            if (empty($clubAllowed)) {
                $errors[] = 'You are not assigned to the selected club.';
            }
        }

        $eventId = (int) ($_POST['id'] ?? 0);
        if ($action === 'update') {
            if ($eventId <= 0) {
                $errors[] = 'Invalid event selected.';
            } else {
                $ownershipStmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE id = :id AND moderator_id = :moderator_id');
                $ownershipStmt->execute(['id' => $eventId, 'moderator_id' => $moderatorId]);
                if ($ownershipStmt->fetchColumn() == 0) {
                    $errors[] = 'You can only update events you manage.';
                }
            }
        }

        if (empty($errors)) {
            try {
                if ($action === 'create') {
                    $insert = $pdo->prepare('INSERT INTO events (title, club_id, moderator_id, location, start_at, capacity, description, created_by) VALUES (:title, :club_id, :moderator_id, :location, :start_at, :capacity, :description, :created_by)');
                    $insert->execute([
                        'title' => $title,
                        'club_id' => $clubId > 0 ? $clubId : null,
                        'moderator_id' => $moderatorId,
                        'location' => $location ?: null,
                        'start_at' => $startDateTime?->format('Y-m-d H:i:s'),
                        'capacity' => $capacity > 0 ? $capacity : null,
                        'description' => $description ?: null,
                        'created_by' => $moderatorId,
                    ]);
                    set_flash('success', 'Event created successfully.');
                } else {
                    $update = $pdo->prepare('UPDATE events SET title = :title, club_id = :club_id, location = :location, start_at = :start_at, capacity = :capacity, description = :description WHERE id = :id AND moderator_id = :moderator_id');
                    $update->execute([
                        'title' => $title,
                        'club_id' => $clubId > 0 ? $clubId : null,
                        'location' => $location ?: null,
                        'start_at' => $startDateTime?->format('Y-m-d H:i:s'),
                        'capacity' => $capacity > 0 ? $capacity : null,
                        'description' => $description ?: null,
                        'id' => $eventId,
                        'moderator_id' => $moderatorId,
                    ]);
                    set_flash('success', 'Event updated successfully.');
                }
                redirect('/public/moderator/events.php');
            } catch (PDOException $e) {
                $errors[] = 'Unable to save event. Please try again.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid event selected.';
        } else {
            $ownershipStmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE id = :id AND moderator_id = :moderator_id');
            $ownershipStmt->execute(['id' => $id, 'moderator_id' => $moderatorId]);
            if ($ownershipStmt->fetchColumn() == 0) {
                $errors[] = 'You can only remove events you manage.';
            } else {
                try {
                    $delete = $pdo->prepare('DELETE FROM events WHERE id = :id AND moderator_id = :moderator_id');
                    $delete->execute(['id' => $id, 'moderator_id' => $moderatorId]);
                    set_flash('success', 'Event deleted successfully.');
                    redirect('/public/moderator/events.php');
                } catch (PDOException $e) {
                    $errors[] = 'Unable to delete event. Remove registrations first if necessary.';
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id AND moderator_id = :moderator_id');
        $stmt->execute(['id' => $id, 'moderator_id' => $moderatorId]);
        $editingEvent = $stmt->fetch();
        if (!$editingEvent) {
            set_flash('warning', 'Event not found or not assigned to you.');
            redirect('/public/moderator/events.php');
        }
    }
}

if (isset($_GET['view'])) {
    $id = (int) $_GET['view'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id AND moderator_id = :moderator_id');
        $stmt->execute(['id' => $id, 'moderator_id' => $moderatorId]);
        $selectedEvent = $stmt->fetch();
        if ($selectedEvent) {
            $participantsStmt = $pdo->prepare('
                SELECT u.name, u.email, u.department, r.created_at
                FROM event_registrations r
                INNER JOIN users u ON u.id = r.student_id
                WHERE r.event_id = :event_id
                ORDER BY r.created_at DESC
            ');
            $participantsStmt->execute(['event_id' => $id]);
            $participants = $participantsStmt->fetchAll();
        } else {
            set_flash('warning', 'Event not found or not assigned to you.');
            redirect('/public/moderator/events.php');
        }
    }
}

$eventsStmt = $pdo->prepare('
    SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations
    FROM events e
    WHERE e.moderator_id = :moderator_id
    ORDER BY e.start_at DESC
');
$eventsStmt->execute(['moderator_id' => $moderatorId]);
$events = $eventsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-heading">Manage Your Events</h1>
        <p class="section-subtitle mb-0">Create workshops, edit details, and stay on top of student participation.</p>
    </div>
    <a href="/public/moderator/events.php" class="btn btn-outline-secondary">Reset</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlentities($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Your Schedule</h2>
                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($events) ?> events</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Start</th>
                        <th>Registrations</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlentities($event['title']) ?></div>
                                <div class="small text-muted"><?= htmlentities($event['location'] ?? 'Location TBA') ?></div>
                            </td>
                            <td><?= $event['start_at'] ? htmlentities((new DateTime($event['start_at']))->format('M d, Y · h:i A')) : '—' ?></td>
                            <td><?= (int) $event['registrations'] ?><?= $event['capacity'] ? ' / ' . (int) $event['capacity'] : '' ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/public/moderator/events.php?edit=<?= (int) $event['id'] ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-secondary" href="/public/moderator/events.php?view=<?= (int) $event['id'] ?>">Participants</a>
                                <form method="post" class="d-inline" action="/public/moderator/events.php" onsubmit="return confirm('Delete this event? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <p class="fw-semibold mb-1">No events yet.</p>
                                    <p class="small mb-0">Use the form on the right to launch your first club experience.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
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
                        <label class="form-label">Title</label>
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
                        <div class="form-text">Only clubs you moderate are available.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date &amp; Time</label>
                        <input type="datetime-local" class="form-control" name="start_at" required value="<?= isset($editingEvent['start_at']) && $editingEvent['start_at'] ? (new DateTime($editingEvent['start_at']))->format('Y-m-d\TH:i') : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" value="<?= htmlentities($editingEvent['location'] ?? '') ?>" placeholder="Innovation Hub, Lab 2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" name="capacity" min="0" value="<?= htmlentities($editingEvent['capacity'] ?? '') ?>" placeholder="Leave blank for unlimited">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="4" name="description" placeholder="Highlight goals, speakers, or requirements"><?= htmlentities($editingEvent['description'] ?? '') ?></textarea>
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
    <div class="card table-card mt-4">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0"><?= htmlentities($selectedEvent['title']) ?> · Participants</h2>
            <span class="badge bg-dark-subtle text-dark fw-semibold"><?= count($participants) ?> registrations</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Registered</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($participants)): ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <p class="fw-semibold mb-1">No participants yet.</p>
                                <p class="small mb-0">Share the event link or remind students via email.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td><?= htmlentities($participant['name']) ?></td>
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
