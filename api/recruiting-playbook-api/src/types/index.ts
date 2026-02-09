/**
 * Freemius Install Response (von API)
 * Endpoint: GET /v1/products/{product_id}/installs/{install_id}.json
 */
export interface FreemiusInstall {
  id: number;
  plugin_id: number;
  user_id: number;
  url: string;
  title: string;
  version: string;
  plan_id: number;
  license_id: number | null;
  trial_ends: string | null;
  is_premium: boolean;
  is_active: boolean;
  is_uninstalled: boolean;
  secret_key: string; // Site's private key
}

/**
 * Freemius License Response (von API)
 * Endpoint: GET /v1/products/{product_id}/licenses/{license_id}.json
 */
export interface FreemiusLicense {
  id: number;
  plugin_id: number;
  user_id: number;
  plan_id: number;
  pricing_id: number;
  quota: number | null;
  activated: number;
  expiration: string | null; // Format: "Y-m-d H:i:s" or null for lifetime
  is_cancelled: boolean;
}

/**
 * Freemius Plan Response
 */
export interface FreemiusPlan {
  id: number;
  plugin_id: number;
  name: string; // "ai_addon" | "bundle" | "pro" | "free"
  title: string;
}

/**
 * Validierte Lizenz-Info (in Context gespeichert)
 */
export interface ValidatedLicense {
  installId: string;
  licenseId: string | null;
  planName: string;
  siteUrl: string;
  isActive: boolean;
  expiresAt: string | null;
}

/**
 * Cloudflare Bindings
 */
export interface Bindings {
  DB: D1Database;
  CACHE: KVNamespace;
  STORAGE: R2Bucket;
  OPENROUTER_API_KEY: string; // OpenRouter API Key (https://openrouter.ai)
  ENVIRONMENT: string;
  FREEMIUS_PRODUCT_ID: string;
  FREEMIUS_BEARER_TOKEN: string; // Product-scope Bearer Token from Freemius Dashboard
  FREEMIUS_ADDON_PRODUCT_ID: string; // KI-Addon Product ID (23996)
  FREEMIUS_ADDON_BEARER_TOKEN: string; // KI-Addon Product-scope Bearer Token
  FREEMIUS_WEBHOOK_SECRET: string;
  PRESIDIO_URL: string;
  PRESIDIO_API_KEY?: string; // Optional wenn Presidio ohne Auth läuft
}

/**
 * Context Variables (von Middleware gesetzt)
 */
export interface Variables {
  license: ValidatedLicense;
}

/**
 * Kombinierter Env-Type für Hono
 */
export type Env = {
  Bindings: Bindings;
  Variables: Variables;
};
