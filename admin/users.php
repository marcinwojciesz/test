<?php
/**
 * Zarządzanie użytkownikami - CMS Portal
 * Lista, dodawanie, edycja użytkowników i administratorów
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

// Sprawdź uprawnienia - uproszczona wersja
$user_id = $_SESSION['user_id'];
try {
    $stmt = $connection->prepare("
        SELECT role_id 
        FROM user_admin_roles 
        WHERE user_id = ? AND role_id IN (1, 4)
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
            <p>Nie masz uprawnień do zarządzania użytkownikami.</p>
            <a href='index.php'>Powrót do panelu</a>
        </div>
    ");
}

// Zmienne
$message = '';
$error = '';

// OPERACJE NA UŻYTKOWNIKACH
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DODAWANIE NOWEGO UŻYTKOWNIKA
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $user_type = $_POST['user_type'];
        $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

        // Walidacja
        if (empty($username) || empty($email) || empty($password)) {
            $error = "Wszystkie pola są wymagane!";
        } else {
            try {
                // Sprawdź czy użytkownik już istnieje
                $stmt = $connection->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->fetch()) {
                    $error = "Użytkownik o podanej nazwie lub emailu już istnieje!";
                } else {
                    // Hashowanie hasła
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Dodaj użytkownika
                    $stmt = $connection->prepare("
                        INSERT INTO users (username, password, email, user_type, is_active, created_at) 
                        VALUES (?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$username, $hashed_password, $email, $user_type]);
                    $new_user_id = $connection->lastInsertId();
                    
                    // Przypisz role jeśli to administrator
                    if ($user_type === 'admin' && !empty($roles)) {
                        foreach ($roles as $role_id) {
                            $stmt = $connection->prepare("
                                INSERT INTO user_admin_roles (user_id, role_id, assigned_at) 
                                VALUES (?, ?, NOW())
                            ");
                            $stmt->execute([$new_user_id, $role_id]);
                        }
                    }
                    
                    $message = "Użytkownik został pomyślnie dodany!";
                }
            } catch (PDOException $e) {
                $error = "Błąd podczas dodawania użytkownika: " . $e->getMessage();
            }
        }
    }
    
    // USUWANIE UŻYTKOWNIKA
    if (isset($_POST['delete_user'])) {
        $user_id_to_delete = $_POST['user_id'];
        
        // Nie można usunąć siebie
        if ($user_id_to_delete == $_SESSION['user_id']) {
            $error = "Nie możesz usunąć własnego konta!";
        } else {
            try {
                $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id_to_delete]);
                $message = "Użytkownik został pomyślnie usunięty!";
            } catch (PDOException $e) {
                $error = "Błąd podczas usuwania użytkownika: " . $e->getMessage();
            }
        }
    }
    
    // BLOKOWANIE/ODBLOKOWYWANIE UŻYTKOWNIKA
    if (isset($_POST['toggle_user'])) {
        $user_id_to_toggle = $_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        try {
            $stmt = $connection->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id_to_toggle]);
            $message = "Status użytkownika został zmieniony!";
        } catch (PDOException $e) {
            $error = "Błąd podczas zmiany statusu użytkownika: " . $e->getMessage();
        }
    }
    
    // EDYCJA RÓL UŻYTKOWNIKA
    if (isset($_POST['edit_roles'])) {
        $user_id_to_edit = $_POST['user_id'];
        $new_roles = isset($_POST['user_roles']) ? $_POST['user_roles'] : [];
        
        try {
            // Usuń stare role
            $stmt = $connection->prepare("DELETE FROM user_admin_roles WHERE user_id = ?");
            $stmt->execute([$user_id_to_edit]);
            
            // Dodaj nowe role
            if (!empty($new_roles)) {
                foreach ($new_roles as $role_id) {
                    $stmt = $connection->prepare("
                        INSERT INTO user_admin_roles (user_id, role_id, assigned_at) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$user_id_to_edit, $role_id]);
                }
            }
            
            $message = "Role użytkownika zostały zaktualizowane!";
        } catch (PDOException $e) {
            $error = "Błąd podczas aktualizacji ról: " . $e->getMessage();
        }
    }
}

// POBRANIE LISTY UŻYTKOWNIKÓW
try {
    $stmt = $connection->prepare("
        SELECT u.*, 
               GROUP_CONCAT(ar.name) as role_names,
               GROUP_CONCAT(ar.id) as role_ids
        FROM users u
        LEFT JOIN user_admin_roles uar ON u.id = uar.user_id
        LEFT JOIN admin_roles ar ON uar.role_id = ar.id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Błąd podczas pobierania użytkowników: " . $e->getMessage();
    $users = [];
}

// POBRANIE LISTY RÓL
try {
    $stmt = $connection->prepare("SELECT * FROM admin_roles ORDER BY id");
    $stmt->execute();
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
}

// Funkcje pomocnicze
function safe_echo($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function getUserTypeLabel($type) {
    $labels = [
        'admin' => 'Administrator',
        'site_user' => 'Użytkownik strony'
    ];
    return $labels[$type] ?? $type;
}

function getRoleDescription($role_id) {
    $descriptions = [
        1 => 'Pełny dostęp do systemu',
        2 => 'Tworzenie i edycja stron',
        3 => 'Tworzenie i zarządzanie menu',
        4 => 'Przydzielanie dostępu do stron i menu'
    ];
    return $descriptions[$role_id] ?? '';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie użytkownikami - CMS Portal</title>
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
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
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
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .checkbox-label:hover {
            background: #f3f4f6;
        }
        
        .role-description {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .close:hover {
            color: var(--dark);
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
                        <li><a href="users.php" class="nav-link active"><span>👥</span><span>Użytkownicy</span></a></li>
                        <li><a href="pages.php" class="nav-link"><span>📄</span><span>Strony</span></a></li>
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
                    <a href="../" class="btn btn-outline" style="background: transparent; color: var(--primary); border: 1px solid var(--primary);">
                        <i class="fas fa-home"></i> Strona Główna
                    </a>
                    <a href="../public/logout.php" class="btn btn-outline" style="background: transparent; color: var(--primary); border: 1px solid var(--primary);">
                        <i class="fas fa-sign-out-alt"></i> Wyloguj
                    </a>
                </div>
            </header>

            <div class="admin-content">
                <!-- Nagłówek strony -->
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> Zarządzanie użytkownikami</h1>
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

                <!-- Formularz dodawania użytkownika -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus"></i> Dodaj nowego użytkownika</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="username">Nazwa użytkownika *</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="email">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="password">Hasło *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="user_type">Typ użytkownika</label>
                                        <select class="form-control" id="user_type" name="user_type" required>
                                            <option value="site_user">Użytkownik strony</option>
                                            <option value="admin">Administrator</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" id="roles-section" style="display: none;">
                                        <label class="form-label">Role administratora</label>
                                        <div class="checkbox-group">
                                            <?php foreach ($roles as $role): ?>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>">
                                                    <span>
                                                        <?php safe_echo($role['name']); ?>
                                                        <div class="role-description"><?php echo getRoleDescription($role['id']); ?></div>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Dodaj użytkownika
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista użytkowników -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Lista użytkowników</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <p>Brak użytkowników w systemie.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nazwa użytkownika</th>
                                            <th>Email</th>
                                            <th>Typ</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Data rejestracji</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php safe_echo($user['id']); ?></td>
                                                <td>
                                                    <strong><?php safe_echo($user['username']); ?></strong>
                                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge badge-primary">Ty</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php safe_echo($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['user_type'] === 'admin' ? 'badge-warning' : 'badge-success'; ?>">
                                                        <?php echo getUserTypeLabel($user['user_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['role_names']): ?>
                                                        <?php 
                                                        $role_names = explode(',', $user['role_names']);
                                                        foreach ($role_names as $role): 
                                                        ?>
                                                            <span class="badge badge-info"><?php safe_echo(trim($role)); ?></span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="badge">Brak ról</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge badge-success">Aktywny</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-error">Zablokowany</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                        <!-- Edycja ról -->
                                                        <?php if ($user['user_type'] === 'admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-primary" onclick="openEditRolesModal(<?php echo $user['id']; ?>, '<?php safe_echo($user['username']); ?>', [<?php echo $user['role_ids'] ?? ''; ?>])">
                                                                <i class="fas fa-edit"></i> Role
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Blokowanie/odblokowywanie -->
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php safe_echo($user['id']); ?>">
                                                            <input type="hidden" name="current_status" value="<?php safe_echo($user['is_active']); ?>">
                                                            <button type="submit" name="toggle_user" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                                <i class="fas <?php echo $user['is_active'] ? 'fa-lock' : 'fa-unlock'; ?>"></i>
                                                                <?php echo $user['is_active'] ? 'Zablokuj' : 'Odblokuj'; ?>
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Usuwanie -->
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php safe_echo($user['id']); ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?')">
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

    <!-- Modal do edycji ról -->
    <div id="editRolesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edytuj role użytkownika</h3>
                <button class="close">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    <div class="form-group">
                        <label class="form-label">Wybierz role dla: <strong id="modal_username"></strong></label>
                        <div class="checkbox-group">
                            <?php foreach ($roles as $role): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="user_roles[]" value="<?php echo $role['id']; ?>" class="role-checkbox">
                                    <span>
                                        <?php safe_echo($role['name']); ?>
                                        <div class="role-description"><?php echo getRoleDescription($role['id']); ?></div>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEditRolesModal()">Anuluj</button>
                    <button type="submit" name="edit_roles" class="btn btn-primary">Zapisz zmiany</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Pokazuj/ukryj sekcję ról w zależności od typu użytkownika
        document.getElementById('user_type').addEventListener('change', function() {
            const rolesSection = document.getElementById('roles-section');
            rolesSection.style.display = this.value === 'admin' ? 'block' : 'none';
        });

        // Modal do edycji ról
        const modal = document.getElementById('editRolesModal');
        const closeBtn = document.querySelector('.close');

        function openEditRolesModal(userId, username, currentRoles) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_username').textContent = username;
            
            // Reset checkboxes
            document.querySelectorAll('.role-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check current roles
            if (currentRoles && currentRoles.length > 0) {
                currentRoles.forEach(roleId => {
                    const checkbox = document.querySelector(`.role-checkbox[value="${roleId}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            modal.style.display = 'block';
        }

        function closeEditRolesModal() {
            modal.style.display = 'none';
        }

        closeBtn.onclick = closeEditRolesModal;

        window.onclick = function(event) {
            if (event.target == modal) {
                closeEditRolesModal();
            }
        }
    </script>
</body>
</html>