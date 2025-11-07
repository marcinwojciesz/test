<?php
/**
 * Ładuje konfigurację systemu - COMPATIBLE version
 */

// Sprawdź czy konfiguracja istnieje
$config_file = __DIR__ . '/../config.php';

if (file_exists($config_file)) {
    // Załaduj konfigurację
    require_once $config_file;
}

// Podstawowe ustawienia jeśli config.php nie istnieje
if (!defined('SITE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    define('SITE_URL', $base_url . $script_path);
}

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', 'uploads/');
}

// Debug info
if (!defined('INSTALLED')) {
    define('INSTALLED', false);
}
?>