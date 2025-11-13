<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Admin');
$pdo = db();
$currentUser = current_user();

$moderators = $pdo->query(
    "SELECT u.id, u.name
     FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE r.name = 'Moderator'
     ORDER BY u.name ASC"
)->fetchAll();

if (is_post()) {
    $token = $_POST['csrf_token'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Invalid or expired security token.');
        redirect('/admin/events.php');
    }

    try {
        if ($action === 'create_club') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $moderatorId = (int)($_POST['moderator_id'] ?? 0) ?: null;

            if ($name === '') {
                throw new RuntimeException('Club name is required.');
            }

            $stmt = $pdo->prepare('INSERT INTO clubs (name, description, moderator_id) VALUES (:name, :description, :moderator_id)');
            $stmt->execute([
                'name' => $name,
                'description' => $description ?: null,
                'moderator_id' => $moderatorId,
            ]);

            log_activity($currentUser['id'], 'club_create', "Created club {$name}");
            set_flash('success', 'Club created successfully.');
        } elseif ($action === 'update_club') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $moderatorId = (int)($_POST['moderator_id'] ?? 0) ?: null;

            if ($id <= 0 || $name === '') {
                throw new RuntimeException('Invalid club data supplied.');
            }

            $stmt = $pdo->prepare('UPDATE clubs SET name = :name, description = :description, moderator_id = :moderator_id WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'description' => $description ?: null,
                'moderator_id' => $moderatorId,
                'id' => $id,
            ]);

            log_activity($currentUser['id'], 'club_update', "Updated club #{$id}");
            set_flash('success', 'Club details updated.');
        } elseif ($action === 'delete_club') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM clubs WHERE id = :id');
            $stmt->execute(['id' => $id]);
            log_activity($currentUser['id'], 'club_delete', "Deleted club #{$id}");
            set_flash('success', 'Club removed.');
        } elseif ($action === 'create_event') {
            $title = trim($_POST['title'] ?? '');
            $clubId = (int)($_POST['club_id'] ?? 0) ?: null;
            $description = trim($_POST['description'] ?? '');
            $eventDate = $_POST['event_date'] ?? '';
            $venue = trim($_POST['venue'] ?? '');
            $capacity = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;

            if ($title === '' || $eventDate === '' || $venue === '') {
                throw new RuntimeException('Title, date/time, and venue are required.');
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
                'created_by' => $currentUser['id'],
            ]);

            log_activity($currentUser['id'], 'event_create', "Created event {$title}");
            set_flash('success', 'Event scheduled.');
        } elseif ($action === 'update_event') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $clubId = (int)($_POST['club_id'] ?? 0) ?: null;
            $description = trim($_POST['description'] ?? '');
            $eventDate = $_POST['event_date'] ?? '';
            $venue = trim($_POST['venue'] ?? '');
            $capacity = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;

            if ($id <= 0 || $title === '') {
                throw new RuntimeException('Invalid event details.');
            }

            $stmt = $pdo->prepare(
                'UPDATE events
                 SET title = :title, club_id = :club_id, description = :description, event_date = :event_date,
                     venue = :venue, capacity = :capacity
                 WHERE id = :id'
            );
            $stmt->execute([
                'title' => $title,
                'club_id' => $clubId,
                'description' => $description ?: null,
                'event_date' => $eventDate,
                'venue' => $venue,
                'capacity' => $capacity,
                'id' => $id,
            ]);

            log_activity($currentUser['id'], 'event_update', "Updated event #{$id}");
            set_flash('success', 'Event updated.');
        } elseif ($action === 'delete_event') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
            $stmt->execute(['id' => $id]);
            log_activity($currentUser['id'], 'event_delete', "Deleted event #{$id}");
            set_flash('success', 'Event deleted.');
        }
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }

    redirect('/admin/events.php');
}

$clubs = $pdo->query(
    "SELECT c.*, u.name AS moderator_name
     FROM clubs c
     LEFT JOIN users u ON u.id = c.moderator_id
     ORDER BY c.created_at DESC"
)->fetchAll();

$events = $pdo->query(
    "SELECT e.*, c.name AS club_name, u.name AS creator_name,
            (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registrations
     FROM events e
     LEFT JOIN clubs c ON c.id = e.club_id
     JOIN users u ON u.id = e.created_by
     ORDER BY e.event_date DESC"
)->fetchAll();

$pageTitle = 'Events & Clubs';
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

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-gradient-admin text-white">Create Club</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                    <input type="hidden" name="action" value="create_club">
                    <div class="mb-3">
                        <label class="form-label">Club Name</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Moderator</label>
                        <select class="form-select" name="moderator_id">
                            <option value="">-- None --</option>
                            <?php foreach ($moderators as $moderator): ?>
                                <option value="<?= (int)$moderator['id']; ?>"><?= e($moderator['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit">Create Club</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-gradient-admin text-white">Clubs Directory</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Moderator</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$clubs): ?>
                            <tr><td colspan="4" class="py-4 text-center text-muted">No clubs defined yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clubs as $club): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($club['name']); ?></div>
                                        <small class="text-muted"><?= e($club['description'] ?? ''); ?></small>
                                    </td>
                                    <td><?= e($club['moderator_name'] ?? 'Unassigned'); ?></td>
                                    <td><?= date('M d, Y', strtotime($club['created_at'])); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#club-<?= (int)$club['id']; ?>">Manage</button>
                                    </td>
                                </tr>
                                <tr class="collapse-row">
                                    <td colspan="4" class="p-0">
                                        <div class="collapse" id="club-<?= (int)$club['id']; ?>">
                                            <div class="border-top p-4 bg-light-subtle">
                                                <div class="row g-3">
                                                    <div class="col-lg-9">
                                                        <form method="post" class="row g-3">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="update_club">
                                                            <input type="hidden" name="id" value="<?= (int)$club['id']; ?>">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Club Name</label>
                                                                <input class="form-control" name="name" value="<?= e($club['name']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Moderator</label>
                                                                <select class="form-select" name="moderator_id">
                                                                    <option value="">-- None --</option>
                                                                    <?php foreach ($moderators as $moderator): ?>
                                                                        <option value="<?= (int)$moderator['id']; ?>" <?= (int)$moderator['id'] === (int)$club['moderator_id'] ? 'selected' : ''; ?>>
                                                                            <?= e($moderator['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label">Description</label>
                                                                <textarea class="form-control" name="description" rows="3"><?= e($club['description'] ?? ''); ?></textarea>
                                                            </div>
                                                            <div class="col-12">
                                                                <button class="btn btn-primary" type="submit">Save</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <form method="post" onsubmit="return confirm('Delete this club?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="delete_club">
                                                            <input type="hidden" name="id" value="<?= (int)$club['id']; ?>">
                                                            <button class="btn btn-outline-danger w-100" type="submit">Delete</button>
                                                        </form>
                                                    </div>
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
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-gradient-admin text-white d-flex justify-content-between align-items-center">
        <span>Schedule Event or Workshop</span>
        <button class="btn btn-outline-light btn-sm" data-bs-toggle="collapse" data-bs-target="#createEventCollapse">New Event</button>
    </div>
    <div class="collapse" id="createEventCollapse">
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
                    <button class="btn btn-primary" type="submit">Schedule Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-gradient-admin text-white">Events &amp; Registrations</div>
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
                    <tr><td colspan="6" class="text-center text-muted py-4">No events scheduled.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($event['title']); ?></td>
                            <td><?= date('M d, Y g:i A', strtotime($event['event_date'])); ?></td>
                            <td><?= e($event['venue']); ?></td>
                            <td><?= e($event['club_name'] ?? 'Independent'); ?></td>
                            <td><span class="badge bg-info text-dark"><?= (int)$event['registrations']; ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#event-<?= (int)$event['id']; ?>">Manage</button>
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
                                            <input type="hidden" name="id" value="<?= (int)$event['id']; ?>">
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
                                        <div class="mt-3">
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this event?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="id" value="<?= (int)$event['id']; ?>">
                                                <button class="btn btn-outline-danger" type="submit">Delete Event</button>
                                            </form>
                                        </div>
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
