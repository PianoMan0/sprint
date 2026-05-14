<?php
require_once __DIR__ . '/../config.php';

// Simple JSON API for public data. Usage examples:
// GET /api/index.php?q=events
// GET /api/index.php?q=events/1
// GET /api/index.php?q=users/1
// POST /api/index.php?q=incidents (requires API key if configured)

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''), '/');
$parts = $q === '' ? [] : explode('/', $q);

// Optional API key enforcement
$apiKey = getenv('API_KEY') ?: null;
if ($apiKey && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? null;
    if (!$provided || $provided !== $apiKey) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
}

try {
    if (count($parts) === 0 || $parts[0] === '') {
        echo json_encode(['ok' => true, 'routes' => ['events','users','submissions','incidents']]);
        exit;
    }

    switch ($parts[0]) {
        case 'events':
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
                $stmt->execute([(int)$parts[1]]);
                $ev = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($ev ?: null);
            } else {
                $stmt = $pdo->query('SELECT id, name, description, start_time, end_time, visibility, judging_mode FROM events ORDER BY start_time DESC');
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            exit;

        case 'users':
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $uid = (int)$parts[1];
                $stmt = $pdo->prepare('SELECT id, name, profile, created_at FROM users WHERE id = ?');
                $stmt->execute([$uid]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    // events attended
                    $stmt = $pdo->prepare('SELECT e.id, e.name, e.start_time FROM events e JOIN user_event_attendance uea ON uea.event_id = e.id WHERE uea.user_id = ? ORDER BY e.start_time DESC');
                    $stmt->execute([$uid]);
                    $u['attended_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                echo json_encode($u ?: null);
            } else {
                $stmt = $pdo->query('SELECT id, name FROM users ORDER BY created_at DESC LIMIT 200');
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            exit;

        case 'submissions':
            $stmt = $pdo->query('SELECT s.*, t.name AS team_name FROM submissions s JOIN teams t ON t.id = s.team_id ORDER BY s.created_at DESC LIMIT 200');
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;

        case 'incidents':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // create incident via API
                $raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                $event_id = !empty($raw['event_id']) ? intval($raw['event_id']) : null;
                $title = trim((string)($raw['title'] ?? ''));
                $description = trim((string)($raw['description'] ?? ''));
                $location = trim((string)($raw['location'] ?? ''));
                $severity = in_array($raw['severity'] ?? 'low', ['low','medium','high']) ? $raw['severity'] : 'low';

                $stmt = $pdo->prepare('INSERT INTO emergency_alerts (event_id, user_id, title, description, location, severity) VALUES (?,?,?,?,?,?)');
                // API-created incidents are unauthenticated (user_id null)
                $stmt->execute([$event_id, null, $title, $description, $location, $severity]);
                http_response_code(201);
                echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                $stmt = $pdo->query('SELECT ea.*, e.name AS event_name FROM emergency_alerts ea LEFT JOIN events e ON e.id=ea.event_id ORDER BY ea.created_at DESC LIMIT 200');
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            exit;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
    exit;
}
