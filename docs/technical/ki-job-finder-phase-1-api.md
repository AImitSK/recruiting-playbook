# KI-Job-Finder: Phase 1 - API-Erweiterung

> **Voraussetzung:** [Übersicht lesen](./ki-job-finder-feature.md) | [Mode A Phase 1-4 abgeschlossen](./ki-matching-phase-1-infrastructure.md)

## Ziel dieser Phase

Erweiterung der bestehenden API um Multi-Job-Matching:
- Neuer WordPress REST Endpoint `/match/job-finder`
- Neuer Worker Route `/v1/analysis/job-finder`
- Claude Prompt für Multi-Job-Analyse

---

## 1. WordPress REST API

### 1.1 Neuer Endpoint in MatchController.php

**Datei:** `plugin/src/Api/MatchController.php`

```php
/**
 * Routen registrieren
 */
public function register_routes(): void {
    // ... bestehende Routen ...

    // NEU: Job-Finder (Multi-Job-Matching)
    register_rest_route(
        $this->namespace,
        '/' . $this->rest_base . '/job-finder',
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'analyze_job_finder' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'limit' => [
                    'default'           => 5,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function( $value ) {
                        return $value >= 1 && $value <= 10;
                    },
                ],
            ],
        ]
    );
}
```

### 1.2 Handler-Methode

```php
/**
 * Job-Finder Analyse starten (Multi-Job-Matching)
 *
 * @param WP_REST_Request $request Request-Objekt.
 * @return WP_REST_Response|WP_Error
 */
public function analyze_job_finder( WP_REST_Request $request ) {
    // 1. Feature-Check
    if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
        return new WP_Error(
            'feature_not_available',
            __( 'KI-Matching ist nicht verfügbar.', 'recruiting-playbook' ),
            [ 'status' => 403 ]
        );
    }

    // 2. Datei validieren
    $files = $request->get_file_params();
    if ( empty( $files['file'] ) ) {
        return new WP_Error(
            'no_file',
            __( 'Keine Datei hochgeladen.', 'recruiting-playbook' ),
            [ 'status' => 400 ]
        );
    }

    $file = $files['file'];
    $validation = $this->validate_file( $file );
    if ( is_wp_error( $validation ) ) {
        return $validation;
    }

    // 3. ALLE aktiven Jobs laden
    $jobs = $this->get_all_active_jobs();
    if ( empty( $jobs ) ) {
        return new WP_Error(
            'no_jobs',
            __( 'Keine aktiven Stellen vorhanden.', 'recruiting-playbook' ),
            [ 'status' => 404 ]
        );
    }

    // 4. Limit aus Request
    $limit = $request->get_param( 'limit' ) ?: 5;

    // 5. An Worker senden
    $result = $this->send_to_job_finder_api( $file, $jobs, $limit );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    return rest_ensure_response( $result );
}
```

### 1.3 Alle aktiven Jobs laden

```php
/**
 * Alle aktiven Jobs laden
 *
 * @return array Array mit Job-Daten.
 */
private function get_all_active_jobs(): array {
    // Cache prüfen (5 Minuten)
    $cache_key = 'rp_active_jobs_for_matching';
    $cached = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $posts = get_posts( [
        'post_type'      => 'job_listing',
        'post_status'    => 'publish',
        'posts_per_page' => 100, // Max 100 Jobs
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    $jobs = [];
    foreach ( $posts as $post ) {
        $jobs[] = [
            'id'           => $post->ID,
            'title'        => $post->post_title,
            'url'          => get_permalink( $post->ID ),
            'applyUrl'     => get_permalink( $post->ID ) . '#apply',
            'description'  => wp_strip_all_tags( $post->post_content ),
            'requirements' => get_post_meta( $post->ID, '_rp_requirements', true ) ?: [],
            'niceToHave'   => get_post_meta( $post->ID, '_rp_nice_to_have', true ) ?: [],
        ];
    }

    // Cache setzen
    set_transient( $cache_key, $jobs, 5 * MINUTE_IN_SECONDS );

    return $jobs;
}
```

### 1.4 An Worker API senden

```php
/**
 * An Job-Finder API senden
 *
 * @param array $file  Datei-Array.
 * @param array $jobs  Array mit Job-Daten.
 * @param int   $limit Anzahl Top-Matches.
 * @return array|WP_Error
 */
private function send_to_job_finder_api( array $file, array $jobs, int $limit ) {
    $api_url = self::API_BASE_URL . '/analysis/job-finder';

    // Multipart-Body erstellen
    $boundary = wp_generate_password( 24, false );
    $body = $this->build_multipart_body_job_finder( $boundary, $file, $jobs, $limit );

    // Auth-Header
    $auth_headers = $this->get_freemius_auth_headers();
    if ( is_wp_error( $auth_headers ) ) {
        return $auth_headers;
    }

    $headers = array_merge(
        $auth_headers,
        [ 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ]
    );

    $response = wp_remote_post( $api_url, [
        'timeout' => 30,
        'headers' => $headers,
        'body'    => $body,
    ] );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status !== 202 ) {
        return new WP_Error(
            $body['code'] ?? 'api_error',
            $body['message'] ?? __( 'API-Fehler', 'recruiting-playbook' ),
            [ 'status' => $status ]
        );
    }

    return $body;
}

/**
 * Multipart-Body für Job-Finder erstellen
 *
 * @param string $boundary Boundary-String.
 * @param array  $file     Datei-Array.
 * @param array  $jobs     Jobs-Array.
 * @param int    $limit    Limit.
 * @return string Multipart-Body.
 */
private function build_multipart_body_job_finder(
    string $boundary,
    array $file,
    array $jobs,
    int $limit
): string {
    $body = '';

    // Datei
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file['name']}\"\r\n";
    $body .= "Content-Type: {$file['type']}\r\n\r\n";
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    $body .= file_get_contents( $file['tmp_name'] ) . "\r\n";

    // Jobs als JSON
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"jobs\"\r\n";
    $body .= "Content-Type: application/json\r\n\r\n";
    $body .= wp_json_encode( $jobs ) . "\r\n";

    // Limit
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"limit\"\r\n\r\n";
    $body .= $limit . "\r\n";

    $body .= "--{$boundary}--\r\n";

    return $body;
}
```

---

## 2. Cloudflare Worker API

### 2.1 Neue Route-Datei

**Datei:** `api/recruiting-playbook-api/src/routes/job-finder.ts`

```typescript
import { Hono } from 'hono';
import type { Env } from '../types';
import { PresidioService } from '../services/presidio';
import { ClaudeService } from '../services/claude';
import { UsageService } from '../services/usage';

interface JobData {
  id: number;
  title: string;
  url: string;
  applyUrl: string;
  description: string;
  requirements: string[];
  niceToHave: string[];
}

interface JobFinderMatch {
  jobId: number;
  jobTitle: string;
  jobUrl: string;
  applyUrl: string;
  score: number;
  category: 'high' | 'medium' | 'low';
  message: string;
  matchedSkills: string[];
  missingSkills: string[];
}

interface JobFinderResult {
  mode: 'job-finder';
  profile: {
    extractedSkills: string[];
    experienceYears: number | null;
    education: string | null;
  };
  matches: JobFinderMatch[];
  totalJobsAnalyzed: number;
}

const jobFinder = new Hono<Env>();

/**
 * POST /v1/analysis/job-finder
 *
 * Multi-Job-Matching: CV gegen alle aktiven Jobs analysieren
 */
jobFinder.post('/', async (c) => {
  try {
    const license = c.get('license');
    const installId = license.installId;

    // 1. Formdata parsen
    const formData = await c.req.formData();
    const file = formData.get('file') as File;
    const jobsJson = formData.get('jobs') as string;
    const limit = parseInt(formData.get('limit') as string) || 5;

    if (!file || !jobsJson) {
      return c.json(
        { code: 'missing_data', message: 'Datei oder Jobs fehlen' },
        400
      );
    }

    const jobs: JobData[] = JSON.parse(jobsJson);

    if (jobs.length === 0) {
      return c.json(
        { code: 'no_jobs', message: 'Keine Jobs zum Analysieren' },
        400
      );
    }

    // 2. Usage Check
    const usageService = new UsageService(c.env.DB);
    const usage = await usageService.getOrCreateUsage(installId, license.siteUrl);

    if (usage.remaining <= 0) {
      return c.json(
        {
          code: 'limit_reached',
          message: 'Monatliches Analyselimit erreicht'
        },
        429
      );
    }

    // 3. Job-ID erstellen
    const jobId = crypto.randomUUID();

    // 4. Job in DB anlegen
    await c.env.DB.prepare(`
      INSERT INTO analysis_jobs (id, freemius_install_id, status, created_at)
      VALUES (?, ?, 'pending', ?)
    `).bind(jobId, installId, Date.now()).run();

    // 5. Async Verarbeitung starten
    c.executionCtx.waitUntil(
      processJobFinder(c.env, jobId, installId, file, jobs, limit)
    );

    // 6. Job-ID zurückgeben
    return c.json({
      job_id: jobId,
      status: 'pending',
      message: 'Analyse gestartet'
    }, 202);

  } catch (error) {
    console.error('Job-Finder Error:', error);
    return c.json(
      { code: 'internal_error', message: 'Interner Fehler' },
      500
    );
  }
});

/**
 * Async Verarbeitung
 */
async function processJobFinder(
  env: Env['Bindings'],
  jobId: string,
  installId: string,
  file: File,
  jobs: JobData[],
  limit: number
): Promise<void> {
  try {
    // Status: processing
    await env.DB.prepare(`
      UPDATE analysis_jobs SET status = 'processing', started_at = ? WHERE id = ?
    `).bind(Date.now(), jobId).run();

    // 1. Anonymisieren
    const presidio = new PresidioService(env.PRESIDIO_URL, env.PRESIDIO_API_KEY);
    const anonymizedText = await presidio.anonymize(file);

    // 2. Multi-Job-Analyse
    const claude = new ClaudeService(env.CLAUDE_API_KEY);
    const result = await claude.analyzeJobFinder(anonymizedText, jobs, limit);

    // 3. Ergebnis speichern
    await env.DB.prepare(`
      UPDATE analysis_jobs
      SET status = 'completed',
          result_score = ?,
          result_category = ?,
          result_message = ?,
          completed_at = ?
      WHERE id = ?
    `).bind(
      result.matches[0]?.score || 0,
      result.matches[0]?.category || 'low',
      JSON.stringify(result),
      Date.now(),
      jobId
    ).run();

    // 4. Usage inkrementieren
    const usageService = new UsageService(env.DB);
    await usageService.incrementUsage(installId);

  } catch (error) {
    console.error('Job-Finder Processing Error:', error);
    await env.DB.prepare(`
      UPDATE analysis_jobs
      SET status = 'failed',
          error_message = ?,
          completed_at = ?
      WHERE id = ?
    `).bind((error as Error).message, Date.now(), jobId).run();
  }
}

export { jobFinder as jobFinderRoutes };
```

### 2.2 Route in index.ts registrieren

**Datei:** `api/recruiting-playbook-api/src/index.ts`

```typescript
import { jobFinderRoutes } from './routes/job-finder';

// In der v1 Route-Gruppe:
v1.route('/analysis/job-finder', jobFinderRoutes);
```

---

## 3. Claude Prompt für Multi-Job-Matching

### 3.1 Erweiterung ClaudeService

**Datei:** `api/recruiting-playbook-api/src/services/claude.ts`

```typescript
interface JobFinderResult {
  profile: {
    extractedSkills: string[];
    experienceYears: number | null;
    education: string | null;
  };
  matches: Array<{
    jobId: number;
    score: number;
    category: 'high' | 'medium' | 'low';
    message: string;
    matchedSkills: string[];
    missingSkills: string[];
  }>;
  totalJobsAnalyzed: number;
}

/**
 * Multi-Job-Analyse durchführen
 */
async analyzeJobFinder(
  cvText: string,
  jobs: JobData[],
  limit: number
): Promise<JobFinderResult> {
  const systemPrompt = `Du bist ein KI-Recruiting-Assistent. Deine Aufgabe ist es, einen anonymisierten Lebenslauf gegen mehrere Stellenanzeigen zu analysieren und die besten Matches zu finden.

WICHTIG:
- Analysiere den Lebenslauf objektiv
- Vergleiche die extrahierten Qualifikationen mit JEDER Stelle
- Bewerte jede Stelle mit einem Score von 0-100
- Kategorisiere: high (>=70), medium (40-69), low (<40)
- Gib für jede Stelle spezifische Gründe an
- Sortiere nach Score (absteigend)
- Gib nur die Top-${limit} besten Matches zurück

EXTRAKTIONS-SCHRITTE:
1. Extrahiere aus dem CV: Fähigkeiten, Erfahrungsjahre, Ausbildung
2. Für jede Stelle: Vergleiche Anforderungen mit CV-Profil
3. Score: Pflichtanforderungen (70%) + Nice-to-have (20%) + Branchenerfahrung (10%)

ANTWORT-FORMAT (JSON):
{
  "profile": {
    "extractedSkills": ["Skill 1", "Skill 2"],
    "experienceYears": 5,
    "education": "Ausbildung/Studium"
  },
  "matches": [
    {
      "jobId": 123,
      "score": 85,
      "category": "high",
      "message": "Kurze Erklärung warum dieses Match passt",
      "matchedSkills": ["Skill A", "Skill B"],
      "missingSkills": ["Skill C"]
    }
  ],
  "totalJobsAnalyzed": ${jobs.length}
}`;

  const jobsText = jobs.map(job => `
---
ID: ${job.id}
Titel: ${job.title}
Beschreibung: ${job.description?.substring(0, 500) || 'Keine Beschreibung'}
Anforderungen: ${(job.requirements || []).join(', ') || 'Keine angegeben'}
Nice-to-have: ${(job.niceToHave || []).join(', ') || 'Keine angegeben'}
---`).join('\n');

  const userPrompt = `ANONYMISIERTER LEBENSLAUF:
${cvText}

VERFÜGBARE STELLEN (${jobs.length} Stellen):
${jobsText}

Analysiere den Lebenslauf und finde die ${limit} besten Matches.
Antworte NUR mit validem JSON im angegebenen Format.`;

  const response = await this.callClaude(systemPrompt, userPrompt);

  // JSON parsen
  const result = JSON.parse(response);

  // Job-URLs hinzufügen
  result.matches = result.matches.map((match: any) => {
    const job = jobs.find(j => j.id === match.jobId);
    return {
      ...match,
      jobTitle: job?.title || 'Unbekannt',
      jobUrl: job?.url || '#',
      applyUrl: job?.applyUrl || '#',
    };
  });

  result.mode = 'job-finder';
  result.totalJobsAnalyzed = jobs.length;

  return result;
}
```

---

## 4. Status-Endpoint Erweiterung

Der bestehende Status-Endpoint `/recruiting/v1/match/status/{id}` funktioniert bereits für Job-Finder-Analysen. Das `result`-Feld enthält dann das Multi-Job-Ergebnis.

**Response-Beispiel (completed):**

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "result": {
    "mode": "job-finder",
    "profile": {
      "extractedSkills": ["PHP", "WordPress", "JavaScript", "MySQL"],
      "experienceYears": 5,
      "education": "Bachelor Informatik"
    },
    "matches": [
      {
        "jobId": 123,
        "jobTitle": "Senior WordPress Developer",
        "jobUrl": "https://example.com/jobs/senior-wordpress-developer/",
        "applyUrl": "https://example.com/jobs/senior-wordpress-developer/#apply",
        "score": 87,
        "category": "high",
        "message": "Ihre PHP- und WordPress-Kenntnisse passen hervorragend zu dieser Stelle.",
        "matchedSkills": ["PHP", "WordPress", "MySQL"],
        "missingSkills": ["React"]
      },
      {
        "jobId": 456,
        "jobTitle": "Full-Stack Developer",
        "jobUrl": "https://example.com/jobs/full-stack-developer/",
        "applyUrl": "https://example.com/jobs/full-stack-developer/#apply",
        "score": 72,
        "category": "high",
        "message": "Gute Grundlagen vorhanden, aber Frontend-Erfahrung könnte stärker sein.",
        "matchedSkills": ["JavaScript", "MySQL"],
        "missingSkills": ["Vue.js", "TypeScript"]
      }
    ],
    "totalJobsAnalyzed": 12
  }
}
```

---

## Checkliste Phase 1

- [ ] `MatchController.php`: Neue Route `/job-finder` registrieren
- [ ] `MatchController.php`: Methode `analyze_job_finder()` implementieren
- [ ] `MatchController.php`: Methode `get_all_active_jobs()` implementieren
- [ ] `MatchController.php`: Methode `send_to_job_finder_api()` implementieren
- [ ] `MatchController.php`: Methode `build_multipart_body_job_finder()` implementieren
- [ ] Worker: Neue Datei `routes/job-finder.ts` erstellen
- [ ] Worker: Route in `index.ts` registrieren
- [ ] Worker: `ClaudeService.analyzeJobFinder()` implementieren
- [ ] Testen: WordPress Endpoint erreichbar
- [ ] Testen: Worker Endpoint erreichbar
- [ ] Testen: Multi-Job-Analyse funktioniert

---

## Nächste Phase

→ [Phase 2: Frontend](./ki-job-finder-phase-2-frontend.md)

---

*Erstellt: Januar 2026*
