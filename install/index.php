<?php
/**
 * Instalator CMS Portal - Krok 1: Sprawdzenie wymagań
 */
session_start();

// Sprawdź czy system jest już zainstalowany
if (file_exists('../config.php')) {
    header('Location: ../admin/');
    exit();
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$BASE_URL = $base_url . $script_path;

$krok = $_GET['krok'] ?? 1;
$blad = $_SESSION['install_error'] ?? '';
unset($_SESSION['install_error']);

// Sprawdzenie wymagań systemowych
$wymagania = [
    'php_version' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'gd' => true, // tymczasowo wylaczone('gd'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
    'uploads' => ini_get('file_uploads'),
    'write_perms' => is_writable('../') && is_writable('../uploads')
];

$wszystkie_wymagania_spelnione = !in_array(false, $wymagania, true);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalacja CMS Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #6366f1 0%, #06b6d4 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .install-steps {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            font-weight: 500;
            color: #64748b;
            border-bottom: 3px solid transparent;
        }
        
        .step.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
            background: white;
        }
        
        .install-content {
            padding: 2rem;
        }
        
        .requirements-list {
            margin: 1.5rem 0;
        }
        
        .requirement {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .requirement:last-child {
            border-bottom: none;
        }
        
        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-ok {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-error {
            background: #fecaca;
            color: #dc2626;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .progress-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #6366f1;
            transition: width 0.3s;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🧩 Instalacja CMS Portal</h1>
            <p>Krok <?php echo $krok; ?> z 3</p>
        </div>
        
        <div class="install-steps">
            <div class="step <?php echo $krok == 1 ? 'active' : ''; ?>">1. Wymagania</div>
            <div class="step <?php echo $krok == 2 ? 'active' : ''; ?>">2. Konfiguracja</div>
            <div class="step <?php echo $krok == 3 ? 'active' : ''; ?>">3. Gotowe</div>
        </div>
        
        <div class="install-content">
            <?php if ($blad): ?>
                <div class="alert alert-error">
                    <strong>Błąd:</strong> <?php echo $blad; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($krok == 1): ?>
                <!-- KROK 1: Sprawdzenie wymagań -->
                <h2>Sprawdzenie wymagań systemowych</h2>
                <p>Przed instalacją sprawdzimy czy Twój serwer spełnia wszystkie wymagania.</p>
                
                <div class="requirements-list">
                    <div class="requirement">
                        <span>PHP w wersji 8.0 lub nowszej</span>
                        <span class="status <?php echo $wymagania['php_version'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo PHP_VERSION; ?>
                        </span>
                    </div>
                    
                    <div class="requirement">
                        <span>Rozszerzenie PDO MySQL</span>
                        <span class="status <?php echo $wymagania['pdo_mysql'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $wymagania['pdo_mysql'] ? 'Dostępne' : 'Brak'; ?>
                        </span>
                    </div>
                    
                    <div class="requirement">
                        <span>Rozszerzenie GD (obrazki)</span>
                        <span class="status <?php echo $wymagania['gd'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $wymagania['gd'] ? 'Dostępne' : 'Brak'; ?>
                        </span>
                    </div>
                    
                    <div class="requirement">
                        <span>Rozszerzenie JSON</span>
                        <span class="status <?php echo $wymagania['json'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $wymagania['json'] ? 'Dostępne' : 'Brak'; ?>
                        </span>
                    </div>
                    
                    <div class="requirement">
                        <span>Rozszerzenie MBString</span>
                        <span class="status <?php echo $wymagania['mbstring'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $wymagania['mbstring'] ? 'Dostępne' : 'Brak'; ?>
                        </span>
                    </div>
                    
                    <div class="requirement">
                        <span>Upload plików włączony</span>
                        <span class="status <?php echo $wymagania['uploads'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $wymagania['uploads'] ? 'Tak' : 'Nie'; ?>
                        </span>
                    </div>
                    
                    <div class="requirement">
                        <span>Uprawnienia do zapisu</span>
                        <span class="status <?php echo $wymagania['write_perms'] ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $wymagania['write_perms'] ? 'Zapis możliwy' : 'Brak uprawnień'; ?>
                        </span>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <?php if ($wszystkie_wymagania_spelnione): ?>
                        <a href="?krok=2" class="btn btn-primary">
                            ✅ Kontynuuj instalację
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled>
                            ❌ Napraw wymagania aby kontynuować
                        </button>
                        <p style="margin-top: 1rem; color: #64748b;">
                            Skontaktuj się z administratorem serwera w celu spełnienia wymagań.
                        </p>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($krok == 2): ?>
                <!-- KROK 2: Konfiguracja bazy danych -->
                <h2>Konfiguracja bazy danych</h2>
                <p>Podaj dane dostępowe do bazy danych MySQL.</p>
                
                <form action="process.php" method="POST">
                    <input type="hidden" name="krok" value="2">
                    
                    <div class="form-group">
                        <label class="form-label">Serwer MySQL</label>
                        <input type="text" name="db_host" value="localhost" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nazwa bazy danych</label>
                        <input type="text" name="db_name" value="cms_portal" class="form-input" required>
                        <small style="color: #64748b;">Baza zostanie utworzona jeśli nie istnieje</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Użytkownik MySQL</label>
                        <input type="text" name="db_user" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Hasło MySQL</label>
                        <input type="password" name="db_pass" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Prefiks tabel (opcjonalnie)</label>
                        <input type="text" name="db_prefix" value="cms_" class="form-input">
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            🚀 Zainstaluj system
                        </button>
                    </div>
                </form>
                
            <?php elseif ($krok == 3): ?>
                <!-- KROK 3: Instalacja zakończona -->
                <div style="text-align: center; padding: 2rem 0;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🎉</div>
                    <h2>Instalacja zakończona pomyślnie!</h2>
                    <p style="margin: 1rem 0; color: #64748b;">
                        System CMS Portal został pomyślnie zainstalowany.
                    </p>
                    
                    <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; text-align: left;">
                        <h4 style="margin-bottom: 1rem;">Dane logowania:</h4>
                        <p><strong>Login:</strong> admin</p>
                        <p><strong>Hasło:</strong> admin123</p>
                        <p style="margin-top: 1rem; color: #dc2626; font-size: 0.875rem;">
                            ⚠️ Zmień hasło po pierwszym logowaniu!
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                        <a href="../admin/" class="btn btn-primary">
                            🧑‍💼 Przejdź do panelu admina
                        </a>
                        <a href="../" class="btn" style="background: #e2e8f0; color: #374151;">
                            🏠 Strona główna
                        </a>
                    </div>
                    
                    <p style="margin-top: 2rem; color: #dc2626; font-size: 0.875rem;">
                        ❗ Usuń folder <strong>install</strong> z serwera dla bezpieczeństwa!
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>