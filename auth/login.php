<?php
require_once '../config.php';

$page_title = "Login · Sprint";
include '../includes/header.php';

$clientId = getenv('HACKCLUB_CLIENT_ID');
$host = $_SERVER['HTTP_HOST'] ?? '';
$allowDemo = getenv('ALLOW_DEMO_LOGIN') === '1' || stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false || stripos($host, 'dev.') === 0;
?>


<section class="hero container">
    <div class="ultratitle">Login</div>
    <p class="lead" style="max-width: 720px;">
        Sprint only supports login & sign up with <strong>Hack Club</strong>. You can connect other accounts (GitHub, Slack, Hackatime) in your profile.
    </p>
</section>

<section class="container" style="margin-top:-2rem;">
    <div class="card" style="max-width: 520px; margin: 0 auto;">
        <?php if ($clientId): ?>
            <p style="margin-top:0;">
                <a class="btn" href="<?= url('/sprint/auth/oauth.php') ?>" style="width:100%; display:block;">Continue with Hack Club</a>
            </p>
            <p class="meta" style="margin-top:12px;">
                If this is your first time, you’ll be automatically registered.
            </p>
            <div style="margin-top:14px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a class="btn outline" href="<?= url('/sprint/auth/register.php') ?>" style="flex: 0 0 auto;">Sign up</a>
            </div>
        <?php else: ?>
            <p>Hack Club OAuth is not configured for this installation.</p>

            <?php if ($allowDemo): ?>
                <form method="post" action="<?= url('/sprint/auth/dev_login.php') ?>" style="margin-top:12px;">
                    <?= csrf_input_field() ?>
                    <button class="btn" style="width:100%; display:block;">Login with Hack Club (Demo)</button>
                </form>
                <p class="meta" style="margin-top:12px;">
                    This creates a temporary demo user for local development. Disable by setting <code>ALLOW_DEMO_LOGIN=0</code>.
                </p>
            <?php else: ?>
                <p class="meta" style="margin-top:12px;">
                    Ask an administrator to configure OAuth by setting <code>HACKCLUB_CLIENT_ID</code> and <code>HACKCLUB_CLIENT_SECRET</code> in your environment or <code>.env</code>.
                </p>
                <p style="margin-top:14px;">
                    <a class="btn" href="<?= url('/sprint/organizer/oauth_admin.php') ?>" style="width:100%; display:block;">Configure OAuth</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

