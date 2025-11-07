<?php
/**
 * Edytor stron - CMS Portal
 * Tworzenie, edycja, zarządzanie stronami z systemem dostępu
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

// Sprawdź uprawnienia (autor stron lub administrator)
$user_id = $_SESSION['user_id'];
try {
    $stmt = $connection->prepare("
        SELECT role_id 
        FROM user_admin_roles 
        WHERE user_id = ? AND role_id IN (1, 2)
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
            <p>Nie masz uprawnień do zarządzania stronami.</p>
            <a href='index.php'>Powrót do panelu</a>
        </div>
    ");
}

// Zmienne
$message = '';
$error = '';

// OPERACJE NA STRONACH
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DODAWANIE/EDYCJA STRONY
    if (isset($_POST['save_page'])) {
        $page_id = $_POST['page_id'] ?? null;
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $content = $_POST['content'];
        $meta_description = trim($_POST['meta_description']);
        $access_level = $_POST['access_level'];
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        // Automatyczne generowanie sluga jeśli pusty
        if (empty($slug)) {
            $slug = generateSlug($title);
        } else {
            $slug = generateSlug($slug);
        }
        
        // Walidacja
        if (empty($title) || empty($content)) {
            $error = "Tytuł i treść są wymagane!";
        } else {
            try {
                // Sprawdź unikalność sluga
                $stmt = $connection->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $page_id]);
                
                if ($stmt->fetch()) {
                    $error = "Strona o podanym adresie URL już istnieje!";
                } else {
                    if ($page_id) {
                        // Edycja istniejącej strony
                        $stmt = $connection->prepare("
                            UPDATE pages 
                            SET title = ?, slug = ?, content = ?, meta_description = ?, 
                                access_level = ?, is_published = ?, updated_at = NOW()
                            WHERE id = ? AND author_id = ?
                        ");
                        $stmt->execute([$title, $slug, $content, $meta_description, $access_level, $is_published, $page_id, $user_id]);
                        $message = "Strona została zaktualizowana!";
                    } else {
                        // Dodanie nowej strony
                        $stmt = $connection->prepare("
                            INSERT INTO pages (title, slug, content, meta_description, author_id, access_level, is_published, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$title, $slug, $content, $meta_description, $user_id, $access_level, $is_published]);
                        $message = "Strona została pomyślnie utworzona!";
                    }
                }
            } catch (PDOException $e) {
                $error = "Błąd podczas zapisywania strony: " . $e->getMessage();
            }
        }
    }
    
    // USUWANIE STRONY
    if (isset($_POST['delete_page'])) {
        $page_id_to_delete = $_POST['page_id'];
        
        try {
            $stmt = $connection->prepare("DELETE FROM pages WHERE id = ? AND author_id = ?");
            $stmt->execute([$page_id_to_delete, $user_id]);
            $message = "Strona została pomyślnie usunięta!";
        } catch (PDOException $e) {
            $error = "Błąd podczas usuwania strony: " . $e->getMessage();
        }
    }
    
    // IMPORT Z WORDA
    if (isset($_POST['import_word'])) {
        $word_content = $_POST['word_content'];
        
        if (!empty($word_content)) {
            // Funkcja czyszczenia HTML z Worda
            $cleaned_content = cleanWordHTML($word_content);
            $_POST['content'] = $cleaned_content; // Wstaw do edytora
            $message = "Zawartość z Worda została zaimportowana i wyczyszczona!";
        } else {
            $error = "Proszę wkleić treść z Worda!";
        }
    }
}

// POBRANIE LISTY STRON
try {
    // Dla administratora - wszystkie strony, dla autora - tylko swoje
    if ($has_permission['role_id'] == 1) { // Administrator
        $stmt = $connection->prepare("
            SELECT p.*, u.username as author_name
            FROM pages p
            LEFT JOIN users u ON p.author_id = u.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
    } else { // Autor
        $stmt = $connection->prepare("
            SELECT p.*, u.username as author_name
            FROM pages p
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.author_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
    }
    $pages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Błąd podczas pobierania stron: " . $e->getMessage();
    $pages = [];
}

// Pobierz stronę do edycji jeśli podano ID
$edit_page = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $connection->prepare("
            SELECT * FROM pages 
            WHERE id = ? AND (author_id = ? OR ? IN (SELECT user_id FROM user_admin_roles WHERE role_id = 1))
        ");
        $stmt->execute([$_GET['edit'], $user_id, $user_id]);
        $edit_page = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Błąd podczas ładowania strony: " . $e->getMessage();
    }
}

// Funkcje pomocnicze
function safe_echo($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text;
}

function cleanWordHTML($html) {
    // Usuwanie niepotrzebnych tagów i stylów z Worda
    $html = preg_replace('/<o:p>.*?<\/o:p>/si', '', $html);
    $html = preg_replace('/<!--.*?-->/s', '', $html);
    $html = preg_replace('/<style>.*?<\/style>/si', '', $html);
    $html = preg_replace('/<meta[^>]*>/si', '', $html);
    $html = preg_replace('/<xml>.*?<\/xml>/si', '', $html);
    
    // Usuwanie niebezpiecznych atrybutów
    $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+lang="[^"]*"/i', '', $html);
    
    // Dozwolone tagi
    $allowed_tags = '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><u><ul><ol><li><a><img><table><tr><td><th><div><span>';
    $html = strip_tags($html, $allowed_tags);
    
    // Czyszczenie białych znaków
    $html = preg_replace('/\s+/', ' ', $html);
    $html = trim($html);
    
    return $html;
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
    <title>Edytor stron - CMS Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js"></script>
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .import-section {
            background: #f8fafc;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .import-section h4 {
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .textarea-import {
            width: 100%;
            min-height: 150px;
            padding: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.875rem;
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
                        <li><a href="pages.php" class="nav-link active"><span>📄</span><span>Strony</span></a></li>
                        <li><a href="menu.php" class="nav-link"><span>🔗</span><span>Menu</span></a></li>
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
                    <h1><i class="fas fa-file"></i> Edytor stron</h1>
                    <a href="pages.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nowa strona
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

                <!-- Formularz edycji/dodawania strony -->
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-edit"></i> 
                            <?php echo $edit_page ? 'Edycja strony' : 'Tworzenie nowej strony'; ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <!-- Sekcja importu z Worda -->
                        <div class="import-section">
                            <h4><i class="fas fa-file-word"></i> Import z Microsoft Word</h4>
                            <form method="POST" action="" style="margin-bottom: 1rem;">
                                <textarea name="word_content" class="textarea-import" placeholder="Wklej tutaj zawartość z Worda..."><?php echo $_POST['word_content'] ?? ''; ?></textarea>
                                <div style="margin-top: 1rem;">
                                    <button type="submit" name="import_word" class="btn btn-success">
                                        <i class="fas fa-file-import"></i> Importuj do edytora
                                    </button>
                                    <small style="color: var(--gray); margin-left: 1rem;">
                                        Uwaga: Formatowanie zostanie dostosowane do standardów HTML
                                    </small>
                                </div>
                            </form>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="page_id" value="<?php echo $edit_page['id'] ?? ''; ?>">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="title">Tytuł strony *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo safe_echo($edit_page['title'] ?? $_POST['title'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="slug">Adres URL (slug)</label>
                                    <input type="text" class="form-control" id="slug" name="slug" 
                                           value="<?php echo safe_echo($edit_page['slug'] ?? $_POST['slug'] ?? ''); ?>"
                                           placeholder="auto-generowany z tytułu">
                                    <small style="color: var(--gray);">Tylko litery, cyfry i myślniki</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="access_level">Poziom dostępu</label>
                                    <select class="form-select" id="access_level" name="access_level">
                                        <option value="public" <?php echo ($edit_page['access_level'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Publiczny - wszyscy</option>
                                        <option value="logged_in" <?php echo ($edit_page['access_level'] ?? '') === 'logged_in' ? 'selected' : ''; ?>>Tylko zalogowani użytkownicy</option>
                                        <option value="private" <?php echo ($edit_page['access_level'] ?? '') === 'private' ? 'selected' : ''; ?>>Prywatny - tylko wybrani użytkownicy</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_published" value="1" 
                                               <?php echo ($edit_page['is_published'] ?? 1) ? 'checked' : ''; ?>>
                                        <span>Opublikowana</span>
                                    </label>
                                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                                        Nieopublikowane strony są widoczne tylko w panelu administratora
                                    </small>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label" for="meta_description">Opis meta (SEO)</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" 
                                              rows="3" placeholder="Krótki opis strony dla wyszukiwarek..."><?php echo safe_echo($edit_page['meta_description'] ?? $_POST['meta_description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label" for="content">Treść strony *</label>
                                    <textarea class="form-control" id="content" name="content" rows="15" required><?php echo safe_echo($edit_page['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" name="save_page" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?php echo $edit_page ? 'Zaktualizuj stronę' : 'Utwórz stronę'; ?>
                                </button>
                                
                                <?php if ($edit_page): ?>
                                    <a href="pages.php" class="btn btn-outline">Anuluj</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista stron -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Lista stron</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pages)): ?>
                            <p>Brak stron w systemie.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tytuł</th>
                                            <th>Adres URL</th>
                                            <th>Autor</th>
                                            <th>Dostęp</th>
                                            <th>Status</th>
                                            <th>Data utworzenia</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pages as $page): ?>
                                            <tr>
                                                <td><?php safe_echo($page['id']); ?></td>
                                                <td>
                                                    <strong><?php safe_echo($page['title']); ?></strong>
                                                    <?php if (!$page['is_published']): ?>
                                                        <span class="badge badge-warning">Szkic</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code>/<?php safe_echo($page['slug']); ?></code>
                                                </td>
                                                <td><?php safe_echo($page['author_name'] ?? 'Nieznany'); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo getAccessLevelLabel($page['access_level']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($page['is_published']): ?>
                                                        <span class="badge badge-success">Opublikowana</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Szkic</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($page['created_at'])); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                        <a href="pages.php?edit=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i> Edytuj
                                                        </a>
                                                        
                                                        <?php if ($page['author_id'] == $user_id || $has_permission['role_id'] == 1): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="page_id" value="<?php safe_echo($page['id']); ?>">
                                                                <button type="submit" name="delete_page" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('Czy na pewno chcesz usunąć tę stronę?')">
                                                                    <i class="fas fa-trash"></i> Usuń
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <a href="../page.php?slug=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-sm btn-success">
                                                            <i class="fas fa-eye"></i> Podgląd
                                                        </a>
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
        // Inicjalizacja TinyMCE
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak code',
            toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code',
            height: 500,
            menubar: false,
            branding: false,
            language: 'pl',
            image_advtab: true,
            content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; }'
        });

        // Auto-generowanie sluga z tytułu
        document.getElementById('title').addEventListener('blur', function() {
            const slugField = document.getElementById('slug');
            if (!slugField.value) {
                // Prosta funkcja do tworzenia sluga
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^\w ]+/g, '')
                    .replace(/ +/g, '-');
                slugField.value = slug;
            }
        });
    </script>
</body>
</html>