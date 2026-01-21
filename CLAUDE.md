# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Recruiting Playbook** is a WordPress plugin specification for professional job listing and applicant management with AI-powered candidate analysis. This repository contains comprehensive documentation and specifications - the actual plugin code is ready to be developed.

- **Type**: WordPress Plugin (ATS - Applicant Tracking System)
- **Status**: Documentation complete, ready for development
- **Language**: German documentation, German-language plugin

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

### PHP
```bash
composer install                 # Install dependencies
composer phpcs                   # Run WordPress coding standards check
composer phpcbf                  # Auto-fix code style
composer test                    # Run PHPUnit tests
composer test:unit               # Run only unit tests
composer test:integration        # Run only integration tests
```

### Admin UI (React)
```bash
cd admin-ui
npm install
npm start                        # Development with watch
npm run build                    # Production build
npm run lint:js                  # Lint JavaScript
npm run lint:css                 # Lint styles
npm test                         # Jest tests
npm run test:watch               # Tests in watch mode
npm run test:coverage            # Tests with coverage report
```

### Frontend (Alpine.js/Tailwind)
```bash
npm run watch                    # Tailwind + esbuild watch
npm run build                    # Production build (minified)
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
5. **Freemium Model**: Free (3 jobs) → Pro (unlimited, 149€) → AI-Addon (19€/month)

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
