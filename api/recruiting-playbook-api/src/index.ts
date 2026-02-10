import { Hono } from 'hono';
import { cors } from 'hono/cors';
import { logger } from 'hono/logger';

import { analysisRoutes } from './routes/analysis';
import { jobFinderRoutes } from './routes/job-finder';
import { usageRoutes } from './routes/usage';
import { webhookRoutes } from './routes/webhooks';
import { authMiddleware } from './middleware/auth';
import type { Env } from './types';

const app = new Hono<Env>();

// Middleware
app.use('*', logger());
app.use(
  '*',
  cors({
    origin: '*', // In Produktion: spezifische Domains
    allowMethods: ['GET', 'POST', 'OPTIONS'],
    allowHeaders: [
      'Content-Type',
      'X-Freemius-Install-Id',
      'X-Freemius-Timestamp',
      'X-Freemius-Signature',
      'X-Site-Url',
    ],
  })
);

// Health Check (ohne Auth)
app.get('/health', (c) => c.json({ status: 'ok', timestamp: Date.now() }));

// Warm-Up: Presidio (Cloud Run) aufwecken um Cold Starts zu vermeiden.
// Wird vom WordPress-Plugin aufgerufen wenn User das Match-Modal öffnet.
app.get('/warmup', async (c) => {
  const presidioUrl = c.env.PRESIDIO_URL;
  const start = Date.now();

  // Presidio Health-Check im Background, sofort antworten
  c.executionCtx.waitUntil(
    fetch(`${presidioUrl}/health`, { signal: AbortSignal.timeout(45000) })
      .then((res) => {
        console.log(`[warmup] Presidio responded: ${res.status} in ${Date.now() - start}ms`);
      })
      .catch((err) => {
        console.warn(`[warmup] Presidio ping failed: ${err.message}`);
      })
  );

  return c.json({ status: 'warming', timestamp: Date.now() });
});

// Webhooks (eigene Auth via Signatur)
app.route('/webhooks', webhookRoutes);

// API v1 Routes (mit Freemius Auth)
const v1 = new Hono<Env>();
v1.use('*', authMiddleware);

v1.route('/analysis', analysisRoutes);
v1.route('/analysis/job-finder', jobFinderRoutes);
v1.route('/usage', usageRoutes);

app.route('/v1', v1);

// 404 Handler
app.notFound((c) => c.json({ error: 'Not Found' }, 404));

// Error Handler
app.onError((err, c) => {
  console.error('Error:', err);
  return c.json(
    {
      error: 'Internal Server Error',
      message: err.message,
    },
    500
  );
});

export default app;
