/**
 * Usage Tracking Service
 *
 * Verwaltet das monatliche Nutzungs-Kontingent für KI-Analysen.
 */
export class UsageService {
  constructor(private db: D1Database) {}

  private getCurrentMonth(): string {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  }

  async getOrCreateUsage(
    installId: string,
    siteUrl: string,
    licenseId?: string
  ): Promise<{
    count: number;
    limit: number;
    remaining: number;
  }> {
    const month = this.getCurrentMonth();

    let usage = await this.db
      .prepare('SELECT * FROM usage WHERE freemius_install_id = ? AND month = ?')
      .bind(installId, month)
      .first();

    if (!usage) {
      await this.db
        .prepare(
          `
          INSERT INTO usage (freemius_install_id, freemius_license_id, site_url, month, analyses_count, analyses_limit)
          VALUES (?, ?, ?, ?, 0, 100)
        `
        )
        .bind(installId, licenseId || null, siteUrl, month)
        .run();

      usage = { analyses_count: 0, analyses_limit: 100 };
    }

    return {
      count: usage.analyses_count as number,
      limit: usage.analyses_limit as number,
      remaining: (usage.analyses_limit as number) - (usage.analyses_count as number),
    };
  }

  async canAnalyze(installId: string, siteUrl: string): Promise<boolean> {
    const usage = await this.getOrCreateUsage(installId, siteUrl);
    return usage.remaining > 0;
  }

  async incrementUsage(installId: string): Promise<void> {
    const month = this.getCurrentMonth();

    await this.db
      .prepare(
        `
        UPDATE usage
        SET analyses_count = analyses_count + 1, updated_at = unixepoch()
        WHERE freemius_install_id = ? AND month = ?
      `
      )
      .bind(installId, month)
      .run();
  }
}
