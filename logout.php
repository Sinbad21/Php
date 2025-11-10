<?php
/**
 * ===================================================
 * LOGOUT AMMINISTRATORE
 * Termina la sessione e reindirizza al login
 * ===================================================
 */

// Permetti l'accesso al file di configurazione
define('API_ACCESS', true);

// Includi il file di configurazione (che avvia anche la sessione)
require_once 'config.php';

// ===================================================
// DISTRUGGI LA SESSIONE
// ===================================================

// Unset di tutte le variabili di sessione
$_SESSION = array();

// Se si desidera distruggere completamente la sessione, cancella anche il cookie di sessione
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Distruggi la sessione
session_destroy();

// ===================================================
// REINDIRIZZA AL LOGIN
// ===================================================

// Reindirizza alla pagina di login con messaggio di logout avvenuto
header('Location: login.php?logout=success');
exit;
