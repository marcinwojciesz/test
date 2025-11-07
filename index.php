<?php
/**
 * GŁÓWNY PLIK SYSTEMU CMS PORTAL
 * Router - kieruje ruch do odpowiednich sekcji
 */

// Sprawdzamy czy sesja nie jest już rozpoczęta
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug informacji (możesz później usunąć)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Baza URL systemu
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$script_path = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $base_url . $script_path);

// Pobierz żądaną ścieżkę
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = $_SERVER['SCRIPT_NAME'];

// Usuń ścieżkę skryptu z URI
$clean_path = str_replace(dirname($script_name), '', $request_uri);
$path = trim($clean_path, '/');

// Debug ścieżki (tymczasowo)
// echo "Ścieżka: " . $path . "<br>";

// Podziel ścieżkę na segmenty
$path_segments = $path ? explode('/', $path) : [];

// Pierwszy segment określa sekcję
$section = $path_segments[0] ?? '';

// Routing
switch($section) {
    case 'admin':
        // Przekierowanie do panelu administratora
        $_SERVER['REQUEST_URI'] = str_replace('/admin', '', $_SERVER['REQUEST_URI']);
        require_once __DIR__ . '/admin/index.php';
        exit;
        break;
        
    case 'install':
        // Przekierowanie do instalatora
        $_SERVER['REQUEST_URI'] = str_replace('/install', '', $_SERVER['REQUEST_URI']);
        require_once __DIR__ . '/install/index.php';
        exit;
        break;
        
    default:
        // Domyślnie pokazujemy stronę publiczną
        require_once __DIR__ . '/public/index.php';
        exit;
        break;
}
?>