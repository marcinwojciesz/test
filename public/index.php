<?php
/**
 * Strona główna - publiczna - NOWOCZESNY WYGLĄD
 */


// Ładujemy pomocnik ścieżek
require_once __DIR__ . '/../includes/PathHelper.php';


// WYMUS WYLOGOWANIE JEŚLI JEST W URL
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    unset($_SESSION);
}

// Ładujemy funkcje jeśli plik istnieje
if (file_exists(__DIR__ . '/../includes/Functions.php')) {
    require_once __DIR__ . '/../includes/Functions.php';
}

// Tymczasowa funkcja jeśli Files.php nie istnieje
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

$page_title = "Nowoczesny CMS Portal";

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - System Zarządzania Treścią</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="<?php echo url(); ?>" class="logo">
                    <div class="logo-icon">CMS</div>
                    <span>Portal</span>
                </a>
                <nav>
                    <a href="<?php echo url(); ?>" class="nav-link active">Strona Główna</a>
                    <a href="<?php echo public_url('login.php'); ?>" class="nav-link">Logowanie</a>
                    <a href="<?php echo public_url('register.php'); ?>" class="nav-link">Rejestracja</a>
                    <a href="<?php echo admin_url(); ?>" class="nav-link">Panel Admina</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Nowoczesny System CMS</h1>
                <p>Twórz, zarządzaj i publikuj treści z łatwością. Nasz system oferuje intuicyjny interfejs i zaawansowane funkcje dla profesjonalistów.</p>
                <div class="cta-buttons">
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo admin_url(); ?>" class="btn btn-primary">🧑‍💼 Przejdź do Panelu</a>
                        <a href="<?php echo public_url('profile.php'); ?>" class="btn btn-secondary">👤 Mój Profil</a>
                    <?php else: ?>
                        <a href="<?php echo public_url('register.php'); ?>" class="btn btn-primary">🚀 Rozpocznij Teraz</a>
                        <a href="<?php echo public_url('login.php'); ?>" class="btn btn-secondary">🔐 Zaloguj Się</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="container">
            <div class="content-grid">
                <div class="feature-card">
                    <div class="feature-icon">📄</div>
                    <h3>Zaawansowany Edytor</h3>
                    <p>Twórz piękne strony z naszym intuicyjnym edytorem WYSIWYG. Importuj treści bezpośrednio z Worda.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🧩</div>
                    <h3>Modułowe Menu</h3>
                    <p>Twórz wielopoziomowe menu z przeciąganiem i upuszczaniem. Poziome, pionowe, dropdown - pełna elastyczność.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Bezpieczny System</h3>
                    <p>Zaawansowane zabezpieczenia przed atakami SQL Injection, XSS i CSRF. Twoje dane są bezpieczne.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>System Ról</h3>
                    <p>Elastyczny system uprawnień z wieloma rolami. Administrator, Autor, Edytor Menu - pełna kontrola dostępu.</p>
                </div>
            </div>

            <?php if (is_logged_in()): ?>
                <div class="user-panel">
                    <h3>👋 Witaj z powrotem!</h3>
                    <p>Jesteś zalogowany jako: <strong><?php echo $_SESSION['user_login'] ?? 'Użytkownik'; ?></strong></p>
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="<?php echo admin_url(); ?>" class="btn btn-primary">🧑‍💼 Panel Administracji</a>
                        <a href="<?php echo public_url('profile.php'); ?>" class="btn btn-secondary">👤 Edytuj Profil</a>
                        <a href="<?php echo public_url('logout.php'); ?>" class="btn btn-secondary">🚪 Wyloguj</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="guest-panel">
                    <h3>💡 Dołącz do nas!</h3>
                    <p>Zarejestruj się, aby uzyskać dostęp do wszystkich funkcji systemu zarządzania treścią.</p>
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="<?php echo public_url('register.php'); ?>" class="btn btn-primary">📝 Zarejestruj Się</a>
                        <a href="<?php echo public_url('login.php'); ?>" class="btn btn-secondary">🔐 Zaloguj Się</a>
                        <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">👀 Demo Panelu</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>CMS Portal</h4>
                    <p>Nowoczesny system zarządzania treścią zaprojektowany z myślą o profesjonalistach.</p>
                </div>
                <div class="footer-section">
                    <h4>Nawigacja</h4>
                    <a href="<?php echo url(); ?>">Strona Główna</a>
                    <a href="<?php echo admin_url(); ?>">Panel Admina</a>
                    <a href="<?php echo public_url('login.php'); ?>">Logowanie</a>
                    <a href="<?php echo public_url('register.php'); ?>">Rejestracja</a>
                </div>
                <div class="footer-section">
                    <h4>Funkcje</h4>
                    <a href="#">Edytor Stron</a>
                    <a href="#">Konstruktor Menu</a>
                    <a href="#">System Ról</a>
                    <a href="#">Bezpieczeństwo</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> CMS Portal. Wszelkie prawa zastrzeżone. | Wersja: 1.0 Development</p>
            </div>
        </div>
    </footer>
</body>
</html>