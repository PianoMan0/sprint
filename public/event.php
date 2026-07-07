<?php
require_once '../config.php';
require_once '../includes/functions.php';

$event_id = $_GET['id'] ?? null;
$event = get_event($pdo, $event_id);

if (!$event) {
    abort_page('Event not found', 404);
}

$teams = get_event_teams($pdo, $event_id);
$submissions = get_event_submissions($pdo, $event_id);
$announcements = get_event_announcements($pdo, $event_id);

$page_title = $event['name'] . " · Sprint";
include '../includes/header.php';
?>

<h1><?= htmlspecialchars($event['name']) ?></h1>
<p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

<div class="event-actions">
    <?php if (current_user_id()): ?>
        <a class="btn" href="teams.php?event_id=<?= intval($event_id) ?>">Teams</a>
        <a class="btn" href="submit.php?event_id=<?= intval($event_id) ?>">Submit</a>
        <a class="btn" href="leaderboard.php?event_id=<?= intval($event_id) ?>">Leaderboard</a>

        <?php if (!is_organizer()): ?>
            <a class="btn outline" href="<?= url('/sprint/organizer/claim_organizer_for_event.php') . '?event_id=' . intval($event_id) ?>">Become organizer</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<h2>Announcements</h2>
<?php foreach ($announcements as $a): ?>
    <div class="card">
        <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>
        <p class="meta"><?= htmlspecialchars($a['created_at']) ?></p>
    </div>
<?php endforeach; ?>

<h2>Submissions</h2>
<div class="card-grid">
<?php foreach ($submissions as $s): ?>
    <div class="card">
        <h3><?= htmlspecialchars($s['title']) ?></h3>
        <p class="meta">Team: <?= htmlspecialchars($s['team_name']) ?></p>
        <p><?= htmlspecialchars(substr($s['description'], 0, 120)) ?>...</p>
        <?php if (!empty($s['screenshot_path'])): ?>
            <div style="margin-top:8px;"><img src="<?= htmlspecialchars(url($s['screenshot_path'])) ?>" alt="Screenshot" style="max-width:100%;border-radius:8px;"></div>
        <?php endif; ?>
        <?php if (!empty($s['video_path'])): ?>
            <div style="margin-top:8px;"><video controls style="max-width:100%;border-radius:8px;"><source src="<?= htmlspecialchars(url($s['video_path'])) ?>">Your browser does not support video playback.</video></div>
        <?php endif; ?>
        <p style="margin-top:8px;"><a class="btn" href="edit_submission.php?id=<?= (int)$s['id'] ?>">View / Edit</a></p>
    </div>
<?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
