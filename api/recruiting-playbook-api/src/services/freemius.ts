import type { FreemiusInstall, FreemiusLicense, FreemiusPlan } from '../types';

/**
 * Freemius API Service
 *
 * Verwendet Product-Scope API mit Bearer Token für Server-zu-Server Kommunikation.
 * Bearer Tokens funktionieren nur für /products/{product_id}/ Endpoints.
 * Docs: https://docs.freemius.com/api
 */
export class FreemiusService {
  private baseUrl = 'https://api.freemius.com/v1';

  constructor(
    private productId: string,
    private bearerToken: string
  ) {}

  /**
   * Install-Details abrufen (inkl. secret_key)
   */
  async getInstall(installId: string): Promise<FreemiusInstall | null> {
    const path = `/products/${this.productId}/installs/${installId}.json`;

    try {
      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          Authorization: `Bearer ${this.bearerToken}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        console.error('Freemius API error:', response.status, await response.text());
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
    const path = `/products/${this.productId}/licenses/${licenseId}.json`;

    try {
      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          Authorization: `Bearer ${this.bearerToken}`,
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
    const path = `/products/${this.productId}/plans/${planId}.json`;

    try {
      const response = await fetch(`${this.baseUrl}${path}`, {
        headers: {
          Authorization: `Bearer ${this.bearerToken}`,
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
   * Prüfen ob Plan KI-Features beinhaltet
   *
   * AI-Features sind im Pro-Plan enthalten.
   */
  hasAiFeature(planName: string): boolean {
    return planName.toLowerCase() === 'pro';
  }
}
