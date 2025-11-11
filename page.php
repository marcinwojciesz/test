<?php
/**
 * Wyświetlanie pojedynczej strony - CMS Portal
 */

// Nagłówki i bezpieczeństwo
header('Content-Type: text/html; charset=utf-8');
session_start();

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
    die("Błąd połączenia z bazą danych.");
}

// Pobierz slug z URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

// Pobierz stronę z bazy
try {
    $stmt = $connection->prepare("
        SELECT p.*, u.username as author_name 
        FROM pages p 
        LEFT JOIN users u ON p.author_id = u.id 
        WHERE p.slug = ? AND p.is_published = 1
    ");
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
} catch (PDOException $e) {
    die("Błąd podczas ładowania strony.");
}

// Sprawdź czy strona istnieje
if (!$page) {
    header('HTTP/1.0 404 Not Found');
    die("Strona nie istnieje.");
}

// Sprawdź uprawnienia dostępu
$user_id = $_SESSION['user_id'] ?? null;
$has_access = false;

switch ($page['access_level']) {
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
    header('Location: login.php');
    exit();
}

// Pobierz menu główne
try {
    $stmt = $connection->prepare("
        SELECT * FROM menus 
        WHERE name = 'Menu Główne' AND is_active = 1
    ");
    $stmt->execute();
    $menu = $stmt->fetch();
    $menu_items = $menu ? json_decode($menu['structure'], true) : [];
    $has_dropdown = $menu ? $menu['has_dropdown'] : false;
} catch(PDOException $e) {
    $menu_items = [];
    $has_dropdown = false;
}

// Funkcje pomocnicze
function safe_echo($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Funkcja do generowania menu HTML
function generateMenuHTML($items, $has_dropdown = false, $level = 0) {
    $html = '';
    
    foreach ($items as $item) {
        $has_children = !empty($item['children']);
        $url = !empty($item['url']) ? "page.php?slug=" . htmlspecialchars($item['url']) : "index.php";
        
        if ($has_children && $has_dropdown && $level === 0) {
            // Menu z dropdown
            $html .= '
            <li class="dropdown">
                <a href="' . $url . '" style="text-decoration: none; color: #64748b; font-weight: 500; transition: color 0.3s;">
                    ' . safe_echo($item['title']) . ' ▼
                </a>
                <ul class="dropdown-menu">';
            
            foreach ($item['children'] as $child) {
                $child_url = !empty($child['url']) ? "page.php?slug=" . htmlspecialchars($child['url']) : "index.php";
                $html .= '
                    <li>
                        <a href="' . $child_url . '" style="text-decoration: none; color: #64748b; font-weight: 500; transition: color 0.3s;">
                            ' . safe_echo($child['title']) . '
                        </a>
                    </li>';
            }
            
            $html .= '
                </ul>
            </li>';
        } else {
            // Zwykły element menu
            $html .= '
            <li>
                <a href="' . $url . '" style="text-decoration: none; color: #64748b; font-weight: 500; transition: color 0.3s;">
                    ' . safe_echo($item['title']) . '
                </a>
            </li>';
        }
    }
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php safe_echo($page['title']); ?> - CMS Portal</title>
    <meta name="description" content="<?php safe_echo($page['meta_description']); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: #f8fafc;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #6366f1;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            position: relative;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #6366f1;
        }
        
        /* Dropdown menu styles */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            border-radius: 6px;
            min-width: 200px;
            z-index: 1000;
            list-style: none;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-menu li {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .dropdown-menu li:last-child {
            border-bottom: none;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 0.5rem 0;
        }
        
        .page-content {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2rem;
        }
        
        .page-body {
            font-size: 1.125rem;
            line-height: 1.8;
        }
        
        .page-body h1, .page-body h2, .page-body h3 {
            margin: 2rem 0 1rem 0;
            color: #1e293b;
        }
        
        .page-body p {
            margin-bottom: 1.5rem;
        }
        
        .page-body ul, .page-body ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }
        
        .page-body li {
            margin-bottom: 0.5rem;
        }
        
        .footer {
            text-align: center;
            padding: 2rem 0;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            margin-top: 3rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn-outline {
            background: transparent;
            color: #6366f1;
            border: 1px solid #6366f1;
        }
        
        .btn-outline:hover {
            background: #6366f1;
            color: white;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Nagłówek -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="index.php" class="logo">
                    <i class="fas fa-cogs"></i> CMS Portal
                </a>
                
                <!-- MENU GŁÓWNE -->
                <?php if (!empty($menu_items)): ?>
                    <ul class="nav-links">
                        <?php echo generateMenuHTML($menu_items, $has_dropdown); ?>
                    </ul>
                <?php else: ?>
                    <!-- Domyślne menu jeśli nie ma skonfigurowanego -->
                    <ul class="nav-links">
                        <li><a href="index.php">Strona Główna</a></li>
                        <li><a href="page.php?slug=o-nas">O Nas</a></li>
                        <li><a href="page.php?slug=kontakt">Kontakt</a></li>
                    </ul>
                <?php endif; ?>
                
                <?php if ($user_id): ?>
                    <div class="user-menu">
                        <span>Witaj, <?php echo $_SESSION['user_login'] ?? 'Użytkowniku'; ?></span>
                        <a href="public/logout.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Wyloguj
                        </a>
                    </div>
                <?php else: ?>
                    <div class="user-menu">
                        <a href="public/login.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-sign-in-alt"></i> Zaloguj
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Główna zawartość -->
    <main class="container">
        <article class="page-content">
            <header class="page-header">
                <h1 class="page-title"><?php safe_echo($page['title']); ?></h1>
            </header>
            
            <div class="page-body">
                <?php echo $page['content']; ?>
            </div>
        </article>
        
        <!-- Link powrotu -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Powrót do strony głównej
            </a>
            
            <?php if ($user_id): ?>
                <a href="admin/pages.php?edit=<?php echo $page['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-edit"></i> Edytuj stronę
                </a>
            <?php endif; ?>
        </div>
    </main>

    <!-- Stopka -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 CMS Portal. Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>
</body>
</html>