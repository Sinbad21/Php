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

// Chiama API
try {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        echo json_encode([
            'success' => false,
            'error' => 'Errore nella chiamata API'
        ]);
        exit;
    }

    // Ritorna la risposta dell'API
    header('Content-Type: application/json');
    echo $response;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore server: ' . $e->getMessage()
    ]);
}
