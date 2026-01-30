import { Hono } from 'hono';
import { UsageService } from '../services/usage';
import type { Env } from '../types';

const analysis = new Hono<Env>();

/**
 * POST /v1/analysis/start
 *
 * Startet eine neue CV-Analyse.
 * In Phase 1 nur Platzhalter - die eigentliche Analyse wird in Phase 2/3 implementiert.
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
        message: 'Monthly analysis quota exceeded',
      },
      429
    );
  }

  // Body parsen
  const body = await c.req.json<{
    job_posting_id: number;
    file?: {
      name: string;
      content: string; // Base64
      type: string;
    };
  }>();

  if (!body.job_posting_id) {
    return c.json(
      {
        error: 'invalid_request',
        message: 'job_posting_id is required',
      },
      400
    );
  }

  // Job-ID generieren
  const jobId = crypto.randomUUID();

  // In Datenbank speichern
  await c.env.DB.prepare(
    `
    INSERT INTO analysis_jobs (id, freemius_install_id, job_posting_id, status, file_type)
    VALUES (?, ?, ?, 'pending', ?)
  `
  )
    .bind(jobId, license.installId, body.job_posting_id, body.file?.type || null)
    .run();

  // Nutzung inkrementieren
  await usageService.incrementUsage(license.installId);

  // Audit Log
  await c.env.DB.prepare(
    `
    INSERT INTO audit_log (freemius_install_id, action, details, ip_address)
    VALUES (?, 'analysis_started', ?, ?)
  `
  )
    .bind(
      license.installId,
      JSON.stringify({ job_id: jobId, job_posting_id: body.job_posting_id }),
      c.req.header('CF-Connecting-IP') || 'unknown'
    )
    .run();

  return c.json({
    job_id: jobId,
    status: 'pending',
    message: 'Analysis started. Use GET /v1/analysis/{job_id} to check status.',
  });
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
        message: 'Analysis job not found',
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

export { analysis as analysisRoutes };
