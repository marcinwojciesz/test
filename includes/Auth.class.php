<?php
/**
 * Klasa autoryzacji - PROSTA WERSJA BEZ HASHOWANIA
 */
class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Logowanie użytkownika - PROSTA WERSJA
     */
    public function login($login, $password) {
        // Zabezpieczenie przed SQL Injection
        $login = trim($login);
        
        // PROSTE LOGOWANIE - bez hashowania
        $user = $this->db->selectOne(
            "SELECT u.*, GROUP_CONCAT(r.nazwa) as role 
             FROM uzytkownicy u 
             LEFT JOIN uzytkownicy_role ur ON u.id = ur.uzytkownik_id 
             LEFT JOIN role r ON ur.rola_id = r.id 
             WHERE u.login = ? AND u.haslo = ? AND u.aktywny = 1 
             GROUP BY u.id",
            [$login, $password]  // Bezpośrednie porównanie hasła
        );
        
        if ($user) {
            // Aktualizuj czas ostatniego logowania
            $this->db->execute(
                "UPDATE uzytkownicy SET ostatnie_logowanie = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Ustaw sesję użytkownika
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_imie'] = $user['imie'];
            $_SESSION['user_nazwisko'] = $user['nazwisko'];
            $_SESSION['user_role'] = $user['role'] ? explode(',', $user['role']) : [];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Wylogowanie użytkownika
     */
    public function logout() {
        session_unset();
        session_destroy();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Sprawdza czy użytkownik jest zalogowany
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Sprawdza czy użytkownik ma określoną rolę
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return in_array($role, $_SESSION['user_role']);
    }
    
    /**
     * Sprawdza czy użytkownik jest administratorem
     */
    public function isAdmin() {
        return $this->hasRole('administrator');
    }
    
    /**
     * Pobiera dane zalogowanego użytkownika
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'login' => $_SESSION['user_login'],
            'email' => $_SESSION['user_email'],
            'imie' => $_SESSION['user_imie'],
            'nazwisko' => $_SESSION['user_nazwisko'],
            'role' => $_SESSION['user_role']
        ];
    }
}
?>