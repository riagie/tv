<?php
/**
 * Load .env and define constants
 * Include this at the top of any file that needs config
 */

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Define constants from environment
define('APP_NAME', getenv('APP_NAME') ?: '');
define('APP_TITLE', getenv('APP_TITLE') ?: '');
define('APP_DESCRIPTION', getenv('APP_DESCRIPTION') ?: '');
define('APP_URL', getenv('APP_URL') ?: '');
define('APP_ICON', getenv('APP_ICON') ?: '');
define('DB_FILE', getenv('DB_FILE') ? __DIR__ . '/' . getenv('DB_FILE') : __DIR__ . '/tv.db');
define('API_RATE_LIMIT', (int)(getenv('API_RATE_LIMIT') ?: 100));
define('API_RATE_WINDOW', (int)(getenv('API_RATE_WINDOW') ?: 60));
define('ALLOWED_ORIGINS', array_map('trim', explode(',', getenv('ALLOWED_ORIGINS') ?: '')));
define('ADMIN_ENABLED', filter_var(getenv('ADMIN_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('ADMIN_PASSWORD_PROTECTED', filter_var(getenv('ADMIN_PASSWORD_PROTECTED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
