<?php
require_once '../config.php';

$clientId = getenv('HACKCLUB_CLIENT_ID');
if (!$clientId) {
    http_response_code(500);
    echo "<p>Hack Club OAuth is not configured. Set <code>HACKCLUB_CLIENT_ID</code> in your .env.</p>";
    exit;
}

$redirectUri = getenv('HACKCLUB_REDIRECT_URI') ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('auth/callback.php'));

try {
    $state = bin2hex(random_bytes(16));
} catch (Exception $e) {
    $state = bin2hex(openssl_random_pseudo_bytes(16));
}

$_SESSION['hc_oauth_state'] = $state;

// Also set a short-lived cookie fallback for the OAuth state so the callback
// can validate state even if the session cookie is lost (helps in some setups).
$cookieOptions = [
    'expires' => time() + 300,
    'path' => '/',
    'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || getenv('FORCE_HTTPS') === '1',
    'httponly' => true,
    'samesite' => 'Lax',
];
$cookieDomain = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST']) : '';
if ($cookieDomain !== '') $cookieOptions['domain'] = $cookieDomain;
if (PHP_VERSION_ID >= 70300) {
    setcookie('hc_oauth_state', $state, $cookieOptions);
} else {
    // Fallback for older PHP
    setcookie('hc_oauth_state', $state, time() + 300, '/');
}
$scope = 'openid profile email name slack_id';

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $scope,
    'state' => $state,
];

$authUrl = 'https://auth.hackclub.com/oauth/authorize?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

// If the configured redirect host (explicit env) differs from this host,
// warn the user and show a confirmation before redirecting. This helps when
// the OAuth client is registered for a different domain (causing 404s).
$configuredRedirect = getenv('HACKCLUB_REDIRECT_URI');
if ($configuredRedirect) {
    $cfg = parse_url($configuredRedirect);
    $cfgHost = $cfg['host'] ?? null;
    $currentHost = $_SERVER['HTTP_HOST'] ?? null;
    if ($cfgHost && $currentHost && strcasecmp($cfgHost, $currentHost) !== 0) {
        $page_title = "Login · Sprint";
        include '../includes/header.php';
        ?>
        <h1>Proceed to Hack Club</h1>
        <p>The OAuth client is configured to redirect to <strong><?= htmlspecialchars($configuredRedirect) ?></strong>.</p>
        <p>When you continue, Hack Club may redirect back to that host after sign-in. If that host is not this server, you may get a 404.</p>
        <p>
            <a class="btn" href="<?= htmlspecialchars($authUrl) ?>">Continue to Hack Club</a>
        </p>
        <p><small>If you want to use a different callback URL for local development, set <code>HACKCLUB_REDIRECT_URI</code> in your <code>.env</code> or register a redirect for your local host in the Hack Club app settings.</small></p>
        <?php
        include '../includes/footer.php';
        exit;
    }
}

header('Location: ' . $authUrl);
exit;
