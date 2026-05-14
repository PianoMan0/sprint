<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/judge_functions.php';
require_login();

$submission_id = intval($_GET['id'] ?? 0);
$submission = get_submission($pdo, $submission_id);

if (!$submission) abort_page('Submission not found', 404);

$event_id = $submission['event_id'];
$event = get_event($pdo, $event_id);
$categories = get_event_categories($pdo, $event_id);

// Permission: if event uses judges, require judge role; if peer, allow
// participants who are part of the event to score (or judges always allowed)
if ($event && ($event['judging_mode'] ?? 'judges') === 'judges') {
    if (!is_judge()) {
        abort_page('Access denied', 403);
    }
} else {
    // peer scoring: ensure the current user is a participant in the event or a judge
    if (!is_judge()) {
        $member = get_user_team($pdo, $event_id, current_user_id());
        if (!$member) {
            abort_page('Access denied', 403);
        }
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        http_response_code(400);
        $message = "Invalid CSRF token.";
    } else {
        foreach ($categories as $c) {
        $score = intval($_POST['score_' . $c['id']]);
        $comment = trim($_POST['comment_' . $c['id']]);

        $stmt = $pdo->prepare("
            INSERT INTO scores (submission_id, judge_id, category, score, comment)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([
            $submission_id,
            current_user_id(),
            $c['name'],
            $score,
            $comment
        ]);
    }
        $message = "Scores saved.";
    }
}

$page_title = "Score Submission · Sprint";
include '../includes/header.php';
?>

<h1>Score: <?= htmlspecialchars($submission['title']) ?></h1>

<?php if ($message): ?>
    <p class="flash"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post" class="form">
    <?= csrf_input_field() ?>
<?php foreach ($categories as $c): ?>
    <div class="card">
        <h3><?= htmlspecialchars($c['name']) ?></h3>
        <p class="meta">Weight: <?= htmlspecialchars($c['weight']) ?></p>

        <label>Score (0–10)
            <input type="number" name="score_<?= (int)$c['id'] ?>" min="0" max="10" required>
        </label>

        <label>Comment
            <input type="text" name="comment_<?= (int)$c['id'] ?>">
        </label>
    </div>
<?php endforeach; ?>

    <button class="btn">Submit Scores</button>
</form>

<?php include '../includes/footer.php'; ?>
