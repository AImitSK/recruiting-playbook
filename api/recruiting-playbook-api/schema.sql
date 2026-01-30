-- Recruiting Playbook API - Database Schema
-- Phase 1: Infrastructure
--
-- Hinweis: Keine `licenses` Tabelle nötig - Freemius verwaltet alle Lizenzen!

-- Nutzungs-Tracking (referenziert Freemius Install ID)
CREATE TABLE IF NOT EXISTS usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    freemius_install_id TEXT NOT NULL,
    freemius_license_id TEXT,
    site_url TEXT NOT NULL,
    month TEXT NOT NULL,                -- Format: "2025-01"
    analyses_count INTEGER DEFAULT 0,
    analyses_limit INTEGER DEFAULT 100,
    created_at INTEGER DEFAULT (unixepoch()),
    updated_at INTEGER DEFAULT (unixepoch()),
    UNIQUE(freemius_install_id, month)
);

CREATE INDEX IF NOT EXISTS idx_usage_install_month ON usage(freemius_install_id, month);

-- Analyse-Jobs (für async Verarbeitung)
CREATE TABLE IF NOT EXISTS analysis_jobs (
    id TEXT PRIMARY KEY,  -- UUID
    freemius_install_id TEXT NOT NULL,
    job_posting_id INTEGER,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    file_type TEXT,
    result_score INTEGER,
    result_category TEXT,
    result_message TEXT,
    error_message TEXT,
    created_at INTEGER DEFAULT (unixepoch()),
    started_at INTEGER,
    completed_at INTEGER
);

CREATE INDEX IF NOT EXISTS idx_jobs_status ON analysis_jobs(status);
CREATE INDEX IF NOT EXISTS idx_jobs_install ON analysis_jobs(freemius_install_id);

-- Audit Log
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    freemius_install_id TEXT,
    action TEXT NOT NULL,
    details TEXT,  -- JSON
    ip_address TEXT,
    created_at INTEGER DEFAULT (unixepoch())
);

CREATE INDEX IF NOT EXISTS idx_audit_install ON audit_log(freemius_install_id);
CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_log(created_at);
