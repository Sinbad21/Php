-- ===================================================
-- Aggiorna tabella UTENTI per aggiungere password
-- ===================================================

-- Aggiungi colonna password_hash alla tabella utenti
ALTER TABLE utenti
ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER api_key;

-- Imposta password temporanea 'password123' per gli utenti esistenti
-- Hash generato con password_hash('password123', PASSWORD_DEFAULT)
UPDATE utenti
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password_hash IS NULL;

-- Verifica che le modifiche siano state applicate
SELECT id, username, api_key, password_hash, richieste FROM utenti;
