<?php
/**
 * Data - Fetch TV channels from SQLite database
 */

// Define base path untuk kompatibilitas di hosting
$path = dirname(__FILE__);
$db = $path . '/tv.db';
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

    // Fetch all channels ordered by name
    $stmt = $pdo->query("
        SELECT
            id,
            name as alt,
            url,
            image as img
        FROM channels
        ORDER BY name ASC
    ");

    $channels = $stmt->fetchAll();

} catch (PDOException $e) {
    // If all fails, return empty array
    $channels = [];
}

// Return channels array
return $channels;
