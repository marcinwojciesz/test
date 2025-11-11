<?php
/**
 * Pomocnik ścieżek - POPRAWIONY BEZ BŁĘDU SITE_URL
 */

// Ładuj konfigurację jeśli plik istnieje
$config_file = __DIR__ . '/../config.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

// SPRAWDŹ I NAPRAW SITE_URL JEŻELI ZŁE
if (defined('SITE_URL')) {
    // Jeśli SITE_URL zawiera /install - napraw
    if (strpos(SITE_URL, '/install') !== false) {
        $fixed_url = str_replace('/install', '', SITE_URL);
        // Nie definiuj ponownie, użyj zmiennej
        $final_site_url = $fixed_url;
    } else {
        $final_site_url = SITE_URL;
    }
} else {
    // Jeśli SITE_URL nie jest zdefiniowane - utwórz
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $final_site_url = rtrim($base_url . $script_path, '/');
}

// Użyj poprawnego URL (bez define jeśli już istnieje)
if (!defined('BASE_URL')) {
    define('BASE_URL', $final_site_url);
}

if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', $final_site_url . '/assets');
}

// Ustaw SITE_URL tylko jeśli nie jest zdefiniowane
if (!defined('SITE_URL')) {
    define('SITE_URL', $final_site_url);
}

/**
 * Zwraca poprawną ścieżkę do assetów
 */
function asset($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Zwraca poprawną ścieżkę URL
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return SITE_URL . ($path ? '/' . $path : '');
}

/**
 * Zwraca poprawną ścieżkę do admina
 */
function admin_url($path = '') {
    return url('admin/' . ltrim($path, '/'));
}

/**
 * Zwraca poprawną ścieżkę do public
 */
function public_url($path = '') {
    return url('public/' . ltrim($path, '/'));
}

/**
 * Sprawdza czy system jest zainstalowany
 */
function is_installed() {
    return defined('INSTALLED') && INSTALLED === true;
}
?>