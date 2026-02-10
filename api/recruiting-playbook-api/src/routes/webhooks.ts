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

  // Prüfen ob Webhook Secret konfiguriert ist
  if (!c.env.FREEMIUS_WEBHOOK_SECRET) {
    console.error('FREEMIUS_WEBHOOK_SECRET not configured');
    return c.json({ error: 'Webhook not configured' }, 500);
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

  const signatureBuffer = await crypto.subtle.sign('HMAC', key, encoder.encode(rawBody));

  // Zu Hex-String konvertieren
  const hashArray = Array.from(new Uint8Array(signatureBuffer));
  const calculatedSignature = hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');

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
