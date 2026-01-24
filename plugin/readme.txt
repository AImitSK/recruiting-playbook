=== Recruiting Playbook ===
Contributors: aimitsk
Tags: recruiting, job board, applicant tracking system, careers, jobs
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professionelles Stellenausschreibungs- und Bewerbermanagement für KMUs und Personaldienstleister direkt in WordPress integriert – jetzt mit unbegrenzten Stellenanzeigen.

== Description ==

Das **Recruiting Playbook** ist die native WordPress-Lösung für kleine bis mittlere Unternehmen und Personaldienstleister, die ihren Bewerbungsprozess ohne teure Drittsoftware direkt auf der eigenen Website professionalisieren möchten.

Im Gegensatz zu anderen Lösungen bietet das Recruiting Playbook bereits in der kostenlosen Version die Möglichkeit, unbegrenzt viele Stellenanzeigen zu schalten und zu verwalten. Anstatt Bewerbungen in unübersichtlichen E-Mail-Postfächern oder Excel-Listen zu verlieren, bietet dieses Plugin einen strukturierten Workflow von der ersten Anzeige bis zur Einstellung.

**Kernfunktionen der Free-Version:**
* **Unbegrenzte Stellenanzeigen:** Erstellen Sie so viele Jobangebote als Custom Post Type, wie Sie benötigen.
* **Google for Jobs Integration:** Automatische Generierung des erforderlichen JSON-LD Schemas für maximale Sichtbarkeit in den Suchergebnissen.
* **Modernes Bewerbungsformular:** Ein für mobile Geräte optimiertes Frontend-Formular inklusive sicherem Dateiupload für Lebensläufe.
* **Sichere Dokumentenverwaltung:** Alle Uploads werden in einem geschützten Verzeichnis gespeichert, das vor direktem URL-Zugriff gesperrt ist.
* **Integrierter Spam-Schutz:** Schutz durch Honeypot-Felder, verschlüsselte Tokens und Rate-Limiting.
* **DSGVO-Bereit:** Inklusive Consent-Tracking und Funktionen zur Anonymisierung von Kandidatendaten.

**Warum Recruiting Playbook?**
Über 40% aller Websites nutzen WordPress. Viele Unternehmen stehen vor der Wahl zwischen "Excel-Chaos" und teuren SaaS-Lösungen. Das Recruiting Playbook schließt diese Lücke direkt in Ihrer gewohnten Umgebung – performant, sicher und erweiterbar.

== Installation ==

1. Laden Sie den Plugin-Ordner in das Verzeichnis `/wp-content/plugins/` hoch oder installieren Sie das Plugin direkt über das WordPress-Backend.
2. Aktivieren Sie das Plugin.
3. Folgen Sie dem integrierten **Setup-Wizard**, um Ihren Firmennamen und die Benachrichtigungs-E-Mail einzurichten.
4. Erstellen Sie Ihre erste Stelle unter "Recruiting" -> "Stellen".

== Frequently Asked Questions ==

= Gibt es ein Limit für die Anzahl der Stellenanzeigen? =
Nein. In der aktuellen Version können Sie unbegrenzt viele aktive Stellenanzeigen gleichzeitig schalten.

= Werden meine Stellen bei Google for Jobs angezeigt? =
Ja, das Plugin erstellt automatisch das notwendige Schema.org Markup. Sobald Google Ihre Seite indexiert, können die Stellen in den speziellen Job-Suchergebnissen erscheinen.

= Wie sicher sind die Bewerberdaten? =
Sicherheit hat Priorität. Dokumente liegen in einem geschützten Ordner. Zudem bietet das Plugin Werkzeuge zur DSGVO-konformen Löschung und Anonymisierung.

== Upgrade Path: Pro & AI ==

Das Recruiting Playbook wächst mit Ihren Anforderungen.

**Recruiting Playbook Pro:**
Professionalisieren Sie Ihr Recruiting mit einem interaktiven Kanban-Board für das Bewerber-Tracking, anpassbaren E-Mail-Templates für Absagen oder Einladungen und einer vollständigen REST API für Drittsysteme.

**AI-Addon (Demnächst):**
Nutzen Sie modernste KI für automatisches Job-Matching. Bewerber erhalten sofort einen Match-Score und wertvolle Tipps zu ihren Einstellungschancen basierend auf ihrem Lebenslauf.

== Screenshots ==

1. **Stellenübersicht** – Alle offenen Positionen auf einen Blick mit Standort, Beschäftigungsart und Remote-Option.
2. **Stellendetailseite** – Professionelle Darstellung mit Google for Jobs Schema und integriertem Bewerbungsformular.
3. **Bewerbungsformular** – Mehrstufiges, mobil-optimiertes Formular mit Datei-Upload und Fortschrittsanzeige.
4. **Admin Dashboard** – Übersichtliche Verwaltung aller Bewerbungen mit Status-Tracking.
5. **Setup-Wizard** – Einfache Erstkonfiguration in wenigen Schritten.

== Changelog ==

= 1.0.0 =
* Initialer Release der MVP-Version.
* Unbegrenzte Stellenanzeigen und Bewerbungs-Workflow.
* Google for Jobs Integration (Schema.org JSON-LD).
* Mehrstufiges Bewerbungsformular mit Alpine.js.
* Sichere Dokumentenverwaltung mit geschütztem Upload-Verzeichnis.
* Integrierter Spam-Schutz (Honeypot, Rate-Limiting, Timestamp-Validierung).
* DSGVO-konforme Datenverarbeitung mit Anonymisierungsfunktion.
* Setup-Wizard für einfache Erstkonfiguration.
* Responsive Design mit Tailwind CSS.
* Shortcodes für flexible Einbindung: [rp_jobs], [rp_job_search], [rp_application_form].