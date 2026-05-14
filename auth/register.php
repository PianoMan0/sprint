<?php
require_once '../config.php';

$page_title = "Register · Sprint";
include '../includes/header.php';

$clientId = getenv('HACKCLUB_CLIENT_ID');
?>

<h1>Register</h1>

<?php if ($clientId): ?>
    <p><a class="btn" href="<?= url('/sprint/auth/oauth.php') ?>">Register with Hack Club</a></p>
<?php else: ?>
    <p>Ask an administrator to set <code>HACKCLUB_CLIENT_ID</code> and <code>HACKCLUB_CLIENT_SECRET</code> in the environment or <code>.env</code>.</p>
    <p><a href="<?= url('/sprint/auth/login.php') ?>">Back to login</a></p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
