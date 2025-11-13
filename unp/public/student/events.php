<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';

require_role('Student');
$pdo = db();
$user = current_user();

if (is_post()) {
    $token = $_POST['csrf_token'] ?? null;
    $action = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Invalid session token. Refresh and try again.');
        redirect('/student/events.php');
    }

    try {
        if ($eventId <= 0) {
            throw new RuntimeException('Invalid event.');
        }

        $eventStmt = $pdo->prepare('SELECT * FROM events WHERE id = :id');
        $eventStmt->execute(['id' => $eventId]);
        $event = $eventStmt->fetch();

        if (!$event) {
            throw new RuntimeException('Event not found.');
        }

        if ($action === 'register') {
            if (strtotime($event['event_date']) < time()) {
                throw new RuntimeException('Event registration is closed.');
            }

            if ($event['capacity'] !== null) {
                $count = $pdo->prepare('SELECT COUNT(*) FROM event_registrations WHERE event_id = :event');
                $count->execute(['event' => $eventId]);
                if ((int)$count->fetchColumn() >= (int)$event['capacity']) {
                    throw new RuntimeException('This event has reached capacity.');
                }
            }

            $register = $pdo->prepare(
                'INSERT INTO event_registrations (event_id, student_id) VALUES (:event, :student)
                 ON DUPLICATE KEY UPDATE registered_at = NOW()'
            );
            $register->execute([
                'event' => $eventId,
                'student' => $user['id'],
            ]);

            log_activity($user['id'], 'event_register', "Registered for event #{$eventId}");
            set_flash('success', 'You have registered for this event.');
        } elseif ($action === 'unregister') {
            $delete = $pdo->prepare('DELETE FROM event_registrations WHERE event_id = :event AND student_id = :student');
            $delete->execute([
                'event' => $eventId,
                'student' => $user['id'],
            ]);

            log_activity($user['id'], 'event_unregister', "Unregistered from event #{$eventId}");
            set_flash('info', 'You have been removed from the event.');
        }
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }

    redirect('/student/events.php');
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'club' => trim($_GET['club'] ?? ''),
    'show' => $_GET['show'] ?? 'upcoming',
];

$query = "SELECT e.*, c.name AS club_name,
                 EXISTS(SELECT 1 FROM event_registrations er WHERE er.event_id = e.id AND er.student_id = :student_id) AS is_registered,
                 (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registrations
          FROM events e
          LEFT JOIN clubs c ON c.id = e.club_id";
$params = ['student_id' => $user['id']];
$conditions = [];

if ($filters['show'] === 'mine') {
    $conditions[] = 'EXISTS(SELECT 1 FROM event_registrations er WHERE er.event_id = e.id AND er.student_id = :student_id)';
} else {
    $conditions[] = 'e.event_date >= NOW()';
}

if ($filters['search'] !== '') {
    $conditions[] = '(e.title LIKE :term OR e.description LIKE :term OR c.name LIKE :term OR e.venue LIKE :term)';
    $params['term'] = '%' . $filters['search'] . '%';
}

if ($filters['club'] !== '') {
    $conditions[] = 'c.name = :club';
    $params['club'] = $filters['club'];
}

if ($conditions) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}

$query .= ' ORDER BY e.event_date ASC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

$clubs = $pdo->query('SELECT name FROM clubs ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Events & Workshops';
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

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="get">
            <div class="col-md-4">
                <label class="form-label">Keyword</label>
                <input class="form-control" name="search" placeholder="Title, venue, club" value="<?= e($filters['search']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Club</label>
                <select class="form-select" name="club">
                    <option value="">All Clubs</option>
                    <?php foreach ($clubs as $clubName): ?>
                        <option value="<?= e($clubName); ?>" <?= $filters['club'] === $clubName ? 'selected' : ''; ?>><?= e($clubName); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Show</label>
                <select class="form-select" name="show">
                    <option value="upcoming" <?= $filters['show'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming Events</option>
                    <option value="mine" <?= $filters['show'] === 'mine' ? 'selected' : ''; ?>>My Registrations</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-filter"></i> Apply Filters</button>
                <a class="btn btn-outline-light" href="<?= BASE_URL; ?>/student/events.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$events): ?>
    <div class="alert alert-info">No events to display.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($events as $event): ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card h-100 event-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-moderator-subtle text-moderator"><?= e($event['club_name'] ?? 'Campus Event'); ?></span>
                            <small class="text-muted"><?= date('M d, Y g:i A', strtotime($event['event_date'])); ?></small>
                        </div>
                        <h5 class="card-title"><?= e($event['title']); ?></h5>
                        <p class="card-text flex-grow-1"><?= e(substr($event['description'] ?? '', 0, 150)); ?><?= isset($event['description']) && strlen($event['description']) > 150 ? '…' : ''; ?></p>
                        <div class="mt-3">
                            <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= e($event['venue']); ?></div>
                            <?php if ($event['capacity']): ?>
                                <div class="small text-muted"><i class="bi bi-people"></i> Capacity <?= (int)$event['capacity']; ?> &middot; <?= (int)$event['registrations']; ?> registered</div>
                            <?php else: ?>
                                <div class="small text-muted"><i class="bi bi-people"></i> <?= (int)$event['registrations']; ?> registered</div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <?php if ($event['is_registered']): ?>
                                <form method="post" class="flex-grow-1">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                    <input type="hidden" name="action" value="unregister">
                                    <input type="hidden" name="event_id" value="<?= (int)$event['id']; ?>">
                                    <button class="btn btn-outline-danger w-100" type="submit">Unregister</button>
                                </form>
                            <?php else: ?>
                                <form method="post" class="flex-grow-1">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="event_id" value="<?= (int)$event['id']; ?>">
                                    <button class="btn btn-primary w-100" type="submit" <?= ($event['capacity'] !== null && (int)$event['registrations'] >= (int)$event['capacity']) ? 'disabled' : ''; ?>>
                                        Register
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-outline-light" type="button" data-bs-toggle="modal" data-bs-target="#eventModal-<?= (int)$event['id']; ?>">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="eventModal-<?= (int)$event['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= e($event['title']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                <span class="badge bg-moderator-subtle text-moderator"><?= e($event['club_name'] ?? 'Campus Event'); ?></span>
                                &middot; <?= date('M d, Y g:i A', strtotime($event['event_date'])); ?>
                            </p>
                            <?php if ($event['description']): ?>
                                <p><?= nl2br(e($event['description'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No additional description provided.</p>
                            <?php endif; ?>
                            <div class="small text-muted">
                                <i class="bi bi-geo-alt me-1"></i> <?= e($event['venue']); ?>
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-people me-1"></i> <?= (int)$event['registrations']; ?> registered
                                <?php if ($event['capacity']): ?>
                                    &middot; Capacity <?= (int)$event['capacity']; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <?php if ($event['is_registered']): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                    <input type="hidden" name="action" value="unregister">
                                    <input type="hidden" name="event_id" value="<?= (int)$event['id']; ?>">
                                    <button class="btn btn-outline-danger" type="submit">Unregister</button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="event_id" value="<?= (int)$event['id']; ?>">
                                    <button class="btn btn-primary" type="submit" <?= ($event['capacity'] !== null && (int)$event['registrations'] >= (int)$event['capacity']) ? 'disabled' : ''; ?>>
                                        Register
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
