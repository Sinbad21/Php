<?php
/**
 * ===================================================
 * DASHBOARD UTENTE
 * Pannello personale per testare e integrare l'API
 * ===================================================
 */

// Permetti l'accesso al file di configurazione
define('API_ACCESS', true);

// Includi il file di configurazione
require_once 'config.php';

// ===================================================
// VERIFICA AUTENTICAZIONE UTENTE
// ===================================================

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Se è un admin che prova ad accedere, reindirizza
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Timeout sessione dopo 30 minuti
$timeout_duration = 1800;
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
// CARICA DATI UTENTE
// ===================================================

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$apiKey = $_SESSION['api_key'];

// Carica statistiche utente
$stmt = $pdo->prepare("SELECT richieste, data_creazione FROM utenti WHERE id = :id");
$stmt->execute(['id' => $userId]);
$userData = $stmt->fetch();

$totalRequests = $userData['richieste'] ?? 0;
$accountCreated = $userData['data_creazione'] ?? '';

// Log richieste recenti (ultime 20)
$stmt = $pdo->prepare("
    SELECT endpoint, data, ip_address
    FROM log_richieste
    WHERE utente_id = :user_id
    ORDER BY data DESC
    LIMIT 20
");
$stmt->execute(['user_id' => $userId]);
$recentLogs = $stmt->fetchAll();

// Tabelle disponibili
$tabelle = ['alimentatore', 'case', 'cpu', 'dissipatore', 'gpu', 'hdd', 'ram', 'scheda_aggiuntiva', 'scheda_madre', 'ssd'];

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utente - API System</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
            border-left: 4px solid #2c3e50;
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
            color: #2c3e50;
        }

        .api-key-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #e74c3c;
            display: flex;
            justify-content: space-between;
            align-items: center;
            word-break: break-all;
        }

        .btn-copy {
            background: #2c3e50;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
            margin-left: 10px;
        }

        .btn-copy:hover {
            background: #34495e;
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

        .tester-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }

        .form-group select,
        .form-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-test {
            background: #2c3e50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-test:hover {
            background: #34495e;
        }

        .response-box {
            background: white;
            color: #000;
            padding: 20px;
            border-radius: 5px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            border: 1px solid #e0e0e0;
        }

        .table-wrapper {
            overflow-y: auto;
            max-height: 600px;
        }

        .column-group {
            margin-bottom: 30px;
        }

        .column-group-title {
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .response-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .response-box th {
            background: #f0f0f0;
            color: #000;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
            border-right: 1px solid #ddd;
        }

        .response-box th:last-child {
            border-right: none;
        }

        .response-box td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            border-right: 1px solid #f0f0f0;
            color: #000;
        }

        .response-box td:last-child {
            border-right: none;
        }

        .response-box tr:nth-child(even) {
            background: #f8f9fa;
        }

        .response-box tr:hover {
            background: #e8f4f8;
        }

        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-bottom: 20px;
            position: relative;
        }

        .code-block .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #2c3e50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #2c3e50;
            border-bottom-color: #2c3e50;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        .timestamp {
            color: #999;
            font-size: 12px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <!-- Navbar -->
    <div class="navbar">
        <h1>👤 Dashboard Utente</h1>
        <div class="navbar-info">
            <span>👋 <?php echo htmlspecialchars($username); ?> (<?php echo number_format($totalRequests); ?> richieste)</span>
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- API Key -->
        <div class="section">
            <div class="section-header">
                <h2>🔑 La Tua API Key</h2>
            </div>
            <div class="api-key-box">
                <span id="apiKey">••••••••••••••••••••••••••••••••</span>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-copy" id="showKeyBtn" onclick="showApiKeyModal()">👁️ Mostra</button>
                    <button class="btn-copy" id="copyKeyBtn" onclick="copyApiKey()" style="display: none;">📋 Copia</button>
                </div>
            </div>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                🔒 Per motivi di sicurezza, l'API key è nascosta. Inserisci la tua password per visualizzarla.
            </p>
        </div>

        <!-- Modal Password per API Key -->
        <div id="apiKeyModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 2000;">
            <div style="background: white; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%;">
                <h3 style="margin-bottom: 20px;">🔐 Verifica Password</h3>
                <p style="margin-bottom: 15px; color: #666; font-size: 14px;">
                    Inserisci la tua password per visualizzare l'API key
                </p>
                <input type="password" id="verifyPassword" placeholder="Password" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; margin-bottom: 15px;">
                <div id="passwordError" style="color: #e74c3c; font-size: 13px; margin-bottom: 15px; display: none;"></div>
                <div style="display: flex; gap: 10px;">
                    <button onclick="verifyPasswordAndShowKey()" style="flex: 1; padding: 10px; background: #2c3e50; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">Conferma</button>
                    <button onclick="closeApiKeyModal()" style="flex: 1; padding: 10px; background: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer;">Annulla</button>
                </div>
            </div>
        </div>

        <!-- API Tester -->
        <div class="section">
            <div class="section-header">
                <h2>🧪 Tester API Interattivo</h2>
            </div>

            <form id="apiTesterForm" class="tester-form">
                <div class="form-group">
                    <label>Azione</label>
                    <select id="action" name="action" onchange="toggleFields()">
                        <option value="ping">Ping - Test connessione</option>
                        <option value="list">List - Elenca componenti</option>
                        <option value="search">Search - Cerca per EAN</option>
                        <option value="stats">Stats - Statistiche</option>
                    </select>
                </div>

                <div class="form-group" id="tableGroup" style="display: none;">
                    <label>Tabella</label>
                    <select id="tabella" name="tabella">
                        <?php foreach ($tabelle as $tab): ?>
                            <option value="<?php echo $tab; ?>"><?php echo ucfirst($tab); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="eanGroup" style="display: none;">
                    <label>Codice EAN</label>
                    <input type="text" id="ean" name="ean" placeholder="Es: 1234567890123">
                </div>

                <div class="form-group" id="limitGroup" style="display: none;">
                    <label>Limit (max 100)</label>
                    <input type="number" id="limit" name="limit" value="10" min="1" max="100">
                </div>

                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn-test" onclick="testApi()">🚀 Esegui Test</button>
                </div>
            </form>

            <div id="responseContainer" style="display: none;">
                <h3 style="margin-bottom: 10px;">Risposta API:</h3>
                <div class="response-box" id="responseBox"></div>
            </div>
        </div>

        <!-- Guida Integrazione -->
        <div class="section">
            <div class="section-header">
                <h2>📖 Guida Integrazione API</h2>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('javascript')">JavaScript</button>
                <button class="tab" onclick="showTab('php')">PHP</button>
                <button class="tab" onclick="showTab('python')">Python</button>
                <button class="tab" onclick="showTab('curl')">cURL</button>
            </div>

            <div id="javascript" class="tab-content active">
                <h3>JavaScript (Fetch API)</h3>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode('jsCode')">Copia</button>
                    <pre id="jsCode">const API_URL = 'https://coded4u.com/api.php';
const API_KEY = '<?php echo $apiKey; ?>';

// Funzione helper per chiamate API
async function callApi(action, params = {}) {
    const url = new URL(API_URL);
    url.searchParams.append('api_key', API_KEY);
    url.searchParams.append('action', action);

    Object.keys(params).forEach(key => {
        url.searchParams.append(key, params[key]);
    });

    const response = await fetch(url);
    return await response.json();
}

// Esempio: Lista CPU
const cpuList = await callApi('list', {
    tabella: 'cpu',
    limit: 10
});
console.log(cpuList.data.dati);

// Esempio: Cerca GPU
const gpuSearch = await callApi('search', {
    tabella: 'gpu',
    produttore: 'NVIDIA',
    prezzo_max: 1000
});
console.log(gpuSearch.data.dati);</pre>
                </div>
            </div>

            <div id="php" class="tab-content">
                <h3>PHP</h3>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode('phpCode')">Copia</button>
                    <pre id="phpCode">&lt;?php
$apiUrl = 'https://coded4u.com/api.php';
$apiKey = '<?php echo $apiKey; ?>';

function callApi($action, $params = []) {
    global $apiUrl, $apiKey;

    $params['api_key'] = $apiKey;
    $params['action'] = $action;

    $url = $apiUrl . '?' . http_build_query($params);
    $response = file_get_contents($url);

    return json_decode($response, true);
}

// Esempio: Lista CPU
$cpuList = callApi('list', [
    'tabella' => 'cpu',
    'limit' => 10
]);

foreach ($cpuList['data']['dati'] as $cpu) {
    echo $cpu['nome'] . " - €" . $cpu['prezzo'] . "\n";
}

// Esempio: Cerca RAM
$ramSearch = callApi('search', [
    'tabella' => 'ram',
    'produttore' => 'Corsair',
    'disponibile' => 1
]);
?&gt;</pre>
                </div>
            </div>

            <div id="python" class="tab-content">
                <h3>Python (requests)</h3>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode('pythonCode')">Copia</button>
                    <pre id="pythonCode">import requests

API_URL = 'https://coded4u.com/api.php'
API_KEY = '<?php echo $apiKey; ?>'

def call_api(action, **params):
    params['api_key'] = API_KEY
    params['action'] = action
    response = requests.get(API_URL, params=params)
    return response.json()

# Esempio: Lista CPU
cpu_list = call_api('list', tabella='cpu', limit=10)
for cpu in cpu_list['data']['dati']:
    print(f"{cpu['nome']} - €{cpu['prezzo']}")

# Esempio: Cerca GPU sotto 1000€
gpu_search = call_api('search',
    tabella='gpu',
    prezzo_max=1000,
    disponibile=1
)
print(f"Trovate {gpu_search['data']['totale_risultati']} GPU")</pre>
                </div>
            </div>

            <div id="curl" class="tab-content">
                <h3>cURL (Command Line)</h3>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode('curlCode')">Copia</button>
                    <pre id="curlCode"># Test Ping
curl "https://coded4u.com/api.php?api_key=<?php echo $apiKey; ?>&action=ping"

# Lista CPU (prime 10)
curl "https://coded4u.com/api.php?api_key=<?php echo $apiKey; ?>&action=list&tabella=cpu&limit=10"

# Dettaglio CPU con ID 1
curl "https://coded4u.com/api.php?api_key=<?php echo $apiKey; ?>&action=get&tabella=cpu&id=1"

# Cerca GPU NVIDIA disponibili
curl "https://coded4u.com/api.php?api_key=<?php echo $apiKey; ?>&action=search&tabella=gpu&produttore=NVIDIA&disponibile=1"</pre>
                </div>
            </div>
        </div>

        <!-- Log Recenti -->
        <div class="section">
            <div class="section-header">
                <h2>📋 Le Tue Richieste Recenti</h2>
            </div>

            <?php if (count($recentLogs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>IP Address</th>
                            <th>Data e Ora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td>
                                    <code style="font-size: 12px; color: #e74c3c;">
                                        <?php echo htmlspecialchars($log['endpoint']); ?>
                                    </code>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                <td class="timestamp">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['data'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    Nessuna richiesta effettuata ancora. Prova il tester API qui sopra!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let API_KEY = ''; // Sarà popolata dopo verifica password
        const API_URL = 'https://www.coded4u.com/api.php'; // Usa www. per evitare redirect 301
        let apiKeyRevealed = false;
        let hideKeyTimer = null;

        // Modal API Key
        function showApiKeyModal() {
            document.getElementById('apiKeyModal').style.display = 'flex';
            document.getElementById('verifyPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
            setTimeout(() => document.getElementById('verifyPassword').focus(), 100);
        }

        function closeApiKeyModal() {
            document.getElementById('apiKeyModal').style.display = 'none';
        }

        // Verifica password e mostra API key
        async function verifyPasswordAndShowKey() {
            const password = document.getElementById('verifyPassword').value;
            const errorDiv = document.getElementById('passwordError');

            if (!password) {
                errorDiv.textContent = 'Inserisci la password';
                errorDiv.style.display = 'block';
                return;
            }

            try {
                const response = await fetch('reveal-api-key.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'password=' + encodeURIComponent(password)
                });

                const data = await response.json();

                if (data.success) {
                    // Password corretta - mostra API key
                    API_KEY = data.api_key;
                    document.getElementById('apiKey').textContent = data.api_key;
                    document.getElementById('showKeyBtn').style.display = 'none';
                    document.getElementById('copyKeyBtn').style.display = 'block';
                    apiKeyRevealed = true;
                    closeApiKeyModal();
                    showNotification('✓ API Key visualizzata. Verrà nascosta automaticamente tra 30 secondi.');

                    // Nascondi automaticamente dopo 30 secondi
                    if (hideKeyTimer) clearTimeout(hideKeyTimer);
                    hideKeyTimer = setTimeout(hideApiKey, 30000);
                } else {
                    // Password errata
                    errorDiv.textContent = data.error || 'Password non corretta';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'Errore di connessione';
                errorDiv.style.display = 'block';
            }
        }

        // Nascondi API key
        function hideApiKey() {
            document.getElementById('apiKey').textContent = '••••••••••••••••••••••••••••••••';
            document.getElementById('showKeyBtn').style.display = 'block';
            document.getElementById('copyKeyBtn').style.display = 'none';
            API_KEY = '';
            apiKeyRevealed = false;
            if (hideKeyTimer) clearTimeout(hideKeyTimer);
        }

        // Copia API Key (richiede password se non ancora visualizzata)
        async function copyApiKey() {
            if (!apiKeyRevealed) {
                showApiKeyModal();
                return;
            }

            const apiKeyText = document.getElementById('apiKey').textContent;
            if (apiKeyText && apiKeyText !== '••••••••••••••••••••••••••••••••') {
                navigator.clipboard.writeText(apiKeyText);
                showNotification('✓ API Key copiata!');
            }
        }

        // Chiudi modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApiKeyModal();
            }
        });

        // Chiudi modal cliccando fuori
        document.getElementById('apiKeyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApiKeyModal();
            }
        });

        // Submit con Enter nel campo password
        document.getElementById('verifyPassword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verifyPasswordAndShowKey();
            }
        });

        // Toggle campi form in base all'azione
        function toggleFields() {
            const action = document.getElementById('action').value;
            const tableGroup = document.getElementById('tableGroup');
            const eanGroup = document.getElementById('eanGroup');
            const limitGroup = document.getElementById('limitGroup');

            // Nascondi tutto
            tableGroup.style.display = 'none';
            eanGroup.style.display = 'none';
            limitGroup.style.display = 'none';

            // Mostra campi in base all'azione
            if (action === 'list') {
                tableGroup.style.display = 'block';
                limitGroup.style.display = 'block';
            } else if (action === 'search') {
                tableGroup.style.display = 'block';
                eanGroup.style.display = 'block';
            }
        }

        // Test API (usa proxy per sicurezza)
        async function testApi() {
            const action = document.getElementById('action').value;
            const formData = new FormData();
            formData.append('action', action);

            // Aggiungi parametri in base all'azione
            if (action === 'list') {
                formData.append('tabella', document.getElementById('tabella').value);
                formData.append('limit', document.getElementById('limit').value);
            } else if (action === 'search') {
                formData.append('tabella', document.getElementById('tabella').value);
                const ean = document.getElementById('ean').value;
                if (ean) {
                    formData.append('ean', ean);
                }
            }

            try {
                // Mostra loading
                document.getElementById('responseContainer').style.display = 'block';
                document.getElementById('responseBox').innerHTML = '<div style="color: #2c3e50;">⏳ Chiamata API in corso...</div>';

                // Usa il proxy invece di chiamare direttamente l'API
                const response = await fetch('test-api-proxy.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                // Formatta risposta in HTML
                document.getElementById('responseBox').innerHTML = formatApiResponse(data);
            } catch (error) {
                document.getElementById('responseContainer').style.display = 'block';
                document.getElementById('responseBox').innerHTML = `
                    <div style="color: #e74c3c; padding: 15px; background: #fee; border-radius: 5px; border-left: 4px solid #e74c3c;">
                        <strong>✗ Errore di connessione</strong><br>
                        <div style="margin-top: 10px;">${escapeHtml(error.message)}</div>
                        <div style="margin-top: 10px; font-size: 12px; color: #999;">
                            Verifica che tutti i file siano stati caricati sul server e che il file test-api-proxy.php sia accessibile.
                        </div>
                    </div>
                `;
            }
        }

        // Formatta risposta API in HTML leggibile
        function formatApiResponse(data) {
            let html = '';

            if (data.success) {
                // Mostra solo la tabella con i dati
                if (data.data && data.data.dati && data.data.dati.length > 0) {
                    // Info: numero risultati
                    html += '<div style="padding: 12px 0; font-weight: 600; color: #2c3e50; margin-bottom: 15px;">';
                    html += '📊 Risultati trovati: ' + data.data.dati.length;
                    html += '</div>';

                    // Ottieni tutte le colonne
                    const firstItem = data.data.dati[0];
                    const allColumns = Object.keys(firstItem);
                    const columnCount = allColumns.length;

                    // Dividi colonne in gruppi di 8
                    const columnsPerGroup = 8;
                    const groupCount = Math.ceil(columnCount / columnsPerGroup);

                    // Wrapper con scroll
                    html += '<div class="table-wrapper">';

                    // Crea una tabella per ogni gruppo di 8 colonne
                    for (let groupIndex = 0; groupIndex < groupCount; groupIndex++) {
                        const startCol = groupIndex * columnsPerGroup;
                        const endCol = Math.min(startCol + columnsPerGroup, columnCount);
                        const groupColumns = allColumns.slice(startCol, endCol);

                        // Titolo gruppo (se ci sono più gruppi)
                        if (groupCount > 1) {
                            html += '<div class="column-group-title">';
                            html += 'Colonne ' + (startCol + 1) + '-' + endCol;
                            html += '</div>';
                        }

                        // Tabella per questo gruppo
                        html += '<table>';

                        // Header
                        html += '<thead><tr>';
                        groupColumns.forEach(key => {
                            let displayKey = key.replace(/_/g, ' ').split(' ')
                                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                                .join(' ');
                            html += '<th>' + escapeHtml(displayKey) + '</th>';
                        });
                        html += '</tr></thead>';

                        // Body
                        html += '<tbody>';
                        data.data.dati.forEach((item) => {
                            html += '<tr>';
                            groupColumns.forEach(key => {
                                let value = item[key];
                                let displayValue = value !== null && value !== '' ? String(value) : '-';
                                html += '<td>' + escapeHtml(displayValue) + '</td>';
                            });
                            html += '</tr>';
                        });
                        html += '</tbody>';
                        html += '</table>';
                    }

                    html += '</div>';
                } else if (data.data && data.data.message) {
                    // Messaggio semplice (es: ping)
                    html += '<div style="color: #000; padding: 15px;">' + escapeHtml(data.data.message) + '</div>';
                } else {
                    html += '<div style="color: #666; padding: 20px; text-align: center;">📭 Nessun risultato trovato.</div>';
                }

            } else {
                // Errore
                html += '<div style="color: #e74c3c; padding: 15px; background: #fee; border-radius: 5px; border-left: 4px solid #e74c3c;">';
                html += '<strong>✗ Errore</strong><br>';
                html += '<div style="margin-top: 10px;">' + escapeHtml(data.error || 'Errore sconosciuto') + '</div>';
                if (data.message) {
                    html += '<div style="margin-top: 5px; font-size: 14px;">' + escapeHtml(data.message) + '</div>';
                }
                if (data.details) {
                    html += '<div style="margin-top: 10px; font-size: 12px; background: #fff; padding: 10px; border-radius: 3px;">';
                    html += '<strong>Dettagli:</strong> ' + escapeHtml(data.details);
                    html += '</div>';
                }
                html += '</div>';
            }

            return html;
        }

        // Escape HTML per sicurezza
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Copia codice
        function copyCode(id) {
            const code = document.getElementById(id).textContent;
            navigator.clipboard.writeText(code);
            showNotification('✓ Codice copiato!');
        }

        // Mostra tab
        function showTab(tabName) {
            // Nascondi tutte le tab
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Mostra tab selezionata
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Mostra notifica
        function showNotification(message) {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.style.display = 'block';
            setTimeout(() => {
                notif.style.display = 'none';
            }, 2000);
        }

        // Inizializza
        toggleFields();
    </script>
</body>
</html>
