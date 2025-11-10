-- ===================================================
-- Script di Setup Database per Sistema API Tracking
-- Compatibile con MySQL 5.5+ (Aruba Hosting)
-- ===================================================

-- Creazione database (opzionale, dipende dalla configurazione Aruba)
-- CREATE DATABASE IF NOT EXISTS api_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE api_tracking;

-- ===================================================
-- Tabella UTENTI
-- Contiene gli utenti che possono usare l'API
-- ===================================================
CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    richieste INT DEFAULT 0,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- Tabella LOG_RICHIESTE
-- Registra ogni chiamata API effettuata
-- ===================================================
CREATE TABLE IF NOT EXISTS log_richieste (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_utente_id (utente_id),
    INDEX idx_data (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- Tabella ADMIN
-- Contiene le credenziali degli amministratori
-- ===================================================
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- Inserimento dati di esempio
-- ===================================================

-- Inserisci un amministratore di default
-- Username: admin
-- Password: admin123 (CAMBIARE IN PRODUZIONE!)
-- Hash generato con password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admin (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Inserisci alcuni utenti di test con API key
INSERT INTO utenti (username, api_key, richieste) VALUES
('utente_demo', 'demo_key_123456789abcdef', 0),
('test_user', 'test_key_987654321fedcba', 0);

-- ===================================================
-- Query utili per la gestione
-- ===================================================

-- Per generare una nuova API key (esegui in PHP):
-- bin2hex(random_bytes(32))

-- Per creare un nuovo admin:
-- INSERT INTO admin (username, password_hash) VALUES ('nuovo_admin', 'HASH_GENERATO_CON_password_hash');

-- Per resettare il contatore di un utente:
-- UPDATE utenti SET richieste = 0 WHERE username = 'nome_utente';

-- Per visualizzare statistiche:
-- SELECT u.username, u.api_key, u.richieste, COUNT(l.id) as log_count
-- FROM utenti u LEFT JOIN log_richieste l ON u.id = l.utente_id
-- GROUP BY u.id;
