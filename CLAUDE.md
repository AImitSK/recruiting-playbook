# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Sprache

Die gesamte Kommunikation mit dem Benutzer erfolgt auf **Deutsch**. Code, Variablennamen und technische Bezeichner bleiben auf Englisch.

## Project Overview

**Recruiting Playbook** is a WordPress plugin specification for professional job listing and applicant management with AI-powered candidate analysis. This repository contains comprehensive documentation and specifications - the actual plugin code is ready to be developed.

- **Type**: WordPress Plugin (ATS - Applicant Tracking System)
- **Status**: Documentation complete, plugin scaffold ready
- **Language**: German documentation, German-language plugin

## Development Environment (Dev Container)

This repository uses VS Code Dev Containers for a consistent development environment across all machines.

### Quick Start

1. Install Docker Desktop
2. Install VS Code with "Dev Containers" extension
3. Clone the repository
4. Open in VS Code → Click "Reopen in Container"
5. Wait for setup to complete

### URLs (after container starts)

| Service | URL | Credentials |
|---------|-----|-------------|
| WordPress | http://localhost:8080 | admin / admin |
| WP Admin | http://localhost:8080/wp-admin | admin / admin |
| phpMyAdmin | http://localhost:8081 | wordpress / wordpress |
| MailHog | http://localhost:8025 | - |

### Repository Structure

```
recruiting-playbook-docs/
├── .devcontainer/        # Dev Container configuration
├── docs/                 # Documentation & specifications
│   ├── product/          # Vision, features, pricing
│   ├── technical/        # Architecture, API specs
│   └── roadmap.md        # Development phases
└── plugin/               # WordPress plugin code
    ├── src/              # PHP source (PSR-4)
    ├── assets/           # CSS, JS
    ├── templates/        # Frontend templates
    └── languages/        # Translations
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.0+, WordPress 6.x, OOP with PSR-4 autoloading |
| Admin UI | React (@wordpress/scripts) |
| Frontend | Alpine.js, Tailwind CSS |
| Database | WordPress posts (Jobs) + Custom tables (rp_*) |
| API | REST API (recruiting/v1 namespace) |
| AI | Anthropic Claude API |
| Testing | PHPUnit, Jest, Playwright (post-MVP) |

## Build Commands

All commands run from the `plugin/` directory:

### PHP
```bash
cd plugin
composer install                 # Install dependencies
composer phpcs                   # Run WordPress coding standards check
composer phpcbf                  # Auto-fix code style
composer test                    # Run PHPUnit tests
```

### Assets (Tailwind + Alpine.js)
```bash
cd plugin
npm install
npm run dev                      # Development with watch (CSS + JS)
npm run build                    # Production build (minified)
npm run lint:js                  # Lint JavaScript
npm run lint:css                 # Lint styles
```

## Architecture

### Layered OOP Pattern
```
Entry Point (recruiting-playbook.php)
    ↓
Core Layer (Plugin, Activator, Deactivator, I18n)
    ↓
Admin/Public/API Layers
    ↓
Service Layer (Business Logic)
    ↓
Repository Layer (Data Access)
    ↓
Model Layer (Data Objects)
    ↓
Database (wp_posts + Custom Tables rp_*)
```

### Key Namespace
- Root namespace: `RecruitingPlaybook`
- PSR-4 autoloading from `src/`

### Plugin Constants
```php
RP_VERSION           // Plugin version
RP_PLUGIN_FILE       // __FILE__
RP_PLUGIN_DIR        // plugin_dir_path()
RP_PLUGIN_URL        // plugin_dir_url()
RP_PLUGIN_BASENAME   // plugin_basename()
```

### Custom Database Tables
- `rp_applications` - Job applications
- `rp_candidates` - Applicant profiles
- `rp_documents` - CVs, certificates
- `rp_activity_log` - Audit trail
- `rp_api_keys` - API key management
- `rp_webhooks` / `rp_webhook_deliveries` - Webhook system
- `rp_email_log` - Email history (Pro)

### Custom Post Type
- `job_listing` - Jobs
- Taxonomies: `job_category`, `job_location`, `employment_type`

## Application Status Flow

Applications follow defined status transitions:
```
new → screening → interview → offer → hired
  ↓         ↓          ↓         ↓
  rejected (can happen from any state)
  withdrawn (can happen from any state)
```

## Key Design Decisions

1. **Hybrid Database**: WordPress posts for Jobs (SEO, WPML support) + custom tables for applications (performance)
2. **Progressive Enhancement**: Frontend works without JS, Alpine.js adds interactivity
3. **Server-Side Rendering**: PHP renders content (SEO-friendly)
4. **Soft Deletes**: GDPR compliance with `deleted_at` field and anonymization
5. **Freemium Model**: Free (unlimited jobs) → Pro (Kanban, API, 149€) → AI-Addon (19€/month)

## Testing Strategy

**Target Coverage**: 50-60% (critical paths)

### Critical Test Areas (always test)
- License Manager (revenue-critical)
- Feature Flags (freemium logic)
- Application Service (core business logic)
- REST API Endpoints
- Form Validation

### Test Tools
- PHP: PHPUnit with Brain Monkey for WP function mocking
- JavaScript: Jest with Testing Library
- E2E: Playwright (post-MVP)

## Documentation Structure

```
docs/
├── product/           # Vision, features, pricing
├── technical/         # Architecture, database, API specs
├── requirements/      # User stories
└── roadmap.md        # Development phases
```

Key technical docs:
- `docs/technical/plugin-architecture.md` - Full architecture with code examples
- `docs/technical/database-schema.md` - Complete DB schema
- `docs/technical/api-specification.md` - REST API endpoints
- `docs/technical/testing-strategy.md` - Test setup and examples

## Coding Standards

- Follow WordPress Coding Standards (PHPCS with WordPress ruleset)
- PHP 8.0+ features allowed (typed properties, union types, etc.)
- Admin UI uses @wordpress/scripts conventions
- Translations: Text domain `recruiting-playbook`, domain path `/languages`
