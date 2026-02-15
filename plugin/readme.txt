=== Recruiting Playbook ===
Contributors: aimitsk
Tags: recruiting, job board, applicant tracking system, careers, jobs
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional job posting and applicant management for SMBs and recruiters – directly integrated into WordPress with unlimited job listings.

== Description ==

**Recruiting Playbook** is the native WordPress solution for small to medium-sized businesses and recruiters who want to professionalize their hiring process directly on their own website without expensive third-party software.

Unlike other solutions, Recruiting Playbook offers unlimited job listings even in the free version. Instead of losing applications in cluttered email inboxes or Excel spreadsheets, this plugin provides a structured workflow from the first posting to the final hire.

**Core Features (Free Version):**

* **Unlimited Job Listings:** Create as many job postings as you need using a custom post type.
* **Google for Jobs Integration:** Automatic JSON-LD schema generation for maximum visibility in search results.
* **Modern Application Form:** A mobile-optimized frontend form with secure file upload for resumes.
* **Secure Document Management:** All uploads are stored in a protected directory, blocked from direct URL access.
* **Built-in Spam Protection:** Protection via honeypot fields, encrypted tokens, and rate limiting.
* **GDPR Ready:** Includes consent tracking and candidate data anonymization features.

**Why Recruiting Playbook?**

Over 40% of all websites use WordPress. Many companies face the choice between "Excel chaos" and expensive SaaS solutions. Recruiting Playbook bridges this gap directly in your familiar environment – performant, secure, and extensible.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory or install the plugin directly through the WordPress admin.
2. Activate the plugin.
3. Follow the integrated **Setup Wizard** to configure your company name and notification email.
4. Create your first job listing under "Recruiting" -> "Jobs".

== Frequently Asked Questions ==

= Is there a limit on the number of job listings? =
No. In the current version, you can publish unlimited active job listings simultaneously.

= Will my jobs appear on Google for Jobs? =
Yes, the plugin automatically creates the necessary Schema.org markup. Once Google indexes your page, your jobs can appear in the specialized job search results.

= How secure is applicant data? =
Security is a priority. Documents are stored in a protected folder. The plugin also provides tools for GDPR-compliant deletion and anonymization.

== Upgrade Path: Pro & AI ==

Recruiting Playbook grows with your requirements.

**Recruiting Playbook Pro:**
Professionalize your recruiting with an interactive Kanban board for applicant tracking, customizable email templates for rejections or invitations, and a full REST API for third-party systems.

**AI Addon (Coming Soon):**
Use cutting-edge AI for automatic job matching. Applicants receive an instant match score and valuable tips about their hiring chances based on their resume.

== Screenshots ==

1. **Job Overview** – All open positions at a glance with location, employment type, and remote option.
2. **Job Detail Page** – Professional presentation with Google for Jobs schema and integrated application form.
3. **Application Form** – Multi-step, mobile-optimized form with file upload and progress indicator.
4. **Admin Dashboard** – Clear management of all applications with status tracking.
5. **Setup Wizard** – Simple initial configuration in just a few steps.

== Changelog ==

= 1.0.0 =
* Initial MVP release.
* Unlimited job listings and application workflow.
* Google for Jobs integration (Schema.org JSON-LD).
* Multi-step application form with Alpine.js.
* Secure document management with protected upload directory.
* Built-in spam protection (honeypot, rate limiting, timestamp validation).
* GDPR-compliant data processing with anonymization feature.
* Setup wizard for easy initial configuration.
* Responsive design with Tailwind CSS.
* Shortcodes for flexible integration: [rp_jobs], [rp_job_search], [rp_application_form].
