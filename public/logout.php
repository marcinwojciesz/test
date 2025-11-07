<?php
/**
 * Wylogowanie użytkownika - PRAWDZIWE WYLOGOWANIE
 */

// ZACZYNAMY OD CZYSTEJ KARTY - ZMIEŃ NAZWĘ SESJI
session_name('cms_portal_session_' . mt_rand(1000, 9999));
session_start();

// 1. WYCZYŚĆ WSZYSTKIE ZMIENNE SESJI
$_SESSION = array();

// 2. USUŃ CIASTECZKO SESJI - AGRESYWNIE
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    setcookie(session_name(), '', time() - 3600, "/"); // DODATKOWO dla całej domeny
}

// 3. ZNISZCZ SESJĘ
session_destroy();

// 4. WYCZYŚĆ ZMIENNĄ $_SESSION
unset($_SESSION);

// 5. WYMUŚ NOWĄ SESJĘ Z NOWYM ID
session_regenerate_id(true);

// 6. PRZEKIERUJ Z CACHE BUSTER
header('Location: http://localhost/cms_portal/?logout=' . time());
exit();
?>