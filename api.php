<?php
/**
 * API - IPTV Channels
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$type = $_GET['type'] ?? 'indonesia';
$search = $_GET['search'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;

// Define base path untuk kompatibilitas di hosting
$path = dirname(__FILE__);
$db = $path . '/tv.db';

try {
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if database has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM iptv_channels");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo json_encode([
            'error' => true,
            'message' => 'Database kosong. Jalankan import_iptv_combined.php terlebih dahulu!',
            'channels' => []
        ]);
        exit;
    }

    // Filter channels based on type
    if ($type === 'indonesia') {
        // Indonesia channels only
        $sql = "
            SELECT id, channel, url, country, language, categories,
                   COALESCE(logo_base64, logo) as logo
            FROM iptv_channels
            WHERE country = 'ID'
        ";

        if (!empty($search)) {
            $sql .= " AND channel LIKE :search";
        }

        $sql .= " ORDER BY channel ASC";

        if ($type === 'indonesia') {
            // Indonesia: show all (no limit or higher limit)
            $sql .= " LIMIT 1000";
        } else {
            $sql .= " LIMIT " . $limit;
        }

        $stmt = $pdo->prepare($sql);

        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->execute();
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Global channels (exclude Indonesia)
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

    echo json_encode([
        'error' => false,
        'type' => $type,
        'count' => count($channels),
        'channels' => $channels
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'channels' => []
    ]);
}
