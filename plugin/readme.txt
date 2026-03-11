=== Recruiting Playbook ===
Contributors: stefankuehne
Tags: recruiting, jobs, job-board, applicant-tracking, ats
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.2.25
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
