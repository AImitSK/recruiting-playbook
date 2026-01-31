# KI-Matching: Phase 3 - Claude API Integration

> **Voraussetzung:** [Phase 2 abgeschlossen](./ki-matching-phase-2-anonymization.md)

## Ziel dieser Phase

Integration der Claude API für die Matching-Analyse:
- Anonymisierte Daten an Claude senden
- Prompt Engineering für Match-Score
- Ergebnis-Parsing und Kategorisierung

---

## Versionen & Abhängigkeiten (Stand: Januar 2026)

| Komponente | Version | Hinweis |
|------------|---------|---------|
| `@anthropic-ai/sdk` | 0.71.2 | Anthropic TypeScript SDK (kompatibel mit OpenRouter) |
| Claude Modell | `anthropic/claude-haiku-4.5` | Via OpenRouter |
| Hono | 4.11.x | Web Framework für Workers |
| OpenRouter | - | Unified AI API Gateway |

### Warum OpenRouter statt direkte Anthropic API?

| Aspekt | OpenRouter | Direkte Anthropic |
|--------|------------|-------------------|
| **Unified Billing** | ✅ Ein Account für alle Modelle | ❌ Separate Accounts |
| **Model Switching** | ✅ Einfach wechseln | ❌ Code-Änderungen nötig |
| **Fallback** | ✅ Automatisch bei Outages | ❌ Manuell |
| **Pricing** | Gleich ($1/$5 pro MTok für Haiku) | Gleich |

### Modell-Auswahl (OpenRouter IDs)

| Modell | OpenRouter ID | Preis (Input/Output) | Empfehlung |
|--------|---------------|----------------------|------------|
| **Haiku 4.5** | `anthropic/claude-haiku-4.5` | $1 / $5 pro MTok | ✅ Für CV-Matching |
| Sonnet 4 | `anthropic/claude-sonnet-4` | $3 / $15 pro MTok | Komplexere Analysen |
| Opus 4.5 | `anthropic/claude-opus-4.5` | $15 / $75 pro MTok | Premium-Features |

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ANALYSE FLOW                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Anonymisierter Text              OpenRouter                        │
│  (aus Phase 2)                    (Claude API)                      │
│        │                                                             │
│        ▼                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐ │
│  │  Prompt Builder │───▶│ Claude Haiku 4.5│───▶│ Response Parser │ │
│  │                 │    │                 │    │                 │ │
│  │  • CV Text      │    │  • Analyse      │    │  • Score        │ │
│  │  • Job-Daten    │    │  • Vergleich    │    │  • Kategorie    │ │
│  │  • Anweisungen  │    │  • Bewertung    │    │  • Message      │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────┘ │
│                                                       │             │
│                                                       ▼             │
│                                                ┌─────────────┐     │
│                                                │   Result    │     │
│                                                │             │     │
│                                                │  score: 78  │     │
│                                                │  cat: good  │     │
│                                                │  msg: "..." │     │
│                                                └─────────────┘     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 1. Dependencies installieren

```bash
cd api/recruiting-playbook-api
npm install @anthropic-ai/sdk@0.71.2
```

---

## 2. Claude Client mit OpenRouter

### src/services/claude.ts

```typescript
import Anthropic from '@anthropic-ai/sdk';

// OpenRouter Konfiguration
// Wichtig: OHNE /v1 - das Anthropic SDK fügt /v1/messages automatisch hinzu
const OPENROUTER_BASE_URL = 'https://openrouter.ai/api';

// Modell-Konstante (OpenRouter Model ID)
const DEFAULT_MODEL = 'anthropic/claude-haiku-4.5';

export interface MatchResult {
  score: number;           // 0-100
  category: 'low' | 'medium' | 'high';
  message: string;
  details?: {
    matchedSkills: string[];
    missingSkills: string[];
    recommendations: string[];
  };
}

export interface JobData {
  title: string;
  description: string;
  requirements: string[];
  niceToHave?: string[];
  location?: string;
  employmentType?: string;
}

export class ClaudeService {
  private client: Anthropic;
  private model: string;

  /**
   * @param apiKey - OpenRouter API Key
   * @param model - Optional: Modell überschreiben (default: claude-haiku-4.5)
   */
  constructor(apiKey: string, model?: string) {
    this.client = new Anthropic({
      apiKey,
      baseURL: OPENROUTER_BASE_URL,
    });
    this.model = model || DEFAULT_MODEL;
  }

  /**
   * CV mit Job matchen
   */
  async analyzeMatch(
    anonymizedCV: string,
    jobData: JobData,
  ): Promise<MatchResult> {
    const prompt = this.buildPrompt(anonymizedCV, jobData);

    const response = await this.client.messages.create({
      model: this.model,
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: prompt,
        },
      ],
    });

    const content = response.content[0];
    if (content.type !== 'text') {
      throw new Error('Unexpected response type');
    }

    return this.parseResponse(content.text);
  }

  /**
   * CV mit Job matchen (Vision für Bilder)
   */
  async analyzeMatchWithImage(
    imageBase64: string,
    mimeType: string,
    jobData: JobData,
  ): Promise<MatchResult> {
    const prompt = this.buildPrompt('', jobData, true);

    const response = await this.client.messages.create({
      model: this.model,
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: [
            {
              type: 'image',
              source: {
                type: 'base64',
                media_type: mimeType as 'image/jpeg' | 'image/png' | 'image/webp' | 'image/gif',
                data: imageBase64,
              },
            },
            {
              type: 'text',
              text: prompt,
            },
          ],
        },
      ],
    });

    const content = response.content[0];
    if (content.type !== 'text') {
      throw new Error('Unexpected response type');
    }

    return this.parseResponse(content.text);
  }

  // ... buildPrompt, parseResponse, etc.
}
```

---

## 3. Secrets konfigurieren

```bash
cd api/recruiting-playbook-api

# OpenRouter API Key (erforderlich)
wrangler secret put OPENROUTER_API_KEY
# → OpenRouter API Key eingeben (von https://openrouter.ai/keys)
```

### Environment Types (src/types/index.ts)

```typescript
export interface Bindings {
  DB: D1Database;
  CACHE: KVNamespace;
  STORAGE: R2Bucket;
  OPENROUTER_API_KEY: string;  // OpenRouter API Key
  ENVIRONMENT: string;
  FREEMIUS_PRODUCT_ID: string;
  FREEMIUS_DEV_ID: string;
  FREEMIUS_DEV_PUBLIC_KEY: string;
  FREEMIUS_DEV_SECRET_KEY: string;
  FREEMIUS_WEBHOOK_SECRET: string;
  PRESIDIO_URL: string;
  PRESIDIO_API_KEY?: string;
}
```

---

## 4. Development Mode

Für lokales Testen ohne echte Freemius-Lizenz:

### .dev.vars (lokale Entwicklung)

```env
ENVIRONMENT=development
OPENROUTER_API_KEY=sk-or-v1-xxx
```

### Auth Middleware

Die Auth-Middleware erlaubt in Development-Mode Test-Requests:

```typescript
// Header für Test-Requests:
// X-Test-Mode: true

if (c.env.ENVIRONMENT === 'development') {
  const testMode = c.req.header('X-Test-Mode');
  if (testMode === 'true') {
    // Simulierte Test-Lizenz
    c.set('license', {
      installId: 'test-install-123',
      planName: 'ai_addon',
      // ...
    });
    await next();
    return;
  }
}
```

### Lokales Testen

```bash
# Worker starten
cd api/recruiting-playbook-api
npx wrangler dev

# Test-Request
curl -X POST http://localhost:8787/v1/analysis/start \
  -H "Content-Type: application/json" \
  -H "X-Test-Mode: true" \
  -d '{
    "anonymizedText": "Erfahrener Entwickler mit 5 Jahren React...",
    "jobData": {
      "title": "Frontend Developer",
      "description": "Wir suchen...",
      "requirements": ["React", "TypeScript"]
    }
  }'
```

---

## 5. API-Dokumentation

### Endpoints

| Method | Endpoint | Beschreibung |
|--------|----------|--------------|
| POST | `/v1/analysis/upload` | Datei hochladen + Analyse starten |
| POST | `/v1/analysis/start` | Bereits anonymisierte Daten analysieren |
| GET | `/v1/analysis/:id` | Status/Ergebnis abrufen |

### Request: /v1/analysis/upload

```bash
curl -X POST https://recruiting-playbook-api.s-kuehne.workers.dev/v1/analysis/upload \
  -H "X-Freemius-Install-Id: 12345" \
  -H "X-Freemius-Timestamp: 2026-01-31T12:00:00Z" \
  -H "X-Freemius-Signature: sha256-xxx" \
  -F "file=@lebenslauf.pdf" \
  -F 'jobData={"title":"Pflegefachkraft","description":"...","requirements":["Examen","3 Jahre Erfahrung"]}'
```

### Response (Pending)

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "message": "Analyse gestartet. Status abrufen mit GET /v1/analysis/{job_id}"
}
```

### Response (Completed)

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "created_at": 1769866591,
  "result": {
    "score": 92,
    "category": "high",
    "message": "Der Kandidat erfüllt alle MUSS-Anforderungen hervorragend."
  },
  "completed_at": 1769866604
}
```

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ OpenRouter Integration mit Claude Haiku 4.5
- ✅ Prompt Engineering für Match-Score (Deutsch)
- ✅ Text- und Bild-Analyse (Vision)
- ✅ Async Verarbeitung mit Polling
- ✅ Vollständiger Upload→Anonymisierung→Analyse Flow
- ✅ Development Mode für lokales Testen

### Deployed Services

| Service | URL |
|---------|-----|
| API Worker | `recruiting-playbook-api.s-kuehne.workers.dev` |
| Presidio | `presidio.recruiting-playbook.com` |

---

## Nächste Phase

→ [Phase 4: WordPress Frontend](./ki-matching-phase-4-frontend.md)
