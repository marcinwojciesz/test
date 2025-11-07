<?php
/**
 * Zabezpieczenie panelu administratora
 */

header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: http://localhost/cms_portal/public/login.php');
    exit();
}

// Funkcja bezpiecznego wyjścia
function secure_exit($message = 'Access denied') {
    die(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
}
?>