<?php
/**
 * ===================================================
 * ENDPOINT API PUBBLICO - Hardware Components Database
 * Gestisce le richieste API per componenti hardware PC
 * ===================================================
 */

// Permetti l'accesso al file di configurazione
define('API_ACCESS', true);

// Includi il file di configurazione
require_once 'config.php';

// Imposta header CORS (opzionale, decommenta se necessario)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST');
// header('Access-Control-Allow-Headers: Content-Type');

// Imposta sempre header JSON
header('Content-Type: application/json; charset=utf-8');

// ===================================================
// TABELLE HARDWARE DISPONIBILI
// ===================================================
$tabelleValide = [
    'alimentatore' => 'Alimentatore',
    'case' => 'Case',
    'cpu' => 'CPU',
    'dissipatore' => 'Dissipatore',
    'gpu' => 'GPU',
    'hdd' => 'HDD',
    'ram' => 'RAM',
    'scheda_aggiuntiva' => 'Scheda_Aggiuntiva',
    'scheda_madre' => 'Scheda_Madre',
    'ssd' => 'SSD'
];

// ===================================================
// FUNZIONE PRINCIPALE DI GESTIONE API
// ===================================================

try {
    // Ottieni la connessione al database
    $pdo = getDatabaseConnection();

    // ===================================================
    // STEP 1: Verifica presenza API key
    // ===================================================

    $apiKey = null;

    if (isset($_GET['api_key']) && !empty($_GET['api_key'])) {
        $apiKey = cleanInput($_GET['api_key']);
    } elseif (isset($_POST['api_key']) && !empty($_POST['api_key'])) {
        $apiKey = cleanInput($_POST['api_key']);
    } elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = cleanInput($_SERVER['HTTP_X_API_KEY']);
    }

    if (empty($apiKey)) {
        jsonResponse([
            'success' => false,
            'error' => 'API key mancante',
            'message' => 'Fornire un\'API key valida tramite parametro api_key o header X-API-Key',
            'timestamp' => date('Y-m-d H:i:s')
        ], 401);
    }

    // ===================================================
    // STEP 2: Verifica validità API key
    // ===================================================

    $stmt = $pdo->prepare("SELECT id, username, richieste FROM utenti WHERE api_key = :api_key LIMIT 1");
    $stmt->execute(['api_key' => $apiKey]);
    $utente = $stmt->fetch();

    if (!$utente) {
        jsonResponse([
            'success' => false,
            'error' => 'API key non valida',
            'message' => 'L\'API key fornita non corrisponde a nessun utente',
            'timestamp' => date('Y-m-d H:i:s')
        ], 403);
    }

    // ===================================================
    // STEP 3: Incrementa contatore richieste
    // ===================================================

    $stmt = $pdo->prepare("UPDATE utenti SET richieste = richieste + 1 WHERE id = :id");
    $stmt->execute(['id' => $utente['id']]);
    $nuovoConteggio = $utente['richieste'] + 1;

    // ===================================================
    // STEP 4: Registra la chiamata nel log
    // ===================================================

    $endpoint = $_SERVER['REQUEST_URI'] ?? '/api.php';
    $ipAddress = getClientIP();
    $userAgent = getUserAgent();

    $stmt = $pdo->prepare("
        INSERT INTO log_richieste (utente_id, endpoint, ip_address, user_agent)
        VALUES (:utente_id, :endpoint, :ip_address, :user_agent)
    ");

    $stmt->execute([
        'utente_id' => $utente['id'],
        'endpoint' => $endpoint,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ]);

    // ===================================================
    // STEP 5: Elabora l'azione richiesta
    // ===================================================

    $action = $_GET['action'] ?? $_POST['action'] ?? 'help';
    $responseData = [];

    switch ($action) {

        // ================================================
        // PING - Test connessione API
        // ================================================
        case 'ping':
            $responseData = [
                'message' => 'API Hardware Components attiva e funzionante',
                'server_time' => date('Y-m-d H:i:s'),
                'database' => 'connesso',
                'tabelle_disponibili' => array_keys($tabelleValide)
            ];
            break;

        // ================================================
        // LIST - Elenca componenti da una tabella
        // ================================================
        case 'list':
            $tabella = strtolower($_GET['tabella'] ?? $_POST['tabella'] ?? '');

            if (empty($tabella)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Tabella mancante',
                    'message' => 'Specificare il parametro "tabella"',
                    'tabelle_disponibili' => array_keys($tabelleValide)
                ], 400);
            }

            if (!isset($tabelleValide[$tabella])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Tabella non valida',
                    'message' => 'La tabella specificata non esiste',
                    'tabelle_disponibili' => array_keys($tabelleValide)
                ], 400);
            }

            // Parametri paginazione
            $limit = min(100, max(1, intval($_GET['limit'] ?? $_POST['limit'] ?? 20)));
            $offset = max(0, intval($_GET['offset'] ?? $_POST['offset'] ?? 0));

            // Query con paginazione
            $nomeTabella = $tabelleValide[$tabella];
            $query = "SELECT * FROM `$nomeTabella` LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $risultati = $stmt->fetchAll();

            // Conta totale elementi
            $stmtCount = $pdo->query("SELECT COUNT(*) as total FROM `$nomeTabella`");
            $totale = $stmtCount->fetch()['total'];

            $responseData = [
                'message' => 'Lista componenti recuperata',
                'tabella' => $tabella,
                'totale_elementi' => (int)$totale,
                'elementi_restituiti' => count($risultati),
                'offset' => $offset,
                'limit' => $limit,
                'pagina_corrente' => floor($offset / $limit) + 1,
                'totale_pagine' => ceil($totale / $limit),
                'dati' => $risultati
            ];
            break;

        // ================================================
        // GET - Recupera singolo componente per ID
        // ================================================
        case 'get':
            $tabella = strtolower($_GET['tabella'] ?? $_POST['tabella'] ?? '');
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

            if (empty($tabella)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Tabella mancante',
                    'message' => 'Specificare il parametro "tabella"'
                ], 400);
            }

            if ($id <= 0) {
                jsonResponse([
                    'success' => false,
                    'error' => 'ID non valido',
                    'message' => 'Specificare un ID numerico valido'
                ], 400);
            }

            if (!isset($tabelleValide[$tabella])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Tabella non valida',
                    'message' => 'La tabella specificata non esiste'
                ], 400);
            }

            $nomeTabella = $tabelleValide[$tabella];
            $stmt = $pdo->prepare("SELECT * FROM `$nomeTabella` WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $risultato = $stmt->fetch();

            if (!$risultato) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Componente non trovato',
                    'message' => "Nessun componente con ID $id nella tabella $tabella"
                ], 404);
            }

            $responseData = [
                'message' => 'Componente recuperato',
                'tabella' => $tabella,
                'id' => $id,
                'dati' => $risultato
            ];
            break;

        // ================================================
        // SEARCH - Cerca componenti con filtri
        // ================================================
        case 'search':
            $tabella = strtolower($_GET['tabella'] ?? $_POST['tabella'] ?? '');

            if (empty($tabella) || !isset($tabelleValide[$tabella])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Tabella non valida',
                    'message' => 'Specificare una tabella valida'
                ], 400);
            }

            $nomeTabella = $tabelleValide[$tabella];

            // Costruisci query di ricerca dinamica
            $where = [];
            $params = [];

            // Filtro per nome
            if (!empty($_GET['nome']) || !empty($_POST['nome'])) {
                $where[] = "nome LIKE :nome";
                $params['nome'] = '%' . ($_GET['nome'] ?? $_POST['nome']) . '%';
            }

            // Filtro per produttore
            if (!empty($_GET['produttore']) || !empty($_POST['produttore'])) {
                $where[] = "produttore LIKE :produttore";
                $params['produttore'] = '%' . ($_GET['produttore'] ?? $_POST['produttore']) . '%';
            }

            // Filtro per prezzo minimo
            if (isset($_GET['prezzo_min']) || isset($_POST['prezzo_min'])) {
                $where[] = "prezzo >= :prezzo_min";
                $params['prezzo_min'] = floatval($_GET['prezzo_min'] ?? $_POST['prezzo_min']);
            }

            // Filtro per prezzo massimo
            if (isset($_GET['prezzo_max']) || isset($_POST['prezzo_max'])) {
                $where[] = "prezzo <= :prezzo_max";
                $params['prezzo_max'] = floatval($_GET['prezzo_max'] ?? $_POST['prezzo_max']);
            }

            // Filtro disponibilità
            if (isset($_GET['disponibile']) || isset($_POST['disponibile'])) {
                $where[] = "quantita > 0";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // Paginazione
            $limit = min(100, max(1, intval($_GET['limit'] ?? $_POST['limit'] ?? 20)));
            $offset = max(0, intval($_GET['offset'] ?? $_POST['offset'] ?? 0));

            // Esegui query
            $query = "SELECT * FROM `$nomeTabella` $whereClause LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $risultati = $stmt->fetchAll();

            // Conta totale
            $queryCount = "SELECT COUNT(*) as total FROM `$nomeTabella` $whereClause";
            $stmtCount = $pdo->prepare($queryCount);
            foreach ($params as $key => $value) {
                $stmtCount->bindValue(":$key", $value);
            }
            $stmtCount->execute();
            $totale = $stmtCount->fetch()['total'];

            $responseData = [
                'message' => 'Ricerca completata',
                'tabella' => $tabella,
                'filtri_applicati' => array_keys($params),
                'totale_risultati' => (int)$totale,
                'risultati_restituiti' => count($risultati),
                'offset' => $offset,
                'limit' => $limit,
                'dati' => $risultati
            ];
            break;

        // ================================================
        // STATS - Statistiche utente
        // ================================================
        case 'stats':
            $responseData = [
                'message' => 'Statistiche utente',
                'username' => $utente['username'],
                'total_requests' => $nuovoConteggio,
                'tabelle_disponibili' => count($tabelleValide),
                'lista_tabelle' => array_keys($tabelleValide)
            ];
            break;

        // ================================================
        // HELP - Documentazione API
        // ================================================
        case 'help':
        default:
            $responseData = [
                'message' => 'API Hardware Components - Documentazione',
                'endpoint' => 'api.php',
                'tabelle_disponibili' => array_keys($tabelleValide),
                'azioni' => [
                    'ping' => [
                        'descrizione' => 'Test connessione API',
                        'parametri' => 'Nessuno',
                        'esempio' => 'api.php?api_key=TUA_KEY&action=ping'
                    ],
                    'list' => [
                        'descrizione' => 'Elenca componenti da una tabella',
                        'parametri' => 'tabella (obbligatorio), limit (default 20), offset (default 0)',
                        'esempio' => 'api.php?api_key=TUA_KEY&action=list&tabella=cpu&limit=10'
                    ],
                    'get' => [
                        'descrizione' => 'Recupera singolo componente per ID',
                        'parametri' => 'tabella (obbligatorio), id (obbligatorio)',
                        'esempio' => 'api.php?api_key=TUA_KEY&action=get&tabella=gpu&id=5'
                    ],
                    'search' => [
                        'descrizione' => 'Cerca componenti con filtri',
                        'parametri' => 'tabella (obbligatorio), nome, produttore, prezzo_min, prezzo_max, disponibile',
                        'esempio' => 'api.php?api_key=TUA_KEY&action=search&tabella=ram&produttore=Corsair&prezzo_max=100'
                    ],
                    'stats' => [
                        'descrizione' => 'Statistiche utilizzo API',
                        'parametri' => 'Nessuno',
                        'esempio' => 'api.php?api_key=TUA_KEY&action=stats'
                    ]
                ]
            ];
            break;
    }

    // ===================================================
    // STEP 6: Restituisci risposta JSON di successo
    // ===================================================

    jsonResponse([
        'success' => true,
        'user' => [
            'username' => $utente['username'],
            'request_count' => $nuovoConteggio
        ],
        'data' => $responseData,
        'timestamp' => date('Y-m-d H:i:s')
    ], 200);

} catch (PDOException $e) {
    // Errore database
    jsonResponse([
        'success' => false,
        'error' => 'Errore database',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Si è verificato un errore nel processamento della richiesta',
        'timestamp' => date('Y-m-d H:i:s')
    ], 500);

} catch (Exception $e) {
    // Errore generico
    jsonResponse([
        'success' => false,
        'error' => 'Errore server',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Si è verificato un errore imprevisto',
        'timestamp' => date('Y-m-d H:i:s')
    ], 500);
}
