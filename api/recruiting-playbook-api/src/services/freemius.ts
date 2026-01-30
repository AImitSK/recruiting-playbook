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
   * Install-Details abrufen (inkl. secret_key)
   */
  async getInstall(installId: string): Promise<FreemiusInstall | null> {
    const path = `/developers/${this.devId}/plugins/${this.productId}/installs/${installId}.json`;

    try {
      // Für Developer-Scope: Basic Auth mit dev_id:dev_secret
      const authHeader = btoa(`${this.devId}:${this.devSecretKey}`);

      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          Authorization: `Basic ${authHeader}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        console.error('Freemius API error:', response.status);
        return null;
      }

      return (await response.json()) as FreemiusInstall;
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
          Authorization: `Basic ${authHeader}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        return null;
      }

      return (await response.json()) as FreemiusLicense;
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
          Authorization: `Basic ${authHeader}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        return null;
      }

      return (await response.json()) as FreemiusPlan;
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
    const expectedHash = hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');

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
