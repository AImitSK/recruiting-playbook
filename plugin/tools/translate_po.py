#!/usr/bin/env python3
"""
Translate untranslated PO entries for recruiting-playbook.
Run from plugin/languages/ directory:
    python3 ../tools/translate_po.py <po_file> [<json_dict>]

If <json_dict> is provided, translations are loaded from JSON instead of built-in German dict.
JSON format: {"translations": {...}, "plural_translations": {...}, "force_translations": {...}}
"""
import json
import re
import sys
import os

# ============================================================
# TRANSLATION DICTIONARY: English source → German translation
# ============================================================
TRANSLATIONS = {
    # --- Status Labels ---
    "New": "Neu",
    "Screening": "Vorauswahl",
    "Interview": "Vorstellungsgespräch",
    "Offer": "Angebot",
    "Hired": "Eingestellt",
    "Rejected": "Abgelehnt",
    "Withdrawn": "Zurückgezogen",

    # --- Applications Page ---
    "Applications": "Bewerbungen",
    "All": "Alle",
    "Search by name or email\u2026": "Nach Name oder E-Mail suchen\u2026",
    "All jobs": "Alle Stellen",
    "Export": "Exportieren",
    "View": "Ansehen",
    "Review": "Prüfen",
    "No applications": "Keine Bewerbungen",
    "No applications match the current filter.": "Keine Bewerbungen entsprechen dem aktuellen Filter.",
    "application": "Bewerbung",
    "applications": "Bewerbungen",

    # --- Applicant Detail ---
    "Overview": "Übersicht",
    "Documents": "Dokumente",
    "Notes": "Notizen",
    "Timeline": "Zeitverlauf",
    "Rating": "Bewertung",
    "Emails": "E-Mails",
    "Error loading application": "Fehler beim Laden der Bewerbung",
    "Application not found.": "Bewerbung nicht gefunden.",
    "Back to list": "Zurück zur Liste",
    "Applied on": "Beworben am",
    "Application for": "Bewerbung für",
    "Status": "Status",
    "Cover Letter": "Anschreiben",
    "Contact Information": "Kontaktdaten",
    "Phone": "Telefon",
    "Address": "Adresse",
    "Custom Fields": "Benutzerdefinierte Felder",
    "Additional Information": "Zusätzliche Informationen",
    "No documents available": "Keine Dokumente vorhanden",
    "No notes available yet.": "Noch keine Notizen vorhanden.",
    "Error changing status": "Fehler beim Ändern des Status",
    "Change Status": "Status ändern",
    "Confirm status change?": "Statusänderung bestätigen?",
    "Delete Application": "Bewerbung löschen",
    "Permanently Delete": "Endgültig löschen",
    "Really delete this application permanently?": "Diese Bewerbung wirklich endgültig löschen?",
    "Error deleting application": "Fehler beim Löschen der Bewerbung",
    "Add to Talent Pool": "Zum Talent Pool hinzufügen",
    "In Talent Pool": "Im Talent Pool",
    "Adding...": "Wird hinzugefügt...",
    "Send Email": "E-Mail senden",

    # --- Notes ---
    "Add note": "Notiz hinzufügen",
    "Add first note": "Erste Notiz hinzufügen",
    "Enter note\u2026": "Notiz eingeben\u2026",
    "Enter note...": "Notiz eingeben...",
    "Visible only to me": "Nur für mich sichtbar",
    "Private": "Privat",
    "Cancel": "Abbrechen",
    "Saving\u2026": "Wird gespeichert\u2026",
    "Saving...": "Wird gespeichert...",
    "Save": "Speichern",
    "to save": "zum Speichern",
    "edited": "bearbeitet",
    "Edit": "Bearbeiten",
    "Delete": "Löschen",
    "Enter message...": "Nachricht eingeben...",

    # --- Timeline ---
    "Today": "Heute",
    "Yesterday": "Gestern",
    "Refresh": "Aktualisieren",
    "Load more": "Mehr laden",
    "Loading...": "Wird geladen...",
    "No activities yet": "Noch keine Aktivitäten",
    "Ratings": "Bewertungen",
    "Email sent": "E-Mail gesendet",

    # --- Timeline Item / Status Labels ---
    "changed the status": "hat den Status geändert",
    "Overall": "Gesamt",
    "Professional competence": "Fachkompetenz",
    "Cultural fit": "Kulturelle Passung",
    "Experience": "Erfahrung",

    # --- Rating Stars ---
    "Overall Impression": "Gesamteindruck",
    "Professional Competence": "Fachkompetenz",
    "Cultural Fit": "Kulturelle Passung",
    "Distribution": "Verteilung",
    "out of 5 stars": "von 5 Sternen",

    # --- Relative Time ---
    "Just now": "Gerade eben",

    # --- Talent Pool ---
    "Talent Pool": "Talent Pool",
    "Search candidates\u2026": "Kandidaten suchen\u2026",
    "Search candidates...": "Kandidaten suchen...",
    "All tags": "Alle Tags",
    "All Tags": "Alle Tags",
    "GDPR notice: Candidates are automatically removed from the pool after expiry.": "DSGVO-Hinweis: Kandidaten werden nach Ablauf automatisch aus dem Pool entfernt.",
    "The talent pool is still empty.": "Der Talent Pool ist noch leer.",
    "Add promising candidates from the application detail page to the talent pool.": "Fügen Sie vielversprechende Kandidaten von der Bewerbungsdetailseite zum Talent Pool hinzu.",
    "Go to Applications": "Zu den Bewerbungen",
    "Page": "Seite",
    "of": "von",
    "Previous": "Zurück",
    "Next": "Weiter",
    "Expired": "Abgelaufen",
    "Expires soon": "Läuft bald ab",
    "Error removing from pool.": "Fehler beim Entfernen aus dem Pool.",
    "Error saving.": "Fehler beim Speichern.",
    "Really remove candidate from talent pool?": "Kandidaten wirklich aus dem Talent Pool entfernen?",
    "Really remove candidate from the Talent Pool?": "Kandidaten wirklich aus dem Talent Pool entfernen?",
    "Remove": "Entfernen",
    "Remove from pool": "Aus dem Pool entfernen",
    "Remove from Talent Pool": "Aus dem Talent Pool entfernen",
    "Reason": "Grund",
    "Tags": "Tags",
    "No tags": "Keine Tags",
    "View application": "Bewerbung ansehen",
    "e.g. Very good candidate, but currently no suitable position...": "z.B. Sehr guter Kandidat, aber aktuell keine passende Stelle...",
    "e.g. php, react, senior, remote": "z.B. php, react, senior, remote",
    "The entry will be automatically deleted after 24 months (GDPR).": "Der Eintrag wird nach 24 Monaten automatisch gelöscht (DSGVO).",
    "Tags help to find candidates faster later.": "Tags helfen, Kandidaten später schneller zu finden.",

    # --- Form Builder ---
    "Form": "Formular",
    "Preview": "Vorschau",
    "Publish": "Veröffentlichen",
    "Publishing...": "Wird veröffentlicht...",
    "Discard": "Verwerfen",
    "Do you want to discard all changes?": "Möchten Sie alle Änderungen verwerfen?",
    "Form Builder": "Formular-Builder",
    "No Permission": "Keine Berechtigung",
    "You do not have permission to use the Form Builder.": "Sie haben keine Berechtigung, den Formular-Builder zu nutzen.",
    "This feature requires Pro.": "Diese Funktion erfordert Pro.",
    "Upgrade to Pro": "Auf Pro upgraden",
    "Draft": "Entwurf",
    "Content": "Inhalt",
    "Create new field": "Neues Feld erstellen",
    "Field type": "Feldtyp",
    "Select field type": "Feldtyp auswählen",
    "Field settings": "Feldeinstellungen",
    "Field label": "Feldbezeichnung",
    "e.g. Salutation": "z.B. Anrede",
    "Placeholder text...": "Platzhaltertext...",
    "Help text for the field": "Hilfetext für das Feld",
    "Help text for the field (optional)": "Hilfetext für das Feld (optional)",
    "Full width": "Volle Breite",
    "Half width": "Halbe Breite",
    "Required": "Pflichtfeld",
    "Yes": "Ja",
    "No": "Nein",
    "Options": "Optionen",
    "Add option": "Option hinzufügen",
    "No options yet. Add at least one.": "Noch keine Optionen. Fügen Sie mindestens eine hinzu.",
    "Drag options to sort. The value is saved, the label is displayed.": "Optionen ziehen zum Sortieren. Der Wert wird gespeichert, das Label angezeigt.",
    "Unlimited": "Unbegrenzt",
    "No Limit": "Kein Limit",
    "Regular expression for validation (e.g., ^[0-9]+$ for numbers only)": "Regulärer Ausdruck zur Validierung (z.B. ^[0-9]+$ nur für Zahlen)",
    "Comma-separated list of file extensions": "Kommagetrennte Liste von Dateiendungen",
    "Max. File Size (MB)": "Max. Dateigröße (MB)",
    "Max. Number of Files": "Max. Anzahl Dateien",
    "Custom Error Message": "Eigene Fehlermeldung",
    "Displayed when validation fails": "Wird bei fehlgeschlagener Validierung angezeigt",
    "Unsaved": "Nicht gespeichert",
    "Enter HTML content...": "HTML-Inhalt eingeben...",
    "HTML content will be displayed here...": "HTML-Inhalt wird hier angezeigt...",
    "Notice Text": "Hinweistext",
    "Drag files here or click to select": "Dateien hierher ziehen oder klicken zum Auswählen",
    "Allowed types:": "Erlaubte Typen:",
    "Allowed Types:": "Erlaubte Typen:",
    "Max. size:": "Max. Größe:",
    "Max.": "Max.",
    "Your Information Summary": "Zusammenfassung Ihrer Angaben",
    "System Field": "Systemfeld",
    "First Name:": "Vorname:",
    "(will be displayed)": "(wird angezeigt)",
    "Last Name:": "Nachname:",
    "... all entered fields will be summarized here": "... alle eingegebenen Felder werden hier zusammengefasst",
    "I have read the {datenschutz_link} and agree.": "Ich habe die {datenschutz_link} gelesen und stimme zu.",
    "Privacy Consent": "Datenschutz-Einwilligung",
    "Unknown system field:": "Unbekanntes Systemfeld:",
    "No steps to display": "Keine Schritte vorhanden",
    "Add steps in the Form tab to see them here": "Fügen Sie Schritte im Formular-Tab hinzu, um sie hier zu sehen",
    "Final": "Abschluss",
    "No fields in this step": "Keine Felder in diesem Schritt",
    "This is a preview only. The buttons are disabled.": "Dies ist nur eine Vorschau. Die Buttons sind deaktiviert.",
    "Steps": "Schritte",
    "active fields": "aktive Felder",
    "Upload Documents": "Dokumente hochladen",
    "File Upload Settings": "Datei-Upload-Einstellungen",
    "Configure file upload for applications": "Datei-Upload für Bewerbungen konfigurieren",
    "At least one file type must be selected.": "Mindestens ein Dateityp muss ausgewählt werden.",
    "Allowed: 1-100 MB. Server limit may be lower.": "Erlaubt: 1-100 MB. Serverlimit kann niedriger sein.",
    "files": "Dateien",
    "Help Text": "Hilfetext",
    'e.g. "PDF, Word - max. 10 MB per file"': 'z.B. "PDF, Word - max. 10 MB pro Datei"',
    "Summary Settings": "Zusammenfassungs-Einstellungen",
    "Configure how the summary is displayed": "Konfigurieren Sie die Darstellung der Zusammenfassung",
    "Show header": "Überschrift anzeigen",
    "Displays the label as a heading": "Zeigt das Label als Überschrift an",
    "Show step titles": "Schritt-Titel anzeigen",
    "Groups fields by step": "Gruppiert Felder nach Schritt",
    "Show edit buttons": "Bearbeiten-Buttons anzeigen",
    "Allows editing individual steps": "Ermöglicht das Bearbeiten einzelner Schritte",
    'e.g. "Please review your information before submitting."': 'z.B. "Bitte überprüfen Sie Ihre Angaben vor dem Absenden."',
    "I have read the {privacy_link} and agree to the processing of my data.": "Ich habe die {privacy_link} gelesen und stimme der Verarbeitung meiner Daten zu.",
    "You must agree to the privacy policy.": "Sie müssen der Datenschutzerklärung zustimmen.",
    "Privacy Consent Settings": "Datenschutz-Einstellungs-Einstellungen",
    "Configure the privacy notice": "Datenschutzhinweis konfigurieren",

    # --- Reporting ---
    "Score": "Bewertung",
    "Total conversion": "Gesamt-Conversion",
    "Check now": "Jetzt prüfen",
    "Last check": "Letzte Prüfung",
    "No health check performed yet. Click \"Check now\".": "Noch keine Prüfung durchgeführt. Klicken Sie auf \"Jetzt prüfen\".",
    "Completed": "Abgeschlossen",
    "Activity": "Aktivität",
    "Done": "Fertig",
    "Position": "Position",
    "Reachable": "Erreichbar",
    "Response time": "Antwortzeit",

    # --- Settings ---
    "Settings for job listings and the careers page": "Einstellungen für Stellenanzeigen und die Karriereseite",
    "Company Information": "Unternehmensinformationen",
    "Roles & Permissions": "Rollen & Berechtigungen",
    "User Roles": "Benutzerrollen",
    "Admin": "Administrator",
    "Worker": "Mitarbeiter",
    "Permission": "Berechtigung",
    "Permissions saved.": "Berechtigungen gespeichert.",
    "Integrations": "Integrationen",
    "Save changes": "Änderungen speichern",
    "API": "API",
    "API Keys": "API-Schlüssel",
    "API Key Created": "API-Schlüssel erstellt",
    "API Status": "API-Status",
    "Create Key": "Schlüssel erstellen",
    "Create New API Key": "Neuen API-Schlüssel erstellen",
    "Create New Key": "Neuen Schlüssel erstellen",
    "Create a key to use the REST API.": "Erstellen Sie einen Schlüssel für die REST API.",
    "Create and manage API keys for external access to the REST API.": "API-Schlüssel für externen Zugriff auf die REST API erstellen und verwalten.",
    "Creating...": "Wird erstellt...",
    "Enter a name and select permissions.": "Geben Sie einen Namen ein und wählen Sie Berechtigungen.",
    "Copy the key now! It will only be displayed once and cannot be recovered.": "Kopieren Sie den Schlüssel jetzt! Er wird nur einmal angezeigt und kann nicht wiederhergestellt werden.",
    "Copied!": "Kopiert!",
    "Copy": "Kopieren",
    "Delete API Key?": "API-Schlüssel löschen?",
    "The key will be permanently deleted. All integrations using this key will lose access.": "Der Schlüssel wird endgültig gelöscht. Alle Integrationen, die diesen Schlüssel verwenden, verlieren den Zugriff.",
    "Deleting...": "Wird gelöscht...",
    "No API keys available.": "Keine API-Schlüssel vorhanden.",
    "Key": "Schlüssel",
    "Last Used": "Zuletzt verwendet",
    "Rate Limit (requests/hour)": "Rate Limit (Anfragen/Stunde)",
    "Requests": "Anfragen",
    "e.g. CRM Integration": "z.B. CRM-Integration",
    "Read": "Lesen",
    "Write": "Schreiben",

    # --- AI Analysis ---
    "AI Analysis": "KI-Analyse",
    "AI analyses": "KI-Analysen",
    "analyses": "Analysen",
    "Budget and file settings for AI analysis.": "Budget- und Dateieinstellungen für die KI-Analyse.",
    "Budget limit per month": "Budgetlimit pro Monat",
    "Warning threshold (%)": "Warnschwelle (%)",
    "Allowed file formats": "Erlaubte Dateiformate",
    "Analysis History": "Analysehistorie",
    "No analyses available yet.": "Noch keine Analysen verfügbar.",
    "Next reset:": "Nächster Reset:",
    "Warning: You have already used": "Warnung: Sie haben bereits verbraucht",
    "of your monthly budget.": "Ihres monatlichen Budgets.",
    "License & Usage": "Lizenz & Nutzung",

    # --- AI Features ---
    "AI BUTTON": "KI-BUTTON",
    "AI Button": "KI-Button",
    "AI Button Style": "KI-Button-Stil",
    "AI Matching Button": "KI-Matching-Button",
    "Show AI matching buttons": "KI-Matching-Buttons anzeigen",
    "Enable or disable AI matching features on your website.": "KI-Matching-Funktionen auf Ihrer Webseite aktivieren oder deaktivieren.",
    "When disabled, AI buttons will be hidden in job listings and cards.": "Wenn deaktiviert, werden KI-Buttons in Stellenanzeigen und Karten ausgeblendet.",
    "Start AI Matching": "KI-Matching starten",
    "Start AI matching": "KI-Matching starten",
    "Global style for all AI buttons in the plugin": "Globaler Stil für alle KI-Buttons im Plugin",
    "Text and icon for the AI Matching button": "Text und Symbol für den KI-Matching-Button",
    "Predefined AI Button Designs": "Vordefinierte KI-Button-Designs",
    "Note: The preview only shows the primary color. You will see the actual button design in the frontend.": "Hinweis: Die Vorschau zeigt nur die Primärfarbe. Das tatsächliche Button-Design sehen Sie im Frontend.",

    # --- Design & Branding ---
    "Design": "Design",
    "Design & Branding": "Design & Branding",
    "Design settings could not be loaded.": "Design-Einstellungen konnten nicht geladen werden.",
    "Design settings have been reset.": "Design-Einstellungen wurden zurückgesetzt.",
    "Design settings have been saved.": "Design-Einstellungen wurden gespeichert.",
    "Appearance": "Darstellung",
    "PRIMARY COLOR": "PRIMÄRFARBE",
    "Primary Color": "Primärfarbe",
    "Primary color": "Primärfarbe",
    "Primary color for buttons, links and accents": "Primärfarbe für Buttons, Links und Akzente",
    "Active primary color:": "Aktive Primärfarbe:",
    "Inherit primary color from the active WordPress theme": "Primärfarbe vom aktiven WordPress-Theme übernehmen",
    "Use Primary Color": "Primärfarbe verwenden",
    "Colors": "Farben",
    "Custom Colors": "Eigene Farben",
    "Manual Colors": "Manuelle Farben",
    "Individual Color Settings": "Individuelle Farbeinstellungen",
    "Background": "Hintergrund",
    "Background (Hover)": "Hintergrund (Hover)",
    "Background and text colors": "Hintergrund- und Textfarben",
    "Text Color": "Textfarbe",
    "Text Color (Hover)": "Textfarbe (Hover)",
    "Border Color": "Rahmenfarbe",
    "Border": "Rahmen",
    "Border Radius": "Eckenradius",
    "Border Width": "Rahmenbreite",
    "Corner Radius": "Eckenradius",
    "Shadow": "Schatten",
    "Shadow (Hover)": "Schatten (Hover)",
    "Gradient": "Farbverlauf",
    "Color 1 (Start)": "Farbe 1 (Start)",
    "Color 2 (End)": "Farbe 2 (Ende)",
    "Mix two colors as gradient": "Zwei Farben als Farbverlauf mischen",
    "Badge Colors": "Badge-Farben",
    "Badge Style": "Badge-Stil",
    "Badges": "Badges",
    "Colors for different badge types": "Farben für verschiedene Badge-Typen",

    # --- Design: Typography ---
    "TYPOGRAPHY": "TYPOGRAFIE",
    "Typography": "Typografie",
    "Font Sizes": "Schriftgrößen",
    "Sizes for headings and body text in rem": "Größen für Überschriften und Fließtext in rem",
    "Headings": "Überschriften",
    "Body Text": "Fließtext",
    "Line Height": "Zeilenhöhe",
    "Vertical spacing between lines of text": "Vertikaler Abstand zwischen Textzeilen",
    "Paragraph Spacing": "Absatzabstand",
    "Spacing Above Headings": "Abstand über Überschriften",
    "Spacing Below Headings": "Abstand unter Überschriften",

    # --- Design: Buttons ---
    "BUTTONS": "BUTTONS",
    "Buttons": "Buttons",
    "Custom Button Design": "Eigenes Button-Design",
    "Default: Buttons inherit the complete appearance of the theme": "Standard: Buttons übernehmen das komplette Erscheinungsbild des Themes",
    "Enable for custom settings": "Für eigene Einstellungen aktivieren",
    "Buttons automatically inherit colors, radius, padding and all other styles from your WordPress theme.": "Buttons übernehmen automatisch Farben, Radius, Padding und alle anderen Stile von Ihrem WordPress-Theme.",
    "Shape & Effects": "Form & Effekte",
    "Size, radius, border and shadow": "Größe, Radius, Rahmen und Schatten",
    "Hover Effect": "Hover-Effekt",
    "Glow": "Leuchten",
    "Lift": "Anheben",
    "None": "Keine",

    # --- Design: Layout ---
    "Layout & Display": "Layout & Anzeige",
    "Layout Preset": "Layout-Vorlage",
    "Preset": "Vorlage",
    "Preset Selection": "Vorlagenauswahl",
    "Predefined layouts for job cards": "Vordefinierte Layouts für Stellenkarten",
    "Display": "Anzeige",
    "Display of job listing overview": "Darstellung der Stellenübersicht",
    "Displayed Information": "Angezeigte Informationen",
    "Column Count": "Spaltenanzahl",
    "Grid": "Raster",
    "List View": "Listenansicht",
    "Cards": "Karten",
    "Compact": "Kompakt",
    "Spacious": "Großzügig",
    "Standard": "Standard",
    "Balanced layout": "Ausgewogenes Layout",
    "Plenty of whitespace": "Viel Weißraum",
    "Low padding, space-saving": "Wenig Abstand, platzsparend",
    "JOB CARD": "STELLENKARTE",
    "FORM BOX": "FORMULAR-BOX",
    "Visual properties of job cards and form box": "Visuelle Eigenschaften von Stellenkarten und Formular-Box",
    "Jobs per Page": "Stellen pro Seite",
    "Show Border": "Rahmen anzeigen",
    "Show salary (if available)": "Gehalt anzeigen (falls vorhanden)",

    # --- Design: Links ---
    "Link Color": "Linkfarbe",
    "Links": "Links",
    "Links inherit the primary color": "Links erben die Primärfarbe",
    "Color and style of links": "Farbe und Stil von Links",
    "Underline": "Unterstrichen",
    "On Hover": "Bei Hover",
    "Always": "Immer",

    # --- Design: Logo ---
    "Logo": "Logo",
    "Use theme logo": "Theme-Logo verwenden",
    "Inherit custom logo from WordPress theme": "Logo vom WordPress-Theme übernehmen",
    "Logo for email signatures and document headers": "Logo für E-Mail-Signaturen und Dokumentkopfzeilen",
    "Logo in email signature": "Logo in E-Mail-Signatur",
    "Automatically insert logo in email signatures": "Logo automatisch in E-Mail-Signaturen einfügen",
    "Max. height": "Max. Höhe",

    # --- Design: Spacing ---
    "Spacing": "Abstände",
    "Top": "Oben",
    "Bottom": "Unten",
    "Left": "Links",
    "Spacing in the job listing (em)": "Abstände in der Stellenanzeige (em)",

    # --- Design: Theme ---
    "Theme": "Theme",
    "Theme Design Active": "Theme-Design aktiv",
    "From theme:": "Vom Theme:",
    "Use theme colors": "Theme-Farben verwenden",
    "Predefined": "Vordefiniert",
    "Live Preview": "Live-Vorschau",
    "Changes are displayed immediately": "Änderungen werden sofort angezeigt",
    "Reset": "Zurücksetzen",
    "Reset all design settings to default values?": "Alle Design-Einstellungen auf Standardwerte zurücksetzen?",
    "Discard unsaved changes?": "Nicht gespeicherte Änderungen verwerfen?",

    # --- Design: Misc ---
    "Large": "Groß",
    "Medium": "Mittel",
    "Small": "Klein",
    "Soft": "Weich",
    "Strong": "Stark",
    "Light": "Hell",
    "Light Background": "Heller Hintergrund",
    "Solid": "Solide",
    "Transparent Background": "Transparenter Hintergrund",
    "Transparent background, colored text": "Transparenter Hintergrund, farbiger Text",
    "Colored background, white text": "Farbiger Hintergrund, weißer Text",
    "Subtle and Simple": "Dezent und Einfach",
    "Purple-Pink Gradient": "Lila-Pink Farbverlauf",
    "With Glow Effect": "Mit Leuchteffekt",
    "Icon": "Symbol",
    "Minimal": "Minimal",
    "Manual": "Manuell",

    # --- Design: White-Label ---
    "White-Label": "White-Label",
    "White-label emails": "White-Label-E-Mails",
    "Branding": "Branding",
    "Remove plugin branding": "Plugin-Branding entfernen",
    'Removes "Powered by Recruiting Playbook" from frontend': 'Entfernt "Powered by Recruiting Playbook" aus dem Frontend',
    "Removes plugin branding from email footers": "Entfernt Plugin-Branding aus E-Mail-Fußzeilen",
    'Hide "Powered by"': '"Powered by" ausblenden',

    # --- Email ---
    "E-Mail": "E-Mail",
    "email": "E-Mail",
    "emails": "E-Mails",
    "Email History": "E-Mail-Verlauf",
    "Loading email history...": "E-Mail-Verlauf wird geladen...",
    "No emails available": "Keine E-Mails vorhanden",
    "No emails found.": "Keine E-Mails gefunden.",
    "Compose": "Verfassen",
    "Compose Email": "E-Mail verfassen",
    "Recipient": "Empfänger",
    "Recipient is required": "Empfänger ist erforderlich",
    "Subject is required": "Betreff ist erforderlich",
    "Subject too long": "Betreff zu lang",
    "Content is required": "Inhalt ist erforderlich",
    "Content too long": "Inhalt zu lang",
    "Invalid email address": "Ungültige E-Mail-Adresse",
    "Send": "Senden",
    "Sending...": "Wird gesendet...",
    "Template": "Vorlage",
    "-- Select template --": "-- Vorlage wählen --",
    "Signature": "Signatur",
    "Company Signature (Default)": "Firmensignatur (Standard)",
    "Resend": "Erneut senden",
    "Resend email": "E-Mail erneut senden",
    "Do you want to resend this email?": "Möchten Sie diese E-Mail erneut senden?",
    "Cancel Email": "E-Mail abbrechen",
    "Cancel email": "E-Mail abbrechen",
    "Do you want to cancel this scheduled email?": "Möchten Sie diese geplante E-Mail abbrechen?",
    "Scheduled": "Geplant",

    # --- Email Notifications ---
    "New job published": "Neue Stelle veröffentlicht",
    "Application status changed": "Bewerbungsstatus geändert",
    "Application deadline expiring (3 days before)": "Bewerbungsfrist läuft ab (3 Tage vorher)",
    "Notify on": "Benachrichtigen bei",

    # --- Google / Integrations ---
    "Google Ads Conversion": "Google Ads Conversion",
    "Google Ads Conversion is a Pro feature.": "Google Ads Conversion ist eine Pro-Funktion.",
    "Google for Jobs": "Google for Jobs",
    "Active \u2013 Schema markup is output on all job pages": "Aktiv \u2013 Schema-Markup wird auf allen Stellenseiten ausgegeben",
    "Jobs automatically appear in Google Job Search through structured JSON-LD data.": "Stellen erscheinen automatisch in der Google-Stellensuche durch strukturierte JSON-LD-Daten.",
    "Set application deadline as validThrough": "Bewerbungsfrist als validThrough setzen",
    "Mark remote option": "Remote-Option markieren",
    "Application Deadline": "Bewerbungsfrist",
    "Conversion ID": "Conversion-ID",
    "Conversion Label": "Conversion-Label",
    "Conversion Value (EUR)": "Conversion-Wert (EUR)",
    "Conversion tracking directly with Google Ads \u2013 without Google Tag Manager.": "Conversion-Tracking direkt mit Google Ads \u2013 ohne Google Tag Manager.",
    "Automatically reported to Google Ads as a conversion for every successful application.": "Wird bei jeder erfolgreichen Bewerbung automatisch als Conversion an Google Ads gemeldet.",
    "Copy from Google Ads": "Aus Google Ads kopieren",

    # --- Slack / Teams ---
    "Slack": "Slack",
    "Slack notifications are a Pro feature.": "Slack-Benachrichtigungen sind eine Pro-Funktion.",
    "Notifications for new applications and status changes in a Slack channel.": "Benachrichtigungen über neue Bewerbungen und Statusänderungen in einem Slack-Kanal.",
    "Webhook URL": "Webhook-URL",
    "Send test message": "Testnachricht senden",
    "Microsoft Teams": "Microsoft Teams",
    "Teams notifications are a Pro feature.": "Teams-Benachrichtigungen sind eine Pro-Funktion.",
    "Notifications in a Microsoft Teams channel.": "Benachrichtigungen in einem Microsoft-Teams-Kanal.",
    'Teams \u2192 Channel \u2192 ... \u2192 Workflows \u2192 "When a Teams webhook request is received"': 'Teams \u2192 Kanal \u2192 ... \u2192 Workflows \u2192 "Wenn eine Teams-Webhook-Anfrage empfangen wird"',
    "Workflow Webhook URL": "Workflow-Webhook-URL",

    # --- XML Feed ---
    "XML Job Feed": "XML-Stellenfeed",
    "Feed URL": "Feed-URL",
    "Feed Options": "Feed-Optionen",
    "Max. jobs in feed:": "Max. Stellen im Feed:",
    "0 = no limit": "0 = kein Limit",
    "Show salary in feed": "Gehalt im Feed anzeigen",
    "Description as HTML (instead of plain text)": "Beschreibung als HTML (statt reinem Text)",
    "Universal feed for job boards like Jooble, Talent.com and more.": "Universeller Feed für Stellenbörsen wie Jooble, Talent.com und weitere.",

    # --- Job Assignments ---
    "Job Assignment": "Stellenzuweisung",
    "Job assignments": "Stellenzuweisungen",
    "Assigned Jobs": "Zugewiesene Stellen",
    "Available Jobs": "Verfügbare Stellen",
    "Assigned on": "Zugewiesen am",
    "Assign": "Zuweisen",
    "Assign All": "Alle zuweisen",
    "All jobs are already assigned.": "Alle Stellen sind bereits zugewiesen.",
    "No jobs assigned.": "Keine Stellen zugewiesen.",
    "Error during bulk assignment": "Fehler bei der Massenzuweisung",
    "Select User": "Benutzer auswählen",
    "\u2014 Select User \u2014": "\u2014 Benutzer wählen \u2014",
    "No job": "Keine Stelle",
    "All types": "Alle Typen",
    "Search applicants...": "Bewerber suchen...",

    # --- Export / Import ---
    "CSV with applicant data": "CSV mit Bewerberdaten",
    "Export all plugin data as JSON file": "Alle Plugin-Daten als JSON-Datei exportieren",
    "Download started.": "Download gestartet.",
    "Import Backup": "Backup importieren",
    "Start Import": "Import starten",
    "Import Result": "Import-Ergebnis",
    "Import email log": "E-Mail-Log importieren",
    "It is recommended to create a backup before importing.": "Es wird empfohlen, vor dem Import ein Backup zu erstellen.",
    "Skip (keep current)": "Überspringen (aktuelle behalten)",
    "Merge (add missing)": "Zusammenführen (fehlende hinzufügen)",

    # --- Kanban ---
    "Kanban board for applications": "Kanban-Board für Bewerbungen",
    "Move cancelled.": "Verschiebung abgebrochen.",

    # --- Pagination ---
    "First page": "Erste Seite",
    "Last page": "Letzte Seite",
    "Next page": "Nächste Seite",
    "Previous page": "Vorherige Seite",

    # --- Error Messages (Hooks) ---
    "Error loading notes": "Fehler beim Laden der Notizen",
    "Error creating note": "Fehler beim Erstellen der Notiz",
    "Error updating note": "Fehler beim Aktualisieren der Notiz",
    "Error deleting note": "Fehler beim Löschen der Notiz",
    "Error loading timeline": "Fehler beim Laden des Zeitverlaufs",
    "Error loading fields": "Fehler beim Laden der Felder",
    "Error loading statistics": "Fehler beim Laden der Statistiken",
    "Error loading data": "Fehler beim Laden der Daten",
    "Error loading system status": "Fehler beim Laden des Systemstatus",
    "Error loading conversion data": "Fehler beim Laden der Conversion-Daten",
    "Error loading time-to-hire data": "Fehler beim Laden der Time-to-Hire-Daten",
    "Error loading settings": "Fehler beim Laden der Einstellungen",
    "Error saving settings": "Fehler beim Speichern der Einstellungen",
    "Error loading roles": "Fehler beim Laden der Rollen",
    "Error saving roles": "Fehler beim Speichern der Rollen",
    "Error loading integrations": "Fehler beim Laden der Integrationen",
    "Error saving integrations": "Fehler beim Speichern der Integrationen",
    "Error loading design settings": "Fehler beim Laden der Design-Einstellungen",
    "Error saving design settings": "Fehler beim Speichern der Design-Einstellungen",
    "Error resetting design settings": "Fehler beim Zurücksetzen der Design-Einstellungen",
    "Error loading API keys": "Fehler beim Laden der API-Schlüssel",
    "Error creating API key": "Fehler beim Erstellen des API-Schlüssels",
    "Error deleting API key": "Fehler beim Löschen des API-Schlüssels",
    "Error loading API key usage": "Fehler beim Laden der API-Schlüssel-Nutzung",
    "Error loading form configuration": "Fehler beim Laden der Formularkonfiguration",
    "Error saving form configuration": "Fehler beim Speichern der Formularkonfiguration",
    "Error publishing form configuration": "Fehler beim Veröffentlichen der Formularkonfiguration",
    "Error discarding draft": "Fehler beim Verwerfen des Entwurfs",
    "Error resetting to default": "Fehler beim Zurücksetzen auf Standard",
    "Error loading AI analysis settings": "Fehler beim Laden der KI-Analyse-Einstellungen",
    "Error saving AI analysis settings": "Fehler beim Speichern der KI-Analyse-Einstellungen",
    "Error loading AI analysis history": "Fehler beim Laden der KI-Analyse-Historie",

    # --- Misc ---
    "Remote": "Remote",
    "Message": "Nachricht",
    "Add": "Hinzufügen",
    "Apply": "Übernehmen",
    "Submit": "Absenden",
    "Your Responsibilities": "Ihre Aufgaben",
    "Optional Fields": "Optionale Felder",
    "Zip Code": "Postleitzahl",
    "URL path for the job listing overview.": "URL-Pfad für die Stellenübersicht.",
    "Upgrade to Pro to unlock this feature. You can compare plans and pricing on the upgrade page.": "Upgraden Sie auf Pro, um diese Funktion freizuschalten. Auf der Upgrade-Seite können Sie Pläne und Preise vergleichen.",
    "Job-Finder": "Stellenfinder",
    "Job-Match": "Job-Match",
    "Job List": "Stellenliste",
    "Jobs (including metadata)": "Stellen (inkl. Metadaten)",
    "Taxonomies": "Taxonomien",
}

# ============================================================
# Plural translations: (msgid, msgid_plural) → (singular, plural)
# ============================================================
PLURAL_TRANSLATIONS = {
    ("%d minute ago", "%d minutes ago"): ("%d Minute her", "vor %d Minuten"),
    ("%d hour ago", "%d hours ago"): ("%d Stunde her", "vor %d Stunden"),
    ("%d day ago", "%d days ago"): ("%d Tag her", "vor %d Tagen"),
    ("%d rating", "%d ratings"): ("%d Bewertung", "%d Bewertungen"),
    ("%d candidate", "%d candidates"): ("%d Kandidat", "%d Kandidaten"),
}

# ============================================================
# Force-update translations: entries where msgstr is wrong (not empty)
# Format: msgid → (wrong_msgstr, correct_msgstr)
# ============================================================
FORCE_TRANSLATIONS = {
    "Screening": ("Screening", "Vorauswahl"),
    "Interview": ("Interview", "Vorstellungsgespräch"),
}


def translate_po_file(filepath, translations, plural_translations, force_translations):
    """Translate PO file using binary find-and-replace. Preserves all bytes except msgstr values."""
    with open(filepath, 'rb') as f:
        content = f.read()

    translated_count = 0

    # Try both line endings (file may have mixed CRLF/LF after msgmerge on Linux)
    eols = [b'\r\n', b'\n'] if b'\r\n' in content else [b'\n']

    # Singular translations: replace msgid "X"\nmsgstr "" with msgid "X"\nmsgstr "translated"
    for english, translated in translations.items():
        eng_bytes = english.encode('utf-8')
        trans_bytes = translated.encode('utf-8')

        for eol in eols:
            old = b'msgid "' + eng_bytes + b'"' + eol + b'msgstr ""'
            new = b'msgid "' + eng_bytes + b'"' + eol + b'msgstr "' + trans_bytes + b'"'

            if old in content:
                content = content.replace(old, new)
                translated_count += 1
                break

    # Plural translations
    for (eng_sing, eng_plur), (trans_sing, trans_plur) in plural_translations.items():
        es = eng_sing.encode('utf-8')
        ep = eng_plur.encode('utf-8')
        ts = trans_sing.encode('utf-8')
        tp = trans_plur.encode('utf-8')

        for eol in eols:
            old = (b'msgid "' + es + b'"' + eol +
                   b'msgid_plural "' + ep + b'"' + eol +
                   b'msgstr[0] ""' + eol +
                   b'msgstr[1] ""')
            new = (b'msgid "' + es + b'"' + eol +
                   b'msgid_plural "' + ep + b'"' + eol +
                   b'msgstr[0] "' + ts + b'"' + eol +
                   b'msgstr[1] "' + tp + b'"')

            if old in content:
                content = content.replace(old, new)
                translated_count += 1
                break

    # Force-update entries with wrong existing translations
    for english, (wrong, correct) in force_translations.items():
        eng_bytes = english.encode('utf-8')
        wrong_bytes = wrong.encode('utf-8')
        correct_bytes = correct.encode('utf-8')

        for eol in eols:
            old = b'msgid "' + eng_bytes + b'"' + eol + b'msgstr "' + wrong_bytes + b'"'
            new = b'msgid "' + eng_bytes + b'"' + eol + b'msgstr "' + correct_bytes + b'"'

            if old in content:
                content = content.replace(old, new)
                translated_count += 1
                break

    with open(filepath, 'wb') as f:
        f.write(content)

    return translated_count


def load_json_dict(json_path):
    """Load translations from a JSON dictionary file."""
    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    translations = data.get('translations', {})

    # Convert plural_translations from JSON format to tuple-keyed dict
    plural_translations = {}
    for key, val in data.get('plural_translations', {}).items():
        parts = key.split('|')
        if len(parts) == 2 and len(val) == 2:
            plural_translations[(parts[0], parts[1])] = (val[0], val[1])

    # Convert force_translations from JSON format
    force_translations = {}
    for key, val in data.get('force_translations', {}).items():
        if len(val) == 2:
            force_translations[key] = (val[0], val[1])

    return translations, plural_translations, force_translations


def main():
    po_file = sys.argv[1] if len(sys.argv) > 1 else 'recruiting-playbook-de_DE.po'
    json_dict = sys.argv[2] if len(sys.argv) > 2 else None

    if json_dict:
        translations, plural_translations, force_translations = load_json_dict(json_dict)
    else:
        translations = TRANSLATIONS
        plural_translations = PLURAL_TRANSLATIONS
        force_translations = FORCE_TRANSLATIONS

    print(f"Translating {po_file} ({len(translations)} entries)...")
    count = translate_po_file(po_file, translations, plural_translations, force_translations)
    print(f"Translated {count} entries in {po_file}")


if __name__ == '__main__':
    main()
