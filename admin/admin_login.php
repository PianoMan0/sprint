<?php
require_once '../config.php';
require_once '../includes/functions.php';

$page_title = "Admin Login · Sprint";
include '../includes/header.php';

$error = null;
$success = null;

require_once '../includes/auth.php';

// If already admin, skip.
if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        abort_page('Invalid CSRF token', 400);
    }

    $password = (string)($_POST['password'] ?? '');
    $expected = (string)(getenv('ADMIN_PASSWORD') ?: '');

    if ($expected === '') {
        $error = 'Admin password is not configured. Set ADMIN_PASSWORD in your environment.';
    } elseif (!hash_equals($expected, $password)) {
        $error = 'Incorrect admin password.';
    } else {
        // Must be logged in to elevate role.
        if (!current_user_id()) {
            $success = 'Login required before becoming admin.';
            header('Location: ../auth/login.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([current_user_id()]);

            if (!empty($_SESSION['user'])) {
                $_SESSION['user']['role'] = 'admin';
            }

            $success = 'You are now an admin.';
            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to update role: ' . $e->getMessage();
        }
    }
}
?>

<section class="hero container">
    <div class="ultratitle">Admin Login</div>
    <p class="lead" style="max-width: 720px;">
        Enter the admin password to elevate your account to administrator privileges.
    </p>
</section>

<section class="container" style="margin-top:-2rem;">
    <div class="card" style="max-width: 520px; margin: 0 auto;">
        <?php if (!empty($error)): ?>
            <p class="flash error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p class="flash success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="post" class="form">
            <?= csrf_input_field() ?>
            <label for="password">Admin password
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </label>
            <button class="btn" style="margin-top:12px; width:100%; display:block;" type="submit">Login as admin</button>
        </form>

        <p class="meta" style="margin-top:14px; text-align:center;">
            If you are not an admin, please do not attempt to log in.
        </p>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

