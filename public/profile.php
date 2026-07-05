<?php
require_once '../config.php';
require_login();

// Defensive: profile can be hit after OAuth flows where session shape may be incomplete.
$u = current_user() ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        http_response_code(400);
        $error = 'Invalid CSRF token.';
    }

    if (empty($error)) {
        $name = trim((string)($_POST['name'] ?? ''));
        $profile_text = trim((string)($_POST['profile'] ?? ''));

        if ($name === '') {
            $error = 'Name cannot be empty.';
        } else {
            if (!empty($db_connection_failed)) {
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['profile'] = $profile_text;
                $success = 'Profile updated locally (DB unavailable).';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, profile = ? WHERE id = ?");
                    $stmt->execute([$name, $profile_text, current_user_id()]);
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['profile'] = $profile_text;
                    $success = 'Profile updated.';
                } catch (Exception $e) {
                    $error = 'Update failed: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

// Refresh defensive user array after POST handling.
$u = current_user() ?? [];

$page_title = "Profile · Sprint";
include '../includes/header.php';
?>

<h1>Profile</h1>
<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['profile_error'])): ?>
    <div class="error"><?= htmlspecialchars($_SESSION['profile_error']) ?></div>
    <?php unset($_SESSION['profile_error']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['profile_success'])): ?>
    <div class="success"><?= htmlspecialchars($_SESSION['profile_success']) ?></div>
    <?php unset($_SESSION['profile_success']); ?>
<?php endif; ?>

<form method="post" action="<?= url('/sprint/public/profile.php') ?>">
    <?= csrf_input_field() ?>

    <div>
        <label for="name">Display name</label>
        <input id="name" name="name" type="text" required value="<?= htmlspecialchars($u['name'] ?? '') ?>">
    </div>

    <div style="margin-top:8px;">
        <label>Email</label>
        <div><?= htmlspecialchars($u['email'] ?? '') ?></div>
    </div>

    <div style="margin-top:12px;">
        <label for="profile">Bio</label>
        <textarea id="profile" name="profile" rows="5"><?= htmlspecialchars($u['profile'] ?? '') ?></textarea>
    </div>

    <div style="margin-top:12px;">
        <button class="btn">Save</button>
        <a class="btn" href="<?= url('/sprint/public/index.php') ?>">Back</a>
    </div>
</form>

<section style="margin-top:18px;">
    <h2>Bio</h2>
    <p><?= nl2br(htmlspecialchars($u['profile'] ?? '')) ?></p>
</section>

<section style="margin-top:18px;">
    <h2>Connect Accounts</h2>
    <p>Link accounts you use most. Hack Club is the only login provider.</p>

    <?php if (getenv('GITHUB_CLIENT_ID')): ?>
        <p style="margin-top:10px;">
            <a class="btn" href="<?= url('/sprint/auth/github.php') ?>" style="width:100%; display:block;">Connect GitHub</a>
        </p>
    <?php else: ?>
        <p class="meta">GitHub isn’t configured on this site.</p>
    <?php endif; ?>

    <?php if (getenv('SLACK_CLIENT_ID')): ?>
        <p style="margin-top:10px;">
            <a class="btn" href="<?= url('/sprint/auth/slack.php') ?>" style="width:100%; display:block;">Connect Slack</a>
        </p>
    <?php else: ?>
        <p class="meta">Slack isn’t configured on this site.</p>
    <?php endif; ?>

    <?php if (getenv('HACKATIME_CLIENT_ID')): ?>
        <p style="margin-top:10px;">
            <a class="btn" href="<?= url('/sprint/auth/hackatime.php') ?>" style="width:100%; display:block;">Connect Hackatime</a>
        </p>
    <?php else: ?>
        <p class="meta">Hackatime isn’t configured on this site.</p>
    <?php endif; ?>
</section>

<?php
// Everything below is DB-driven; keep it defensive so the page renders even if some queries fail.

try {
    $orgCount = 0;
    if (empty($db_connection_failed)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'");
        $orgCount = $stmt ? intval($stmt->fetchColumn()) : 0;
    }
} catch (Exception $e) {
    $orgCount = 0;
}

if ($orgCount === 0 && (($u['role'] ?? '') !== 'organizer')):
?>
    <section style="margin-top:18px;">
        <h2>Site Setup</h2>
        <p>No organizers exist for this instance yet. If you're the site owner you can claim the organizer role.</p>
        <p><a class="btn" href="<?= url('/sprint/organizer/claim_organizer.php') ?>">Claim Organizer Role</a></p>
    </section>
<?php endif; ?>

<section style="margin-top:18px;">
    <h2>Hackathons Attended</h2>
    <?php
    $attended = [];
    if (empty($db_connection_failed)) {
        try {
            $stmt = $pdo->prepare("SELECT e.*
                FROM events e
                JOIN user_event_attendance uea ON uea.event_id = e.id
                WHERE uea.user_id = ?
                ORDER BY e.start_time DESC");
            $stmt->execute([current_user_id()]);
            $att1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT DISTINCT e.*
                FROM events e
                JOIN teams t ON t.event_id = e.id
                JOIN team_members tm ON tm.team_id = t.id
                WHERE tm.user_id = ?
                ORDER BY e.start_time DESC");
            $stmt->execute([current_user_id()]);
            $att2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $byId = [];
            foreach (array_merge($att1, $att2) as $ev) {
                if (isset($ev['id'])) $byId[$ev['id']] = $ev;
            }
            $attended = array_values($byId);
        } catch (Exception $e) {
            // ignore DB errors
            $attended = [];
        }
    }

    if (empty($attended)) {
        echo '<p>No recorded hackathons attended yet.</p>';
    } else {
        echo '<ul>';
        foreach ($attended as $e) {
            $id = $e['id'] ?? null;
            if ($id === null) continue;
            echo '<li><a href="' . htmlspecialchars(url('/sprint/public/event.php') . '?id=' . $id) . '">' . htmlspecialchars($e['name'] ?? '') . '</a> — ' . htmlspecialchars(substr($e['description'] ?? '', 0, 140)) . '</li>';
        }
        echo '</ul>';
    }
    ?>
</section>

<section style="margin-top:24px;">
    <h2>Linked accounts</h2>
    <?php
    $linked = [];
    if (empty($db_connection_failed)) {
        try {
            $stmt = $pdo->prepare("SELECT provider, provider_user_id, created_at
                FROM oauth_accounts WHERE user_id = ?
                ORDER BY created_at DESC");
            $stmt->execute([current_user_id()]);
            $linked = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $linked = [];
        }
    }
    ?>

    <?php if (empty($linked)): ?>
        <p>No linked third-party accounts.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($linked as $l): ?>
                <li>
                    <?php if (($l['provider'] ?? '') === 'github'): ?>
                        <img src="https://avatars.githubusercontent.com/<?= rawurlencode($l['provider_user_id'] ?? '') ?>?s=24" alt="" style="vertical-align:middle;width:24px;height:24px;border-radius:4px;margin-right:8px;">
                        <strong>GitHub</strong> —
                        <a href="https://github.com/<?= rawurlencode($l['provider_user_id'] ?? '') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($l['provider_user_id'] ?? '') ?></a>
                    <?php else: ?>
                        <?= htmlspecialchars($l['provider'] ?? '') ?> — <?= htmlspecialchars($l['provider_user_id'] ?? '') ?>
                    <?php endif; ?>

                    <form method="post" action="<?= url('auth/unlink.php') ?>" style="display:inline;margin-left:8px;">
                        <?= csrf_input_field() ?>
                        <input type="hidden" name="provider" value="<?= htmlspecialchars($l['provider'] ?? '') ?>">
                        <button class="btn" onclick="return confirm('Unlink <?= htmlspecialchars($l['provider'] ?? '') ?>?')">Unlink</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php include '../includes/footer.php'; ?>

