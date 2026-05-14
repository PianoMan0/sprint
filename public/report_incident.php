<?php
require_once '../config.php';
require_login();

$message = '';

// Fetch events the user can report under (their events or all public events)
$events = [];
if (empty($db_connection_failed)) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM events ORDER BY start_time DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        http_response_code(400);
        $message = 'Invalid CSRF token.';
    } else {
        $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $severity = in_array($_POST['severity'] ?? 'low', ['low','medium','high']) ? $_POST['severity'] : 'low';

        if ($title === '' && $desc === '') {
            $message = 'Please provide a title or description.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO emergency_alerts (event_id, user_id, title, description, location, severity) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$event_id, current_user_id(), $title, $desc, $location, $severity]);
                $_SESSION['profile_success'] = 'Incident reported. Organizers have been notified.';
                header('Location: ' . url('public/profile.php'));
                exit;
            } catch (Exception $e) {
                $message = 'Failed to report incident: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$page_title = "Report Incident · Sprint";
include '../includes/header.php';
?>

<div class="incident-header">
    <h1>Report an Incident</h1>
    <a href="https://hackclub.com/safeguarding-policy/" target="_blank" rel="noopener noreferrer">
        <button class="tiny-btn">Safeguarding</button>
    </a>
</div>
<br>
<?php if ($message): ?>
    <p class="flash"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post" class="form">
    <?= csrf_input_field() ?>

    <label>Event (optional)
        <select name="event_id">
            <option value="">General / Not specific</option>
            <?php foreach ($events as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Title
        <input type="text" name="title">
    </label>

    <label>Description
        <textarea name="description" rows="6"></textarea>
    </label>

    <label>Location (optional)
        <input type="text" name="location">
    </label>

    <label>Severity
        <select name="severity">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>
    </label>

    <button class="btn">Report</button>
</form>

<?php include '../includes/footer.php'; ?>
