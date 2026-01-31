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
| `@anthropic-ai/sdk` | 0.71.2 | Anthropic TypeScript SDK |
| Claude Modell | `claude-haiku-4-5-20251001` | Schnellstes 4.5 Modell |
| Hono | 4.11.x | Web Framework für Workers |

### Modell-Auswahl

| Modell | API ID | Preis (Input/Output) | Empfehlung |
|--------|--------|----------------------|------------|
| **Haiku 4.5** | `claude-haiku-4-5-20251001` | $1 / $5 pro MTok | ✅ Für CV-Matching |
| Sonnet 4.5 | `claude-sonnet-4-5-20250929` | $3 / $15 pro MTok | Komplexere Analysen |
| Opus 4.5 | `claude-opus-4-5-20251101` | $5 / $25 pro MTok | Premium-Features |

> **Hinweis:** Für Produktion spezifische Model-IDs verwenden (nicht Aliase wie `claude-haiku-4-5`), um konsistentes Verhalten zu gewährleisten.

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ANALYSE FLOW                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Anonymisierter Text              Claude API                        │
│  (aus Phase 2)                                                       │
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

### Optional: Cloudflare AI Gateway

AI Gateway bietet Monitoring, Caching und Rate Limiting für API-Aufrufe:

1. **AI Gateway erstellen:** Cloudflare Dashboard → AI → Gateway → Create
2. **Gateway URL kopieren:** `https://gateway.ai.cloudflare.com/v1/{account_id}/{gateway_id}/anthropic`
3. **Als Secret speichern:** `wrangler secret put AI_GATEWAY_URL`

Vorteile:
- 📊 Request Analytics & Logging
- 💾 Response Caching (Kosten sparen)
- 🚦 Rate Limiting
- ⏱️ Latenz-Monitoring

---

## 2. Claude Client (Cloudflare Worker)

### src/services/claude.ts

```typescript
import Anthropic from '@anthropic-ai/sdk';

// Modell-Konstante für einfache Aktualisierung
const CLAUDE_MODEL = 'claude-haiku-4-5-20251001';

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

  /**
   * @param apiKey - Anthropic API Key
   * @param aiGatewayUrl - Optional: Cloudflare AI Gateway URL für Monitoring
   *                       Format: https://gateway.ai.cloudflare.com/v1/{account_id}/{gateway_id}/anthropic
   */
  constructor(apiKey: string, aiGatewayUrl?: string) {
    this.client = new Anthropic({
      apiKey,
      // Optional: AI Gateway für Caching, Rate Limiting, Analytics
      ...(aiGatewayUrl && { baseURL: aiGatewayUrl }),
    });
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
      model: CLAUDE_MODEL,
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: prompt,
        },
      ],
    });

    // Response parsen
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
      model: CLAUDE_MODEL,
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: [
            {
              type: 'image',
              source: {
                type: 'base64',
                media_type: mimeType as 'image/jpeg' | 'image/png' | 'image/webp',
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

  /**
   * Prompt für Matching-Analyse bauen
   */
  private buildPrompt(cvText: string, jobData: JobData, isImage: boolean = false): string {
    const cvSection = isImage
      ? 'Der Lebenslauf ist als Bild angehängt. Analysiere den sichtbaren Inhalt.'
      : `## Lebenslauf (anonymisiert)\n\n${cvText}`;

    return `Du bist ein erfahrener Recruiting-Experte. Deine Aufgabe ist es, einen Lebenslauf mit einer Stellenausschreibung zu vergleichen und einen Match-Score zu berechnen.

## Stellenausschreibung

**Titel:** ${jobData.title}

**Beschreibung:**
${jobData.description}

**Anforderungen (MUSS):**
${jobData.requirements.map(r => `- ${r}`).join('\n')}

${jobData.niceToHave?.length ? `**Wünschenswert (KANN):**\n${jobData.niceToHave.map(r => `- ${r}`).join('\n')}` : ''}

${cvSection}

## Aufgabe

Analysiere, wie gut der Kandidat zur Stelle passt. Beachte:
1. MUSS-Anforderungen sind wichtiger als KANN-Anforderungen
2. Berufserfahrung in ähnlichen Positionen ist ein Plus
3. Fehlende Anforderungen sind nicht automatisch ein Ausschluss

Antworte NUR mit einem validen JSON-Objekt in diesem Format:

{
  "score": <Zahl 0-100>,
  "category": "<low|medium|high>",
  "message": "<Kurze Begründung auf Deutsch, max 2 Sätze>",
  "matchedSkills": ["<Erfüllte Anforderung 1>", "<Erfüllte Anforderung 2>"],
  "missingSkills": ["<Fehlende Anforderung 1>"],
  "recommendations": ["<Tipp für Bewerbung>"]
}

Kategorien:
- "low" (0-40%): Eher nicht passend
- "medium" (41-70%): Teilweise passend
- "high" (71-100%): Gute Übereinstimmung

Antworte NUR mit dem JSON, ohne Erklärungen davor oder danach.`;
  }

  /**
   * Claude Response parsen
   */
  private parseResponse(text: string): MatchResult {
    // JSON aus Response extrahieren
    let jsonStr = text.trim();

    // Falls in Markdown Code-Block
    const jsonMatch = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
    if (jsonMatch) {
      jsonStr = jsonMatch[1].trim();
    }

    try {
      const data = JSON.parse(jsonStr);

      // Validierung
      const score = Math.max(0, Math.min(100, Number(data.score) || 0));
      const category = this.scoreToCategory(score);

      return {
        score,
        category,
        message: String(data.message || this.getDefaultMessage(category)),
        details: {
          matchedSkills: Array.isArray(data.matchedSkills) ? data.matchedSkills : [],
          missingSkills: Array.isArray(data.missingSkills) ? data.missingSkills : [],
          recommendations: Array.isArray(data.recommendations) ? data.recommendations : [],
        },
      };

    } catch (e) {
      // Fallback bei Parse-Fehler
      console.error('Failed to parse Claude response:', text);

      return {
        score: 50,
        category: 'medium',
        message: 'Die Analyse konnte nicht vollständig durchgeführt werden.',
      };
    }
  }

  /**
   * Score zu Kategorie mappen
   */
  private scoreToCategory(score: number): 'low' | 'medium' | 'high' {
    if (score <= 40) return 'low';
    if (score <= 70) return 'medium';
    return 'high';
  }

  /**
   * Default-Nachrichten pro Kategorie
   */
  private getDefaultMessage(category: 'low' | 'medium' | 'high'): string {
    const messages = {
      low: 'Diese Stelle passt wahrscheinlich nicht zu Ihrem Profil.',
      medium: 'Sie erfüllen einige Anforderungen. Eine Bewerbung könnte sich lohnen.',
      high: 'Ihr Profil passt gut zu dieser Stelle!',
    };
    return messages[category];
  }
}
```

---

## 3. Analysis Route (Cloudflare Worker)

### src/routes/analysis.ts

```typescript
import { Hono } from 'hono';
import type { Env } from '../types';
import { ClaudeService, JobData, MatchResult } from '../services/claude';
import { UsageService } from '../services/usage';

export const analysisRoutes = new Hono<{ Bindings: Env }>();

interface AnalysisRequest {
  anonymizedText?: string;
  anonymizedImageBase64?: string;
  imageMimeType?: string;
  jobData: JobData;
}

/**
 * POST /v1/analysis/start
 * Startet eine neue Matching-Analyse
 */
analysisRoutes.post('/start', async (c) => {
  const license = c.get('license');
  const usageService = new UsageService(c.env.DB);

  // Limit prüfen
  const canAnalyze = await usageService.canAnalyze(license.id);
  if (!canAnalyze) {
    return c.json({
      error: 'limit_reached',
      message: 'Monatliches Analyse-Limit erreicht',
    }, 429);
  }

  // Request-Body parsen
  const body = await c.req.json<AnalysisRequest>();

  // Validierung
  if (!body.jobData?.title || !body.jobData?.requirements?.length) {
    return c.json({
      error: 'invalid_request',
      message: 'Job data with title and requirements required',
    }, 400);
  }

  if (!body.anonymizedText && !body.anonymizedImageBase64) {
    return c.json({
      error: 'invalid_request',
      message: 'Either anonymizedText or anonymizedImageBase64 required',
    }, 400);
  }

  // Job-ID generieren
  const jobId = crypto.randomUUID();

  // Job in DB speichern (Status: pending)
  await c.env.DB
    .prepare(`
      INSERT INTO analysis_jobs (id, license_id, status, created_at)
      VALUES (?, ?, 'pending', unixepoch())
    `)
    .bind(jobId, license.id)
    .run();

  // Analyse asynchron starten
  c.executionCtx.waitUntil(
    processAnalysis(c.env, jobId, license.id, body)
  );

  return c.json({
    jobId,
    status: 'pending',
    message: 'Analyse gestartet',
  }, 202);
});

/**
 * GET /v1/analysis/:id
 * Status einer Analyse abfragen
 */
analysisRoutes.get('/:id', async (c) => {
  const jobId = c.req.param('id');
  const license = c.get('license');

  const job = await c.env.DB
    .prepare(`
      SELECT * FROM analysis_jobs
      WHERE id = ? AND license_id = ?
    `)
    .bind(jobId, license.id)
    .first();

  if (!job) {
    return c.json({
      error: 'not_found',
      message: 'Analyse nicht gefunden',
    }, 404);
  }

  const response: any = {
    jobId: job.id,
    status: job.status,
  };

  if (job.status === 'completed') {
    response.result = {
      score: job.result_score,
      category: job.result_category,
      message: job.result_message,
    };
  }

  if (job.status === 'failed') {
    response.error = job.error_message;
  }

  return c.json(response);
});

/**
 * Analyse im Hintergrund verarbeiten
 */
async function processAnalysis(
  env: Env,
  jobId: string,
  licenseId: number,
  request: AnalysisRequest,
): Promise<void> {

  try {
    // Status auf "processing" setzen
    await env.DB
      .prepare(`
        UPDATE analysis_jobs
        SET status = 'processing', started_at = unixepoch()
        WHERE id = ?
      `)
      .bind(jobId)
      .run();

    // Claude Service initialisieren
    const claude = new ClaudeService(env.CLAUDE_API_KEY, env.AI_GATEWAY_URL);

    let result: MatchResult;

    // Text oder Bild analysieren
    if (request.anonymizedText) {
      result = await claude.analyzeMatch(
        request.anonymizedText,
        request.jobData,
      );
    } else if (request.anonymizedImageBase64 && request.imageMimeType) {
      result = await claude.analyzeMatchWithImage(
        request.anonymizedImageBase64,
        request.imageMimeType,
        request.jobData,
      );
    } else {
      throw new Error('No input provided');
    }

    // Ergebnis speichern
    await env.DB
      .prepare(`
        UPDATE analysis_jobs
        SET
          status = 'completed',
          result_score = ?,
          result_category = ?,
          result_message = ?,
          completed_at = unixepoch()
        WHERE id = ?
      `)
      .bind(result.score, result.category, result.message, jobId)
      .run();

    // Usage hochzählen
    const usageService = new UsageService(env.DB);
    await usageService.incrementUsage(licenseId);

  } catch (error) {
    // Fehler speichern
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';

    await env.DB
      .prepare(`
        UPDATE analysis_jobs
        SET status = 'failed', error_message = ?, completed_at = unixepoch()
        WHERE id = ?
      `)
      .bind(errorMessage, jobId)
      .run();
  }
}
```

---

## 4. Vollständiger Flow mit Presidio

### src/services/presidio.ts

```typescript
export interface AnonymizeResult {
  type: 'text' | 'image';
  anonymizedText?: string;
  anonymizedImageBase64?: string;
  imageMimeType?: string;
  originalType: string;
  piiFound?: number;
}

export class PresidioService {
  constructor(
    private baseUrl: string,
    private apiKey?: string,
  ) {}

  /**
   * Dokument anonymisieren
   */
  async anonymize(
    fileBuffer: ArrayBuffer,
    filename: string,
    outputFormat: 'text' | 'auto' = 'auto',
  ): Promise<AnonymizeResult> {

    const formData = new FormData();
    formData.append('file', new Blob([fileBuffer]), filename);
    formData.append('output_format', outputFormat);
    formData.append('language', 'de');

    const headers: Record<string, string> = {};
    if (this.apiKey) {
      headers['X-API-Key'] = this.apiKey;
    }

    const response = await fetch(`${this.baseUrl}/api/v1/anonymize`, {
      method: 'POST',
      body: formData,
      headers,
    });

    if (!response.ok) {
      const error = await response.text();
      throw new Error(`Presidio error: ${error}`);
    }

    const contentType = response.headers.get('content-type');

    // JSON Response (Text)
    if (contentType?.includes('application/json')) {
      const data = await response.json();
      return {
        type: 'text',
        anonymizedText: data.anonymized_text,
        originalType: data.original_type,
        piiFound: data.pii_found,
      };
    }

    // Binary Response (Bild)
    const buffer = await response.arrayBuffer();
    const base64 = btoa(String.fromCharCode(...new Uint8Array(buffer)));

    return {
      type: 'image',
      anonymizedImageBase64: base64,
      imageMimeType: contentType || 'image/png',
      originalType: response.headers.get('X-Original-Type') || 'unknown',
    };
  }
}
```

---

## 5. Kombinierter Endpoint mit Upload

### src/routes/analysis.ts (erweitert)

```typescript
/**
 * POST /v1/analysis/upload
 * Kompletter Flow: Upload → Anonymisierung → Analyse
 */
analysisRoutes.post('/upload', async (c) => {
  const license = c.get('license');
  const usageService = new UsageService(c.env.DB);

  // Limit prüfen
  const canAnalyze = await usageService.canAnalyze(license.id);
  if (!canAnalyze) {
    return c.json({
      error: 'limit_reached',
      message: 'Monatliches Analyse-Limit erreicht',
    }, 429);
  }

  // Multipart Form Data parsen
  const formData = await c.req.formData();
  const file = formData.get('file') as File | null;
  const jobDataStr = formData.get('jobData') as string | null;

  if (!file || !jobDataStr) {
    return c.json({
      error: 'invalid_request',
      message: 'file and jobData required',
    }, 400);
  }

  let jobData: JobData;
  try {
    jobData = JSON.parse(jobDataStr);
  } catch {
    return c.json({
      error: 'invalid_request',
      message: 'jobData must be valid JSON',
    }, 400);
  }

  // Dateigröße prüfen (max 10MB)
  if (file.size > 10 * 1024 * 1024) {
    return c.json({
      error: 'file_too_large',
      message: 'Maximum file size is 10MB',
    }, 413);
  }

  // Job-ID generieren
  const jobId = crypto.randomUUID();

  // Job erstellen
  await c.env.DB
    .prepare(`
      INSERT INTO analysis_jobs (id, license_id, file_type, status, created_at)
      VALUES (?, ?, ?, 'pending', unixepoch())
    `)
    .bind(jobId, license.id, file.type)
    .run();

  // File-Buffer lesen
  const fileBuffer = await file.arrayBuffer();

  // Verarbeitung asynchron starten
  c.executionCtx.waitUntil(
    processFullAnalysis(c.env, jobId, license.id, fileBuffer, file.name, jobData)
  );

  return c.json({
    jobId,
    status: 'pending',
    message: 'Analyse gestartet',
  }, 202);
});

/**
 * Vollständige Analyse: Anonymisierung + Claude
 */
async function processFullAnalysis(
  env: Env,
  jobId: string,
  licenseId: number,
  fileBuffer: ArrayBuffer,
  filename: string,
  jobData: JobData,
): Promise<void> {

  try {
    // Status: processing
    await env.DB
      .prepare(`UPDATE analysis_jobs SET status = 'processing', started_at = unixepoch() WHERE id = ?`)
      .bind(jobId)
      .run();

    // 1. Anonymisierung
    const presidio = new PresidioService(env.PRESIDIO_URL, env.PRESIDIO_API_KEY);
    const anonymized = await presidio.anonymize(fileBuffer, filename, 'text');

    // 2. Claude Analyse
    const claude = new ClaudeService(env.CLAUDE_API_KEY, env.AI_GATEWAY_URL);
    let result: MatchResult;

    if (anonymized.type === 'text' && anonymized.anonymizedText) {
      result = await claude.analyzeMatch(anonymized.anonymizedText, jobData);
    } else if (anonymized.anonymizedImageBase64 && anonymized.imageMimeType) {
      result = await claude.analyzeMatchWithImage(
        anonymized.anonymizedImageBase64,
        anonymized.imageMimeType,
        jobData,
      );
    } else {
      throw new Error('Anonymization returned no usable data');
    }

    // 3. Ergebnis speichern
    await env.DB
      .prepare(`
        UPDATE analysis_jobs
        SET status = 'completed', result_score = ?, result_category = ?, result_message = ?, completed_at = unixepoch()
        WHERE id = ?
      `)
      .bind(result.score, result.category, result.message, jobId)
      .run();

    // 4. Usage hochzählen
    const usageService = new UsageService(env.DB);
    await usageService.incrementUsage(licenseId);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    await env.DB
      .prepare(`UPDATE analysis_jobs SET status = 'failed', error_message = ?, completed_at = unixepoch() WHERE id = ?`)
      .bind(errorMessage, jobId)
      .run();
  }
}
```

---

## 6. Secrets konfigurieren

```bash
cd api/recruiting-playbook-api

# Claude API Key (erforderlich)
wrangler secret put CLAUDE_API_KEY
# → Anthropic API Key eingeben

# AI Gateway URL (optional, für Monitoring)
wrangler secret put AI_GATEWAY_URL
# → https://gateway.ai.cloudflare.com/v1/{account_id}/{gateway_id}/anthropic
```

### Environment Types (src/types.ts erweitern)

```typescript
export interface Bindings {
  // ... bestehende Bindings
  CLAUDE_API_KEY: string;
  AI_GATEWAY_URL?: string;  // Optional
}
```

---

## 7. API-Dokumentation

### Endpoints

| Method | Endpoint | Beschreibung |
|--------|----------|--------------|
| POST | `/v1/analysis/upload` | Datei hochladen + Analyse starten |
| POST | `/v1/analysis/start` | Bereits anonymisierte Daten analysieren |
| GET | `/v1/analysis/:id` | Status/Ergebnis abrufen |

### Request: /v1/analysis/upload

```bash
curl -X POST https://api.recruiting-playbook.com/v1/analysis/upload \
  -H "X-License-Key: RP-AI-XXXX-XXXX-XXXX-XXXX-XXXX" \
  -F "file=@lebenslauf.pdf" \
  -F 'jobData={"title":"Pflegefachkraft","description":"...","requirements":["Examen","3 Jahre Erfahrung"]}'
```

### Response

```json
{
  "jobId": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "message": "Analyse gestartet"
}
```

### Polling: /v1/analysis/:id

```json
{
  "jobId": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "result": {
    "score": 78,
    "category": "high",
    "message": "Ihr Profil passt gut zu dieser Stelle!"
  }
}
```

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ Claude API Integration mit Haiku 4.5 (`claude-haiku-4-5-20251001`)
- ✅ Prompt Engineering für Match-Score
- ✅ Text- und Bild-Analyse (Vision)
- ✅ Async Verarbeitung mit Polling
- ✅ Vollständiger Upload→Anonymisierung→Analyse Flow
- ✅ Optional: Cloudflare AI Gateway für Monitoring

---

## Nächste Phase

→ [Phase 4: WordPress Frontend](./ki-matching-phase-4-frontend.md)
