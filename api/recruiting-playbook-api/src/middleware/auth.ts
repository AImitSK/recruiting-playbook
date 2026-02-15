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
 *
 * Development Mode:
 * - ENVIRONMENT=development erlaubt Test-Requests mit X-Test-Mode: true
 * - Simuliert eine gültige Pro-Lizenz für lokales Testing
 */
export const authMiddleware = createMiddleware<Env>(async (c, next) => {
  // Development Mode: Erlaube Test-Requests ohne echte Freemius-Lizenz
  if (c.env.ENVIRONMENT === 'development') {
    const testMode = c.req.header('X-Test-Mode');

    if (testMode === 'true') {
      // Simulierte Test-Lizenz für Development
      const testLicense: ValidatedLicense = {
        installId: 'test-install-123',
        licenseId: 'test-license-456',
        planName: 'pro',
        siteUrl: c.req.header('X-Site-Url') || 'http://localhost:8082',
        isActive: true,
        expiresAt: null, // Lifetime für Tests
      };

      c.set('license', testLicense);
      console.log('[Auth] Development mode: Using test license');
      await next();
      return;
    }
  }

  const installId = c.req.header('X-Freemius-Install-Id');
  const timestamp = c.req.header('X-Freemius-Timestamp');
  const signature = c.req.header('X-Freemius-Signature');
  const siteUrl = c.req.header('X-Site-Url');

  if (!installId || !timestamp || !signature) {
    return c.json(
      {
        error: 'unauthorized',
        message: 'Missing authentication headers',
      },
      401
    );
  }

  // Cache-Key für Lizenz
  const cacheKey = `license:${installId}`;

  // Erst im Cache schauen (5 Minuten gültig)
  let cachedLicense = (await c.env.CACHE.get(cacheKey, 'json')) as ValidatedLicense | null;

  if (!cachedLicense) {
    // Freemius API abfragen (Product-Scope mit Bearer Token)
    const freemius = new FreemiusService(
      c.env.FREEMIUS_PRODUCT_ID,
      c.env.FREEMIUS_BEARER_TOKEN
    );

    // Install-Details holen (inkl. secret_key)
    const install = await freemius.getInstall(installId);

    if (!install) {
      return c.json(
        {
          error: 'invalid_install',
          message: 'Installation not found',
        },
        401
      );
    }

    // Signatur verifizieren
    const isValidSignature = await freemius.verifySignature(
      signature,
      timestamp,
      install.secret_key
    );

    if (!isValidSignature) {
      return c.json(
        {
          error: 'invalid_signature',
          message: 'Authentication signature invalid or expired',
        },
        401
      );
    }

    // Prüfen ob Installation aktiv
    if (!install.is_active || install.is_uninstalled) {
      return c.json(
        {
          error: 'install_inactive',
          message: 'Installation is not active',
        },
        403
      );
    }

    // Plan-Details holen
    const plan = await freemius.getPlan(install.plan_id);

    let planName = plan?.name || 'unknown';
    let expiresAt: string | null = null;
    let licenseId = install.license_id;

    if (plan && freemius.hasAiFeature(plan.name)) {
      // Plan hat AI-Feature (Pro-Plan)
      if (install.license_id) {
        const license = await freemius.getLicense(String(install.license_id));

        if (license) {
          if (license.is_cancelled) {
            return c.json(
              { error: 'license_cancelled', message: 'License has been cancelled' },
              403
            );
          }

          if (license.expiration) {
            const expirationDate = new Date(license.expiration);
            if (expirationDate < new Date()) {
              return c.json(
                { error: 'license_expired', message: 'License has expired' },
                403
              );
            }
            expiresAt = license.expiration;
          }
        }
      }
    } else {
      // Plan hat kein AI-Feature → Zugriff verweigern
      return c.json(
        {
          error: 'feature_not_available',
          message: 'AI features require the Pro plan',
        },
        403
      );
    }

    // Validierte Lizenz erstellen
    cachedLicense = {
      installId: String(install.id),
      licenseId: licenseId ? String(licenseId) : null,
      planName,
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
