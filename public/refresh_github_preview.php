<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_login();

$eventId = intval($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
$submissionId = intval($_POST['submission_id'] ?? $_GET['submission_id'] ?? 0);

if (!$submissionId) {
    abort_page('Missing submission id', 400);
}

// Ensure the submission belongs to the given event and that the current user is on its team.
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.event_id, s.repo_url, t.id AS team_id
        FROM submissions s
        JOIN teams t ON t.id = s.team_id
        JOIN team_members tm ON tm.team_id = t.id
        WHERE s.id = ? AND tm.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$submissionId, current_user_id()]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        abort_page('Not allowed to refresh this submission.', 403);
    }

    if ($eventId && intval($submission['event_id']) !== $eventId) {
        abort_page('Submission does not belong to this event.', 400);
    }

    $repoUrl = (string)($submission['repo_url'] ?? '');
    if ($repoUrl === '') {
        $_SESSION['profile_error'] = 'This submission has no repo URL to fetch from.';
        header('Location: ' . url('/sprint/public/event.php') . '?id=' . (int)$submission['event_id']);
        exit;
    }

$meta = github_fetch_and_cache_repo_preview($pdo, (int)$submissionId, $repoUrl);

    // If refresh succeeded, clear any stale cached preview for this submission.
    $_SESSION['profile_success'] = 'GitHub preview refreshed.';

} catch (Exception $e) {
    $_SESSION['profile_error'] = 'Failed to refresh GitHub preview: ' . $e->getMessage();
}

header('Location: ' . url('/sprint/public/event.php') . '?id=' . (int)$submission['event_id']);
exit;

