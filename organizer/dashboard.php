<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_role('organizer');

$events = get_events($pdo);
$page_title = "Organizer Dashboard · Sprint";

include '../includes/header.php';
?>

<h1>Organizer Dashboard</h1>

<div class="card-grid">
    <a class="card" href="create_event.php">
        <h2>Create Event</h2>
        <p>Start a new hackathon</p>
    </a>

    <a class="card" href="oauth_admin.php">
        <h2>OAuth Accounts</h2>
        <p>View linked OAuth accounts and tokens</p>
    </a>

    <a class="card" href="site_analytics.php">
        <h2>Site Analytics</h2>
        <p>Overview of events, users, and submissions</p>
    </a>

    <a class="card" href="logs.php">
        <h2>Logs</h2>
        <p>View application logs and DB status</p>
    </a>

    <?php foreach ($events as $e): ?>
        <a class="card" href="manage_event.php?id=<?= (int)$e['id'] ?>">
            <h2><?= htmlspecialchars($e['name']) ?></h2>
            <p><?= htmlspecialchars(substr($e['description'], 0, 100)) ?>...</p>
        </a>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
