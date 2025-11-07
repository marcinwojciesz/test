<?php
// Ustawienie kodowania UTF-8 dla polskich znaków
header('Content-Type: text/html; charset=utf-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Konfiguracja CMS Portal - z polskimi znakami
define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PREFIX', 'cms_');
define('SITE_URL', 'http://localhost/cms_portal');
define('UPLOAD_DIR', 'uploads/');
define('SECRET_KEY', 'generated_secret_key_placeholder');
define('INSTALLED', true);
?>