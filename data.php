<?php
/**
 * Data - Fetch TV channels from SQLite database
 */

// Load environment variables
require_once __DIR__ . '/loader.php';

// Define base path untuk kompatibilitas di hosting
$path = dirname(__FILE__);
$db = DB_FILE;
$channels = [];

try {
    // Check if database exists
    if (!file_exists($db)) {
        return [];
    }

    // Create SQLite database connection with optimizations
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Optimize SQLite performance
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA temp_store = MEMORY');
    $pdo->exec('PRAGMA mmap_size = 268435456');

    // Fetch all active channels ordered by name
    $stmt = $pdo->query("
        SELECT
            id,
            name as alt,
            url,
            image as img
        FROM channels
        WHERE status = 1
        ORDER BY name ASC
    ");

    $channels = $stmt->fetchAll();

} catch (PDOException $e) {
    $channels = [];
}

// Return channels array
return $channels;
