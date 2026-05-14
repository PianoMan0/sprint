<?php
require_once '../config.php';

$page_title = "Login · Sprint";
include '../includes/header.php';

$clientId = getenv('HACKCLUB_CLIENT_ID');
$host = $_SERVER['HTTP_HOST'] ?? '';
$allowDemo = getenv('ALLOW_DEMO_LOGIN') === '1' || stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false || stripos($host, 'dev.') === 0;
$slackClient = getenv('SLACK_CLIENT_ID');
?>

<h1>Login</h1>

<?php if ($clientId): ?>
    <p><a class="btn" href="<?= url('/sprint/auth/oauth.php') ?>">Login with Hack Club</a></p>
    <?php if ($slackClient): ?>
        <p><a class="btn" href="<?= url('/sprint/auth/slack.php') ?>">Login with Slack</a></p>
    <?php endif; ?>
<?php else: ?>
    <p>Hack Club OAuth is not configured for this installation.</p>
    <?php if ($allowDemo): ?>
        <form method="post" action="<?= url('/sprint/auth/dev_login.php') ?>">
            <?= csrf_input_field() ?>
            <button class="btn">Login with Hack Club (Demo)</button>
        </form>
        <p><small>This creates a temporary demo user for local development. Disable by setting <code>ALLOW_DEMO_LOGIN=0</code>.</small></p>
    <?php else: ?>
        <p>Ask an administrator to configure OAuth by setting <code>HACKCLUB_CLIENT_ID</code> and <code>HACKCLUB_CLIENT_SECRET</code> in your environment or <code>.env</code>.</p>
        <p><a href="<?= url('/sprint/organizer/oauth_admin.php') ?>">Configure OAuth</a></p>
    <?php endif; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
