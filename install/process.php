<?php
/**
 * Przetwarzanie instalacji CMS Portal - COMPATIBLE with older PHP
 */
session_start();

// Zabezpieczenie przed bezpośśrednim dostępem
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$krok = $_POST['krok'] ?? 1;

if ($krok == 2) {
    // KROK 2: Konfiguracja bazy danych
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'cms_portal';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_prefix = $_POST['db_prefix'] ?? 'cms_';
    
    // Walidacja danych
    if (empty($db_user)) {
        $_SESSION['install_error'] = 'Nazwa użytkownika bazy danych jest wymagana';
        header('Location: index.php?krok=2');
        exit();
    }
    
    try {
        // Próba połączenia z MySQL - COMPATIBLE version
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        
        // Ustawienia kompatybilności dla starszych PHP
        if (defined('PDO::ATTR_ERRMODE')) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        // Sprawdzenie czy baza istnieje, jeśli nie - utwórz
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
        $baza_istnieje = $stmt ? $stmt->fetch() : false;
        
        if (!$baza_istnieje) {
            $pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci");
        }
        
        // Przełącz na bazę danych
        $pdo->exec("USE `$db_name`");
        
        // Wczytaj i wykonaj SQL
        $sql = @file_get_contents('database.sql');
        if ($sql === false) {
            throw new Exception('Nie można wczytać pliku database.sql');
        }
        
        // Zamień prefixy jeśli podano
        if ($db_prefix !== 'cms_') {
            $sql = str_replace('cms_', $db_prefix, $sql);
        }
        
        // Wykonaj SQL - dziel na pojedyncze zapytania
        $zapytania = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($zapytania as $zapytanie) {
            if (!empty($zapytanie)) {
                $pdo->exec($zapytanie);
            }
        }
        
        // Generuj plik config.php - COMPATIBLE version
        $config_content = "<?php\n";
        $config_content .= "// Konfiguracja CMS Portal - wygenerowana automatycznie\n";
        $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
        $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
        $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
        $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";
        $config_content .= "define('DB_PREFIX', '" . addslashes($db_prefix) . "');\n";
        
        $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['SCRIPT_NAME']);
        $config_content .= "define('SITE_URL', '" . addslashes($site_url) . "');\n";
        
        $config_content .= "define('UPLOAD_DIR', 'uploads/');\n";
        $config_content .= "define('SECRET_KEY', '" . bin2hex(openssl_random_pseudo_bytes(32)) . "');\n";
        $config_content .= "define('INSTALLED', true);\n";
        $config_content .= "?>";
        
        // Zapisz plik config.php
        if (file_put_contents('../config.php', $config_content) === false) {
            throw new Exception('Nie można zapisać pliku config.php. Sprawdź uprawnienia do zapisu.');
        }
        
        // Tworzenie folderu uploads jeśli nie istnieje
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0755, true);
        }
        
        // Przekieruj do kroku 3
        header('Location: index.php?krok=3');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['install_error'] = 'Błąd bazy danych: ' . $e->getMessage();
        header('Location: index.php?krok=2');
        exit();
    } catch (PDOException $e) {
        $_SESSION['install_error'] = 'Błąd PDO: ' . $e->getMessage();
        header('Location: index.php?krok=2');
        exit();
    }
}

// Domyślne przekierowanie
header('Location: index.php');
?>