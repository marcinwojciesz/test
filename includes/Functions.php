<?php
/**
 * Podstawowe funkcje systemu CMS
 */

/**
 * Zabezpiecza tekst przed XSS
 */
function safe_input($data) {
    if (is_array($data)) {
        return array_map('safe_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Hashuje hasło
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Weryfikuje hasło
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Przekierowanie na podany URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Sprawdza czy użytkownik jest zalogowany
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Wyświetla komunikaty
 */
function show_message($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

/**
 * Wyświetla zapisane komunikaty
 */
function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $class = '';
        switch($message['type']) {
            case 'success': $class = 'alert-success'; break;
            case 'error': $class = 'alert-danger'; break;
            case 'warning': $class = 'alert-warning'; break;
            default: $class = 'alert-info';
        }
        echo '<div class="alert ' . $class . '">' . $message['text'] . '</div>';
        unset($_SESSION['message']);
    }
}

/**
 * Generuje losowy ciąg znaków
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Walidacja emaila
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formatuje datę
 */
function format_date($date, $format = 'd.m.Y H:i') {
    $datetime = new DateTime($date);
    return $datetime->format($format);
}
?>