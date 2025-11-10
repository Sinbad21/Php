<?php
/**
 * ===================================================
 * PROXY API TESTER
 * Permette di testare l'API senza esporre la key al browser
 * ===================================================
 */

define('API_ACCESS', true);
require_once 'config.php';

// Solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

// Verifica che sia un utente loggato
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

// Ottieni API key dalla sessione (NON dal client!)
$apiKey = $_SESSION['api_key'] ?? '';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'error' => 'API key non trovata']);
    exit;
}

// Ottieni parametri
$action = $_POST['action'] ?? 'ping';
$params = [
    'api_key' => $apiKey,
    'action' => $action
];

// Aggiungi parametri opzionali
if (isset($_POST['tabella'])) {
    $params['tabella'] = $_POST['tabella'];
}

if (isset($_POST['ean'])) {
    $params['ean'] = $_POST['ean'];
}

if (isset($_POST['limit'])) {
    $params['limit'] = $_POST['limit'];
}

if (isset($_POST['offset'])) {
    $params['offset'] = $_POST['offset'];
}

if (isset($_POST['nome'])) {
    $params['nome'] = $_POST['nome'];
}

if (isset($_POST['produttore'])) {
    $params['produttore'] = $_POST['produttore'];
}

if (isset($_POST['prezzo_min'])) {
    $params['prezzo_min'] = $_POST['prezzo_min'];
}

if (isset($_POST['prezzo_max'])) {
    $params['prezzo_max'] = $_POST['prezzo_max'];
}

if (isset($_POST['disponibile'])) {
    $params['disponibile'] = $_POST['disponibile'];
}

// Costruisci URL
$apiUrl = 'https://www.coded4u.com/api.php?' . http_build_query($params);

// Chiama API usando cURL (compatibile con allow_url_fopen disabilitato)
try {
    // Verifica che cURL sia disponibile
    if (!function_exists('curl_init')) {
        echo json_encode([
            'success' => false,
            'error' => 'cURL non è disponibile su questo server',
            'details' => 'Contatta il provider di hosting per abilitare l\'estensione cURL'
        ]);
        exit;
    }

    // Inizializza cURL
    $ch = curl_init();

    // Configura opzioni cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'PHP-API-Proxy/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Cache-Control: no-cache'
        ]
    ]);

    // Esegui richiesta
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    curl_close($ch);

    // Verifica errori cURL
    if ($response === false || $curlErrno !== 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Errore nella chiamata API',
            'details' => $curlError ?: 'Errore cURL sconosciuto (errno: ' . $curlErrno . ')',
            'url' => $apiUrl
        ]);
        exit;
    }

    // Verifica HTTP status code
    if ($httpCode !== 200) {
        echo json_encode([
            'success' => false,
            'error' => 'Errore HTTP ' . $httpCode,
            'details' => 'Il server ha risposto con codice ' . $httpCode,
            'response' => substr($response, 0, 500),
            'url' => $apiUrl
        ]);
        exit;
    }

    // Controlla se la risposta è JSON valido
    $jsonTest = json_decode($response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => 'Risposta API non valida',
            'details' => 'La risposta non è un JSON valido: ' . json_last_error_msg(),
            'response' => substr($response, 0, 500),
            'url' => $apiUrl
        ]);
        exit;
    }

    // Ritorna la risposta dell'API
    header('Content-Type: application/json');
    echo $response;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore server: ' . $e->getMessage(),
        'url' => $apiUrl
    ]);
}
