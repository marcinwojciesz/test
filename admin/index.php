<?php
/**
 * Panel Administratora - POPRAWIONA WERSJA Z UTF-8
 */

// USTAWIENIA KODOWANIA UTF-8
header('Content-Type: text/html; charset=utf-8');
header('Content-Language: pl');
ini_set('default_charset', 'utf-8');

// Sesja z UTF-8
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ustawienie locale dla polskich znaków
setlocale(LC_ALL, 'pl_PL.UTF-8', 'polish_pol');

// SPRAWDZENIE CZY ZALOGOWANY - Z PRZEKIEROWANIEM DO LOGIN
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// JEŚLI NIEZALOGOWANY - PRZEKIERUJ DO LOGIN
if (!$is_logged_in) {
    header('Location: http://localhost/cms_portal/public/login.php');
    exit();
}

$current_user = $_SESSION['user_login'] ?? 'Administrator';

// Funkcja bezpiecznego wyświetlania polskich znaków
function safe_echo($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Prosty system ścieżek
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$BASE_URL = rtrim($base_url . $script_path, '/');

function asset_url($path) { global $BASE_URL; return $BASE_URL . '/assets/' . ltrim($path, '/'); }
function site_url($path = '') { global $BASE_URL; $path = ltrim($path, '/'); return $BASE_URL . ($path ? '/' . $path : ''); }
function admin_url($path = '') { return site_url('admin/' . ltrim($path, '/')); }
function public_url($path = '') { return site_url('public/' . ltrim($path, '/')); }

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="pl">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - CMS Portal</title>
    
    <style>
        /* NOWOCZESNY PANEL ADMINA - UTF-8 */
        :root { 
            --primary: #6366f1; 
            --primary-dark: #4f46e5; 
            --accent: #06b6d4; 
            --dark: #1e293b; 
            --darker: #0f172a; 
            --light: #f8fafc; 
            --gray: #64748b; 
            --gray-light: #cbd5e1; 
            --success: #10b981; 
            --error: #ef4444; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: var(--dark); line-height: 1.6; }
        
        .admin-container { display: flex; min-height: 100vh; }
        
        /* SIDEBAR */
        .admin-sidebar { width: 280px; background: linear-gradient(135deg, var(--darker) 0%, #1e293b 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 2rem 1.5rem 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .logo-text { font-size: 1.25rem; font-weight: 700; }
        .sidebar-nav { padding: 1.5rem 0; }
        .nav-section { margin-bottom: 1.5rem; }
        .nav-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-light); padding: 0 1.5rem 0.5rem; font-weight: 600; }
        .nav-links { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 0.75rem 1.5rem; color: var(--gray-light); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 255, 255, 0.05); color: white; border-left-color: var(--primary); }
        .nav-link.active { background: rgba(99, 102, 241, 0.1); }
        
        /* MAIN CONTENT */
        .admin-main { flex: 1; margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-header { height: 70px; background: white; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; }
        .header-title h1 { font-size: 1.5rem; font-weight: 700; color: var(--dark); }
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { display: flex; align-items: center; gap: 12px; padding: 0.5rem 1rem; background: var(--light); border-radius: 12px; font-weight: 500; }
        .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        
        /* CONTENT */
        .admin-content { flex: 1; padding: 2rem; background: #f8fafc; }
        .welcome-banner { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .welcome-banner h2 { font-size: 1.75rem; margin-bottom: 0.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-left: 4px solid var(--primary); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .stat-header { display: flex; align-items: center; margin-bottom: 1rem; }
        .stat-icon { width: 48px; height: 48px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--primary); }
        .stat-content h3 { font-size: 2rem; font-weight: 800; color: var(--dark); margin-bottom: 0.25rem; }
        .stat-content p { color: var(--gray); font-size: 0.875rem; }
        
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .feature-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transition: all 0.3s; border: 1px solid #f1f5f9; }
        .feature-card:hover { transform: translateY(-3px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border-color: var(--primary); }
        .feature-icon { width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; font-size: 1.5rem; color: white; }
        .feature-card h3 { font-size: 1.25rem; margin-bottom: 0.75rem; color: var(--dark); }
        .feature-card p { color: var(--gray); margin-bottom: 1.5rem; line-height: 1.6; }
        
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s; border: none; cursor: pointer; font-size: 0.875rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
        .btn-outline:hover { background: var(--primary); color: white; }
        
        .system-info { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); margin-top: 2rem; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .info-item { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
        .info-item:last-child { border-bottom: none; }
    </style>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="<?php echo admin_url(); ?>" class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-cogs"></i></div>
                    <span class="logo-text">CMS Admin</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">GŁÓWNE</div>
                    <ul class="nav-links">
                        <li><a href="index.php" class="nav-link active"><span>🏠</span><span>Dashboard</span></a></li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">ZARZĄDZANIE</div>
                    <ul class="nav-links">
                        <li><a href="users.php" class="nav-link"><span>👥</span><span>Użytkownicy</span></a></li>
                        <li><a href="pages.php" class="nav-link"><span>📄</span><span>Strony</span></a></li>
                        <li><a href="menu.php" class="nav-link"><span>🔗</span><span>Menu</span></a></li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">SYSTEM</div>
                    <ul class="nav-links">
                        <li><a href="settings.php" class="nav-link"><span>⚙️</span><span>Ustawienia</span></a></li>
                    </ul>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="header-title"><h1>Panel Administratora</h1></div>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <span><?php safe_echo($current_user); ?></span>
                    </div>
                    <a href="<?php echo site_url(); ?>" class="btn btn-outline"><i class="fas fa-home"></i>Strona Główna</a>
                    <a href="http://localhost/cms_portal/public/logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i>Wyloguj</a>
                </div>
            </header>

            <div class="admin-content">
                <div class="welcome-banner">
                    <h2>🎉 Witaj w Panelu Administratora!</h2>
                    <p>Status: <strong>ZALOGOWANY</strong></p>
                    <p>Zarządzaj całym systemem CMS z tego nowoczesnego panelu kontrolnego.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-content">
                            <h3>2</h3>
                            <p>Użytkowników</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-file"></i></div>
                        </div>
                        <div class="stat-content">
                            <h3>0</h3>
                            <p>Stron</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-bars"></i></div>
                        </div>
                        <div class="stat-content">
                            <h3>0</h3>
                            <p>Menu</p>
                        </div>
                    </div>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
                        <h3>Zarządzanie Użytkownikami</h3>
                        <p>Twórz role, nadawaj uprawnienia, zarządzaj dostępami użytkowników systemu.</p>
                        <a href="<?php echo admin_url('users.php'); ?>" class="btn btn-primary"><i class="fas fa-arrow-right"></i>Przejdź</a>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-edit"></i></div>
                        <h3>Edytor Stron</h3>
                        <p>Twórz i edytuj strony z zaawansowanym edytorem WYSIWYG. Importuj z Worda.</p>
                        <a href="<?php echo admin_url('pages.php'); ?>" class="btn btn-primary"><i class="fas fa-arrow-right"></i>Przejdź</a>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-sitemap"></i></div>
                        <h3>Konstruktor Menu</h3>
                        <p>Twórz wielopoziomowe menu z przeciąganiem i upuszczaniem. Pełna elastyczność.</p>
                        <a href="<?php echo admin_url('menu.php'); ?>" class="btn btn-primary"><i class="fas fa-arrow-right"></i>Przejdź</a>
                    </div>
                </div>

                <div class="system-info">
                    <h3>🔧 Informacje o Systemie</h3>
                    <div class="info-grid">
                        <div class="info-item"><span>Wersja CMS:</span><span>1.0</span></div>
                        <div class="info-item"><span>PHP:</span><span><?php echo phpversion(); ?></span></div>
                        <div class="info-item"><span>Użytkownik:</span><span><?php safe_echo($current_user); ?></span></div>
                        <div class="info-item"><span>Status:</span><span style="color: #10b981;">✅ Zalogowany</span></div>
                        <div class="info-item"><span>ID Sesji:</span><span><?php echo session_id(); ?></span></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>