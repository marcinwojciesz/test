<?php
/**
 * Konstruktor menu z podmenu - CMS Portal
 * Wielopoziomowe menu z drag & drop
 */

// Nagłówki i bezpieczeństwo
header('Content-Type: text/html; charset=utf-8');
session_start();

// Sprawdź czy zalogowany jako administrator
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit();
}

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
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}

// Sprawdź uprawnienia (autor menu lub administrator)
$user_id = $_SESSION['user_id'];
try {
    $stmt = $connection->prepare("
        SELECT role_id 
        FROM user_admin_roles 
        WHERE user_id = ? AND role_id IN (1, 3)
    ");
    $stmt->execute([$user_id]);
    $has_permission = $stmt->fetch();
} catch (PDOException $e) {
    die("Błąd podczas sprawdzania uprawnień: " . $e->getMessage());
}

if (!$has_permission) {
    die("
        <div style='padding: 2rem; text-align: center;'>
            <h2>❌ Brak uprawnień</h2>
            <p>Nie masz uprawnień do zarządzania menu.</p>
            <a href='index.php'>Powrót do panelu</a>
        </div>
    ");
}

// Zmienne
$message = '';
$error = '';

// OPERACJE NA MENU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ZAPISYWANIE MENU
    if (isset($_POST['save_menu'])) {
        $menu_id = $_POST['menu_id'] ?? null;
        $name = trim($_POST['name']);
        $menu_type = $_POST['menu_type'];
        $access_level = $_POST['access_level'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $has_dropdown = isset($_POST['has_dropdown']) ? 1 : 0;
        $structure = $_POST['menu_structure'] ?? '[]';
        
        // Walidacja
        if (empty($name)) {
            $error = "Nazwa menu jest wymagana!";
        } else {
            try {
                if ($menu_id) {
                    // Edycja istniejącego menu
                    $stmt = $connection->prepare("
                        UPDATE menus 
                        SET name = ?, menu_type = ?, access_level = ?, is_active = ?, has_dropdown = ?, structure = ?
                        WHERE id = ? AND created_by = ?
                    ");
                    $stmt->execute([$name, $menu_type, $access_level, $is_active, $has_dropdown, $structure, $menu_id, $user_id]);
                    $message = "Menu zostało zaktualizowane!";
                } else {
                    // Dodanie nowego menu
                    $stmt = $connection->prepare("
                        INSERT INTO menus (name, menu_type, access_level, is_active, has_dropdown, structure, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $menu_type, $access_level, $is_active, $has_dropdown, $structure, $user_id]);
                    $message = "Menu zostało pomyślnie utworzone!";
                }
            } catch (PDOException $e) {
                $error = "Błąd podczas zapisywania menu: " . $e->getMessage();
            }
        }
    }
    
    // USUWANIE MENU
    if (isset($_POST['delete_menu'])) {
        $menu_id_to_delete = $_POST['menu_id'];
        
        try {
            $stmt = $connection->prepare("DELETE FROM menus WHERE id = ? AND created_by = ?");
            $stmt->execute([$menu_id_to_delete, $user_id]);
            $message = "Menu zostało pomyślnie usunięte!";
        } catch (PDOException $e) {
            $error = "Błąd podczas usuwania menu: " . $e->getMessage();
        }
    }
}

// POBRANIE LISTY MENU
try {
    if ($has_permission['role_id'] == 1) {
        $stmt = $connection->prepare("
            SELECT m.*, u.username as author_name
            FROM menus m
            LEFT JOIN users u ON m.created_by = u.id
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $connection->prepare("
            SELECT m.*, u.username as author_name
            FROM menus m
            LEFT JOIN users u ON m.created_by = u.id
            WHERE m.created_by = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user_id]);
    }
    $menus = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Błąd podczas pobierania menu: " . $e->getMessage();
    $menus = [];
}

// Pobierz menu do edycji jeśli podano ID
$edit_menu = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $connection->prepare("
            SELECT * FROM menus 
            WHERE id = ? AND (created_by = ? OR ? IN (SELECT user_id FROM user_admin_roles WHERE role_id = 1))
        ");
        $stmt->execute([$_GET['edit'], $user_id, $user_id]);
        $edit_menu = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Błąd podczas ładowania menu: " . $e->getMessage();
    }
}

// Pobierz listę stron do podpinania w menu
try {
    $stmt = $connection->prepare("
        SELECT id, title, slug 
        FROM pages 
        WHERE is_published = 1 
        ORDER BY title
    ");
    $stmt->execute();
    $pages = $stmt->fetchAll();
} catch (PDOException $e) {
    $pages = [];
}

// Funkcje pomocnicze
function safe_echo($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function getMenuTypeLabel($type) {
    $labels = [
        'horizontal' => 'Poziome',
        'vertical' => 'Pionowe',
        'dropdown' => 'Rozwijane'
    ];
    return $labels[$type] ?? $type;
}

function getAccessLevelLabel($level) {
    $labels = [
        'public' => 'Publiczny',
        'logged_in' => 'Tylko zalogowani',
        'private' => 'Prywatny'
    ];
    return $labels[$level] ?? $level;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konstruktor menu - CMS Portal</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* SIDEBAR - ten sam co w index.php */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .sidebar-nav {
            padding: 1.5rem 0;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #cbd5e1;
            padding: 0 1.5rem 0.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            list-style: none;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--primary);
        }
        
        .nav-link.active {
            background: rgba(99, 102, 241, 0.1);
        }
        
        .admin-main {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .admin-header {
            height: 70px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.5rem 1rem;
            background: var(--light);
            border-radius: 12px;
            font-weight: 500;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .admin-content {
            flex: 1;
            padding: 2rem;
            background: #f8fafc;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        
        .alert-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            background: var(--light);
        }
        
        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
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
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .badge-primary {
            background: #e0e7ff;
            color: var(--primary-dark);
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .builder-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            height: 600px;
        }
        
        .pages-panel {
            background: #f8fafc;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .pages-panel h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .page-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: grab;
            transition: all 0.3s;
        }
        
        .page-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-item:active {
            cursor: grabbing;
        }
        
        .page-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .page-url {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        .menu-builder {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .menu-builder h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .menu-items {
            min-height: 400px;
            border: 2px dashed #d1d5db;
            border-radius: 6px;
            padding: 1rem;
        }
        
        .menu-item {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: move;
            transition: all 0.3s;
            position: relative;
        }
        
        .menu-item.has-children {
            background: #e2e8f0;
            border-left: 4px solid var(--primary);
        }
        
        .submenu {
            margin-left: 20px;
            margin-top: 0.5rem;
            border-left: 2px solid #cbd5e1;
            padding-left: 1rem;
        }
        
        .submenu .menu-item {
            background: #f8fafc;
            margin-left: 0;
        }
        
        .menu-item:hover {
            background: #e2e8f0;
        }
        
        .menu-item.sortable-ghost {
            opacity: 0.4;
        }
        
        .menu-item.sortable-chosen {
            background: #dbeafe;
            border-color: var(--primary);
        }
        
        .empty-state {
            text-align: center;
            color: var(--gray);
            padding: 3rem 1rem;
        }
        
        .menu-preview {
            background: #f8fafc;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .menu-preview h4 {
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .preview-horizontal {
            display: flex;
            gap: 2rem;
            list-style: none;
            position: relative;
        }
        
        .preview-horizontal li {
            padding: 0.5rem 1rem;
            position: relative;
        }
        
        .preview-horizontal .dropdown {
            position: relative;
        }
        
        .preview-horizontal .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            border-radius: 6px;
            min-width: 200px;
            z-index: 1000;
        }
        
        .preview-horizontal .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .preview-horizontal .dropdown-menu li {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .preview-horizontal .dropdown-menu li:last-child {
            border-bottom: none;
        }
        
        .preview-vertical {
            list-style: none;
        }
        
        .preview-vertical li {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .preview-vertical .dropdown-menu {
            margin-left: 1rem;
            display: none;
        }
        
        .preview-vertical .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .menu-actions {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 5px;
        }
        
        .menu-action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .menu-action-btn:hover {
            background: rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .submenu-indicator {
            display: inline-block;
            margin-left: 0.5rem;
            color: var(--gray);
            font-size: 0.75rem;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-cogs"></i></div>
                    <span class="logo-text">CMS Admin</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">GŁÓWNE</div>
                    <ul class="nav-links">
                        <li><a href="index.php" class="nav-link"><span>🏠</span><span>Dashboard</span></a></li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-title">ZARZĄDZANIE</div>
                    <ul class="nav-links">
                        <li><a href="users.php" class="nav-link"><span>👥</span><span>Użytkownicy</span></a></li>
                        <li><a href="pages.php" class="nav-link"><span>📄</span><span>Strony</span></a></li>
                        <li><a href="menu.php" class="nav-link active"><span>🔗</span><span>Menu</span></a></li>
                    </ul>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-title">
                    <h1>Panel Administratora</h1>
                </div>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span><?php echo $_SESSION['user_login'] ?? 'Administrator'; ?></span>
                    </div>
                    <a href="../" class="btn btn-outline">
                        <i class="fas fa-home"></i> Strona Główna
                    </a>
                    <a href="../public/logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Wyloguj
                    </a>
                </div>
            </header>

            <div class="admin-content">
                <!-- Nagłówek strony -->
                <div class="page-header">
                    <h1><i class="fas fa-bars"></i> Konstruktor menu z podmenu</h1>
                    <a href="menu.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nowe menu
                    </a>
                </div>

                <!-- Komunikaty -->
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php safe_echo($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php safe_echo($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Formularz edycji/dodawania menu -->
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-edit"></i> 
                            <?php echo $edit_menu ? 'Edycja menu' : 'Tworzenie nowego menu'; ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="menuForm">
                            <input type="hidden" name="menu_id" value="<?php echo $edit_menu['id'] ?? ''; ?>">
                            <input type="hidden" name="menu_structure" id="menu_structure" value='<?php echo $edit_menu['structure'] ?? '[]'; ?>'>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="name">Nazwa menu *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo safe_echo($edit_menu['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="menu_type">Typ menu</label>
                                    <select class="form-select" id="menu_type" name="menu_type">
                                        <option value="horizontal" <?php echo ($edit_menu['menu_type'] ?? 'horizontal') === 'horizontal' ? 'selected' : ''; ?>>Poziome</option>
                                        <option value="vertical" <?php echo ($edit_menu['menu_type'] ?? '') === 'vertical' ? 'selected' : ''; ?>>Pionowe</option>
                                        <option value="dropdown" <?php echo ($edit_menu['menu_type'] ?? '') === 'dropdown' ? 'selected' : ''; ?>>Rozwijane</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="access_level">Poziom dostępu</label>
                                    <select class="form-select" id="access_level" name="access_level">
                                        <option value="public" <?php echo ($edit_menu['access_level'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Publiczny - wszyscy</option>
                                        <option value="logged_in" <?php echo ($edit_menu['access_level'] ?? '') === 'logged_in' ? 'selected' : ''; ?>>Tylko zalogowani użytkownicy</option>
                                        <option value="private" <?php echo ($edit_menu['access_level'] ?? '') === 'private' ? 'selected' : ''; ?>>Prywatny - tylko wybrani użytkownicy</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_dropdown" value="1" 
                                               <?php echo ($edit_menu['has_dropdown'] ?? 0) ? 'checked' : ''; ?>>
                                        <span>Włącz podmenu (dropdown)</span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo ($edit_menu['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                        <span>Aktywne</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Konstruktor menu -->
                            <div class="form-group">
                                <label class="form-label">Konstruktor menu z podmenu</label>
                                <div class="builder-container">
                                    <!-- Panel dostępnych stron -->
                                    <div class="pages-panel">
                                        <h3><i class="fas fa-file"></i> Dostępne strony</h3>
                                        <?php if (empty($pages)): ?>
                                            <div class="empty-state">
                                                <i class="fas fa-folder-open"></i>
                                                <p>Brak opublikowanych stron</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($pages as $page): ?>
                                                <div class="page-item" draggable="true" 
                                                     data-page-id="<?php echo $page['id']; ?>"
                                                     data-page-title="<?php safe_echo($page['title']); ?>"
                                                     data-page-url="<?php safe_echo($page['slug']); ?>">
                                                    <div class="page-title"><?php safe_echo($page['title']); ?></div>
                                                    <div class="page-url">/<?php safe_echo($page['slug']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Custom menu items -->
                                        <div style="margin-top: 2rem;">
                                            <h4>Niestandardowe linki</h4>
                                            <div class="page-item" draggable="true" data-custom="true" data-page-title="Strona główna" data-page-url="">
                                                <div class="page-title">Strona główna</div>
                                                <div class="page-url">/index.php</div>
                                            </div>
                                            <div class="page-item" draggable="true" data-custom="true" data-page-title="Kontakt" data-page-url="kontakt">
                                                <div class="page-title">Kontakt</div>
                                                <div class="page-url">/kontakt</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Edytor menu -->
                                    <div class="menu-builder">
                                        <h3><i class="fas fa-sitemap"></i> Struktura menu</h3>
                                        <div class="menu-items" id="menuItems">
                                            <!-- Tutaj będą dodawane elementy menu -->
                                        </div>
                                        <div class="empty-state" id="emptyState">
                                            <i class="fas fa-arrows-alt"></i>
                                            <p>Przeciągnij strony tutaj aby dodać je do menu</p>
                                            <p><small>Przeciągnij na istniejący element aby dodać do podmenu</small></p>
                                        </div>
                                        
                                        <!-- Podgląd menu -->
                                        <div class="menu-preview">
                                            <h4><i class="fas fa-eye"></i> Podgląd</h4>
                                            <div id="menuPreview">
                                                <!-- Podgląd zostanie wygenerowany przez JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" name="save_menu" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?php echo $edit_menu ? 'Zaktualizuj menu' : 'Utwórz menu'; ?>
                                </button>
                                
                                <?php if ($edit_menu): ?>
                                    <a href="menu.php" class="btn btn-outline">Anuluj</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista menu -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Lista menu</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($menus)): ?>
                            <p>Brak menu w systemie.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nazwa</th>
                                            <th>Typ</th>
                                            <th>Podmenu</th>
                                            <th>Autor</th>
                                            <th>Dostęp</th>
                                            <th>Status</th>
                                            <th>Data utworzenia</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($menus as $menu): ?>
                                            <tr>
                                                <td><?php safe_echo($menu['id']); ?></td>
                                                <td>
                                                    <strong><?php safe_echo($menu['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo getMenuTypeLabel($menu['menu_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($menu['has_dropdown']): ?>
                                                        <span class="badge badge-success">Tak</span>
                                                    <?php else: ?>
                                                        <span class="badge">Nie</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php safe_echo($menu['author_name'] ?? 'Nieznany'); ?></td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?php echo getAccessLevelLabel($menu['access_level']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($menu['is_active']): ?>
                                                        <span class="badge badge-success">Aktywne</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-error">Nieaktywne</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($menu['created_at'])); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                        <a href="menu.php?edit=<?php echo $menu['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i> Edytuj
                                                        </a>
                                                        
                                                        <?php if ($menu['created_by'] == $user_id || $has_permission['role_id'] == 1): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="menu_id" value="<?php safe_echo($menu['id']); ?>">
                                                                <button type="submit" name="delete_menu" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('Czy na pewno chcesz usunąć to menu?')">
                                                                    <i class="fas fa-trash"></i> Usuń
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Inicjalizacja struktury menu
        let menuStructure = <?php echo $edit_menu['structure'] ?? '[]'; ?>;
        
        // Ładowanie istniejącej struktury menu
        function loadMenuStructure() {
            const menuItems = document.getElementById('menuItems');
            const emptyState = document.getElementById('emptyState');
            
            if (menuStructure.length === 0) {
                emptyState.style.display = 'block';
                menuItems.innerHTML = '';
                return;
            }
            
            emptyState.style.display = 'none';
            menuItems.innerHTML = '';
            
            menuStructure.forEach((item, index) => {
                const menuItem = createMenuItem(item, index);
                menuItems.appendChild(menuItem);
                
                // Ładowanie podmenu jeśli istnieje
                if (item.children && item.children.length > 0) {
                    const submenu = createSubmenu(item.children, index);
                    menuItem.appendChild(submenu);
                }
            });
            
            initSortable();
            updateMenuPreview();
            updateHiddenField();
        }
        
        // Tworzenie elementu menu
        function createMenuItem(item, index) {
            const div = document.createElement('div');
            div.className = item.children && item.children.length > 0 ? 'menu-item has-children' : 'menu-item';
            div.setAttribute('data-index', index);
            div.setAttribute('data-has-children', item.children && item.children.length > 0);
            
            const hasChildren = item.children && item.children.length > 0;
            const childrenCount = hasChildren ? item.children.length : 0;
            
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>${item.title}</strong>
                        <div style="font-size: 0.75rem; color: #64748b;">
                            /${item.url}
                            ${hasChildren ? `<span class="submenu-indicator">(${childrenCount} podstron)</span>` : ''}
                        </div>
                    </div>
                    <div class="menu-actions">
                        <button type="button" onclick="addSubmenu(${index})" class="menu-action-btn" title="Dodaj podmenu">
                            <i class="fas fa-plus"></i>
                        </button>
                        ${hasChildren ? `
                            <button type="button" onclick="removeSubmenu(${index})" class="menu-action-btn" title="Usuń podmenu">
                                <i class="fas fa-minus"></i>
                            </button>
                        ` : ''}
                        <button type="button" onclick="removeMenuItem(${index})" class="menu-action-btn" title="Usuń">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            return div;
        }
        
        // Tworzenie podmenu
        function createSubmenu(children, parentIndex) {
            const submenu = document.createElement('div');
            submenu.className = 'submenu';
            submenu.setAttribute('data-parent-index', parentIndex);
            
            children.forEach((child, childIndex) => {
                const childItem = document.createElement('div');
                childItem.className = 'menu-item';
                childItem.setAttribute('data-parent-index', parentIndex);
                childItem.setAttribute('data-child-index', childIndex);
                childItem.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${child.title}</strong>
                            <div style="font-size: 0.75rem; color: #64748b;">/${child.url}</div>
                        </div>
                        <div class="menu-actions">
                            <button type="button" onclick="removeSubmenuItem(${parentIndex}, ${childIndex})" class="menu-action-btn" title="Usuń">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
                submenu.appendChild(childItem);
            });
            
            return submenu;
        }
        
        // Dodawanie podmenu
        function addSubmenu(parentIndex) {
            if (!menuStructure[parentIndex].children) {
                menuStructure[parentIndex].children = [];
            }
            
            // Dodaj przykładowy element do podmenu
            menuStructure[parentIndex].children.push({
                id: 'sub-' + Date.now(),
                title: 'Nowa podstrona',
                url: 'podstrona',
                custom: true
            });
            
            loadMenuStructure();
        }
        
        // Usuwanie podmenu
        function removeSubmenu(parentIndex) {
            if (confirm('Czy na pewno chcesz usunąć całe podmenu?')) {
                menuStructure[parentIndex].children = [];
                loadMenuStructure();
            }
        }
        
        // Usuwanie pojedynczego elementu podmenu
        function removeSubmenuItem(parentIndex, childIndex) {
            if (confirm('Czy na pewno chcesz usunąć tę podstronę?')) {
                menuStructure[parentIndex].children.splice(childIndex, 1);
                loadMenuStructure();
            }
        }
        
        // Usuwanie elementu menu
        function removeMenuItem(index) {
            if (confirm('Czy na pewno chcesz usunąć ten element menu?')) {
                menuStructure.splice(index, 1);
                loadMenuStructure();
            }
        }
        
        // Inicjalizacja drag & drop
        function initSortable() {
            // Główne menu
            new Sortable(document.getElementById('menuItems'), {
                group: {
                    name: 'menu',
                    pull: true,
                    put: true
                },
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function(evt) {
                    const oldIndex = evt.oldIndex;
                    const newIndex = evt.newIndex;
                    
                    if (oldIndex !== newIndex) {
                        const [movedItem] = menuStructure.splice(oldIndex, 1);
                        menuStructure.splice(newIndex, 0, movedItem);
                        updateHiddenField();
                        updateMenuPreview();
                    }
                },
                onAdd: function(evt) {
                    // Obsługa dodawania do podmenu
                    const parentIndex = evt.to.getAttribute('data-parent-index');
                    if (parentIndex !== null) {
                        const itemData = JSON.parse(evt.item.getAttribute('data-item'));
                        if (!menuStructure[parentIndex].children) {
                            menuStructure[parentIndex].children = [];
                        }
                        menuStructure[parentIndex].children.push(itemData);
                        evt.item.remove();
                        loadMenuStructure();
                    }
                }
            });
            
            // Podmenu
            document.querySelectorAll('.submenu').forEach(submenu => {
                new Sortable(submenu, {
                    group: {
                        name: 'menu',
                        pull: true,
                        put: true
                    },
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen'
                });
            });
        }
        
        // Aktualizacja ukrytego pola formularza
        function updateHiddenField() {
            document.getElementById('menu_structure').value = JSON.stringify(menuStructure);
        }
        
        // Aktualizacja podglądu menu
        function updateMenuPreview() {
            const preview = document.getElementById('menuPreview');
            const menuType = document.getElementById('menu_type').value;
            const hasDropdown = document.querySelector('input[name="has_dropdown"]').checked;
            
            if (menuStructure.length === 0) {
                preview.innerHTML = '<p style="color: #64748b; text-align: center;">Brak elementów w menu</p>';
                return;
            }
            
            function generateMenuHTML(items, level = 0) {
                let html = level === 0 ? '<ul class="' + (menuType === 'horizontal' ? 'preview-horizontal' : 'preview-vertical') + '">' : '<ul class="dropdown-menu">';
                
                items.forEach(item => {
                    const hasChildren = item.children && item.children.length > 0;
                    
                    if (hasChildren && hasDropdown) {
                        html += `
                            <li class="dropdown">
                                <a href="#" style="text-decoration: none; color: #1e293b;">
                                    ${item.title} ▼
                                </a>
                                ${generateMenuHTML(item.children, level + 1)}
                            </li>
                        `;
                    } else {
                        html += `<li><a href="#" style="text-decoration: none; color: #1e293b;">${item.title}</a></li>`;
                    }
                });
                
                html += '</ul>';
                return html;
            }
            
            preview.innerHTML = generateMenuHTML(menuStructure);
        }
        
        // Obsługa przeciągania stron
        document.addEventListener('DOMContentLoaded', function() {
            loadMenuStructure();
            
            // Drag & drop dla stron
            document.querySelectorAll('.page-item').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    const itemData = {
                        id: this.dataset.pageId || 'custom-' + Date.now(),
                        title: this.dataset.pageTitle,
                        url: this.dataset.pageUrl,
                        custom: this.dataset.custom || false
                    };
                    e.dataTransfer.setData('text/plain', JSON.stringify(itemData));
                    this.setAttribute('data-item', JSON.stringify(itemData));
                });
            });
            
            // Drop w obszarze menu
            document.getElementById('menuItems').addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            document.getElementById('menuItems').addEventListener('drop', function(e) {
                e.preventDefault();
                const emptyState = document.getElementById('emptyState');
                emptyState.style.display = 'none';
                
                try {
                    const pageData = JSON.parse(e.dataTransfer.getData('text/plain'));
                    
                    // Sprawdź czy strona już istnieje w menu
                    const exists = menuStructure.some(item => item.id == pageData.id);
                    if (!exists) {
                        menuStructure.push({
                            ...pageData,
                            children: []
                        });
                        
                        loadMenuStructure();
                    }
                } catch (error) {
                    console.error('Błąd podczas dodawania strony do menu:', error);
                }
            });
            
            // Aktualizuj podgląd przy zmianie ustawień
            document.getElementById('menu_type').addEventListener('change', updateMenuPreview);
            document.querySelector('input[name="has_dropdown"]').addEventListener('change', updateMenuPreview);
        });
    </script>
</body>
</html>