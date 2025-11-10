<?php
/**
 * ===================================================
 * VERIFICA PASSWORD E MOSTRA API KEY
 * Endpoint AJAX per utenti
 * ===================================================
 */

define('API_ACCESS', true);
require_once 'config.php';

// Solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Verifica che sia un utente loggato
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

$pdo = getDatabaseConnection();
$userId = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Password mancante']);
    exit;
}

try {
    // Verifica password
    $stmt = $pdo->prepare("SELECT password_hash, api_key FROM utenti WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Password corretta
        echo json_encode([
            'success' => true,
            'api_key' => $user['api_key']
        ]);
    } else {
        // Password errata
        echo json_encode([
            'success' => false,
            'error' => 'Password non corretta'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore server'
    ]);
}
