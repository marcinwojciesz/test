<?php
/**
 * Strona logowania - PROSTA WERSJA
 */
session_start();

require_once __DIR__ . '/../includes/PathHelper.php';

// Sprawdź czy system jest zainstalowany
if (!is_installed()) {
    die("System nie jest zainstalowany. Przejdź przez proces instalacji.");
}

require_once __DIR__ . '/../includes/Database.class.php';
require_once __DIR__ . '/../includes/Auth.class.php';
require_once __DIR__ . '/../includes/Functions.php';

// Sprawdź czy użytkownik jest już zalogowany
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('admin/'));
    exit();
}

// Inicjalizacja bazy danych i auth
try {
    $database = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    $auth = new Auth($database);
} catch (Exception $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}

$error = '';

// Przetwarzanie formularza logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = safe_input($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Wypełnij wszystkie pola';
    } else {
        // PROSTE LOGOWANIE
        if ($auth->login($login, $password)) {
            header('Location: ' . url('admin/'));
            exit();
        } else {
            $error = 'Nieprawidłowy login lub hasło';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - CMS Portal</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }
        
        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .test-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🔐 Logowanie</h1>
                <p>Zaloguj się do systemu CMS Portal</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Login</label>
                    <input type="text" name="login" class="form-input" required value="admin">
                </div>
                
                <div class="form-group">
                    <label>Hasło</label>
                    <input type="password" name="password" class="form-input" required value="admin123">
                </div>
                
                <button type="submit" class="btn-login">Zaloguj się</button>
            </form>
            
            <div class="test-info">
                <strong>Dane testowe (wpisane automatycznie):</strong><br>
                Login: <strong>admin</strong><br>
                Hasło: <strong>admin123</strong>
            </div>
        </div>
    </div>
</body>
</html>