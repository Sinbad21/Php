<?php
/**
 * ===================================================
 * DASHBOARD AMMINISTRATORE
 * Pannello di controllo per monitorare API e utenti
 * ===================================================
 */

// Permetti l'accesso al file di configurazione
define('API_ACCESS', true);

// Includi il file di configurazione
require_once 'config.php';

// ===================================================
// VERIFICA AUTENTICAZIONE
// ===================================================

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Non loggato, reindirizza al login
    header('Location: login.php');
    exit;
}

// Timeout sessione dopo 30 minuti di inattività
$timeout_duration = 1800; // 30 minuti in secondi
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Ottieni la connessione al database
$pdo = getDatabaseConnection();

// ===================================================
// CARICA STATISTICHE GENERALI
// ===================================================

// Totale utenti
$stmt = $pdo->query("SELECT COUNT(*) as total FROM utenti");
$totalUtenti = $stmt->fetch()['total'];

// Totale richieste complessive
$stmt = $pdo->query("SELECT SUM(richieste) as total FROM utenti");
$totalRichieste = $stmt->fetch()['total'] ?? 0;

// Totale log registrati
$stmt = $pdo->query("SELECT COUNT(*) as total FROM log_richieste");
$totalLog = $stmt->fetch()['total'];

// Richieste ultime 24 ore
$stmt = $pdo->query("SELECT COUNT(*) as total FROM log_richieste WHERE data >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$richieste24h = $stmt->fetch()['total'];

// ===================================================
// CARICA LISTA UTENTI
// ===================================================

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
    ORDER BY u.richieste DESC
");
$utenti = $stmt->fetchAll();

// ===================================================
// CARICA LOG RECENTI (ultimi 50)
// ===================================================

$stmt = $pdo->query("
    SELECT
        l.id,
        l.endpoint,
        l.data,
        l.ip_address,
        u.username
    FROM log_richieste l
    JOIN utenti u ON l.utente_id = u.id
    ORDER BY l.data DESC
    LIMIT 50
");
$logRecenti = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - API Tracking System</title>
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
            font-weight: 600;
        }

        .navbar-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar-info span {
            font-size: 14px;
            opacity: 0.9;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-header h2 {
            font-size: 20px;
            color: #333;
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            font-size: 12px;
            color: #e74c3c;
            cursor: pointer;
        }

        .api-key:hover {
            background: #e0e0e0;
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

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .refresh-btn {
            background: #667eea;
            color: white;
        }

        .refresh-btn:hover {
            background: #5568d3;
        }

        .timestamp {
            color: #999;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }
        }

        .copy-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Notification per copy -->
    <div id="copyNotification" class="copy-notification">
        ✓ API Key copiata negli appunti!
    </div>

    <!-- Navbar -->
    <div class="navbar">
        <h1>📊 Dashboard API Tracking</h1>
        <div class="navbar-info">
            <span>👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <span class="timestamp">⏰ <?php echo date('d/m/Y H:i'); ?></span>
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Statistiche generali -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>👥 Totale Utenti</h3>
                <div class="stat-value"><?php echo number_format($totalUtenti); ?></div>
            </div>
            <div class="stat-card">
                <h3>📡 Richieste Totali</h3>
                <div class="stat-value"><?php echo number_format($totalRichieste); ?></div>
            </div>
            <div class="stat-card">
                <h3>📝 Log Registrati</h3>
                <div class="stat-value"><?php echo number_format($totalLog); ?></div>
            </div>
            <div class="stat-card">
                <h3>🕐 Ultime 24h</h3>
                <div class="stat-value"><?php echo number_format($richieste24h); ?></div>
            </div>
        </div>

        <!-- Tabella utenti -->
        <div class="section">
            <div class="section-header">
                <h2>🔑 Utenti e API Keys</h2>
                <button class="btn refresh-btn" onclick="location.reload()">↻ Aggiorna</button>
            </div>

            <?php if (count($utenti) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>API Key</th>
                            <th>Richieste</th>
                            <th>Log Count</th>
                            <th>Ultima Richiesta</th>
                            <th>Creato il</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utenti as $utente): ?>
                            <tr>
                                <td><?php echo $utente['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($utente['username']); ?></strong></td>
                                <td>
                                    <span class="api-key" onclick="copyToClipboard('<?php echo $utente['api_key']; ?>')">
                                        <?php echo substr($utente['api_key'], 0, 20) . '...'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format($utente['richieste']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo number_format($utente['log_count']); ?>
                                    </span>
                                </td>
                                <td class="timestamp">
                                    <?php
                                    if ($utente['ultima_richiesta']) {
                                        echo date('d/m/Y H:i', strtotime($utente['ultima_richiesta']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td class="timestamp">
                                    <?php echo date('d/m/Y H:i', strtotime($utente['data_creazione'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nessun utente trovato nel database.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabella log recenti -->
        <div class="section">
            <div class="section-header">
                <h2>📋 Log Richieste Recenti (ultime 50)</h2>
            </div>

            <?php if (count($logRecenti) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Endpoint</th>
                            <th>IP Address</th>
                            <th>Data e Ora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logRecenti as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                                <td>
                                    <code style="font-size: 12px; color: #e74c3c;">
                                        <?php echo htmlspecialchars($log['endpoint']); ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge badge-warning">
                                        <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td class="timestamp">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['data'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nessun log disponibile.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Funzione per copiare API key negli appunti
        function copyToClipboard(text) {
            // Crea un elemento textarea temporaneo
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                showCopyNotification();
            } catch (err) {
                console.error('Errore durante la copia:', err);
                alert('API Key: ' + text);
            }

            document.body.removeChild(textarea);
        }

        // Mostra notifica di copia
        function showCopyNotification() {
            const notification = document.getElementById('copyNotification');
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 2000);
        }

        // Auto-refresh ogni 60 secondi (opzionale)
        // setTimeout(() => location.reload(), 60000);
    </script>
</body>
</html>
