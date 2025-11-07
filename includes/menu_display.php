<?php
/**
 * Wyświetlanie menu - CMS Portal
 */

function displayMenu($menu_name = 'Menu Główne') {
    // Połączenie z bazą danych
    $host = 'localhost';
    $dbname = 'cms_portal';
    $username = 'root';
    $password = '';
    
    try {
        $connection = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username, 
            $password,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        );
    } catch(PDOException $e) {
        return "<!-- Błąd połączenia z bazą -->";
    }
    
    // Pobierz menu
    try {
        $stmt = $connection->prepare("
            SELECT * FROM menus 
            WHERE name = ? AND is_active = 1
        ");
        $stmt->execute([$menu_name]);
        $menu = $stmt->fetch();
    } catch(PDOException $e) {
        return "<!-- Błąd ładowania menu -->";
    }
    
    if (!$menu) {
        return "<!-- Menu nie znalezione -->";
    }
    
    // Sprawdź uprawnienia dostępu
    $user_id = $_SESSION['user_id'] ?? null;
    $has_access = false;
    
    switch ($menu['access_level']) {
        case 'public':
            $has_access = true;
            break;
            
        case 'logged_in':
            $has_access = !is_null($user_id);
            break;
            
        case 'private':
            $has_access = !is_null($user_id);
            break;
    }
    
    if (!$has_access) {
        return "<!-- Brak dostępu do menu -->";
    }
    
    // Parsuj strukturę menu
    $menu_items = json_decode($menu['structure'], true) ?: [];
    
    if (empty($menu_items)) {
        return "<!-- Menu puste -->";
    }
    
    // Generuj HTML menu
    $html = '';
    
    if ($menu['menu_type'] === 'horizontal') {
        $html = '<ul class="nav-links">';
        foreach ($menu_items as $item) {
            $html .= '<li><a href="/cms_portal/page.php?slug=' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
        }
        $html .= '</ul>';
    } else {
        $html = '<ul class="nav-links vertical">';
        foreach ($menu_items as $item) {
            $html .= '<li><a href="/cms_portal/page.php?slug=' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
        }
        $html .= '</ul>';
    }
    
    return $html;
}
?>