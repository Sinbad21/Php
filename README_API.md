# 📡 API Hardware Components - Documentazione Completa

API REST per interrogare il database di componenti hardware PC con sistema di tracking integrato.

---

## 📋 Tabelle Disponibili

L'API permette di interrogare le seguenti tabelle di componenti hardware:

- **`alimentatore`** - Alimentatori (PSU)
- **`case`** - Case per PC
- **`cpu`** - Processori
- **`dissipatore`** - Dissipatori/Cooler CPU
- **`gpu`** - Schede Video
- **`hdd`** - Hard Disk
- **`ram`** - Memorie RAM
- **`scheda_aggiuntiva`** - Schede di espansione
- **`scheda_madre`** - Schede Madri (Motherboard)
- **`ssd`** - Solid State Drive

---

## 🔐 Autenticazione

Tutte le richieste API richiedono un'**API key** valida. L'API key può essere fornita in tre modi:

### 1. Parametro GET
```
api.php?api_key=TUA_CHIAVE&action=ping
```

### 2. Parametro POST
```bash
curl -X POST https://coded4u.com/api.php \
  -d "api_key=TUA_CHIAVE" \
  -d "action=stats"
```

### 3. Header HTTP
```bash
curl -X GET https://coded4u.com/api.php?action=ping \
  -H "X-API-Key: TUA_CHIAVE"
```

---

## 🎯 Endpoint Disponibili

### 1️⃣ **PING** - Test Connessione

Verifica che l'API sia attiva e funzionante.

**Parametri:**
- Nessuno

**Esempio:**
```
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=ping
```

**Risposta:**
```json
{
  "success": true,
  "user": {
    "username": "utente_demo",
    "request_count": 15
  },
  "data": {
    "message": "API Hardware Components attiva e funzionante",
    "server_time": "2025-11-10 15:30:45",
    "database": "connesso",
    "tabelle_disponibili": ["alimentatore", "case", "cpu", "dissipatore", "gpu", "hdd", "ram", "scheda_aggiuntiva", "scheda_madre", "ssd"]
  },
  "timestamp": "2025-11-10 15:30:45"
}
```

---

### 2️⃣ **LIST** - Elenca Componenti

Restituisce un elenco di componenti da una tabella specifica con paginazione.

**Parametri:**
- `tabella` (obbligatorio) - Nome della tabella da interrogare
- `limit` (opzionale, default: 20, max: 100) - Numero di risultati per pagina
- `offset` (opzionale, default: 0) - Offset per paginazione

**Esempi:**

```bash
# Primi 10 processori
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=list&tabella=cpu&limit=10

# Schede video dalla 21 alla 40
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=list&tabella=gpu&limit=20&offset=20

# Tutte le RAM (max 100 per chiamata)
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=list&tabella=ram&limit=100
```

**Risposta:**
```json
{
  "success": true,
  "user": {
    "username": "utente_demo",
    "request_count": 16
  },
  "data": {
    "message": "Lista componenti recuperata",
    "tabella": "cpu",
    "totale_elementi": 150,
    "elementi_restituiti": 10,
    "offset": 0,
    "limit": 10,
    "pagina_corrente": 1,
    "totale_pagine": 15,
    "dati": [
      {
        "id": 1,
        "nome": "Intel Core i9-14900K",
        "produttore": "Intel",
        "modello": "i9-14900K",
        "socket": "LGA1700",
        "numero_core": 24,
        "numero_thread": 32,
        "frequenza_base": 3.2,
        "frequenza_turbo_max": 6.0,
        "tdp": 125,
        "prezzo": 589.99,
        "quantita": 5
        // ... altri campi
      }
      // ... altri elementi
    ]
  },
  "timestamp": "2025-11-10 15:35:12"
}
```

---

### 3️⃣ **GET** - Recupera Singolo Componente

Restituisce i dettagli completi di un singolo componente tramite ID.

**Parametri:**
- `tabella` (obbligatorio) - Nome della tabella
- `id` (obbligatorio) - ID numerico del componente

**Esempi:**

```bash
# Dettagli processore con ID 5
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=get&tabella=cpu&id=5

# Dettagli scheda video con ID 12
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=get&tabella=gpu&id=12

# Dettagli scheda madre con ID 3
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=get&tabella=scheda_madre&id=3
```

**Risposta Successo:**
```json
{
  "success": true,
  "user": {
    "username": "utente_demo",
    "request_count": 17
  },
  "data": {
    "message": "Componente recuperato",
    "tabella": "gpu",
    "id": 12,
    "dati": {
      "id": 12,
      "nome": "NVIDIA GeForce RTX 4080",
      "produttore": "NVIDIA",
      "serie": "GeForce RTX 40",
      "modello": "RTX 4080",
      "architettura": "Ada Lovelace",
      "capacita_memoria": 16,
      "tipo_memoria": "GDDR6X",
      "tdp": 320,
      "prezzo": 1199.00,
      "quantita": 8
      // ... tutti gli altri campi della GPU
    }
  },
  "timestamp": "2025-11-10 15:40:22"
}
```

**Risposta Errore (404):**
```json
{
  "success": false,
  "error": "Componente non trovato",
  "message": "Nessun componente con ID 999 nella tabella gpu",
  "timestamp": "2025-11-10 15:42:10"
}
```

---

### 4️⃣ **SEARCH** - Cerca con Filtri

Cerca componenti applicando filtri su vari campi.

**Parametri:**
- `tabella` (obbligatorio) - Nome della tabella
- `nome` (opzionale) - Cerca nel nome del prodotto (LIKE)
- `produttore` (opzionale) - Cerca per produttore (LIKE)
- `prezzo_min` (opzionale) - Prezzo minimo
- `prezzo_max` (opzionale) - Prezzo massimo
- `disponibile` (opzionale) - Solo prodotti con quantità > 0
- `limit` (opzionale, default: 20) - Risultati per pagina
- `offset` (opzionale, default: 0) - Offset paginazione

**Esempi:**

```bash
# Cerca CPU Intel sotto i 300€
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=search&tabella=cpu&produttore=Intel&prezzo_max=300

# Cerca RAM Corsair disponibili
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=search&tabella=ram&produttore=Corsair&disponibile=1

# Cerca GPU con "RTX" nel nome tra 500 e 1000€
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=search&tabella=gpu&nome=RTX&prezzo_min=500&prezzo_max=1000

# Cerca SSD con prezzo massimo 150€, primi 50 risultati
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=search&tabella=ssd&prezzo_max=150&limit=50
```

**Risposta:**
```json
{
  "success": true,
  "user": {
    "username": "utente_demo",
    "request_count": 18
  },
  "data": {
    "message": "Ricerca completata",
    "tabella": "ram",
    "filtri_applicati": ["produttore"],
    "totale_risultati": 45,
    "risultati_restituiti": 20,
    "offset": 0,
    "limit": 20,
    "dati": [
      {
        "id": 7,
        "nome": "Corsair Vengeance RGB 32GB DDR5",
        "produttore": "Corsair",
        "tipo_memoria": "DDR5",
        "capacita_totale": 32,
        "frequenza": 6000,
        "prezzo": 149.99,
        "quantita": 12
      }
      // ... altri risultati
    ]
  },
  "timestamp": "2025-11-10 15:45:33"
}
```

---

### 5️⃣ **STATS** - Statistiche Utilizzo

Restituisce statistiche sul proprio utilizzo dell'API.

**Parametri:**
- Nessuno

**Esempio:**
```bash
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=stats
```

**Risposta:**
```json
{
  "success": true,
  "user": {
    "username": "utente_demo",
    "request_count": 19
  },
  "data": {
    "message": "Statistiche utente",
    "username": "utente_demo",
    "total_requests": 19,
    "tabelle_disponibili": 10,
    "lista_tabelle": ["alimentatore", "case", "cpu", "dissipatore", "gpu", "hdd", "ram", "scheda_aggiuntiva", "scheda_madre", "ssd"]
  },
  "timestamp": "2025-11-10 15:50:00"
}
```

---

### 6️⃣ **HELP** - Documentazione

Restituisce la documentazione dell'API in formato JSON.

**Parametri:**
- Nessuno

**Esempio:**
```bash
GET https://coded4u.com/api.php?api_key=TUA_KEY&action=help
# oppure semplicemente
GET https://coded4u.com/api.php?api_key=TUA_KEY
```

---

## ⚠️ Gestione Errori

L'API restituisce sempre risposte JSON strutturate. In caso di errore, il campo `success` sarà `false`.

### Errore 401 - API Key Mancante
```json
{
  "success": false,
  "error": "API key mancante",
  "message": "Fornire un'API key valida tramite parametro api_key o header X-API-Key",
  "timestamp": "2025-11-10 16:00:00"
}
```

### Errore 403 - API Key Non Valida
```json
{
  "success": false,
  "error": "API key non valida",
  "message": "L'API key fornita non corrisponde a nessun utente",
  "timestamp": "2025-11-10 16:01:00"
}
```

### Errore 400 - Parametri Mancanti
```json
{
  "success": false,
  "error": "Tabella mancante",
  "message": "Specificare il parametro \"tabella\"",
  "tabelle_disponibili": ["alimentatore", "case", "cpu", ...],
  "timestamp": "2025-11-10 16:02:00"
}
```

### Errore 404 - Risorsa Non Trovata
```json
{
  "success": false,
  "error": "Componente non trovato",
  "message": "Nessun componente con ID 999 nella tabella cpu",
  "timestamp": "2025-11-10 16:03:00"
}
```

### Errore 500 - Errore Server
```json
{
  "success": false,
  "error": "Errore database",
  "message": "Si è verificato un errore nel processamento della richiesta",
  "timestamp": "2025-11-10 16:04:00"
}
```

---

## 🔧 Esempi Pratici

### cURL

```bash
# Test connessione
curl "https://coded4u.com/api.php?api_key=TUA_KEY&action=ping"

# Lista prime 10 CPU
curl "https://coded4u.com/api.php?api_key=TUA_KEY&action=list&tabella=cpu&limit=10"

# Cerca GPU NVIDIA
curl "https://coded4u.com/api.php?api_key=TUA_KEY&action=search&tabella=gpu&produttore=NVIDIA"

# Con header personalizzato
curl -H "X-API-Key: TUA_KEY" "https://coded4u.com/api.php?action=stats"
```

### JavaScript (Fetch API)

```javascript
// Configurazione
const API_URL = 'https://coded4u.com/api.php';
const API_KEY = 'TUA_CHIAVE_API';

// Funzione helper
async function apiCall(action, params = {}) {
  const url = new URL(API_URL);
  url.searchParams.append('api_key', API_KEY);
  url.searchParams.append('action', action);

  Object.keys(params).forEach(key => {
    url.searchParams.append(key, params[key]);
  });

  const response = await fetch(url);
  return await response.json();
}

// Esempi di utilizzo
async function esempi() {
  // Ping
  const ping = await apiCall('ping');
  console.log(ping);

  // Lista CPU
  const cpuList = await apiCall('list', {
    tabella: 'cpu',
    limit: 10
  });
  console.log(cpuList.data.dati);

  // Cerca RAM
  const ramSearch = await apiCall('search', {
    tabella: 'ram',
    produttore: 'Corsair',
    prezzo_max: 200
  });
  console.log(ramSearch.data.dati);

  // Dettaglio GPU
  const gpu = await apiCall('get', {
    tabella: 'gpu',
    id: 5
  });
  console.log(gpu.data.dati);
}
```

### Python (requests)

```python
import requests

API_URL = 'https://coded4u.com/api.php'
API_KEY = 'TUA_CHIAVE_API'

def api_call(action, **params):
    params['api_key'] = API_KEY
    params['action'] = action
    response = requests.get(API_URL, params=params)
    return response.json()

# Esempi
# Test connessione
ping = api_call('ping')
print(ping)

# Lista CPU
cpu_list = api_call('list', tabella='cpu', limit=10)
for cpu in cpu_list['data']['dati']:
    print(f"{cpu['nome']} - €{cpu['prezzo']}")

# Cerca GPU disponibili sotto 1000€
gpu_search = api_call('search',
    tabella='gpu',
    prezzo_max=1000,
    disponibile=1
)
print(f"Trovate {gpu_search['data']['totale_risultati']} GPU")

# Dettaglio singolo componente
componente = api_call('get', tabella='ssd', id=3)
print(componente['data']['dati'])
```

### PHP

```php
<?php
$apiUrl = 'https://coded4u.com/api.php';
$apiKey = 'TUA_CHIAVE_API';

function apiCall($action, $params = []) {
    global $apiUrl, $apiKey;

    $params['api_key'] = $apiKey;
    $params['action'] = $action;

    $url = $apiUrl . '?' . http_build_query($params);
    $response = file_get_contents($url);

    return json_decode($response, true);
}

// Esempi
$ping = apiCall('ping');
print_r($ping);

$cpuList = apiCall('list', [
    'tabella' => 'cpu',
    'limit' => 10
]);

foreach ($cpuList['data']['dati'] as $cpu) {
    echo "{$cpu['nome']} - €{$cpu['prezzo']}\n";
}

$ramSearch = apiCall('search', [
    'tabella' => 'ram',
    'produttore' => 'Corsair',
    'prezzo_max' => 200
]);
?>
```

---

## 📊 Limiti e Rate Limiting

- **Massimo risultati per chiamata**: 100 elementi
- **Paginazione**: Usa `offset` e `limit` per navigare risultati numerosi
- **Timeout**: Le query sono ottimizzate, ma cerca di evitare chiamate troppo complesse

---

## 💡 Best Practices

1. **Caching**: Cachea i risultati lato client per ridurre chiamate API
2. **Paginazione**: Non richiedere più dati di quelli necessari
3. **Gestione Errori**: Implementa sempre retry logic per errori 500
4. **Sicurezza**: Non esporre mai l'API key nel frontend (usa un proxy backend)
5. **Compressione**: L'API supporta gzip compression

---

## 🔗 Risorse Aggiuntive

- **Dashboard Admin**: `https://coded4u.com/dashboard.php`
- **Login Admin**: `https://coded4u.com/login.php`
- **Supporto**: Contatta l'amministratore del sistema

---

**Ultima revisione**: 2025-11-10
**Versione API**: 2.0
