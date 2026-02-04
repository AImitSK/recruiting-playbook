import { Hono } from 'hono';
import { UsageService } from '../services/usage';
import { ClaudeService, type JobFinderJobData } from '../services/claude';
import { PresidioService } from '../services/presidio';
import type { Env, Bindings } from '../types';

const jobFinder = new Hono<Env>();

/**
 * POST /v1/analysis/job-finder
 *
 * Multi-Job-Matching: CV gegen alle aktiven Jobs analysieren (Mode B)
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
      return c.json({ code: 'missing_data', message: 'Datei oder Jobs fehlen' }, 400);
    }

    let jobs: JobFinderJobData[];
    try {
      jobs = JSON.parse(jobsJson);
    } catch {
      return c.json({ code: 'invalid_jobs', message: 'Jobs müssen valides JSON sein' }, 400);
    }

    if (jobs.length === 0) {
      return c.json({ code: 'no_jobs', message: 'Keine Jobs zum Analysieren' }, 400);
    }

    // 2. Usage Check
    const usageService = new UsageService(c.env.DB);
    const canAnalyze = await usageService.canAnalyze(installId, license.siteUrl);

    if (!canAnalyze) {
      return c.json(
        {
          code: 'limit_reached',
          message: 'Monatliches Analyselimit erreicht',
        },
        429
      );
    }

    // 3. Job-ID erstellen
    const jobId = crypto.randomUUID();

    // 4. Job in DB anlegen
    await c.env.DB.prepare(
      `
      INSERT INTO analysis_jobs (id, freemius_install_id, status, file_type)
      VALUES (?, ?, 'pending', ?)
    `
    )
      .bind(jobId, installId, file.type)
      .run();

    // 5. File-Buffer lesen
    const fileBuffer = await file.arrayBuffer();

    // 6. Async Verarbeitung starten
    c.executionCtx.waitUntil(
      processJobFinder(c.env, jobId, installId, fileBuffer, file.name, jobs, limit)
    );

    // 7. Job-ID zurückgeben
    return c.json(
      {
        job_id: jobId,
        status: 'pending',
        message: 'Analyse gestartet. Status abrufen mit GET /v1/analysis/{job_id}',
      },
      202
    );
  } catch (error) {
    console.error('Job-Finder Error:', error);
    return c.json({ code: 'internal_error', message: 'Interner Fehler' }, 500);
  }
});

/**
 * Async Verarbeitung für Job-Finder
 */
async function processJobFinder(
  env: Bindings,
  jobId: string,
  installId: string,
  fileBuffer: ArrayBuffer,
  filename: string,
  jobs: JobFinderJobData[],
  limit: number
): Promise<void> {
  try {
    // Status: processing
    await env.DB.prepare(
      `UPDATE analysis_jobs SET status = 'processing', started_at = unixepoch() WHERE id = ?`
    )
      .bind(jobId)
      .run();

    // 1. File zu ArrayBuffer konvertieren (bereits geschehen)

    // 2. Anonymisieren (wie in Mode A)
    const presidio = new PresidioService(env.PRESIDIO_URL, env.PRESIDIO_API_KEY);
    const anonymized = await presidio.anonymize(fileBuffer, filename, 'text');

    if (!anonymized.anonymizedText) {
      throw new Error('Anonymisierung fehlgeschlagen - kein Text extrahiert');
    }

    // 3. Multi-Job-Analyse
    const claude = new ClaudeService(env.OPENROUTER_API_KEY);
    const result = await claude.analyzeJobFinder(anonymized.anonymizedText, jobs, limit);

    // 4. Ergebnis speichern
    // result_message enthält das vollständige JSON für Job-Finder
    await env.DB.prepare(
      `
      UPDATE analysis_jobs
      SET status = 'completed',
          result_score = ?,
          result_category = ?,
          result_message = ?,
          completed_at = unixepoch()
      WHERE id = ?
    `
    )
      .bind(
        result.matches[0]?.score || 0,
        result.matches[0]?.category || 'low',
        JSON.stringify(result),
        jobId
      )
      .run();

    // 5. Usage inkrementieren
    const usageService = new UsageService(env.DB);
    await usageService.incrementUsage(installId);
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unbekannter Fehler';
    console.error(`Job-Finder ${jobId} failed:`, errorMessage);

    await env.DB.prepare(
      `UPDATE analysis_jobs SET status = 'failed', error_message = ?, completed_at = unixepoch() WHERE id = ?`
    )
      .bind(errorMessage, jobId)
      .run();
  }
}

export { jobFinder as jobFinderRoutes };
