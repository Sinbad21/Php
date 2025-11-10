<?php
/**
 * FILE DI TEST LOGIN
 * Verifica connessione database e credenziali admin
 */

define('API_ACCESS', true);
require_once 'config.php';

echo "<html><head><meta charset='UTF-8'><title>Test Login</title></head><body>";
echo "<h1>🔍 Test Sistema Login</h1>";
echo "<hr>";

try {
    // Test 1: Connessione Database
    echo "<h2>✅ Test 1: Connessione Database</h2>";
    $pdo = getDatabaseConnection();
    echo "<p style='color: green;'>✓ Connessione al database riuscita!</p>";

    // Test 2: Verifica tabella admin esiste
    echo "<h2>✅ Test 2: Verifica Tabella Admin</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "<p style='color: green;'>✓ Tabella 'admin' esiste</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabella 'admin' NON esiste!</p>";
        echo "<p><strong>SOLUZIONE:</strong> Esegui lo script SQL in phpMyAdmin per creare la tabella.</p>";
        die();
    }

    // Test 3: Conta admin nel database
    echo "<h2>✅ Test 3: Utenti Admin</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM admin");
    $count = $stmt->fetch()['total'];
    echo "<p>Totale admin nel database: <strong>$count</strong></p>";

    if ($count == 0) {
        echo "<p style='color: red;'>✗ Nessun admin trovato nel database!</p>";
        echo "<p><strong>SOLUZIONE:</strong> Esegui questo SQL in phpMyAdmin:</p>";
        echo "<pre>INSERT INTO admin (username, password_hash) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');</pre>";
        die();
    }

    // Test 4: Visualizza dati admin
    echo "<h2>✅ Test 4: Dati Admin</h2>";
    $stmt = $pdo->query("SELECT id, username, password_hash, data_creazione FROM admin");
    $admins = $stmt->fetchAll();

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Data Creazione</th></tr>";
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td><strong>{$admin['username']}</strong></td>";
        echo "<td style='font-family: monospace; font-size: 10px;'>" . substr($admin['password_hash'], 0, 30) . "...</td>";
        echo "<td>{$admin['data_creazione']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test 5: Test password_verify
    echo "<h2>✅ Test 5: Verifica Password</h2>";

    $username = 'admin';
    $password = 'admin123';

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<p>✓ Utente '<strong>$username</strong>' trovato nel database</p>";
        echo "<p>Hash nel database: <code style='font-size: 10px;'>{$admin['password_hash']}</code></p>";

        // Test password_verify
        if (password_verify($password, $admin['password_hash'])) {
            echo "<p style='color: green; font-size: 18px;'><strong>✓✓✓ PASSWORD CORRETTA! ✓✓✓</strong></p>";
            echo "<p>La password 'admin123' corrisponde all'hash nel database.</p>";
            echo "<p style='background: #ffe; padding: 10px; border: 2px solid #aa0;'><strong>CONCLUSIONE:</strong> Il sistema di login dovrebbe funzionare. Se continui ad avere problemi, potrebbe essere un problema con le SESSIONI PHP.</p>";
        } else {
            echo "<p style='color: red; font-size: 18px;'><strong>✗✗✗ PASSWORD ERRATA! ✗✗✗</strong></p>";
            echo "<p>La password 'admin123' NON corrisponde all'hash nel database.</p>";
            echo "<p style='background: #fee; padding: 10px; border: 2px solid #a00;'><strong>SOLUZIONE:</strong> L'hash nel database è sbagliato. Elimina l'utente admin e ricrealo con questo SQL:</p>";
            echo "<pre>DELETE FROM admin WHERE username = 'admin';
INSERT INTO admin (username, password_hash) VALUES
('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');</pre>";
        }
    } else {
        echo "<p style='color: red;'>✗ Utente 'admin' NON trovato!</p>";
    }

    // Test 6: Genera nuovo hash (opzionale)
    echo "<h2>✅ Test 6: Genera Nuovo Hash</h2>";
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "<p>Se vuoi creare un nuovo admin con password 'admin123', usa questo hash:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>INSERT INTO admin (username, password_hash) VALUES ('nuovo_admin', '$newHash');</pre>";

    // Test 7: Test sessione
    echo "<h2>✅ Test 7: Verifica Sessioni PHP</h2>";
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<p style='color: green;'>✓ Sessioni PHP attive</p>";
        echo "<p>Session ID: <code>" . session_id() . "</code></p>";
    } else {
        echo "<p style='color: red;'>✗ Sessioni PHP non attive!</p>";
    }

    echo "<hr>";
    echo "<h2>🎯 RIASSUNTO</h2>";
    echo "<ul>";
    echo "<li>Database: <strong style='color: green;'>Connesso</strong></li>";
    echo "<li>Tabella admin: <strong style='color: green;'>Esiste</strong></li>";
    echo "<li>Utenti admin: <strong>$count</strong></li>";
    echo "<li>Test password: <strong>" . (password_verify('admin123', $admin['password_hash'] ?? '') ? "OK ✓" : "FALLITO ✗") . "</strong></li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>ERRORE DATABASE:</strong> " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRORE:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Torna al Login</a></p>";
echo "</body></html>";
?>
