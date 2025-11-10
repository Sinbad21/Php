<?php
/**
 * ===================================================
 * FILE DI CONFIGURAZIONE DATABASE
 * Gestisce la connessione MySQL per il sistema API
 * ===================================================
 */

// Previeni accesso diretto al file
if (!defined('API_ACCESS')) {
    die('Accesso negato');
}

// ===================================================
// CONFIGURAZIONE DATABASE MYSQL
// Modifica questi parametri con quelli del tuo hosting Aruba
// ===================================================

// Host del database (indirizzo IP specifico del server MySQL Aruba)
define('DB_HOST', '31.11.39.251:3306');

// Nome del database (fornito da Aruba nel pannello di controllo)
define('DB_NAME', 'Sql1897398_1');

// Username del database (fornito da Aruba)
define('DB_USER', 'Sql1897398');

// Password del database (fornito da Aruba)
define('DB_PASS', 'nezkyT-gaxru4-forzih');

// Charset della connessione (lasciare utf8mb4 per supporto completo emoji e caratteri speciali)
define('DB_CHARSET', 'utf8mb4');

// ===================================================
// CONFIGURAZIONE APPLICAZIONE
// ===================================================

// Attiva la visualizzazione errori in sviluppo (DISATTIVARE IN PRODUZIONE!)
define('DEBUG_MODE', true);

// Timezone dell'applicazione
date_default_timezone_set('Europe/Rome');

// Avvia la sessione PHP se non già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===================================================
// FUNZIONE DI CONNESSIONE DATABASE
// Crea e restituisce un oggetto PDO per le query
// ===================================================
function getDatabaseConnection() {
    try {
        // Crea DSN (Data Source Name) per PDO
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        // Opzioni PDO per sicurezza e prestazioni
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Genera eccezioni in caso di errore
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associativo di default
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Usa prepared statements nativi
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET // Forza charset
        ];

        // Crea la connessione PDO
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        return $pdo;

    } catch (PDOException $e) {
        // Gestione errore connessione
        if (DEBUG_MODE) {
            // In modalità debug, mostra l'errore completo
            die('Errore di connessione al database: ' . $e->getMessage());
        } else {
            // In produzione, mostra messaggio generico
            die('Errore di connessione al database. Contattare l\'amministratore.');
        }
    }
}

// ===================================================
// FUNZIONI UTILITY
// ===================================================

/**
 * Genera una API key univoca e sicura
 * @return string API key di 64 caratteri esadecimali
 */
function generateApiKey() {
    return bin2hex(random_bytes(32));
}

/**
 * Risponde con JSON e termina l'esecuzione
 * @param array $data Dati da restituire in formato JSON
 * @param int $httpCode Codice HTTP di risposta (default 200)
 */
function jsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Pulisce e valida l'input utente
 * @param string $data Dato da pulire
 * @return string Dato pulito
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Ottiene l'indirizzo IP reale del client (anche dietro proxy/CDN)
 * @return string Indirizzo IP del client
 */
function getClientIP() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Prendi il primo IP dalla lista (il client originale)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    return $ip;
}

/**
 * Ottiene lo User-Agent del client
 * @return string User-Agent string
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// ===================================================
// FINE FILE CONFIGURAZIONE
// ===================================================
