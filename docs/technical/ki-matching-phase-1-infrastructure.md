# KI-Matching: Phase 1 - Infrastruktur

> **Voraussetzung:** [Übersicht lesen](./ki-matching-feature.md)

## Ziel dieser Phase

Aufsetzen der grundlegenden Infrastruktur:
- Cloudflare Worker als API Gateway
- Datenbank für Nutzungszählung
- Verbindung zum Lizenz-System

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                     CLOUDFLARE INFRASTRUKTUR                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    CLOUDFLARE WORKER                         │   │
│  │                   api.recruiting-playbook.de                 │   │
│  │                                                              │   │
│  │   Endpoints:                                                 │   │
│  │   ├── POST /v1/analysis/start      → Analyse starten        │   │
│  │   ├── GET  /v1/analysis/:id        → Status abfragen        │   │
│  │   ├── GET  /v1/usage/stats         → Verbrauch anzeigen     │   │
│  │   └── POST /v1/license/validate    → Lizenz prüfen          │   │
│  │                                                              │   │
│  └──────────────────────────┬──────────────────────────────────┘   │
│                             │                                       │
│            ┌────────────────┼────────────────┐                     │
│            ▼                ▼                ▼                      │
│     ┌──────────┐     ┌──────────┐     ┌──────────┐                │
│     │    D1    │     │    KV    │     │    R2    │                │
│     │ Database │     │  Cache   │     │  Storage │                │
│     │          │     │          │     │          │                │
│     │ Lizenzen │     │ Sessions │     │ Temp     │                │
│     │ Usage    │     │ Results  │     │ Files    │                │
│     └──────────┘     └──────────┘     └──────────┘                │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 1. Cloudflare Account Setup

### 1.1 Account erstellen

1. Gehe zu [dash.cloudflare.com](https://dash.cloudflare.com)
2. Account erstellen (kostenlos)
3. Workers & Pages aktivieren

### 1.2 Wrangler CLI installieren

```bash
npm install -g wrangler

# Login
wrangler login
```

---

## 2. Projekt-Struktur

```
recruiting-playbook-api/
├── src/
│   ├── index.ts              # Haupt-Entry-Point
│   ├── types/
│   │   └── index.ts          # TypeScript Types (Env, License, etc.)
│   ├── routes/
│   │   ├── analysis.ts       # /v1/analysis/* Endpoints
│   │   ├── license.ts        # /v1/license/* Endpoints
│   │   └── usage.ts          # /v1/usage/* Endpoints
│   ├── services/
│   │   ├── presidio.ts       # Presidio Service Client
│   │   ├── claude.ts         # Claude API Client
│   │   └── license.ts        # Lizenz-Validierung
│   └── middleware/
│       ├── auth.ts           # Lizenz-Key Validierung (createMiddleware)
│       └── rateLimit.ts      # Rate Limiting
├── wrangler.toml             # Cloudflare Konfiguration
├── package.json
└── tsconfig.json
```

---

## 3. Wrangler Konfiguration

### wrangler.toml

```toml
name = "recruiting-playbook-api"
main = "src/index.ts"
compatibility_date = "2024-01-01"

# Umgebungsvariablen (Secrets werden separat gesetzt)
[vars]
ENVIRONMENT = "production"
PRESIDIO_URL = "https://presidio.recruiting-playbook.de"

# D1 Datenbank
[[d1_databases]]
binding = "DB"
database_name = "recruiting-playbook"
database_id = "<wird nach Erstellung eingefügt>"

# KV Namespace für Caching
[[kv_namespaces]]
binding = "CACHE"
id = "<wird nach Erstellung eingefügt>"

# R2 Bucket für temporäre Dateien
[[r2_buckets]]
binding = "STORAGE"
bucket_name = "rp-temp-files"

# Secrets (werden mit wrangler secret put gesetzt):
# - CLAUDE_API_KEY
# - LICENSE_SECRET
# - PRESIDIO_API_KEY
```

---

## 4. Datenbank-Schema (D1)

### schema.sql

```sql
-- Lizenzen (Sync mit WordPress License System)
CREATE TABLE licenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_key TEXT UNIQUE NOT NULL,
    tier TEXT NOT NULL CHECK (tier IN ('FREE', 'PRO', 'AI_ADDON', 'BUNDLE')),
    domain TEXT NOT NULL,
    activated_at INTEGER NOT NULL,
    expires_at INTEGER,
    is_active INTEGER DEFAULT 1,
    created_at INTEGER DEFAULT (unixepoch()),
    updated_at INTEGER DEFAULT (unixepoch())
);

CREATE INDEX idx_licenses_key ON licenses(license_key);
CREATE INDEX idx_licenses_domain ON licenses(domain);

-- Nutzungs-Tracking
CREATE TABLE usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    month TEXT NOT NULL,  -- Format: "2025-01"
    analyses_count INTEGER DEFAULT 0,
    analyses_limit INTEGER DEFAULT 100,
    created_at INTEGER DEFAULT (unixepoch()),
    updated_at INTEGER DEFAULT (unixepoch()),
    FOREIGN KEY (license_id) REFERENCES licenses(id),
    UNIQUE(license_id, month)
);

CREATE INDEX idx_usage_license_month ON usage(license_id, month);

-- Analyse-Jobs (für async Verarbeitung)
CREATE TABLE analysis_jobs (
    id TEXT PRIMARY KEY,  -- UUID
    license_id INTEGER NOT NULL,
    job_posting_id INTEGER,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    file_type TEXT,
    result_score INTEGER,
    result_category TEXT,
    result_message TEXT,
    error_message TEXT,
    created_at INTEGER DEFAULT (unixepoch()),
    started_at INTEGER,
    completed_at INTEGER,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);

CREATE INDEX idx_jobs_status ON analysis_jobs(status);
CREATE INDEX idx_jobs_license ON analysis_jobs(license_id);

-- Audit Log
CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,  -- JSON
    ip_address TEXT,
    created_at INTEGER DEFAULT (unixepoch())
);

CREATE INDEX idx_audit_license ON audit_log(license_id);
CREATE INDEX idx_audit_created ON audit_log(created_at);
```

### Datenbank erstellen

```bash
# D1 Datenbank erstellen
wrangler d1 create recruiting-playbook

# Schema anwenden
wrangler d1 execute recruiting-playbook --file=./schema.sql
```

---

## 5. Types Definition

### src/types/index.ts

```typescript
/**
 * License Objekt aus der Datenbank
 */
export interface License {
  id: number;
  license_key: string;
  tier: 'FREE' | 'PRO' | 'AI_ADDON' | 'BUNDLE';
  domain: string;
  activated_at: number;
  expires_at: number | null;
  is_active: number;
  created_at: number;
  updated_at: number;
}

/**
 * Cloudflare Bindings (Datenbanken, Storage, Secrets)
 */
export interface Bindings {
  DB: D1Database;
  CACHE: KVNamespace;
  STORAGE: R2Bucket;
  CLAUDE_API_KEY: string;
  LICENSE_SECRET: string;
  PRESIDIO_URL: string;
  PRESIDIO_API_KEY: string;
}

/**
 * Context Variables (von Middleware gesetzt)
 */
export interface Variables {
  license: License;
}

/**
 * Kombinierter Env-Type für Hono
 */
export type Env = {
  Bindings: Bindings;
  Variables: Variables;
};
```

---

## 6. Haupt-Entry-Point

### src/index.ts

```typescript
import { Hono } from 'hono';
import { cors } from 'hono/cors';
import { logger } from 'hono/logger';

import { analysisRoutes } from './routes/analysis';
import { licenseRoutes } from './routes/license';
import { usageRoutes } from './routes/usage';
import { authMiddleware } from './middleware/auth';
import type { Env } from './types';

const app = new Hono<Env>();

// Middleware
app.use('*', logger());
app.use('*', cors({
  origin: '*',  // In Produktion: spezifische Domains
  allowMethods: ['GET', 'POST', 'OPTIONS'],
  allowHeaders: ['Content-Type', 'X-License-Key'],
}));

// Health Check (ohne Auth)
app.get('/health', (c) => c.json({ status: 'ok', timestamp: Date.now() }));

// API v1 Routes (mit Auth)
const v1 = new Hono<{ Bindings: Env }>();
v1.use('*', authMiddleware);

v1.route('/analysis', analysisRoutes);
v1.route('/license', licenseRoutes);
v1.route('/usage', usageRoutes);

app.route('/v1', v1);

// 404 Handler
app.notFound((c) => c.json({ error: 'Not Found' }, 404));

// Error Handler
app.onError((err, c) => {
  console.error('Error:', err);
  return c.json({
    error: 'Internal Server Error',
    message: err.message
  }, 500);
});

export default app;
```

---

## 7. Auth Middleware

### src/middleware/auth.ts

```typescript
import { createMiddleware } from 'hono/factory';
import type { Env, License } from '../types';

/**
 * Auth Middleware - prüft Lizenz-Key und setzt License in Context
 *
 * Verwendet createMiddleware für volle Type-Sicherheit bei c.set()/c.get()
 */
export const authMiddleware = createMiddleware<Env>(async (c, next) => {
  const licenseKey = c.req.header('X-License-Key');

  if (!licenseKey) {
    return c.json({
      error: 'unauthorized',
      message: 'License key required'
    }, 401);
  }

  // Lizenz in Datenbank prüfen
  const license = await c.env.DB
    .prepare('SELECT * FROM licenses WHERE license_key = ? AND is_active = 1')
    .bind(licenseKey)
    .first<License>();

  if (!license) {
    return c.json({
      error: 'invalid_license',
      message: 'License not found or inactive'
    }, 401);
  }

  // Tier prüfen: AI_ADDON oder BUNDLE erforderlich
  if (!['AI_ADDON', 'BUNDLE'].includes(license.tier)) {
    return c.json({
      error: 'feature_not_available',
      message: 'AI features require AI_ADDON or BUNDLE license'
    }, 403);
  }

  // Ablauf prüfen
  if (license.expires_at && license.expires_at < Math.floor(Date.now() / 1000)) {
    return c.json({
      error: 'license_expired',
      message: 'License has expired'
    }, 403);
  }

  // Lizenz-Info in Context speichern (type-safe durch Variables-Definition)
  c.set('license', license);

  await next();
});
```

---

## 8. Usage Tracking

### src/services/usage.ts

```typescript

export class UsageService {
  constructor(private db: D1Database) {}

  /**
   * Aktuellen Monat im Format "2025-01" zurückgeben
   */
  private getCurrentMonth(): string {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  }

  /**
   * Verbrauch für eine Lizenz abrufen oder erstellen
   */
  async getOrCreateUsage(licenseId: number): Promise<{
    count: number;
    limit: number;
    remaining: number;
  }> {
    const month = this.getCurrentMonth();

    // Existierenden Eintrag suchen
    let usage = await this.db
      .prepare('SELECT * FROM usage WHERE license_id = ? AND month = ?')
      .bind(licenseId, month)
      .first();

    // Wenn nicht vorhanden, erstellen
    if (!usage) {
      await this.db
        .prepare('INSERT INTO usage (license_id, month, analyses_count, analyses_limit) VALUES (?, ?, 0, 100)')
        .bind(licenseId, month)
        .run();

      usage = { analyses_count: 0, analyses_limit: 100 };
    }

    return {
      count: usage.analyses_count as number,
      limit: usage.analyses_limit as number,
      remaining: (usage.analyses_limit as number) - (usage.analyses_count as number),
    };
  }

  /**
   * Prüfen ob Limit erreicht
   */
  async canAnalyze(licenseId: number): Promise<boolean> {
    const usage = await this.getOrCreateUsage(licenseId);
    return usage.remaining > 0;
  }

  /**
   * Verbrauch um 1 erhöhen
   */
  async incrementUsage(licenseId: number): Promise<void> {
    const month = this.getCurrentMonth();

    await this.db
      .prepare(`
        UPDATE usage
        SET analyses_count = analyses_count + 1, updated_at = unixepoch()
        WHERE license_id = ? AND month = ?
      `)
      .bind(licenseId, month)
      .run();
  }
}
```

---

## 9. Deployment

### Secrets setzen

```bash
# Claude API Key
wrangler secret put CLAUDE_API_KEY
# Eingabe: sk-ant-...

# License Secret (muss mit WordPress übereinstimmen!)
wrangler secret put LICENSE_SECRET
# Eingabe: <euer-secret>

# Presidio API Key (falls ihr einen setzt)
wrangler secret put PRESIDIO_API_KEY
```

### Deploy

```bash
# Development
wrangler dev

# Production
wrangler deploy
```

---

## 10. Custom Domain (Optional)

```bash
# Domain hinzufügen
wrangler domains add api.recruiting-playbook.de
```

In Cloudflare Dashboard:
1. DNS → CNAME Record für `api` auf Worker
2. SSL/TLS → Full (strict)

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ Cloudflare Worker als API Gateway
- ✅ D1 Datenbank für Lizenzen und Usage
- ✅ Auth Middleware für Lizenz-Validierung
- ✅ Usage Tracking pro Monat
- ✅ Basis-Endpoints

---

## Nächste Phase

→ [Phase 2: Presidio Anonymisierung](./ki-matching-phase-2-anonymization.md)
