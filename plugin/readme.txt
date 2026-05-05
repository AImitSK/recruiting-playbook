=== Recruiting Playbook ===
Contributors: stefankuehne
Tags: recruiting, jobs, job-board, applicant-tracking, ats
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.9.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional applicant tracking system for WordPress. Create job listings, manage applications, GDPR-compliant.

== Description ==

**Recruiting Playbook** is a professional Applicant Tracking System (ATS) for WordPress. Manage job listings and applications directly from your WordPress dashboard. Built with German companies in mind, fully GDPR-compliant.

### Features (Free)

* Unlimited job listings - No limits
* Multi-step application form - CV upload, cover letter, GDPR checkboxes
* Application management - Clear table view with filtering & sorting
* Status workflow - New, Screening, Interview, Offer, Hired
* Email notifications - Automatic notifications for new applications
* GDPR-compliant - Privacy checkboxes, soft deletes, anonymization
* Responsive design - Works on all devices
* Page Builder support - Gutenberg Blocks, Shortcodes

### Premium Features (Pro)

* Kanban Board - Drag & drop application management
* AI Resume Matching - Automatic evaluation of applications
* Advanced email templates - Templates with placeholders
* Talent Pool - Save candidates for future positions
* Notes & Ratings - Internal candidate evaluations
* Reporting & Analytics - Conversion rate, time-to-hire
* Custom Fields - Custom form fields
* API & Webhooks - Integration with external tools
* Avada & Elementor Integration - Premium page builder elements

[Learn more](https://recruiting-playbook.com/)

== Installation ==

1. Install and activate the plugin
2. Complete the setup wizard
3. Create your first job listing
4. Done!

== Frequently Asked Questions ==

= Is the plugin GDPR-compliant? =

Yes, the plugin includes all required privacy features including consent checkboxes, soft deletes, and data anonymization.

= Can I customize the design? =

Yes, via Design & Branding settings in the plugin admin area.

= How many job listings can I create? =

Unlimited! Both in Free and Pro versions.

== Screenshots ==

1. Job listing overview on the frontend
2. Application form with GDPR checkboxes
3. Admin dashboard - Application overview
4. Kanban Board (Pro)
5. Design & Branding settings

== Changelog ==

= 1.9.2 - 2026-05-05 =
* Hotfix: `RP_DEBUG_TRACKING` constant renamed to `RECPL_DEBUG_TRACKING` (4+ char prefix). Legacy name still recognized for backwards compatibility. Affects both PHP (`Plugin.php`) and the JavaScript window flag in `tracking.js`.
* Tool: `wordpress-org-compliance-test.sh` no longer flags `composer.json` — it is intentionally kept in the release ZIP per WP.org reviewer requirement (v1.7.9 round).

= 1.9.1 - 2026-05-05 =
* Hotfix: register_setting() group renamed from `rp_settings_group` to `recpl_settings_group` (4+ char prefix). The legacy group name was the last remaining short-prefix identifier in admin settings registration.

= 1.9.0 - 2026-05-05 =
* WordPress.org: Trialware compliance — Pro feature-gating helpers (`recpl_can()`, `recpl_require_feature()`, `recpl_check_feature_permission()`, `recpl_is_pro()`, `recpl_has_ai()`, `recpl_has_cv_matching()`, `recpl_features()`, `recpl_user_can_use_feature()`, plus the feature plan mapping) moved to a separate `pro-helpers.php` file that is physically removed from the Free build via Freemius `@fs_premium_only`. The Free build no longer contains any code that locks or describes Pro features.
* WordPress.org: Free build no longer renders a "Pro Settings" admin section, no `isPro` flag is leaked to the React UI, and the Pro-specific i18n strings (`proSettings`, `whiteLabel`, `disableAiFeatures`, …) are stripped at build time.
* WordPress.org: Prefix migration — `RP_*` constants removed (no BC alias). All option keys (`rp_settings` → `recpl_settings`, `rp_integrations`, `rp_design_settings`, `rp_role_capabilities`, `rp_auto_email_settings`, `rp_version`, `rp_db_version`, `rp_wizard_completed`, `rp_employment_types_installed`, `rp_privacy_policy_version`, `rp_keep_data_on_uninstall`, `rp_talent_pool_retention`, `rp_last_cleanup_run`, `rp_activation_redirect`), all post meta (`_rp_*` → `_recpl_*`), the `rp_default_signature_id` user meta, all transients (`rp_flush_rewrite_rules`, `rp_import_result`, `rp_wizard_created_job`, `rp_rewrite_version`, `rp_bulk_email_ids_*`) and the development-mode constant (`RP_DEV_MODE` → `RECPL_DEV_MODE`) renamed to use the `recpl_` prefix (4+ characters per WordPress.org guidelines).
* WordPress.org: Hooks renamed to `recpl_*` prefix with `do_action_deprecated()` / `apply_filters_deprecated()` bridges for backward compatibility — `rp_email_primary_color`, `rp_email_text_color`, `rp_email_content`, `rp_email_headers`, `rp_email_sent`, `rp_application_migrated`, `rp_spam_blocked`, `rp_new_job_days`. Internal scheduler hooks (`rp_send_auto_email`, `rp_deliver_webhook`, `rp_slack_retry_cron`, `rp_teams_retry_cron`) are renamed without bridge — the `Activator::migrateLegacyPrefixes()` routine unschedules the old cron events on update.
* WordPress.org: `Activator::migrateLegacyPrefixes()` automatically migrates options, post meta and user meta on plugin update from any pre-1.9.0 version, idempotently. Old keys are deleted after the copy succeeds. Backup imports continue to accept legacy `rp_settings` keys.
* Security: REST API per-resource permission callbacks for `/applications/stats`, `/applications/reorder`, `/candidates/{id}/export`, `/documents/{id}/download-url`. Stats now require `rp_view_stats` (or `manage_options`); reorder iterates over each `positions[].id` and calls `CapabilityService::canAccessApplication()`; the candidate export resolves all the candidate's applications and verifies access to every single one (so a recruiter who is assigned to only one of the candidate's jobs cannot export the full DSGVO record); the document download URL endpoint resolves the document's `application_id` and verifies access.
* WordPress.org: `External Services` section in readme.txt now distinguishes the proxy (Recruiting Playbook GmbH) from the actual LLM processor (Anthropic, PBC) and links directly to Anthropic's Terms of Service, Privacy Policy and API Usage Policy as required by Guideline 6.

= 1.8.1 - 2026-04-28 =
* Hotfix: Free build pre-processor pattern — Backup handlers in Menu.php now use the canonical `if (recpl_fs()->is__premium_only()) { ... }` wrapper around the call site (Plugin.php convention) plus a `class_exists()` guard inside the methods, so `BackupExporter`/`BackupImporter` references cannot trigger a class-not-found fatal in Free
* Hotfix: Corrected `@fs_premium_only` paths for `match-modal.js` and `job-finder.js` (`/assets/src/js/components/*` → `/assets/dist/js/*`) so the compiled bundles are removed from the Free build

= 1.8.0 - 2026-04-28 =
* WordPress.org: Trialware compliance — `BackupExporter` and `BackupImporter` now removed from Free build via Freemius `@fs_premium_only` directive and wrapped with `is__premium_only()` guards in Menu handlers (Guideline 5)
* Security: REST API permission callbacks now enforce per-resource access — JobController update/delete checks `current_user_can('edit_post'/'delete_post', $id)` and verifies job assignment for non-admins
* Security: ApplicationController get/update/delete callbacks now verify per-application access via `CapabilityService::canAccessApplication()` — assigned recruiters can no longer read or modify applications outside their job assignments
* Security: NoteController, ActivityController, RatingController callbacks now validate the `application_id` route parameter against the user's job assignments
* Internal: `CapabilityService::getJobIdForApplication()` exposed as public helper

= 1.7.10 - 2026-04-20 =
* WordPress.org: Renamed action hook `rp_application_created` to `recpl_application_created` (4+ character prefix requirement). Legacy hook name still fires via do_action_deprecated() for backward compatibility.

= 1.7.9 - 2026-04-20 =
* WordPress.org: Trialware compliance — replaced runtime feature gates with Freemius `is__premium_only()` build-time wrappers (Guideline 5)
* WordPress.org: Fixed Terms/Privacy/Imprint URLs (now under /legal/*)
* WordPress.org: Replaced dead documentation domain with recruiting-playbook.com/docs/*
* WordPress.org: Teams Adaptive Cards schema URL upgraded HTTP → HTTPS
* WordPress.org: composer.json now included in release ZIP (reviewer requirement)
* Feature: Location taxonomy now supports street address, postal code and region term meta (Google for Jobs: streetAddress, postalCode, addressRegion)
* Dependency: AlpineJS 3.15.4 → 3.15.11
* Cleanup: Removed dead getXxxMenuLabel/renderUpgradeNotice/renderUpgradePrompt helpers no longer reachable in Free
* Technical: All Pro feature-gated code blocks in Plugin.php, Menu.php, ApplicationDetail.php, EmailService etc. now use Freemius preprocessor pattern

= 1.7.8 - 2026-04-05 =
* Fix: Application form now works correctly in Free version (Alpine.js attributes preserved)
* Fix: FormRenderService now falls back to standard form when Pro services unavailable
* Fix: Removed debug console.log statements from admin settings
* Fix: Corrected block documentation (Application Form Block → Latest Jobs Block)

= 1.7.7 - 2026-04-05 =
* Fix: Corrected shortcode in Getting Started documentation (rp_job_list → rp_jobs)

= 1.7.6 - 2026-04-05 =
* Fix: Fixed function_exists checks for recpl_has_cv_matching (was checking for rp_has_cv_matching)
* Fix: Updated 8 files with correct function reference: Shortcodes.php, MatchController.php, Plugin.php, WidgetLoader.php, ElementLoader.php, single-job_listing.php, job-card.php, match-modal.php
* Technical: Completes prefix migration from rp_ to recpl_ for all function references

= 1.7.5 - 2026-04-05 =
* Build: Added wildcard patterns */composer.json to catch files in subdirectories
* Build: Added debug output to trace composer.json location in ZIP

= 1.7.4 - 2026-04-05 =
* Build: Fixed ZIP exclusion - now uses zip -d to force-remove dev files after creation
* Build: Removes composer.json, package-lock.json, .distignore from final ZIP
* Test: Fixed compliance test false-positive for RP_ backward-compat constants

= 1.7.3 - 2026-04-05 =
* Build: GitHub Action now excludes composer.json, .distignore and other dev files from ZIP
* Build: Added verification step that fails if forbidden files are in release ZIP

= 1.7.2 - 2026-04-05 =
* WordPress.org: Renamed last remaining rp_check_requirements() to recpl_check_requirements()
* Technical: 100% of global functions now use recpl_ prefix (4+ characters)

= 1.7.1 - 2026-04-05 =
* WordPress.org: Complete prefix migration from rp_ to recpl_ for all 15 helper functions
* WordPress.org: recpl_can(), recpl_tier(), recpl_is_pro(), recpl_require_feature() and 11 more
* WordPress.org: Updated 56 files using these functions throughout the codebase
* WordPress.org: Extended compliance test script with new checks (Update URI, prefix length, hidden files)
* Technical: Compliance test now validates 4+ character prefix requirement

= 1.7.0 - 2026-04-04 =
* WordPress.org: Changed function prefix from rp_fs to recpl_fs (4+ character requirement)
* WordPress.org: Changed global variables prefix from rp_ to recpl_ (rp_authenticated_api_key, rp_rate_limit_headers)
* WordPress.org: Fixed Block render.php escaping (8 files) - proper phpcs:ignore with printf()
* WordPress.org: Plugin header now before ABSPATH check (Plugin Checker compatibility)
* Technical: All Freemius SDK wrapper functions now use recpl_ prefix
* Technical: Backwards compatible - existing rp_can(), rp_tier() helper functions unchanged

= 1.6.1 - 2026-03-29 =
* Feature: Design & Branding tab now accessible in Free version with color customization
* Feature: Basic branding (primary color, button colors, card colors) available to all users
* Feature: Added Getting Started documentation tab with shortcode examples and copy-to-clipboard
* UX: Professional German email formatting (formal "Sie" instead of informal "Du")
* UX: Improved email salutation format (Hallo Herr/Frau [Nachname])
* UX: Lighter placeholder text colors (#9ca3af) for better form field visibility
* Fix: Button colors now respect primary color selection in Free version
* Fix: Card borders now subtle gray (1px) instead of thick blue in Free version
* Technical: CssGeneratorService feature flag checks for Free vs Pro styling
* Technical: EmailService uses last_name for formal German salutation
* WordPress.org: Complete compliance with all automated review guidelines
* WordPress.org: readme.txt stable tag now matches plugin version

= 1.4.0 - 2026-03-29 =
* Fix: Add class_exists guards for EmailService in ApplicationService.php
* Fix: Add class_exists guards for EmailService in Menu.php
* Fix: Add class_exists guards for all Premium services and controllers in Plugin.php
* Fix: Prevents fatal errors in Free version when Premium classes are removed by Freemius
* Stability: Free version now activates and runs without crashes
* Clean release after resolving WordPress.org Guideline 5 compliance issues

= 1.3.3 - 2026-03-29 =
* Fix: Add class_exists guards for EmailService in ApplicationService.php
* Fix: Add class_exists guards for EmailService in Menu.php
* Fix: Add class_exists guards for all Premium services and controllers in Plugin.php
* Fix: Prevents fatal errors in Free version when Premium classes are removed by Freemius
* Stability: Free version now activates and runs without crashes

= 1.3.2 - 2026-03-28 =
* Fix: REST API get_company permission now uses manage_options (WordPress.org Review Issue #2)
* Add: Microsoft Teams / adaptivecards.io external service documentation (WordPress.org Review Issue #4)
* WordPress.org Guideline 5 (Trialware) compliance - critical issues resolved

= 1.3.1 - 2026-03-28 =
* Fix: @fs_premium_only meta-tag syntax for Freemius preprocessor (WordPress.org Review Issue #1)
* Fix: Premium files now correctly removed from Free version by Freemius
* Add: WordPress.org compliance test tool (tools/wordpress-org-compliance-test.sh)
* Add: Comprehensive solution plan documentation for Freemius premium code handling

= 1.2.33 - 2026-03-12 =
* Fix: ABSPATH check at very beginning of file for WordPress.org Plugin Checker

= 1.2.32 - 2026-03-12 =
* Fix: ABSPATH check after namespace declaration for Freemius compatibility

= 1.2.31 - 2026-03-12 =
* Fix: ABSPATH check format for WordPress.org Plugin Checker detection

= 1.2.30 - 2026-03-12 =
* Fix: Split main plugin file to resolve Freemius/WordPress.org namespace conflict
* Fix: ABSPATH check in main file, namespace code in bootstrap.php

= 1.2.29 - 2026-03-12 =
* Fix: ABSPATH check before namespace, version sync for WordPress.org compliance

= 1.2.28 - 2026-03-12 =
* Fix: Version sync across all files for Freemius deployment

= 1.2.27 - 2026-03-12 =
* Fix: ABSPATH check moved before namespace declaration (WordPress.org requirement)

= 1.2.26 - 2026-03-12 =
* Fix: WordPress.org Plugin Review - All 11 review issues resolved
* Fix: Removed deprecated finfo_close() calls (PHP 8.1+ compatibility)
* Fix: Replaced ob_start() output buffering with script_loader_tag filter
* Fix: Admin menu position lowered from 25 to 56 (WordPress.org guidelines)
* Fix: Inline scripts/styles converted to wp_add_inline_style/script
* Fix: REST API endpoints now use proper permission callbacks with documentation
* Fix: Plugin constants now use RECPL_ prefix (min. 4 chars as required)
* Fix: $_FILES sanitization with sanitize_file_name() in FileField.php
* Fix: Output escaping with esc_attr() and esc_html() at critical locations
* Fix: Freemius is_org_compliant mode enabled
* Add: External Services documentation in readme.txt (AI API, Freemius)
* Add: Source Code section in readme.txt with build instructions

= 1.2.25 - 2026-03-11 =
* Fix: WordPress.org Plugin Check - output escaping with wp_kses_post()
* Fix: Plugin name mismatch between header and readme.txt resolved
* Fix: ABSPATH direct access protection detection improved
* Fix: Hook prefix changed to recruiting_playbook_ for WordPress.org compliance
* Fix: SQL prepared statement phpcs annotations corrected
* Fix: Input sanitization and nonce verification improved

= 1.2.24 - 2026-03-11 =
* Fix: Application status filter tabs now persist correctly after page reload
* Fix: WordPress.org Plugin Check errors and warnings resolved (91 files)

= 1.2.19 - 2026-02-20 =
* Add: Delete button in application list (React component)
* Fix: Import script now sends correct taxonomy slug for employment type

= 1.2.18 - 2026-02-20 =
* Fix: Taxonomy management in admin menu corrected (locations, employment type, categories now properly linked)

= 1.2.17 - 2026-02-20 =
* Fix: Applications page header layout (system messages now displayed cleanly above the heading)

= 1.2.16 - 2026-02-20 =
* Add: Taxonomy management in admin menu (locations, employment type, categories)
* Fix: Translation errors corrected

= 1.2.15 - 2026-02-20 =
* Fix: Email salutation now shows full name
* Fix: Removed duplicate signature in email templates

= 1.2.14 - 2026-02-20 =
* Fix: AI analysis tab displays correctly again (React Hooks order)
* Fix: Email automation now saves all statuses (interview, offer, hired)

= 1.2.13 - 2026-02-20 =
* Fix: Pro version sends applicant confirmation only when automation template is configured
* Add: Status "New Application" available in email automation
* Add: Toggle to disable all AI features in settings
* Fix: AI buttons are hidden when feature is disabled

= 1.2.12 - 2026-02-19 =
* Fix: JS paths corrected - now loading from assets/dist/ instead of assets/src/
* Fix: match-modal.js and job-finder.js are correctly included

= 1.2.11 - 2026-02-19 =
* Fix: AI Matching JavaScript (match-modal.js, job-finder.js) now correctly included in build
* Fix: Application form loads correctly

= 1.2.10 - 2026-02-19 =
* Fix: Notification Service array access corrected
* Fix: ActivityService.log() parameters corrected
* Fix: Document download MIME type detection

= 1.2.9 - 2026-02-19 =
* Fix: TeamsNotifier/SlackNotifier findById() changed to get()
* Fix: AI button navigation corrected

= 1.2.8 - 2026-02-18 =
* Add: Microsoft Teams integration with Adaptive Cards
* Add: Teams webhook notifications for applications, status changes, and job publishing

= 1.2.7 - 2025-02-17 =
* Fix: Update tested up to WordPress 6.9
* Fix: Include composer.json in distribution (WordPress.org requirement)

= 1.2.6 - 2025-02-17 =
* Add: German translations for Admin Pricing Page
* Fix: WordPress.org plugin check compliance
* Remove: Hidden .gitkeep files
* Fix: I18n string literal requirement
* Fix: Use WP_Filesystem instead of move_uploaded_file()

= 1.2.4 - 2025-02-17 =
* Fix: Plugin icon display on Freemius Pricing Page

= 1.2.3 - 2025-02-17 =
* Add: Custom CSS for Freemius Pricing Page

= 1.2.2 - 2025-02-17 =
* Add: Freemius SDK customizations

= 1.2.1 - 2025-02-10 =
* Fix: Script dependency warnings

= 1.0.0 - 2025-01-01 =
* Initial release

== Upgrade Notice ==

= 1.2.8 =
New Microsoft Teams integration with Adaptive Cards for real-time notifications.

= 1.2.7 =
WordPress.org final compliance fixes (tested 6.9, composer.json included).

== External Services ==

This plugin connects to external services for specific functionality. By using these features, you agree to their respective terms of service and privacy policies.

= AI Resume Matching (Pro Feature) =

The AI Resume Matching feature is implemented via two layers:

1. The plugin sends data to a Recruiting Playbook GmbH proxy endpoint.
2. The proxy forwards the prompt to Anthropic's Claude API for the actual AI processing.

This means the data is processed by Anthropic, PBC (USA) on behalf of Recruiting Playbook GmbH.

**What data is sent:**
* Uploaded CV/resume text (extracted from PDF, DOCX, images — only the text content, not the original file)
* Job title and description text
* Job requirements (extracted from job content)
* No applicant names, addresses or phone numbers are forwarded to Anthropic.

**When data is sent:**
* Only when an authenticated admin user explicitly triggers an AI matching analysis
* Only for published job listings
* Only if the Pro plan is active and AI features are not disabled in plugin settings

**Service providers and legal documents:**

Recruiting Playbook GmbH (proxy / API gateway):
* [Terms of Service](https://recruiting-playbook.com/legal/terms)
* [Privacy Policy](https://recruiting-playbook.com/legal/privacy)
* [Imprint](https://recruiting-playbook.com/legal/imprint)

Anthropic, PBC (LLM processing — receives the proxied data):
* [Anthropic Terms of Service](https://www.anthropic.com/legal/consumer-terms)
* [Anthropic Privacy Policy](https://www.anthropic.com/legal/privacy)
* [Anthropic API Usage Policy](https://www.anthropic.com/legal/aup)

The proxy strips identifying personal data (names, contact details) before forwarding to Anthropic. Anthropic does not retain inputs by default (per their commercial API terms). The CV/job text is not stored long-term on either service.

= Freemius SDK =

This plugin uses Freemius for licensing, updates, and analytics.

**What data is collected:**
* WordPress site URL and version
* Plugin version and license status
* PHP version and server information (for compatibility)

**When data is sent:**
* On plugin activation and license validation
* Periodically for update checks

**Service provider:** Freemius, Inc.
* [Terms of Service](https://freemius.com/terms/)
* [Privacy Policy](https://freemius.com/privacy/)

= Developer Documentation =

The plugin documentation is hosted on the plugin website.

**Service:** recruiting-playbook.com
* Used for in-app help links (Gutenberg block docs, Avada integration docs)
* No user data is transmitted — only the user's browser navigates to the docs URL when a help link is clicked
* [Privacy Policy](https://recruiting-playbook.com/legal/privacy)

= Microsoft Teams Integration (Pro Feature) =

The Microsoft Teams notification feature sends job and application updates to Microsoft Teams channels using Adaptive Cards.

**What data is sent:**
* Job titles and application status updates
* Applicant names (no CVs or sensitive documents)
* Links to admin dashboard for review

**When data is sent:**
* Only when Microsoft Teams webhook is configured and enabled
* On new applications, status changes, or job publishing

**Service provider:** Microsoft Corporation
* Adaptive Cards Schema: https://adaptivecards.io
* [Microsoft Teams Privacy](https://privacy.microsoft.com/)
* [Microsoft Teams Terms](https://www.microsoft.com/licensing/terms/)

The plugin validates Adaptive Card JSON against the official schema (adaptivecards.io) to ensure proper formatting. Webhook URLs remain on your Microsoft Teams account and are not transmitted to third parties.

**Note:** adaptivecards.io is referenced only as a JSON schema identifier for card validation. The plugin does NOT send data to adaptivecards.io — the schema URL is an identifier, not a network endpoint.

== Source Code ==

The minified JavaScript and CSS files in this plugin are built from source files included in the plugin.

**Source code locations:**
* JavaScript source: `assets/src/js/`
* CSS source: `assets/src/css/`
* React components: `assets/src/js/admin/`

**Build tools:**
* Webpack via @wordpress/scripts (for admin React components)
* Tailwind CSS (for frontend styles)
* esbuild (for frontend JavaScript)

**Build instructions:**
1. Install dependencies: `npm install`
2. Development build: `npm run dev`
3. Production build: `npm run build`

The full source code is available in the `assets/src/` directory and can be modified and rebuilt using the standard WordPress build toolchain.
