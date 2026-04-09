<?php
declare(strict_types=1);

/**
 * API - IPTV Channels (Secured)
 */

// Load environment variables
require_once __DIR__ . '/loader.php';

// Security Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_HOST'] ?? '*'));
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Security: Only allow same-origin requests
$allowedOrigins = ALLOWED_ORIGINS;

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = false;

foreach ($allowedOrigins as $allowedOrigin) {
    if (strpos($origin, $allowedOrigin) === 0) {
        $allowed = true;
        header('Access-Control-Allow-Origin: ' . $origin);
        break;
    }
}

// Additional security checks
if (!$allowed && $origin !== '') {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Forbidden']);
    exit;
}

// Rate limiting (simple implementation)
session_start();
$rateLimitKey = 'api_rate_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$rateLimitWindow = API_RATE_WINDOW; // seconds
$rateLimitMax = API_RATE_LIMIT; // max requests per minute

if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'time' => time()];
}

$timeDiff = time() - $_SESSION[$rateLimitKey]['time'];
if ($timeDiff > $rateLimitWindow) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'time' => time()];
}

if ($_SESSION[$rateLimitKey]['count'] >= $rateLimitMax) {
    http_response_code(429);
    echo json_encode(['error' => true, 'message' => 'Too many requests']);
    exit;
}

$_SESSION[$rateLimitKey]['count']++;

// Validate referer (if exists)
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $refererHost = $referer['host'] ?? '';

    $isValidReferer = false;
    foreach ($allowedOrigins as $allowedOrigin) {
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

// Basic bot detection
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$blockedAgents = ['bot', 'crawl', 'spider', 'scraper', 'curl', 'wget'];
foreach ($blockedAgents as $blocked) {
    if (stripos($userAgent, $blocked) !== false) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Access denied']);
        exit;
    }
}

// Validate parameters
$type = $_GET['type'] ?? 'indonesia';
$search = $_GET['search'] ?? '';
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 1000;

// Validate type parameter
$allowedTypes = ['indonesia', 'global'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Invalid type parameter']);
    exit;
}

// Sanitize search parameter
$search = substr(trim($search), 0, 50); // Max 50 chars
if (!preg_match('/^[a-zA-Z0-9\s\-_]*$/', $search)) {
    $search = '';
}

// Define base path untuk kompatibilitas di hosting
$path = dirname(__FILE__);
$db = DB_FILE;

try {
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Optimize SQLite performance
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA temp_store = MEMORY');
    $pdo->exec('PRAGMA mmap_size = 268435456');

    // Check if database has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM iptv_channels");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo json_encode([
            'error' => true,
            'message' => 'No channels available',
            'channels' => []
        ]);
        exit;
    }

    // Filter channels based on type
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

    // Obfuscate response (make it harder to scrape)
    $response = json_encode([
        'error' => false,
        'type' => $type,
        'count' => count($channels),
        'channels' => $channels,
        '_token' => bin2hex(random_bytes(16)) // Add random token
    ]);

    // Add anti-caching headers for sensitive data
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $response;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Internal server error',
        'channels' => []
    ]);
}
