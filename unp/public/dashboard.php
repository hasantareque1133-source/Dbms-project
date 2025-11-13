<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/database.php';

require_auth();
$user = current_user();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../templates/header.php';

$pdo = db();

// Shared stats
$opportunityCount = (int)$pdo->query('SELECT COUNT(*) FROM opportunities')->fetchColumn();
$upcomingEventCount = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW()")->fetchColumn();
$alumniCount = (int)$pdo->query('SELECT COUNT(*) FROM alumni')->fetchColumn();

?>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="metric-card border-start border-4 border-admin">
            <div class="metric-label text-muted">Opportunities</div>
            <div class="metric-value"><?= $opportunityCount; ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metric-card border-start border-4 border-student">
            <div class="metric-label text-muted">Upcoming Events</div>
            <div class="metric-value"><?= $upcomingEventCount; ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metric-card border-start border-4 border-moderator">
            <div class="metric-label text-muted">Alumni Mentors</div>
            <div class="metric-value"><?= $alumniCount; ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metric-card border-start border-4 border-light">
            <div class="metric-label text-muted">Your Role</div>
            <div class="metric-value"><?= e($user['role']); ?></div>
        </div>
    </div>
</div>

<?php if ($user['role'] === 'Admin'): ?>
    <?php
    $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'Student'")->fetchColumn();
    $moderatorCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'Moderator'")->fetchColumn();
    $recentActivity = $pdo->query(
        "SELECT a.action, a.details, a.created_at, u.name
         FROM activity_log a
         JOIN users u ON u.id = a.user_id
         ORDER BY a.created_at DESC
         LIMIT 6"
    )->fetchAll();
    ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-gradient-admin text-white">User Overview</div>
                <div class="card-body">
                    <p class="mb-1"><span class="fw-semibold">Students:</span> <?= $studentCount; ?></p>
                    <p class="mb-0"><span class="fw-semibold">Moderators:</span> <?= $moderatorCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-gradient-admin text-white">Recent Activity</div>
                <div class="card-body">
                    <?php if (!$recentActivity): ?>
                        <p class="text-muted mb-0">No recent activity logged.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?= e($activity['name']); ?> <span class="badge bg-secondary"><?= e($activity['action']); ?></span></h6>
                                        <?php if ($activity['details']): ?>
                                            <p class="mb-1 small"><?= e($activity['details']); ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($user['role'] === 'Student'): ?>
    <?php
    $studentId = $user['id'];
    $opportunities = $pdo->prepare(
        "SELECT o.*, u.name AS poster
         FROM opportunities o
         JOIN users u ON u.id = o.posted_by
         ORDER BY o.created_at DESC
         LIMIT 5"
    );
    $opportunities->execute();
    $opportunities = $opportunities->fetchAll();

    $events = $pdo->prepare(
        "SELECT e.*, c.name AS club_name,
                EXISTS(SELECT 1 FROM event_registrations er WHERE er.event_id = e.id AND er.student_id = :student) AS is_registered
         FROM events e
         LEFT JOIN clubs c ON c.id = e.club_id
         WHERE e.event_date >= NOW()
         ORDER BY e.event_date ASC
         LIMIT 5"
    );
    $events->execute(['student' => $studentId]);
    $events = $events->fetchAll();
    ?>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-gradient-student text-white">Latest Opportunities</div>
                <div class="list-group list-group-flush">
                    <?php if (!$opportunities): ?>
                        <div class="list-group-item text-muted">No opportunities posted yet.</div>
                    <?php else: ?>
                        <?php foreach ($opportunities as $opp): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?= e($opp['title']); ?></h5>
                                        <p class="small text-muted mb-1"><?= e($opp['description']); ?></p>
                                        <span class="badge bg-primary-subtle text-primary"><?= opportunity_label($opp['type']); ?></span>
                                    </div>
                                    <small class="text-muted text-end">by <?= e($opp['poster']); ?><br><?= date('M d', strtotime($opp['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-gradient-student text-white">Upcoming Events</div>
                <div class="list-group list-group-flush">
                    <?php if (!$events): ?>
                        <div class="list-group-item text-muted">No events available.</div>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?= e($event['title']); ?></h5>
                                        <p class="small text-muted mb-1"><?= e($event['description']); ?></p>
                                        <span class="badge bg-light text-dark"><i class="bi bi-geo-alt me-1"></i><?= e($event['venue']); ?></span>
                                        <?php if ($event['club_name']): ?>
                                            <span class="badge bg-moderator-subtle text-moderator"><i class="bi bi-people me-1"></i><?= e($event['club_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block"><?= date('M d, Y g:i A', strtotime($event['event_date'])); ?></small>
                                        <?php if ($event['is_registered']): ?>
                                            <span class="badge bg-success mt-2">Registered</span>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-outline-primary mt-2" href="<?= BASE_URL; ?>/student/events.php">Register</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($user['role'] === 'Moderator'): ?>
    <?php
    $moderatorId = $user['id'];
    $moderatorEvents = $pdo->prepare(
        "SELECT e.*, c.name AS club_name,
                (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registrations
         FROM events e
         LEFT JOIN clubs c ON c.id = e.club_id
         WHERE e.created_by = :moderator OR (c.moderator_id = :moderator AND c.moderator_id IS NOT NULL)
         ORDER BY e.event_date ASC"
    );
    $moderatorEvents->execute(['moderator' => $moderatorId]);
    $moderatorEvents = $moderatorEvents->fetchAll();
    ?>
    <div class="card">
        <div class="card-header bg-gradient-moderator text-white">Your Events & Workshops</div>
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
                    <?php if (!$moderatorEvents): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No events created yet. Start by publishing your next workshop.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($moderatorEvents as $event): ?>
                            <tr>
                                <td><?= e($event['title']); ?></td>
                                <td><?= date('M d, Y g:i A', strtotime($event['event_date'])); ?></td>
                                <td><?= e($event['venue']); ?></td>
                                <td><?= e($event['club_name'] ?? 'Independent'); ?></td>
                                <td><span class="badge bg-info text-dark"><?= (int)$event['registrations']; ?></span></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL; ?>/moderator/events.php">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
