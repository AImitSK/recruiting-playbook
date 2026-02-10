import { Hono } from 'hono';
import { UsageService } from '../services/usage';
import type { Env } from '../types';

const usage = new Hono<Env>();

/**
 * GET /v1/usage/stats
 *
 * Gibt den aktuellen Verbrauch für die Installation zurück.
 */
usage.get('/stats', async (c) => {
  const license = c.get('license');
  const usageService = new UsageService(c.env.DB);

  const stats = await usageService.getOrCreateUsage(
    license.installId,
    license.siteUrl,
    license.licenseId ?? undefined
  );

  return c.json({
    install_id: license.installId,
    plan: license.planName,
    month: new Date().toISOString().slice(0, 7), // "2025-01"
    usage: {
      count: stats.count,
      limit: stats.limit,
      remaining: stats.remaining,
    },
  });
});

export { usage as usageRoutes };
