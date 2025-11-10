<?php
/**
 * ===================================================
 * ENDPOINT API PUBBLICO
 * Gestisce le richieste API con autenticazione tramite API key
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
// FUNZIONE PRINCIPALE DI GESTIONE API
// ===================================================

try {
    // Ottieni la connessione al database
    $pdo = getDatabaseConnection();

    // ===================================================
    // STEP 1: Verifica presenza API key
    // ===================================================

    // Controlla se l'API key è stata fornita (GET o POST)
    $apiKey = null;

    if (isset($_GET['api_key']) && !empty($_GET['api_key'])) {
        $apiKey = cleanInput($_GET['api_key']);
    } elseif (isset($_POST['api_key']) && !empty($_POST['api_key'])) {
        $apiKey = cleanInput($_POST['api_key']);
    } elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
        // Supporto per header HTTP personalizzato
        $apiKey = cleanInput($_SERVER['HTTP_X_API_KEY']);
    }

    // Se non c'è API key, restituisci errore
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

    // Se l'API key non è valida, restituisci errore
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

    // Ottieni il nuovo conteggio
    $nuovoConteggio = $utente['richieste'] + 1;

    // ===================================================
    // STEP 4: Registra la chiamata nel log
    // ===================================================

    // Determina l'endpoint chiamato (percorso completo della richiesta)
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
    // STEP 5: Prepara dati da restituire
    // ===================================================

    // Qui puoi aggiungere la logica specifica della tua API
    // Per esempio, lettura dal database, calcoli, ecc.

    // Ottieni eventuali parametri aggiuntivi
    $action = $_GET['action'] ?? $_POST['action'] ?? 'ping';

    // Dati di esempio da restituire
    $responseData = [
        'message' => 'Richiesta elaborata con successo',
        'action' => $action
    ];

    // ===================================================
    // ESEMPI DI AZIONI API (opzionale, personalizzabile)
    // ===================================================

    switch ($action) {
        case 'ping':
            $responseData['message'] = 'API attiva e funzionante';
            $responseData['server_time'] = date('Y-m-d H:i:s');
            break;

        case 'stats':
            // Restituisci statistiche utente
            $responseData['message'] = 'Statistiche utente';
            $responseData['user_stats'] = [
                'username' => $utente['username'],
                'total_requests' => $nuovoConteggio
            ];
            break;

        case 'data':
            // Esempio: restituisci dati dal database
            // Personalizza questa sezione con la tua logica
            $responseData['message'] = 'Dati richiesti';
            $responseData['data'] = [
                'example_field_1' => 'Valore 1',
                'example_field_2' => 'Valore 2',
                'example_field_3' => 'Valore 3'
            ];
            break;

        default:
            $responseData['message'] = 'Azione non riconosciuta, restituisco risposta di default';
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

// ===================================================
// ESEMPI DI UTILIZZO API
// ===================================================
/*

1. Chiamata base (ping):
   https://tuosito.it/api.php?api_key=demo_key_123456789abcdef

2. Chiamata con azione stats:
   https://tuosito.it/api.php?api_key=demo_key_123456789abcdef&action=stats

3. Chiamata con azione data:
   https://tuosito.it/api.php?api_key=demo_key_123456789abcdef&action=data

4. Chiamata POST:
   curl -X POST https://tuosito.it/api.php \
     -d "api_key=demo_key_123456789abcdef" \
     -d "action=stats"

5. Chiamata con header personalizzato:
   curl -X GET https://tuosito.it/api.php \
     -H "X-API-Key: demo_key_123456789abcdef"

*/
