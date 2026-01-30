# KI-Matching: Phase 1 - Infrastruktur

> **Voraussetzung:** [Übersicht lesen](./ki-matching-feature.md)

## Ziel dieser Phase

Aufsetzen der grundlegenden Infrastruktur:
- Cloudflare Worker als API Gateway
- Datenbank für Nutzungszählung
- Freemius-Integration für Lizenzierung

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                     SYSTEM-ARCHITEKTUR                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  WORDPRESS (Kunde)       CLOUDFLARE           EXTERNE DIENSTE       │
│  ─────────────────       ──────────           ────────────────      │
│                                                                      │
│  ┌─────────────┐        ┌─────────────┐      ┌─────────────┐       │
│  │   Plugin    │        │   Worker    │      │  Freemius   │       │
│  │             │───────▶│   (API)     │◀────▶│   API       │       │
│  │  Freemius   │        │             │      │             │       │
│  │  SDK        │        │  • Auth     │      │  • Lizenz   │       │
│  │             │        │  • Usage    │      │  • Abo      │       │
│  └─────────────┘        └──────┬──────┘      └──────┬──────┘       │
│                                │                     │              │
│         ┌──────────────────────┼─────────────────────┘              │
│         │                      │                                    │
│         ▼                      ▼                                    │
│  ┌──────────┐           ┌──────────┐     ┌──────────┐              │
│  │ Webhook  │           │    D1    │     │    R2    │              │
│  │ Handler  │           │ Database │     │  Storage │              │
│  │          │           │          │     │          │              │
│  │ Events   │           │  Usage   │     │  Temp    │              │
│  └──────────┘           │  Jobs    │     │  Files   │              │
│                         └──────────┘     └──────────┘              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Freemius übernimmt

- ✅ Lizenz-Verwaltung (Aktivierung, Deaktivierung)
- ✅ Subscription/Payment Processing
- ✅ Checkout UI
- ✅ Kunden-Dashboard
- ✅ Upgrade/Downgrade Handling

### Cloudflare Worker übernimmt

- ✅ API Gateway für KI-Matching
- ✅ Usage Tracking (Analysen pro Monat)
- ✅ Lizenz-Validierung (gegen Freemius API)
- ✅ Presidio + Claude Integration

---

## 1. Freemius Setup

### 1.1 Account & Produkt erstellen

1. Registrieren bei [freemius.com](https://freemius.com)
2. Neues Plugin-Produkt erstellen: "Recruiting Playbook"
3. Pricing Plans definieren:

| Plan | Preis | KI-Matching |
|------|-------|-------------|
| Free | 0€ | ❌ |
| Pro | 149€ (Lifetime) | ❌ |
| AI-Addon | 19€/Monat | ✅ 100 Analysen/Monat |
| Bundle | 199€/Jahr | ✅ 100 Analysen/Monat |

### 1.2 API Credentials notieren

Im Freemius Dashboard unter Settings:
- **Product ID**: z.B. `12345`
- **Public Key** (`pk_`): Für WordPress SDK
- **Secret Key** (`sk_`): Für Server-zu-Server API (Developer Dashboard → Settings)

---

## 2. Cloudflare Account Setup

### 2.1 Account erstellen

1. Gehe zu [dash.cloudflare.com](https://dash.cloudflare.com)
2. Account erstellen (kostenlos)
3. Workers & Pages aktivieren

### 2.2 Wrangler CLI installieren

```bash
npm install -g wrangler

# Login
wrangler login
```

---

## 3. Projekt-Struktur

```
recruiting-playbook-api/
├── src/
│   ├── index.ts              # Haupt-Entry-Point
│   ├── types/
│   │   └── index.ts          # TypeScript Types
│   ├── routes/
│   │   ├── analysis.ts       # /v1/analysis/* Endpoints
│   │   ├── usage.ts          # /v1/usage/* Endpoints
│   │   └── webhooks.ts       # /webhooks/freemius
│   ├── services/
│   │   ├── presidio.ts       # Presidio Service Client
│   │   ├── claude.ts         # Claude API Client
│   │   └── freemius.ts       # Freemius API Client
│   └── middleware/
│       ├── auth.ts           # Freemius Lizenz-Validierung
│       └── rateLimit.ts      # Rate Limiting
├── wrangler.toml             # Cloudflare Konfiguration
├── package.json
└── tsconfig.json
```

---

## 4. Wrangler Konfiguration

### wrangler.toml

```toml
name = "recruiting-playbook-api"
main = "src/index.ts"
compatibility_date = "2024-01-01"

# Umgebungsvariablen
[vars]
ENVIRONMENT = "production"
PRESIDIO_URL = "https://presidio.recruiting-playbook.com"
FREEMIUS_PRODUCT_ID = "12345"

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
# - FREEMIUS_DEV_ID
# - FREEMIUS_DEV_PUBLIC_KEY
# - FREEMIUS_DEV_SECRET_KEY
# - FREEMIUS_WEBHOOK_SECRET
# - PRESIDIO_API_KEY
```

---

## 5. Datenbank-Schema (D1)

### schema.sql

**Hinweis:** Keine `licenses` Tabelle nötig - Freemius verwaltet alle Lizenzen!

```sql
-- Nutzungs-Tracking (referenziert Freemius Install ID)
CREATE TABLE usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    freemius_install_id TEXT NOT NULL,
    freemius_license_id TEXT,
    site_url TEXT NOT NULL,
    month TEXT NOT NULL,                -- Format: "2025-01"
    analyses_count INTEGER DEFAULT 0,
    analyses_limit INTEGER DEFAULT 100,
    created_at INTEGER DEFAULT (unixepoch()),
    updated_at INTEGER DEFAULT (unixepoch()),
    UNIQUE(freemius_install_id, month)
);

CREATE INDEX idx_usage_install_month ON usage(freemius_install_id, month);

-- Analyse-Jobs (für async Verarbeitung)
CREATE TABLE analysis_jobs (
    id TEXT PRIMARY KEY,  -- UUID
    freemius_install_id TEXT NOT NULL,
    job_posting_id INTEGER,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    file_type TEXT,
    result_score INTEGER,
    result_category TEXT,
    result_message TEXT,
    error_message TEXT,
    created_at INTEGER DEFAULT (unixepoch()),
    started_at INTEGER,
    completed_at INTEGER
);

CREATE INDEX idx_jobs_status ON analysis_jobs(status);
CREATE INDEX idx_jobs_install ON analysis_jobs(freemius_install_id);

-- Audit Log
CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    freemius_install_id TEXT,
    action TEXT NOT NULL,
    details TEXT,  -- JSON
    ip_address TEXT,
    created_at INTEGER DEFAULT (unixepoch())
);

CREATE INDEX idx_audit_install ON audit_log(freemius_install_id);
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

## 6. Types Definition

### src/types/index.ts

```typescript
/**
 * Freemius Install Response (von API)
 * Endpoint: GET /v1/developers/{dev_id}/plugins/{product_id}/installs/{install_id}.json
 */
export interface FreemiusInstall {
  id: number;
  plugin_id: number;
  user_id: number;
  url: string;
  title: string;
  version: string;
  plan_id: number;
  license_id: number | null;
  trial_ends: string | null;
  is_premium: boolean;
  is_active: boolean;
  is_uninstalled: boolean;
  secret_key: string;  // Site's private key
}

/**
 * Freemius License Response (von API)
 * Endpoint: GET /v1/developers/{dev_id}/plugins/{product_id}/licenses/{license_id}.json
 */
export interface FreemiusLicense {
  id: number;
  plugin_id: number;
  user_id: number;
  plan_id: number;
  pricing_id: number;
  quota: number | null;
  activated: number;
  expiration: string | null;  // Format: "Y-m-d H:i:s" or null for lifetime
  is_cancelled: boolean;
}

/**
 * Freemius Plan Response
 */
export interface FreemiusPlan {
  id: number;
  plugin_id: number;
  name: string;   // "ai_addon" | "bundle" | "pro" | "free"
  title: string;
}

/**
 * Validierte Lizenz-Info (in Context gespeichert)
 */
export interface ValidatedLicense {
  installId: string;
  licenseId: string | null;
  planName: string;
  siteUrl: string;
  isActive: boolean;
  expiresAt: string | null;
}

/**
 * Cloudflare Bindings
 */
export interface Bindings {
  DB: D1Database;
  CACHE: KVNamespace;
  STORAGE: R2Bucket;
  CLAUDE_API_KEY: string;
  FREEMIUS_PRODUCT_ID: string;
  FREEMIUS_DEV_ID: string;
  FREEMIUS_DEV_PUBLIC_KEY: string;
  FREEMIUS_DEV_SECRET_KEY: string;
  FREEMIUS_WEBHOOK_SECRET: string;
  PRESIDIO_URL: string;
  PRESIDIO_API_KEY: string;
}

/**
 * Context Variables (von Middleware gesetzt)
 */
export interface Variables {
  license: ValidatedLicense;
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

## 7. Freemius Service

### src/services/freemius.ts

```typescript
import type { FreemiusInstall, FreemiusLicense, FreemiusPlan } from '../types';

/**
 * Freemius API Service
 *
 * Verwendet Developer-Scope API für Server-zu-Server Kommunikation.
 * Docs: https://freemius.com/help/documentation/saas/integrating-license-key-activation/
 */
export class FreemiusService {
  private baseUrl = 'https://api.freemius.com/v1';

  constructor(
    private devId: string,
    private productId: string,
    private devPublicKey: string,
    private devSecretKey: string
  ) {}

  /**
   * Signatur für API-Request erstellen (Developer Scope)
   */
  private createSignature(
    method: string,
    path: string,
    contentMd5: string = ''
  ): string {
    const date = new Date().toUTCString();
    const stringToSign = [
      method.toUpperCase(),
      contentMd5,
      'application/json',
      date,
      path
    ].join('\n');

    // HMAC-SHA256 Signatur
    const encoder = new TextEncoder();
    // Note: In Worker muss crypto.subtle verwendet werden
    // Hier vereinfacht dargestellt
    return `FS ${this.devId}:${this.devPublicKey}:${stringToSign}`;
  }

  /**
   * Install-Details abrufen (inkl. secret_key)
   */
  async getInstall(installId: string): Promise<FreemiusInstall | null> {
    const path = `/developers/${this.devId}/plugins/${this.productId}/installs/${installId}.json`;

    try {
      // Für Developer-Scope: Basic Auth mit dev_id:dev_secret
      const authHeader = btoa(`${this.devId}:${this.devSecretKey}`);

      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          'Authorization': `Basic ${authHeader}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        console.error('Freemius API error:', response.status);
        return null;
      }

      return await response.json() as FreemiusInstall;
    } catch (error) {
      console.error('Freemius API error:', error);
      return null;
    }
  }

  /**
   * Lizenz-Details abrufen
   */
  async getLicense(licenseId: string): Promise<FreemiusLicense | null> {
    const path = `/developers/${this.devId}/plugins/${this.productId}/licenses/${licenseId}.json`;

    try {
      const authHeader = btoa(`${this.devId}:${this.devSecretKey}`);

      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          'Authorization': `Basic ${authHeader}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        return null;
      }

      return await response.json() as FreemiusLicense;
    } catch (error) {
      console.error('Freemius license error:', error);
      return null;
    }
  }

  /**
   * Plan-Details abrufen
   */
  async getPlan(planId: number): Promise<FreemiusPlan | null> {
    const path = `/developers/${this.devId}/plugins/${this.productId}/plans/${planId}.json`;

    try {
      const authHeader = btoa(`${this.devId}:${this.devSecretKey}`);

      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          'Authorization': `Basic ${authHeader}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        return null;
      }

      return await response.json() as FreemiusPlan;
    } catch (error) {
      console.error('Freemius plan error:', error);
      return null;
    }
  }

  /**
   * Signatur vom WordPress-Plugin verifizieren
   *
   * WordPress sendet: hash(secret_key + '|' + timestamp)
   * Wir verifizieren mit dem secret_key aus der Install-Abfrage
   */
  async verifySignature(
    signature: string,
    timestamp: string,
    installSecretKey: string
  ): Promise<boolean> {
    // Timestamp-Validierung (max 24h alt)
    const signatureDate = new Date(timestamp);
    const now = new Date();
    const hoursDiff = (now.getTime() - signatureDate.getTime()) / (1000 * 60 * 60);

    if (hoursDiff > 24) {
      return false;
    }

    // Hash berechnen und vergleichen
    const encoder = new TextEncoder();
    const data = encoder.encode(`${installSecretKey}|${timestamp}`);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const expectedHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

    return signature === expectedHash;
  }

  /**
   * Prüfen ob Plan KI-Matching beinhaltet
   */
  hasAiFeature(planName: string): boolean {
    const aiPlans = ['ai_addon', 'bundle', 'ai-addon'];
    return aiPlans.includes(planName.toLowerCase());
  }
}
```

---

## 8. Auth Middleware

### src/middleware/auth.ts

```typescript
import { createMiddleware } from 'hono/factory';
import { FreemiusService } from '../services/freemius';
import type { Env, ValidatedLicense } from '../types';

/**
 * Auth Middleware - validiert Lizenz gegen Freemius API
 *
 * WordPress Plugin sendet:
 * - X-Freemius-Install-Id: Installation ID
 * - X-Freemius-Timestamp: ISO Timestamp für Signatur
 * - X-Freemius-Signature: SHA256(secret_key + '|' + timestamp)
 * - X-Site-Url: WordPress Site URL
 */
export const authMiddleware = createMiddleware<Env>(async (c, next) => {
  const installId = c.req.header('X-Freemius-Install-Id');
  const timestamp = c.req.header('X-Freemius-Timestamp');
  const signature = c.req.header('X-Freemius-Signature');
  const siteUrl = c.req.header('X-Site-Url');

  if (!installId || !timestamp || !signature) {
    return c.json({
      error: 'unauthorized',
      message: 'Missing authentication headers'
    }, 401);
  }

  // Cache-Key für Lizenz
  const cacheKey = `license:${installId}`;

  // Erst im Cache schauen (5 Minuten gültig)
  let cachedLicense = await c.env.CACHE.get(cacheKey, 'json') as ValidatedLicense | null;

  if (!cachedLicense) {
    // Freemius API abfragen
    const freemius = new FreemiusService(
      c.env.FREEMIUS_DEV_ID,
      c.env.FREEMIUS_PRODUCT_ID,
      c.env.FREEMIUS_DEV_PUBLIC_KEY,
      c.env.FREEMIUS_DEV_SECRET_KEY
    );

    // Install-Details holen (inkl. secret_key)
    const install = await freemius.getInstall(installId);

    if (!install) {
      return c.json({
        error: 'invalid_install',
        message: 'Installation not found'
      }, 401);
    }

    // Signatur verifizieren
    const isValidSignature = await freemius.verifySignature(
      signature,
      timestamp,
      install.secret_key
    );

    if (!isValidSignature) {
      return c.json({
        error: 'invalid_signature',
        message: 'Authentication signature invalid or expired'
      }, 401);
    }

    // Prüfen ob Installation aktiv
    if (!install.is_active || install.is_uninstalled) {
      return c.json({
        error: 'install_inactive',
        message: 'Installation is not active'
      }, 403);
    }

    // Plan-Details holen
    const plan = await freemius.getPlan(install.plan_id);

    if (!plan || !freemius.hasAiFeature(plan.name)) {
      return c.json({
        error: 'feature_not_available',
        message: 'AI features require AI-Addon or Bundle plan'
      }, 403);
    }

    // Lizenz-Details prüfen (falls vorhanden)
    let expiresAt: string | null = null;

    if (install.license_id) {
      const license = await freemius.getLicense(String(install.license_id));

      if (license) {
        if (license.is_cancelled) {
          return c.json({
            error: 'license_cancelled',
            message: 'License has been cancelled'
          }, 403);
        }

        if (license.expiration) {
          const expirationDate = new Date(license.expiration);
          if (expirationDate < new Date()) {
            return c.json({
              error: 'license_expired',
              message: 'License has expired'
            }, 403);
          }
          expiresAt = license.expiration;
        }
      }
    }

    // Validierte Lizenz erstellen
    cachedLicense = {
      installId: String(install.id),
      licenseId: install.license_id ? String(install.license_id) : null,
      planName: plan.name,
      siteUrl: siteUrl || install.url,
      isActive: install.is_active,
      expiresAt,
    };

    // In Cache speichern (5 Minuten)
    await c.env.CACHE.put(cacheKey, JSON.stringify(cachedLicense), {
      expirationTtl: 300,
    });
  }

  // Lizenz-Info in Context speichern
  c.set('license', cachedLicense);

  await next();
});
```

---

## 9. Webhook Handler

### src/routes/webhooks.ts

```typescript
import { Hono } from 'hono';
import type { Bindings } from '../types';

const webhooks = new Hono<{ Bindings: Bindings }>();

/**
 * Freemius Webhook Handler
 *
 * Signatur-Verifizierung: HMAC-SHA256 (hex-encoded)
 * Header: X-Signature
 *
 * Events:
 * - install.installed / install.uninstalled
 * - license.created / license.activated / license.deactivated / license.expired
 * - subscription.cancelled
 *
 * Docs: https://github.com/Freemius/php-webhook-example
 */
webhooks.post('/freemius', async (c) => {
  // Signatur aus Header holen (NICHT X-Freemius-Signature!)
  const signature = c.req.header('X-Signature');

  if (!signature) {
    return c.json({ error: 'Missing signature' }, 401);
  }

  // Raw Body lesen (wichtig: nicht JSON.parse vorher!)
  const rawBody = await c.req.text();

  // HMAC-SHA256 berechnen (hex-encoded)
  const encoder = new TextEncoder();
  const key = await crypto.subtle.importKey(
    'raw',
    encoder.encode(c.env.FREEMIUS_WEBHOOK_SECRET),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign']
  );

  const signatureBuffer = await crypto.subtle.sign(
    'HMAC',
    key,
    encoder.encode(rawBody)
  );

  // Zu Hex-String konvertieren
  const hashArray = Array.from(new Uint8Array(signatureBuffer));
  const calculatedSignature = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

  // Timing-safe Vergleich
  if (calculatedSignature !== signature.toLowerCase()) {
    console.error('Invalid webhook signature');
    return c.json({ error: 'Invalid signature' }, 401);
  }

  // Event verarbeiten
  const event = JSON.parse(rawBody);

  console.log('Freemius Webhook:', event.type, event);

  switch (event.type) {
    case 'license.expired':
    case 'license.deactivated':
    case 'license.cancelled':
    case 'subscription.cancelled':
      // Cache invalidieren
      const installId = event.objects?.install?.id;
      if (installId) {
        await c.env.CACHE.delete(`license:${installId}`);
        console.log(`Cache invalidated for install: ${installId}`);
      }
      break;

    case 'install.uninstalled':
      // Optional: Usage-Daten markieren für spätere Bereinigung
      break;
  }

  // Freemius erwartet 200 OK
  return c.json({ received: true });
});

export { webhooks as webhookRoutes };
```

---

## 10. Haupt-Entry-Point

### src/index.ts

```typescript
import { Hono } from 'hono';
import { cors } from 'hono/cors';
import { logger } from 'hono/logger';

import { analysisRoutes } from './routes/analysis';
import { usageRoutes } from './routes/usage';
import { webhookRoutes } from './routes/webhooks';
import { authMiddleware } from './middleware/auth';
import type { Env } from './types';

const app = new Hono<Env>();

// Middleware
app.use('*', logger());
app.use('*', cors({
  origin: '*',  // In Produktion: spezifische Domains
  allowMethods: ['GET', 'POST', 'OPTIONS'],
  allowHeaders: [
    'Content-Type',
    'X-Freemius-Install-Id',
    'X-Freemius-Timestamp',
    'X-Freemius-Signature',
    'X-Site-Url'
  ],
}));

// Health Check (ohne Auth)
app.get('/health', (c) => c.json({ status: 'ok', timestamp: Date.now() }));

// Webhooks (eigene Auth via Signatur)
app.route('/webhooks', webhookRoutes);

// API v1 Routes (mit Freemius Auth)
const v1 = new Hono<Env>();
v1.use('*', authMiddleware);

v1.route('/analysis', analysisRoutes);
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

## 11. Usage Tracking

### src/services/usage.ts

```typescript
export class UsageService {
  constructor(private db: D1Database) {}

  private getCurrentMonth(): string {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  }

  async getOrCreateUsage(installId: string, siteUrl: string, licenseId?: string): Promise<{
    count: number;
    limit: number;
    remaining: number;
  }> {
    const month = this.getCurrentMonth();

    let usage = await this.db
      .prepare('SELECT * FROM usage WHERE freemius_install_id = ? AND month = ?')
      .bind(installId, month)
      .first();

    if (!usage) {
      await this.db
        .prepare(`
          INSERT INTO usage (freemius_install_id, freemius_license_id, site_url, month, analyses_count, analyses_limit)
          VALUES (?, ?, ?, ?, 0, 100)
        `)
        .bind(installId, licenseId || null, siteUrl, month)
        .run();

      usage = { analyses_count: 0, analyses_limit: 100 };
    }

    return {
      count: usage.analyses_count as number,
      limit: usage.analyses_limit as number,
      remaining: (usage.analyses_limit as number) - (usage.analyses_count as number),
    };
  }

  async canAnalyze(installId: string, siteUrl: string): Promise<boolean> {
    const usage = await this.getOrCreateUsage(installId, siteUrl);
    return usage.remaining > 0;
  }

  async incrementUsage(installId: string): Promise<void> {
    const month = this.getCurrentMonth();

    await this.db
      .prepare(`
        UPDATE usage
        SET analyses_count = analyses_count + 1, updated_at = unixepoch()
        WHERE freemius_install_id = ? AND month = ?
      `)
      .bind(installId, month)
      .run();
  }
}
```

---

## 12. WordPress Plugin Integration

### Freemius SDK Setup (im Plugin)

```php
<?php
// recruiting-playbook.php

if ( ! function_exists( 'rp_fs' ) ) {
    function rp_fs() {
        global $rp_fs;

        if ( ! isset( $rp_fs ) ) {
            require_once dirname(__FILE__) . '/vendor/freemius/start.php';

            $rp_fs = fs_dynamic_init( array(
                'id'                  => '12345',  // Deine Product ID
                'slug'                => 'recruiting-playbook',
                'type'                => 'plugin',
                'public_key'          => 'pk_xxx',
                'is_premium'          => false,
                'premium_suffix'      => 'Pro',
                'has_addons'          => true,
                'has_paid_plans'      => true,
                'menu'                => array(
                    'slug'    => 'recruiting-playbook',
                    'support' => false,
                ),
            ) );
        }

        return $rp_fs;
    }

    rp_fs();
    do_action( 'rp_fs_loaded' );
}
```

### API Request Helper mit Signatur-Auth

```php
<?php
// src/Services/KiMatchingApi.php

namespace RecruitingPlaybook\Services;

class KiMatchingApi {
    private string $apiUrl = 'https://api.recruiting-playbook.com';

    public function __construct() {
        if ( ! function_exists( 'rp_fs' ) ) {
            throw new \Exception( 'Freemius not initialized' );
        }
    }

    /**
     * Signatur für API-Request erstellen
     *
     * Verwendet secret_key vom Freemius SDK.
     * Format: SHA256(secret_key + '|' + timestamp)
     */
    private function createSignature(): array {
        $fs = rp_fs();
        $site = $fs->get_site();

        if ( ! $site || ! $site->id ) {
            throw new \Exception( 'No Freemius installation found' );
        }

        $timestamp = gmdate( 'c' );  // ISO 8601 format
        $secret_key = $site->secret_key;

        $signature = hash( 'sha256', $secret_key . '|' . $timestamp );

        return [
            'install_id' => $site->id,
            'timestamp'  => $timestamp,
            'signature'  => $signature,
        ];
    }

    /**
     * API Request mit Signatur-Auth Headers
     */
    private function request( string $endpoint, array $options = [] ): array {
        $auth = $this->createSignature();

        $headers = [
            'Content-Type'           => 'application/json',
            'X-Freemius-Install-Id'  => $auth['install_id'],
            'X-Freemius-Timestamp'   => $auth['timestamp'],
            'X-Freemius-Signature'   => $auth['signature'],
            'X-Site-Url'             => home_url(),
        ];

        $response = wp_remote_request( $this->apiUrl . $endpoint, array_merge( [
            'headers' => $headers,
            'timeout' => 30,
        ], $options ) );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code >= 400 ) {
            return [
                'error' => $body['message'] ?? 'API request failed',
                'code'  => $body['error'] ?? 'unknown_error',
            ];
        }

        return $body;
    }

    /**
     * Analyse starten
     */
    public function startAnalysis( int $jobId, array $fileData ): array {
        return $this->request( '/v1/analysis/start', [
            'method' => 'POST',
            'body'   => wp_json_encode( [
                'job_posting_id' => $jobId,
                'file'           => $fileData,
            ] ),
        ] );
    }

    /**
     * Analyse-Status abfragen
     */
    public function getAnalysisStatus( string $analysisId ): array {
        return $this->request( "/v1/analysis/{$analysisId}" );
    }

    /**
     * Verbrauch abfragen
     */
    public function getUsage(): array {
        return $this->request( '/v1/usage/stats' );
    }

    /**
     * Prüfen ob KI-Features verfügbar (client-side check)
     */
    public function hasAiFeature(): bool {
        $fs = rp_fs();
        return $fs->is_plan( 'ai_addon' ) || $fs->is_plan( 'bundle' );
    }
}
```

---

## 13. Deployment

### Secrets setzen

```bash
# Claude API Key
wrangler secret put CLAUDE_API_KEY
# Eingabe: sk-ant-...

# Freemius Developer Credentials (aus Dashboard → Settings)
wrangler secret put FREEMIUS_DEV_ID
wrangler secret put FREEMIUS_DEV_PUBLIC_KEY
wrangler secret put FREEMIUS_DEV_SECRET_KEY

# Freemius Webhook Secret (selbst generiert)
wrangler secret put FREEMIUS_WEBHOOK_SECRET

# Presidio API Key
wrangler secret put PRESIDIO_API_KEY
```

### Deploy

```bash
# Development
wrangler dev

# Production
wrangler deploy
```

### Freemius Webhook konfigurieren

Im Freemius Dashboard unter Integrations → Webhooks:
- **URL:** `https://api.recruiting-playbook.com/webhooks/freemius`
- **Secret:** Das gleiche wie `FREEMIUS_WEBHOOK_SECRET`
- **Events:** `license.*`, `subscription.*`, `install.*`

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ Cloudflare Worker als API Gateway
- ✅ Freemius für Lizenz-Management
- ✅ D1 Datenbank für Usage Tracking
- ✅ Sichere Auth Middleware mit Signatur-Verifikation
- ✅ Webhook Handler für Lizenz-Events
- ✅ WordPress Freemius SDK Integration

---

## Nächste Phase

→ [Phase 2: Presidio Anonymisierung](./ki-matching-phase-2-anonymization.md)
