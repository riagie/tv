<?php
declare(strict_types=1);

require_once __DIR__ . '/loader.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_HOST'] ?? '*'));
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

session_start();
$sessionKey = 'api_access_' . date('YmdH');
if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = ['count' => 0, 'time' => time()];
}

$timeDiff = time() - $_SESSION[$sessionKey]['time'];
if ($timeDiff > API_RATE_WINDOW) {
    $_SESSION[$sessionKey] = ['count' => 0, 'time' => time()];
}
if ($_SESSION[$sessionKey]['count'] >= API_RATE_LIMIT) {
    http_response_code(429);
    echo json_encode(['error' => true, 'message' => 'Too many requests']);
    exit;
}
$_SESSION[$sessionKey]['count']++;

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = false;
foreach (ALLOWED_ORIGINS as $allowedOrigin) {
    if (strpos($origin, $allowedOrigin) === 0) {
        $allowed = true;
        header('Access-Control-Allow-Origin: ' . $origin);
        break;
    }
}

$sessionToken = bin2hex(random_bytes(16));
$_SESSION['api_token'] = $sessionToken;

if (!$allowed && $origin !== '') {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Forbidden']);
    exit;
}

if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $refererHost = $referer['host'] ?? '';
    $isValidReferer = false;
    foreach (ALLOWED_ORIGINS as $allowedOrigin) {
        $parsedAllowed = parse_url($allowedOrigin);
        if ($refererHost === ($parsedAllowed['host'] ?? '')) {
            $isValidReferer = true;
            break;
        }
    }
    if (!$isValidReferer) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Invalid referer']);
        exit;
    }
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$blockedAgents = ['bot', 'crawl', 'spider', 'scraper', 'curl', 'wget', 'python', 'java'];
foreach ($blockedAgents as $blocked) {
    if (stripos($userAgent, $blocked) !== false) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Access denied']);
        exit;
    }
}

$type = $_GET['type'] ?? 'indonesia';
$search = $_GET['search'] ?? '';
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 1000;
$token = $_GET['token'] ?? '';

if (!empty($token) && $token !== $_SESSION['api_token']) {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Invalid token']);
    exit;
}

$allowedTypes = ['indonesia', 'global'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Invalid type parameter']);
    exit;
}

$search = substr(trim($search), 0, 50);
if (!preg_match('/^[a-zA-Z0-9\s\-_]*$/', $search)) {
    $search = '';
}

$path = dirname(__FILE__);
$db = DB_FILE;

try {
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA temp_store = MEMORY');
    $pdo->exec('PRAGMA mmap_size = 268435456');

    $stmt = $pdo->query("SELECT COUNT(*) FROM iptv_channels");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $response = [
            'error' => true,
            'message' => 'No channels available',
            'data' => [],
            'token' => $sessionToken
        ];
        echo encodeResponse($response);
        exit;
    }

    if ($type === 'indonesia') {
        $sql = "
            SELECT id, channel, url, country, language, categories,
                   COALESCE(logo_base64, logo) as logo
            FROM iptv_channels
            WHERE country = 'ID'
        ";

        if (!empty($search)) {
            $sql .= " AND channel LIKE :search";
        }

        $sql .= " ORDER BY channel ASC LIMIT 1000";

        $stmt = $pdo->prepare($sql);

        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->execute();
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $sql = "
            SELECT id, channel, url, country, language, categories,
                   COALESCE(logo_base64, logo) as logo
            FROM iptv_channels
            WHERE country != 'ID' AND country IS NOT NULL AND country != ''
        ";

        if (!empty($search)) {
            $sql .= " AND channel LIKE :search";
        }

        $sql .= " ORDER BY channel ASC LIMIT " . $limit;

        $stmt = $pdo->prepare($sql);

        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->execute();
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $obfuscatedChannels = [];

    foreach ($channels as $index => $channel) {
        $obfuscatedChannels[] = [
            'd' => base64_encode(json_encode($channel)),
            'i' => $index,
            't' => $sessionToken
        ];
    }

    $response = [
        'error' => false,
        'type' => $type,
        'count' => count($channels),
        'token' => $sessionToken,
        'ts' => time(),
        'data' => $obfuscatedChannels
    ];

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo encodeResponse($response);

} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'error' => true,
        'message' => 'Internal server error',
        'token' => $sessionToken ?? '',
        'ts' => time()
    ];
    echo encodeResponse($response);
}

function encodeResponse($data) {
    $json = json_encode($data);
    $key = SECRET_KEY_PREFIX . date('Ymd');
    $encoded = '';
    $keyLen = strlen($key);

    for ($i = 0; $i < strlen($json); $i++) {
        $encoded .= chr(ord($json[$i]) ^ ord($key[$i % $keyLen]));
    }

    return base64_encode($encoded);
}
