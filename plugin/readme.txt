=== Recruiting Playbook ===
Contributors: stefankuehne
Tags: recruiting, jobs, job-board, applicant-tracking, ats
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.2.37
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional applicant tracking system for WordPress. Create job listings, manage applications, GDPR-compliant.

== Description ==

**Recruiting Playbook** is a professional Applicant Tracking System (ATS) for WordPress. Manage job listings and applications directly from your WordPress dashboard. Built with German companies in mind, fully GDPR-compliant.

### Features

* Unlimited job listings - No limits on creating jobs
* Multi-step application form - CV upload, cover letter, GDPR checkboxes
* Kanban Board - Drag & drop application management
* Application management - Clear table view with filtering & sorting
* Status workflow - New, Screening, Interview, Offer, Hired
* AI Resume Matching - Automatic evaluation of applications
* Advanced email templates - Templates with placeholders
* Talent Pool - Save candidates for future positions
* Notes & Ratings - Internal candidate evaluations
* Reporting & Analytics - Conversion rate, time-to-hire
* Custom Fields - Custom form fields
* API & Webhooks - Integration with external tools
* Email notifications - Automatic notifications for new applications
* GDPR-compliant - Privacy checkboxes, soft deletes, anonymization
* Responsive design - Works on all devices
* Page Builder support - Gutenberg Blocks, Shortcodes, Avada, Elementor

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

= 1.2.37 - 2026-03-27 =
* Fix: Free version UI correctly hides Pro features (WordPress.org Guideline 5)
* Fix: rp_can() returns false for Pro features in Free version
* Fix: Settings and ApplicationDetail pages check is__premium_only()
* Fix: Translation typo "Full Zurückup" corrected to "Vollständiges Backup"

= 1.2.36 - 2026-03-27 =
* Fix: rp_can() function now returns true in Free version (WordPress.org Guideline 5 compliance)
* Fix: All API endpoints and services fully functional without license checks

= 1.2.35 - 2026-03-26 =
* Fix: WordPress.org Plugin Review - All feature gates removed (Guideline 5 Trialware compliance)
* Fix: All features now fully unlocked without license restrictions
* Fix: Freemius SDK updated to 2.13.1
* Fix: SettingsController company endpoint now requires manage_options capability
* Fix: Developer documentation URL corrected to .dev domain
* Fix: Removed Pro/Premium feature labels from readme

= 1.2.34 - 2026-03-20 =
* Fix: Terms/Privacy URLs corrected to /legal/terms and /legal/privacy
* Fix: Alpine.js updated from 3.15.4 to 3.15.8
* Add: External services documentation for Microsoft Teams integration
* Add: Developer documentation service with Terms/Privacy links
* Fix: REST API export endpoint now checks candidate-specific permissions

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

= AI Resume Matching =

The AI Resume Matching feature sends uploaded CVs and job descriptions to our AI analysis service for anonymous evaluation.

**What data is sent:**
* Uploaded CV/resume files (PDF, DOCX, images)
* Job title and description text
* Job requirements (extracted from job content)

**When data is sent:**
* Only when a user explicitly submits a CV for AI analysis
* Only for published job listings

**Service provider:** Recruiting Playbook GmbH
* [Terms of Service](https://recruiting-playbook.com/legal/terms)
* [Privacy Policy](https://recruiting-playbook.com/legal/privacy)

The service anonymizes all personal data before AI analysis and does not store CVs longer than necessary for processing.

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

= Microsoft Teams Integration =

The plugin can send notifications to Microsoft Teams channels using Adaptive Cards.

**What is sent:**
* Application notifications (new applications, status changes)
* Job publishing notifications
* Formatted as Microsoft Adaptive Cards

**When data is sent:**
* Only when Teams webhook is configured in plugin settings
* Only for explicitly enabled notification types

**Service provider:** Microsoft Corporation
* [Adaptive Cards Schema](http://adaptivecards.io/schemas/adaptive-card.json)
* [Microsoft Teams Terms](https://www.microsoft.com/servicesagreement)
* [Microsoft Privacy Policy](https://privacy.microsoft.com/privacystatement)

= Developer Documentation =

The plugin documentation website provides guides and API references.

**Service:** developer.recruiting-playbook.dev
* Used for in-app help links (Avada/Elementor element documentation)
* No user data is transmitted

**Service provider:** Recruiting Playbook GmbH
* [Terms of Service](https://recruiting-playbook.com/legal/terms)
* [Privacy Policy](https://recruiting-playbook.com/legal/privacy)

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
