# 🔐 Sistema API Tracking con Dashboard Admin

Sistema completo di API tracking per utenti con dashboard amministrativa, completamente compatibile con hosting condiviso come **Aruba Hosting Easy Linux**.

---

## 📋 Indice

- [Caratteristiche](#-caratteristiche)
- [Requisiti](#-requisiti)
- [Struttura Progetto](#-struttura-progetto)
- [Installazione](#-installazione)
- [Configurazione](#-configurazione)
- [Utilizzo](#-utilizzo)
- [Sicurezza](#-sicurezza)
- [FAQ](#-faq)

---

## ✨ Caratteristiche

- ✅ **Sistema API completo** con autenticazione tramite API key
- ✅ **Tracking richieste** per ogni utente
- ✅ **Dashboard amministrativa** moderna e responsive
- ✅ **Sistema di login sicuro** con password hashate
- ✅ **Log dettagliato** di tutte le chiamate API
- ✅ **Statistiche in tempo reale** su utenti e richieste
- ✅ **Compatibile con hosting condiviso** (Aruba, SiteGround, ecc.)
- ✅ **Codice sicuro** con prepared statements PDO
- ✅ **Interfaccia user-friendly** con design moderno

---

## 🔧 Requisiti

- **PHP**: 7.0 o superiore (consigliato 7.4+)
- **MySQL**: 5.5 o superiore (consigliato 5.7+)
- **Estensioni PHP necessarie**:
  - PDO
  - PDO_MySQL
  - mbstring
  - session

> **Nota**: Tutti i requisiti sono soddisfatti di default dai piani di hosting Aruba Easy Linux.

---

## 📁 Struttura Progetto

```
/
├── config.php           # Configurazione database e funzioni utility
├── api.php              # Endpoint API pubblico
├── login.php            # Pagina di login amministratore
├── dashboard.php        # Dashboard amministrativa
├── logout.php           # Logout amministratore
├── setup.sql            # Script creazione database
└── README.md            # Questa documentazione
```

---

## 🚀 Installazione

### 1️⃣ Carica i File sul Server

Carica tutti i file nella directory del tuo sito web (es. `public_html/` o sottocartella).

```bash
# Via FTP o File Manager del tuo hosting
/public_html/
  ├── config.php
  ├── api.php
  ├── login.php
  ├── dashboard.php
  ├── logout.php
  └── setup.sql
```

### 2️⃣ Crea il Database MySQL

Accedi al **pannello di controllo Aruba** (o altro hosting) e:

1. Vai nella sezione **Database MySQL**
2. Crea un nuovo database (es. `mio_database`)
3. Annota:
   - Nome database
   - Username database
   - Password database
   - Host (solitamente `localhost`)

### 3️⃣ Importa lo Schema Database

Accedi a **phpMyAdmin** dal pannello di controllo:

1. Seleziona il database creato
2. Vai su **Importa**
3. Seleziona il file `setup.sql`
4. Clicca su **Esegui**

Lo script creerà automaticamente:
- Tabella `utenti` (utenti API con chiavi)
- Tabella `log_richieste` (log chiamate)
- Tabella `admin` (amministratori)
- Utente admin di default (username: `admin`, password: `admin123`)
- 2 utenti di test con API key

---

## ⚙️ Configurazione

### Modifica config.php

Apri il file `config.php` e modifica i parametri del database:

```php
// Host del database (solitamente 'localhost' su Aruba)
define('DB_HOST', 'localhost');

// Nome del database (fornito da Aruba nel pannello di controllo)
define('DB_NAME', 'tuo_database');

// Username del database (fornito da Aruba)
define('DB_USER', 'tuo_username');

// Password del database (fornito da Aruba)
define('DB_PASS', 'tua_password');
```

### Debug Mode (solo sviluppo)

Per attivare la modalità debug durante lo sviluppo:

```php
define('DEBUG_MODE', true);  // Mostra errori dettagliati
```

⚠️ **IMPORTANTE**: In produzione impostare sempre `DEBUG_MODE` a `false` per sicurezza!

---

## 📖 Utilizzo

### 🔑 Accesso Dashboard Amministratore

1. Vai su `https://tuosito.it/login.php`
2. Usa le credenziali di default:
   - **Username**: `admin`
   - **Password**: `admin123`
3. ⚠️ **Cambia immediatamente la password di default!**

### 📊 Dashboard Features

Dalla dashboard puoi:

- ✅ Visualizzare tutti gli utenti registrati
- ✅ Vedere le API key di ogni utente (clicca per copiare)
- ✅ Monitorare il numero di richieste per utente
- ✅ Consultare il log delle chiamate recenti
- ✅ Statistiche generali del sistema

### 🔌 Utilizzo API

#### Chiamata Base (ping)

```bash
https://tuosito.it/api.php?api_key=demo_key_123456789abcdef
```

**Risposta JSON**:
```json
{
  "success": true,
  "user": {
    "username": "utente_demo",
    "request_count": 1
  },
  "data": {
    "message": "API attiva e funzionante",
    "action": "ping",
    "server_time": "2025-11-10 15:30:45"
  },
  "timestamp": "2025-11-10 15:30:45"
}
```

#### Chiamata con Azione Specifica

```bash
# Statistiche utente
https://tuosito.it/api.php?api_key=TUA_API_KEY&action=stats

# Richiesta dati
https://tuosito.it/api.php?api_key=TUA_API_KEY&action=data
```

#### Chiamata POST

```bash
curl -X POST https://tuosito.it/api.php \
  -d "api_key=TUA_API_KEY" \
  -d "action=stats"
```

#### Chiamata con Header Personalizzato

```bash
curl -X GET https://tuosito.it/api.php \
  -H "X-API-Key: TUA_API_KEY"
```

### ❌ Gestione Errori API

#### API Key Mancante (401)

```json
{
  "success": false,
  "error": "API key mancante",
  "message": "Fornire un'API key valida tramite parametro api_key o header X-API-Key",
  "timestamp": "2025-11-10 15:30:45"
}
```

#### API Key Non Valida (403)

```json
{
  "success": false,
  "error": "API key non valida",
  "message": "L'API key fornita non corrisponde a nessun utente",
  "timestamp": "2025-11-10 15:30:45"
}
```

---

## 🔐 Sicurezza

### Password Admin

**Cambiare immediatamente** la password di default!

Per generare una nuova password hashata, usa questo script PHP:

```php
<?php
$nuova_password = 'tua_password_sicura';
$hash = password_hash($nuova_password, PASSWORD_DEFAULT);
echo $hash;
?>
```

Poi aggiorna il database:

```sql
UPDATE admin SET password_hash = 'HASH_GENERATO_SOPRA' WHERE username = 'admin';
```

### Creare Nuovi Amministratori

```sql
INSERT INTO admin (username, password_hash) VALUES
('nuovo_admin', 'HASH_GENERATO_CON_password_hash');
```

### Generare Nuove API Key

Le API key devono essere stringhe casuali di 64 caratteri. Usa questa funzione PHP:

```php
<?php
echo bin2hex(random_bytes(32));
// Output: 3f5a8b9c2d1e4f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0
?>
```

### Aggiungere Nuovi Utenti API

```sql
INSERT INTO utenti (username, api_key, richieste) VALUES
('nome_utente', 'API_KEY_GENERATA_64_CARATTERI', 0);
```

### Best Practices

1. ✅ **Usa HTTPS** in produzione (certificato SSL)
2. ✅ **Cambia password admin** di default
3. ✅ **Disattiva DEBUG_MODE** in produzione
4. ✅ **Backup regolare** del database
5. ✅ **Monitora i log** per attività sospette
6. ✅ **Genera API key uniche** per ogni utente
7. ✅ **Limita accessi** alla dashboard (firewall, IP whitelist)

---

## 🛠 FAQ

### Come personalizzare l'API?

Modifica il file `api.php` nella sezione "ESEMPI DI AZIONI API":

```php
switch ($action) {
    case 'mia_azione':
        // Tua logica personalizzata
        $responseData['data'] = /* ... */;
        break;
}
```

### Come resettare il contatore di un utente?

```sql
UPDATE utenti SET richieste = 0 WHERE username = 'nome_utente';
```

### Come visualizzare tutti i log di un utente?

```sql
SELECT * FROM log_richieste
WHERE utente_id = 1
ORDER BY data DESC;
```

### Come eliminare log vecchi?

```sql
-- Elimina log più vecchi di 30 giorni
DELETE FROM log_richieste
WHERE data < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Timeout sessione amministratore

Il timeout di default è **30 minuti**. Per modificarlo, apri `dashboard.php`:

```php
$timeout_duration = 3600; // 60 minuti (in secondi)
```

### Come abilitare CORS?

Se devi chiamare l'API da domini diversi, decommenta in `api.php`:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
```

### Errore "Accesso negato" al database?

Verifica le credenziali in `config.php` e controlla che l'utente MySQL abbia i permessi corretti.

---

## 📊 Query Utili

### Statistiche Generali

```sql
SELECT
    u.username,
    u.api_key,
    u.richieste,
    COUNT(l.id) as log_count,
    MAX(l.data) as ultima_richiesta
FROM utenti u
LEFT JOIN log_richieste l ON u.id = l.utente_id
GROUP BY u.id;
```

### Utenti più Attivi

```sql
SELECT username, richieste
FROM utenti
ORDER BY richieste DESC
LIMIT 10;
```

### Richieste per Giorno

```sql
SELECT
    DATE(data) as giorno,
    COUNT(*) as totale_richieste
FROM log_richieste
GROUP BY DATE(data)
ORDER BY giorno DESC;
```

---

## 📞 Supporto

Per problemi o domande:

1. Verifica che tutti i requisiti siano soddisfatti
2. Controlla i log degli errori PHP del tuo hosting
3. Verifica le credenziali database in `config.php`
4. Assicurati che `DEBUG_MODE` sia attivo durante il debug

---

## 📝 Licenza

Questo progetto è fornito "as-is" per uso personale e commerciale.

---

## 🎯 Prossimi Sviluppi (Opzionali)

- 🔄 Rate limiting per prevenire abusi
- 📧 Notifiche email per soglie di utilizzo
- 📈 Grafici e analytics avanzate
- 🔑 Gestione API key dalla dashboard
- 👥 Gestione utenti dalla dashboard (CRUD)
- 🌐 Multi-lingua
- 📱 App mobile per monitoraggio

---

**Creato per hosting condiviso Aruba Easy Linux** 🚀
