<?php
/**
 * ===================================================
 * GESTIONE UTENTI - Admin Only
 * Permette all'admin di creare, modificare ed eliminare utenti
 * ===================================================
 */

define('API_ACCESS', true);
require_once 'config.php';

// Verifica che sia admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$pdo = getDatabaseConnection();
$message = '';
$messageType = '';

// ===================================================
// GESTIONE AZIONI
// ===================================================

// CREA UTENTE
if (isset($_POST['create_user'])) {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Valida password
        $validation = validatePassword($password);

        if (!$validation['valid']) {
            $message = "Password non valida:<br>" . implode('<br>', $validation['errors']);
            $messageType = 'error';
        } else {
            // Genera API key univoca
            $apiKey = bin2hex(random_bytes(32));
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO utenti (username, api_key, password_hash, richieste) VALUES (:username, :api_key, :password_hash, 0)");
                $stmt->execute([
                    'username' => $username,
                    'api_key' => $apiKey,
                    'password_hash' => $passwordHash
                ]);
                $message = "Utente '$username' creato con successo!";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "Errore: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    } else {
        $message = "Username e password sono obbligatori!";
        $messageType = 'error';
    }
}

// ELIMINA UTENTE
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM utenti WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $message = "Utente eliminato con successo!";
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = "Errore: " . $e->getMessage();
        $messageType = 'error';
    }
}

// RESET PASSWORD
if (isset($_POST['reset_password'])) {
    $userId = intval($_POST['user_id']);
    $newPassword = $_POST['new_password'];

    if (!empty($newPassword)) {
        // Valida password
        $validation = validatePassword($newPassword);

        if (!$validation['valid']) {
            $message = "Password non valida:<br>" . implode('<br>', $validation['errors']);
            $messageType = 'error';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE utenti SET password_hash = :password_hash WHERE id = :id");
                $stmt->execute([
                    'password_hash' => $passwordHash,
                    'id' => $userId
                ]);
                $message = "Password resettata con successo!";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "Errore: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// CARICA TUTTI GLI UTENTI
$stmt = $pdo->query("
    SELECT
        u.id,
        u.username,
        u.api_key,
        u.richieste,
        u.data_creazione,
        COUNT(l.id) as log_count,
        MAX(l.data) as ultima_richiesta
    FROM utenti u
    LEFT JOIN log_richieste l ON u.id = l.utente_id
    GROUP BY u.id
    ORDER BY u.data_creazione DESC
");
$utenti = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-header h2 {
            font-size: 20px;
            color: #333;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #666;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .api-key {
            font-family: 'Courier New', monospace;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #e74c3c;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👥 Gestione Utenti</h1>
        <a href="dashboard.php">← Torna alla Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Form Crea Utente -->
        <div class="section">
            <div class="section-header">
                <h2>➕ Crea Nuovo Utente</h2>
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required placeholder="Es: mario_rossi">
                    </div>
                    <div class="form-group">
                        <label>Password Iniziale</label>
                        <input type="text" name="password" required placeholder="Verrà hashata automaticamente">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" name="create_user" class="btn btn-primary" style="width: 100%;">
                            Crea Utente
                        </button>
                    </div>
                </div>
                <p style="font-size: 12px; color: #666;">
                    ℹ️ L'API key verrà generata automaticamente. L'utente potrà cambiare la password dopo il primo login.
                </p>
            </form>
        </div>

        <!-- Lista Utenti -->
        <div class="section">
            <div class="section-header">
                <h2>📋 Utenti Registrati (<?php echo count($utenti); ?>)</h2>
            </div>

            <?php if (count($utenti) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>API Key</th>
                            <th>Richieste</th>
                            <th>Log</th>
                            <th>Ultima Richiesta</th>
                            <th>Creato il</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utenti as $utente): ?>
                            <tr>
                                <td><?php echo $utente['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($utente['username']); ?></strong></td>
                                <td>
                                    <span class="api-key">
                                        <?php echo substr($utente['api_key'], 0, 20) . '...'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format($utente['richieste']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($utente['log_count']); ?></td>
                                <td style="font-size: 12px; color: #999;">
                                    <?php
                                    if ($utente['ultima_richiesta']) {
                                        echo date('d/m/Y H:i', strtotime($utente['ultima_richiesta']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td style="font-size: 12px; color: #999;">
                                    <?php echo date('d/m/Y', strtotime($utente['data_creazione'])); ?>
                                </td>
                                <td>
                                    <button class="btn btn-warning" onclick="showResetPasswordModal(<?php echo $utente['id']; ?>, '<?php echo htmlspecialchars($utente['username']); ?>')">
                                        Reset Password
                                    </button>
                                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $utente['id']; ?>, '<?php echo htmlspecialchars($utente['username']); ?>')">
                                        Elimina
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    Nessun utente registrato. Creane uno qui sopra!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔑 Reset Password</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="form-group">
                    <label>Nuova Password per <strong id="resetUsername"></strong></label>
                    <input type="text" name="new_password" required placeholder="Inserisci nuova password">
                </div>
                <div class="btn-group" style="margin-top: 20px;">
                    <button type="submit" name="reset_password" class="btn btn-primary">Conferma</button>
                    <button type="button" class="btn" onclick="closeModal()" style="background: #95a5a6; color: white;">Annulla</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showResetPasswordModal(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            document.getElementById('resetPasswordModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('resetPasswordModal').classList.remove('active');
        }

        function confirmDelete(userId, username) {
            if (confirm(`Sei sicuro di voler eliminare l'utente "${username}"?\n\nQuesta azione eliminerà anche tutti i suoi log.`)) {
                window.location.href = 'manage-users.php?delete=' + userId;
            }
        }

        // Chiudi modal cliccando fuori
        document.getElementById('resetPasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
