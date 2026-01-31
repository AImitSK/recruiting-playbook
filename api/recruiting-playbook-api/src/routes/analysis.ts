import { Hono } from 'hono';
import { UsageService } from '../services/usage';
import { ClaudeService, JobData, MatchResult } from '../services/claude';
import { PresidioService } from '../services/presidio';
import type { Env, Bindings } from '../types';

const analysis = new Hono<Env>();

/**
 * POST /v1/analysis/upload
 *
 * Kompletter Flow: Upload → Anonymisierung → Analyse
 */
analysis.post('/upload', async (c) => {
  const license = c.get('license');
  const usageService = new UsageService(c.env.DB);

  // Prüfen ob noch Kontingent vorhanden
  const canAnalyze = await usageService.canAnalyze(license.installId, license.siteUrl);
  if (!canAnalyze) {
    return c.json(
      {
        error: 'quota_exceeded',
        message: 'Monatliches Analyse-Limit erreicht',
      },
      429
    );
  }

  // Multipart Form Data parsen
  const formData = await c.req.formData();
  const file = formData.get('file') as File | null;
  const jobDataStr = formData.get('jobData') as string | null;

  if (!file || !jobDataStr) {
    return c.json(
      {
        error: 'invalid_request',
        message: 'file und jobData sind erforderlich',
      },
      400
    );
  }

  let jobData: JobData;
  try {
    jobData = JSON.parse(jobDataStr);
  } catch {
    return c.json(
      {
        error: 'invalid_request',
        message: 'jobData muss valides JSON sein',
      },
      400
    );
  }

  // Validierung
  if (!jobData.title || !jobData.requirements?.length) {
    return c.json(
      {
        error: 'invalid_request',
        message: 'jobData muss title und requirements enthalten',
      },
      400
    );
  }

  // Dateigröße prüfen (max 10MB)
  if (file.size > 10 * 1024 * 1024) {
    return c.json(
      {
        error: 'file_too_large',
        message: 'Maximale Dateigröße ist 10MB',
      },
      413
    );
  }

  // Job-ID generieren
  const jobId = crypto.randomUUID();

  // Job erstellen
  await c.env.DB.prepare(
    `
    INSERT INTO analysis_jobs (id, freemius_install_id, status, file_type)
    VALUES (?, ?, 'pending', ?)
  `
  )
    .bind(jobId, license.installId, file.type)
    .run();

  // File-Buffer lesen
  const fileBuffer = await file.arrayBuffer();

  // Verarbeitung asynchron starten
  c.executionCtx.waitUntil(
    processFullAnalysis(c.env, jobId, license.installId, fileBuffer, file.name, jobData)
  );

  return c.json(
    {
      job_id: jobId,
      status: 'pending',
      message: 'Analyse gestartet. Status abrufen mit GET /v1/analysis/{job_id}',
    },
    202
  );
});

/**
 * POST /v1/analysis/start
 *
 * Startet Analyse mit bereits anonymisierten Daten
 */
analysis.post('/start', async (c) => {
  const license = c.get('license');
  const usageService = new UsageService(c.env.DB);

  // Prüfen ob noch Kontingent vorhanden
  const canAnalyze = await usageService.canAnalyze(license.installId, license.siteUrl);
  if (!canAnalyze) {
    return c.json(
      {
        error: 'quota_exceeded',
        message: 'Monatliches Analyse-Limit erreicht',
      },
      429
    );
  }

  // Body parsen
  const body = await c.req.json<{
    anonymizedText?: string;
    anonymizedImageBase64?: string;
    imageMimeType?: string;
    jobData: JobData;
  }>();

  // Validierung
  if (!body.jobData?.title || !body.jobData?.requirements?.length) {
    return c.json(
      {
        error: 'invalid_request',
        message: 'jobData mit title und requirements erforderlich',
      },
      400
    );
  }

  if (!body.anonymizedText && !body.anonymizedImageBase64) {
    return c.json(
      {
        error: 'invalid_request',
        message: 'anonymizedText oder anonymizedImageBase64 erforderlich',
      },
      400
    );
  }

  // Job-ID generieren
  const jobId = crypto.randomUUID();

  // Job in DB speichern
  await c.env.DB.prepare(
    `
    INSERT INTO analysis_jobs (id, freemius_install_id, status)
    VALUES (?, ?, 'pending')
  `
  )
    .bind(jobId, license.installId)
    .run();

  // Analyse asynchron starten
  c.executionCtx.waitUntil(processAnalysis(c.env, jobId, license.installId, body));

  return c.json(
    {
      job_id: jobId,
      status: 'pending',
      message: 'Analyse gestartet',
    },
    202
  );
});

/**
 * GET /v1/analysis/:id
 *
 * Ruft den Status einer Analyse ab.
 */
analysis.get('/:id', async (c) => {
  const jobId = c.req.param('id');
  const license = c.get('license');

  const job = await c.env.DB.prepare(
    `
    SELECT * FROM analysis_jobs
    WHERE id = ? AND freemius_install_id = ?
  `
  )
    .bind(jobId, license.installId)
    .first();

  if (!job) {
    return c.json(
      {
        error: 'not_found',
        message: 'Analyse nicht gefunden',
      },
      404
    );
  }

  const response: Record<string, unknown> = {
    job_id: job.id,
    status: job.status,
    created_at: job.created_at,
  };

  if (job.status === 'completed') {
    response.result = {
      score: job.result_score,
      category: job.result_category,
      message: job.result_message,
    };
    response.completed_at = job.completed_at;
  }

  if (job.status === 'failed') {
    response.error = job.error_message;
  }

  return c.json(response);
});

/**
 * Vollständige Analyse: Anonymisierung + Claude
 */
async function processFullAnalysis(
  env: Bindings,
  jobId: string,
  installId: string,
  fileBuffer: ArrayBuffer,
  filename: string,
  jobData: JobData
): Promise<void> {
  try {
    // Status: processing
    await env.DB.prepare(
      `UPDATE analysis_jobs SET status = 'processing', started_at = unixepoch() WHERE id = ?`
    )
      .bind(jobId)
      .run();

    // 1. Anonymisierung
    const presidio = new PresidioService(env.PRESIDIO_URL, env.PRESIDIO_API_KEY);
    const anonymized = await presidio.anonymize(fileBuffer, filename, 'text');

    // 2. Claude Analyse
    const claude = new ClaudeService(env.OPENROUTER_API_KEY);
    let result: MatchResult;

    if (anonymized.type === 'text' && anonymized.anonymizedText) {
      result = await claude.analyzeMatch(anonymized.anonymizedText, jobData);
    } else if (anonymized.anonymizedImageBase64 && anonymized.imageMimeType) {
      result = await claude.analyzeMatchWithImage(
        anonymized.anonymizedImageBase64,
        anonymized.imageMimeType,
        jobData
      );
    } else {
      throw new Error('Anonymisierung hat keine verwendbaren Daten zurückgegeben');
    }

    // 3. Ergebnis speichern
    await env.DB.prepare(
      `
      UPDATE analysis_jobs
      SET status = 'completed', result_score = ?, result_category = ?, result_message = ?, completed_at = unixepoch()
      WHERE id = ?
    `
    )
      .bind(result.score, result.category, result.message, jobId)
      .run();

    // 4. Usage hochzählen
    const usageService = new UsageService(env.DB);
    await usageService.incrementUsage(installId);
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unbekannter Fehler';
    console.error(`Analysis ${jobId} failed:`, errorMessage);

    await env.DB.prepare(
      `UPDATE analysis_jobs SET status = 'failed', error_message = ?, completed_at = unixepoch() WHERE id = ?`
    )
      .bind(errorMessage, jobId)
      .run();
  }
}

/**
 * Analyse mit bereits anonymisierten Daten
 */
async function processAnalysis(
  env: Bindings,
  jobId: string,
  installId: string,
  request: {
    anonymizedText?: string;
    anonymizedImageBase64?: string;
    imageMimeType?: string;
    jobData: JobData;
  }
): Promise<void> {
  try {
    // Status: processing
    await env.DB.prepare(
      `UPDATE analysis_jobs SET status = 'processing', started_at = unixepoch() WHERE id = ?`
    )
      .bind(jobId)
      .run();

    // Claude Analyse
    const claude = new ClaudeService(env.OPENROUTER_API_KEY);
    let result: MatchResult;

    if (request.anonymizedText) {
      result = await claude.analyzeMatch(request.anonymizedText, request.jobData);
    } else if (request.anonymizedImageBase64 && request.imageMimeType) {
      result = await claude.analyzeMatchWithImage(
        request.anonymizedImageBase64,
        request.imageMimeType,
        request.jobData
      );
    } else {
      throw new Error('Keine Eingabedaten vorhanden');
    }

    // Ergebnis speichern
    await env.DB.prepare(
      `
      UPDATE analysis_jobs
      SET status = 'completed', result_score = ?, result_category = ?, result_message = ?, completed_at = unixepoch()
      WHERE id = ?
    `
    )
      .bind(result.score, result.category, result.message, jobId)
      .run();

    // Usage hochzählen
    const usageService = new UsageService(env.DB);
    await usageService.incrementUsage(installId);
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unbekannter Fehler';
    console.error(`Analysis ${jobId} failed:`, errorMessage);

    await env.DB.prepare(
      `UPDATE analysis_jobs SET status = 'failed', error_message = ?, completed_at = unixepoch() WHERE id = ?`
    )
      .bind(errorMessage, jobId)
      .run();
  }
}

export { analysis as analysisRoutes };
