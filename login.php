<?php
/**
 * ===================================================
 * PAGINA DI LOGIN AMMINISTRATORE
 * Gestisce l'autenticazione degli amministratori
 * ===================================================
 */

// Permetti l'accesso al file di configurazione
define('API_ACCESS', true);

// Includi il file di configurazione
require_once 'config.php';

// Ottieni la connessione al database
$pdo = getDatabaseConnection();

// Variabili per messaggi
$errorMessage = '';
$successMessage = '';

// ===================================================
// GESTIONE LOGIN (se il form è stato inviato)
// ===================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ottieni username e password dal form
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Non pulire la password con cleanInput

    // Verifica che i campi siano compilati
    if (empty($username) || empty($password)) {
        $errorMessage = 'Username e password sono obbligatori';
    } else {
        try {
            // Cerca l'amministratore nel database
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();

            // Verifica se l'utente esiste e la password è corretta
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login riuscito - crea la sessione
                session_regenerate_id(true); // Rigenera ID sessione per sicurezza

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                // Reindirizza alla dashboard
                header('Location: dashboard.php');
                exit;

            } else {
                // Login fallito
                $errorMessage = 'Username o password non corretti';

                // Opzionale: aggiungi un delay per prevenire brute force
                sleep(1);
            }

        } catch (PDOException $e) {
            $errorMessage = 'Errore durante il login. Riprovare più tardi.';
            if (DEBUG_MODE) {
                $errorMessage .= '<br>Dettagli: ' . $e->getMessage();
            }
        }
    }
}

// ===================================================
// VERIFICA SE GIÀ LOGGATO
// ===================================================

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Già loggato, reindirizza alla dashboard
    header('Location: dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Amministratore - API Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .footer-info {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 12px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
            font-size: 12px;
        }

        .password-toggle-btn:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Login Admin</h1>
            <p>Dashboard API Tracking System</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Inserisci username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Inserisci password"
                        required
                    >
                    <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                        Mostra
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                Accedi alla Dashboard
            </button>
        </form>

        <div class="footer-info">
            <p>Credenziali default: admin / admin123</p>
            <p style="margin-top: 5px; font-size: 11px; color: #f44;">
                ⚠️ Cambiare la password dopo il primo accesso!
            </p>
        </div>
    </div>

    <script>
        // Funzione per mostrare/nascondere password
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle-btn');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = 'Nascondi';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = 'Mostra';
            }
        }

        // Auto-focus sul campo username
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
