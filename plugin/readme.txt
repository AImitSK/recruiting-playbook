=== Recruiting Playbook ===
Contributors: stefankuehne
Tags: recruiting, jobs, stellenanzeigen, bewerbermanagement, ats
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.2.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professionelles Bewerbermanagement für WordPress. Stellenanzeigen erstellen, Bewerbungen verwalten, DSGVO-konform.

== Description ==

**Recruiting Playbook** ist ein professionelles Applicant Tracking System (ATS) für WordPress. Verwalten Sie Stellenanzeigen und Bewerbungen direkt in Ihrem WordPress-Dashboard.

### Features (Free)

* ✅ **Unbegrenzte Stellenanzeigen** - Keine Limits
* ✅ **Mehrstufiges Bewerbungsformular** - CV-Upload, Anschreiben, DSGVO-Checkboxen
* ✅ **Bewerbungsverwaltung** - Übersichtliche Tabelle mit Filterung & Sortierung
* ✅ **Status-Workflow** - New → Screening → Interview → Offer → Hired
* ✅ **E-Mail-Benachrichtigungen** - Automatisch bei neuen Bewerbungen
* ✅ **DSGVO-konform** - Datenschutz-Checkboxen, Soft-Deletes, Anonymisierung
* ✅ **Responsive Design** - Funktioniert auf allen Geräten
* ✅ **Page Builder Support** - Gutenberg Blocks, Shortcodes

### Premium Features (Pro)

* 🎯 **Kanban-Board** - Drag & Drop Bewerbermanagement
* 🎯 **KI-Lebenslauf-Matching** - Automatische Bewertung von Bewerbungen
* 🎯 **Erweiterte E-Mail-Templates** - Vorlagen mit Platzhaltern
* 🎯 **Talent Pool** - Kandidaten für zukünftige Stellen speichern
* 🎯 **Notizen & Ratings** - Interne Kandidaten-Bewertungen
* 🎯 **Reporting & Analytics** - Conversion-Rate, Time-to-Hire
* 🎯 **Custom Fields** - Eigene Formularfelder
* 🎯 **API & Webhooks** - Integration mit externen Tools
* 🎯 **Avada & Elementor Integration** - Premium Page Builder Elemente

[Mehr erfahren →](https://recruiting-playbook.com/)

== Installation ==

1. Plugin installieren und aktivieren
2. Setup-Wizard durchlaufen
3. Erste Stellenanzeige erstellen
4. Fertig! 🎉

== Frequently Asked Questions ==

= Ist das Plugin DSGVO-konform? =

Ja, das Plugin enthält alle erforderlichen Datenschutz-Features.

= Kann ich das Design anpassen? =

Ja, über Design & Branding Einstellungen.

= Wie viele Stellenanzeigen kann ich erstellen? =

Unbegrenzt! Sowohl in Free als auch Pro.

== Screenshots ==

1. Job-Übersicht im Frontend
2. Bewerbungsformular mit DSGVO-Checkboxen
3. Admin-Dashboard - Bewerbungsübersicht
4. Kanban-Board (Pro)
5. Design & Branding Einstellungen

== Changelog ==

= 1.2.12 - 2026-02-19 =
* Fix: JS-Pfade korrigiert - laden jetzt von assets/dist/ statt assets/src/
* Fix: match-modal.js und job-finder.js werden korrekt eingebunden

= 1.2.11 - 2026-02-19 =
* Fix: KI-Matching JavaScript (match-modal.js, job-finder.js) jetzt korrekt im Build enthalten
* Fix: Bewerbungsformular wird korrekt geladen

= 1.2.10 - 2026-02-19 =
* Fix: Notification Service Array-Zugriff korrigiert
* Fix: ActivityService.log() Parameter korrigiert
* Fix: Dokument-Download MIME-Type Erkennung

= 1.2.9 - 2026-02-19 =
* Fix: TeamsNotifier/SlackNotifier findById() zu get() geaendert
* Fix: KI-Button Navigation korrigiert

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
