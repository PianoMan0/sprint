<?php
require_once '../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('public/profile.php'));
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$provider = $_POST['provider'] ?? '';

if (!validate_csrf_token($token)) {
    http_response_code(400);
    $_SESSION['profile_error'] = 'Invalid CSRF token.';
    header('Location: ' . url('public/profile.php'));
    exit;
}

$provider = trim((string)$provider);
if ($provider === '') {
    $_SESSION['profile_error'] = 'Missing provider.';
    header('Location: ' . url('public/profile.php'));
    exit;
}

try {
    // Ensure user won't lock themselves out: check for other auth methods or password
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM oauth_accounts WHERE user_id = ?");
    $stmt->execute([current_user_id()]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $linked = intval($row['cnt'] ?? 0);

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasPassword = !empty($user['password_hash']);

    if ($linked <= 1 && !$hasPassword) {
        $_SESSION['profile_error'] = 'Cannot unlink the only login method for your account. Add another login method first.';
        header('Location: ' . url('public/profile.php'));
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM oauth_accounts WHERE provider = ? AND user_id = ?");
    $stmt->execute([$provider, current_user_id()]);

    $_SESSION['profile_success'] = 'Unlinked ' . htmlspecialchars($provider) . ' account.';
    header('Location: ' . url('public/profile.php'));
    exit;
} catch (Exception $e) {
    $_SESSION['profile_error'] = 'Unlink failed: ' . htmlspecialchars($e->getMessage());
    header('Location: ' . url('public/profile.php'));
    exit;
}
