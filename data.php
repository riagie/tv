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

    // Create SQLite database connection
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If all fails, return empty array
    $channels = [];
}

// Return channels array
return $channels;
