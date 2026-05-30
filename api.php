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

    // Count total channels
    $stmt = $pdo->query("SELECT COUNT(*) FROM iptv_channels");
    $totalChannels = $stmt->fetchColumn();

    if ($totalChannels == 0) {
        $response = [
            'error' => true,
            'message' => 'No channels available',
            'data' => [],
            'token' => $sessionToken
        ];
        echo encodeResponse($response);
        exit;
    }

    // Build query based on type
    if ($type === 'indonesia') {
        $sql = "
            SELECT
                c.id,
                c.name as channel,
                c.country,
                c.categories,
                c.network,
                GROUP_CONCAT(DISTINCT s.url) as urls
            FROM iptv_channels c
            LEFT JOIN iptv_streams s ON c.id = s.channel
            WHERE c.favorite_type = 'indonesia'
        ";

        if (!empty($search)) {
            $sql .= " AND c.name LIKE :search";
        }

        $sql .= "
            GROUP BY c.id
            ORDER BY c.name ASC
            LIMIT 1000
        ";

        $stmt = $pdo->prepare($sql);

        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

    } else {
        $sql = "
            SELECT
                c.id,
                c.name as channel,
                c.country,
                c.categories,
                c.network,
                GROUP_CONCAT(DISTINCT s.url) as urls
            FROM iptv_channels c
            LEFT JOIN iptv_streams s ON c.id = s.channel
            WHERE c.favorite_type = 'worldwide'
        ";

        if (!empty($search)) {
            $sql .= " AND c.name LIKE :search";
        }

        $sql .= "
            GROUP BY c.id
            ORDER BY c.name ASC
            LIMIT " . intval($limit)
        ;

        $stmt = $pdo->prepare($sql);

        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Define default logo
    $defaultLogo = APP_ICON ?: 'https://via.placeholder.com/150x150/4A90E2/FFFFFF?text=TV';

    // Process channels - get first URL and logo
    $processedChannels = [];
    foreach ($channels as $channel) {
        $urls = !empty($channel['urls']) ? explode(',', $channel['urls']) : [];
        $firstUrl = !empty($urls) ? $urls[0] : '';

        // Get logo for this channel with status check
        $logoStmt = $pdo->prepare("
            SELECT url, logo_status
            FROM iptv_logos
            WHERE channel = :channelId
            AND (logo_status = 'valid' OR logo_status IS NULL OR logo_status = 'pending')
            ORDER BY
                CASE WHEN logo_status = 'valid' THEN 1 ELSE 2 END,
                id ASC
            LIMIT 1
        ");
        $logoStmt->bindValue(':channelId', $channel['id'], PDO::PARAM_STR);
        $logoStmt->execute();
        $logoData = $logoStmt->fetch(PDO::FETCH_ASSOC);
        $logo = '';

        if ($logoData) {
            // Use logo if status is valid or if status is not yet checked (pending/null)
            if ($logoData['logo_status'] === 'valid' || $logoData['logo_status'] === null || $logoData['logo_status'] === 'pending') {
                $logo = $logoData['url'];
            }
        }

        // Use default logo if empty or invalid
        if (empty($logo)) {
            $logo = $defaultLogo;
        }

        $processedChannels[] = [
            'id' => $channel['id'],
            'channel' => $channel['channel'],
            'url' => $firstUrl,
            'country' => $channel['country'],
            'language' => '',
            'categories' => $channel['categories'],
            'logo' => $logo,
            'network' => $channel['network']
        ];
    }

    $obfuscatedChannels = [];

    foreach ($processedChannels as $index => $channel) {
        $obfuscatedChannels[] = [
            'd' => base64_encode(json_encode($channel)),
            'i' => $index,
            't' => $sessionToken
        ];
    }

    $response = [
        'error' => false,
        'type' => $type,
        'count' => count($processedChannels),
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
    $dateKey = gmdate('Ymd');
    $key = SECRET_KEY_PREFIX . $dateKey;
    $encoded = '';
    $keyLen = strlen($key);

    for ($i = 0; $i < strlen($json); $i++) {
        $encoded .= chr(ord($json[$i]) ^ ord($key[$i % $keyLen]));
    }

    $response = base64_encode($encoded);

    header('X-Date-Key: ' . $dateKey);

    return $response;
}
