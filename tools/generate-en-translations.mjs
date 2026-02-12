#!/usr/bin/env node
/**
 * Generiert englische Übersetzungen aus deutschen Strings
 *
 * Usage: node tools/generate-en-translations.mjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import gettextParser from 'gettext-parser';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const LANGUAGES_DIR = path.join(__dirname, '../plugin/languages');

// Prüft ob ein String bereits englisch ist
function isEnglishString(str) {
    // URLs
    if (/^https?:\/\//.test(str)) return true;

    // Deutsche Indikatoren prüfen
    const germanIndicators = [
        'ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü',
        ' für ', ' und ', ' oder ', ' mit ', ' bei ', ' von ', ' zu ', ' an ', ' auf ', ' aus ',
        ' ist ', ' sind ', ' wird ', ' werden ', ' wurde ', ' können ', ' müssen ', ' sollen ',
        ' nicht ', ' keine ', ' kein ', ' einen ', ' einer ', ' einem ', ' eines ',
        ' dieser ', ' diese ', ' dieses ', ' jetzt ', ' hier ', ' dort ',
        'Bitte ', 'Vielen ', 'Danke', 'Fehler', 'erfolgreich', 'gespeichert',
        'gelöscht', 'aktualisiert', 'hinzugefügt', 'entfernt', 'geändert',
        'Stelle ', 'Stellen', 'Bewerbung', 'Formular', 'Einstellung',
    ];
    for (const indicator of germanIndicators) {
        if (str.includes(indicator)) return false;
    }

    // Wenn es keine deutschen Umlaute enthält und keine deutschen Wörter, ist es wahrscheinlich englisch
    if (!/[äöüßÄÖÜ]/.test(str)) {
        return true;
    }

    return false;
}

// Deutsche → Englische Übersetzungen
const translations = {
    // Plugin Meta
    "Professionelles Bewerbermanagement für WordPress": "Professional applicant management for WordPress",

    // Menu & Navigation
    "Recruiting": "Recruiting",
    "Dashboard": "Dashboard",
    "Bewerbungen": "Applications",
    "Einstellungen": "Settings",
    "Werkzeuge": "Tools",
    "Übersicht": "Overview",
    "Berichte": "Reports",
    "Reporting": "Reporting",
    "Talent-Pool": "Talent Pool",
    "Kanban-Board": "Kanban Board",
    "Formular-Editor": "Form Editor",
    "E-Mail-Vorlagen": "Email Templates",
    "API-Schlüssel": "API Keys",
    "Webhooks": "Webhooks",
    "Integrationen": "Integrations",
    "Design & Branding": "Design & Branding",
    "Benutzerrollen": "User Roles",
    "System-Status": "System Status",
    "Hilfe": "Help",
    "Upgrade auf Pro": "Upgrade to Pro",

    // Job Listings
    "Stellen": "Jobs",
    "Stelle": "Job",
    "Stellenanzeige": "Job Listing",
    "Stellenanzeigen": "Job Listings",
    "Neue Stelle": "New Job",
    "Stelle hinzufügen": "Add Job",
    "Stelle bearbeiten": "Edit Job",
    "Stelle ansehen": "View Job",
    "Stellen durchsuchen": "Browse Jobs",
    "Keine Stellen gefunden": "No jobs found",
    "Alle Stellen": "All Jobs",
    "Offene Stellen": "Open Positions",
    "Aktuelle Stellenangebote": "Current Job Openings",
    "Stellentitel": "Job Title",
    "Stellenbeschreibung": "Job Description",

    // Taxonomies
    "Berufsfelder": "Job Categories",
    "Berufsfeld": "Job Category",
    "Standorte": "Locations",
    "Standort": "Location",
    "Beschäftigungsarten": "Employment Types",
    "Beschäftigungsart": "Employment Type",
    "Alle Berufsfelder": "All Categories",
    "Alle Standorte": "All Locations",
    "Alle Arten": "All Types",

    // Employment Types
    "Vollzeit": "Full-time",
    "Teilzeit": "Part-time",
    "Minijob": "Mini Job",
    "Ausbildung": "Apprenticeship",
    "Praktikum": "Internship",
    "Werkstudent": "Working Student",
    "Freiberuflich": "Freelance",

    // Application Status
    "Neu": "New",
    "In Prüfung": "Under Review",
    "Screening": "Screening",
    "Interview": "Interview",
    "Angebot": "Offer",
    "Eingestellt": "Hired",
    "Abgelehnt": "Rejected",
    "Zurückgezogen": "Withdrawn",
    "Archiviert": "Archived",

    // Job Details
    "Stellen-Details": "Job Details",
    "Gehalt": "Salary",
    "Gehalt (Minimum)": "Salary (Minimum)",
    "Gehalt (Maximum)": "Salary (Maximum)",
    "Währung": "Currency",
    "Gehaltszeitraum": "Salary Period",
    "Gehalt verstecken": "Hide Salary",
    "Bewerbungsfrist": "Application Deadline",
    "Ansprechpartner": "Contact Person",
    "E-Mail": "Email",
    "E-Mail-Adresse": "Email Address",
    "Telefon": "Phone",
    "Remote-Option": "Remote Option",
    "Vor Ort": "On-site",
    "Hybrid": "Hybrid",
    "Remote": "Remote",
    "100% Remote möglich": "100% Remote possible",
    "Hybrid (teilweise Remote)": "Hybrid (partially remote)",
    "Startdatum": "Start Date",
    "Sofort": "Immediately",
    "Nach Vereinbarung": "By arrangement",

    // Salary Periods
    "pro Stunde": "per hour",
    "pro Monat": "per month",
    "pro Jahr": "per year",
    "/Std.": "/hr",
    "/Monat": "/month",
    "/Jahr": "/year",
    "Ab ": "From ",
    "Bis ": "Up to ",

    // Application Form
    "Jetzt bewerben": "Apply Now",
    "Bewerbung absenden": "Submit Application",
    "Bewerbung für: %s": "Application for: %s",
    "Persönliche Daten": "Personal Information",
    "Anrede": "Salutation",
    "Bitte wählen": "Please select",
    "Herr": "Mr.",
    "Frau": "Ms.",
    "Divers": "Diverse",
    "Vorname": "First Name",
    "Nachname": "Last Name",
    "Name": "Name",
    "Straße": "Street",
    "PLZ": "Postal Code",
    "Stadt": "City",
    "Land": "Country",
    "Geburtsdatum": "Date of Birth",
    "Bewerbungsunterlagen": "Application Documents",
    "Lebenslauf": "Resume",
    "Anschreiben": "Cover Letter",
    "Anschreiben / Nachricht": "Cover Letter / Message",
    "Zeugnisse": "Certificates",
    "Sonstige Dokumente": "Other Documents",
    "Datei hierher ziehen oder": "Drag file here or",
    "Datei auswählen": "Choose File",
    "Dateien auswählen": "Choose Files",
    "PDF, DOC, DOCX, JPG, PNG (max. 10 MB)": "PDF, DOC, DOCX, JPG, PNG (max. 10 MB)",
    "Datenschutz & Absenden": "Privacy & Submit",
    "Datenschutzerklärung": "Privacy Policy",
    "Ich habe die %s gelesen und akzeptiere diese.": "I have read and accept the %s.",
    "Bewerbung erfolgreich gesendet!": "Application submitted successfully!",
    "Vielen Dank für Ihre Bewerbung. Sie erhalten in Kürze eine Bestätigung per E-Mail.": "Thank you for your application. You will receive a confirmation email shortly.",
    "Wird gesendet...": "Sending...",
    "Absenden": "Submit",

    // Search & Filter
    "Suche": "Search",
    "Suchen": "Search",
    "Stichwort, Jobtitel...": "Keyword, job title...",
    "Filter": "Filter",
    "Filter zurücksetzen": "Reset Filters",
    "Keine passenden Stellen gefunden. Bitte versuchen Sie andere Suchkriterien.": "No matching jobs found. Please try different search criteria.",
    "Ergebnisse": "Results",
    "Sortieren nach": "Sort by",
    "Neueste zuerst": "Newest first",
    "Älteste zuerst": "Oldest first",
    "Alphabetisch": "Alphabetically",

    // Pagination
    "Zurück": "Back",
    "Weiter": "Next",
    "Seite": "Page",
    "von": "of",
    "Vorherige Seite": "Previous Page",
    "Nächste Seite": "Next Page",

    // Buttons & Actions
    "Speichern": "Save",
    "Speichern...": "Saving...",
    "Gespeichert!": "Saved!",
    "Änderungen speichern": "Save Changes",
    "Abbrechen": "Cancel",
    "Löschen": "Delete",
    "Bearbeiten": "Edit",
    "Hinzufügen": "Add",
    "Erstellen": "Create",
    "Aktualisieren": "Update",
    "Schließen": "Close",
    "Bestätigen": "Confirm",
    "Kopieren": "Copy",
    "Herunterladen": "Download",
    "Exportieren": "Export",
    "Importieren": "Import",
    "Vorschau": "Preview",
    "Anzeigen": "View",
    "Ausblenden": "Hide",
    "Mehr erfahren": "Learn More",
    "Details anzeigen": "Show Details",
    "Alle anzeigen": "View All",

    // Messages & Notifications
    "Erfolg": "Success",
    "Fehler": "Error",
    "Warnung": "Warning",
    "Info": "Info",
    "Ein Fehler ist aufgetreten.": "An error occurred.",
    "Bitte versuchen Sie es erneut.": "Please try again.",
    "Änderungen wurden gespeichert.": "Changes have been saved.",
    "Aktion erfolgreich.": "Action successful.",
    "Sind Sie sicher?": "Are you sure?",
    "Diese Aktion kann nicht rückgängig gemacht werden.": "This action cannot be undone.",

    // Setup Wizard
    "Willkommen": "Welcome",
    "Firmendaten": "Company Data",
    "Erste Stelle": "First Job",
    "Fertig": "Done",
    "Setup-Wizard": "Setup Wizard",
    "Willkommen bei Recruiting Playbook!": "Welcome to Recruiting Playbook!",
    "Dieser Assistent hilft Ihnen, das Plugin in wenigen Minuten einzurichten.": "This wizard will help you set up the plugin in just a few minutes.",
    "Stellenanzeigen verwalten": "Manage job listings",
    "Bewerbungen empfangen": "Receive applications",
    "Übersicht behalten": "Keep track of everything",

    // Email
    "E-Mail wird gesendet...": "Sending email...",
    "Test-E-Mail wurde gesendet!": "Test email sent!",
    "E-Mail konnte nicht gesendet werden.": "Email could not be sent.",
    "Neue Bewerbung eingegangen": "New application received",
    "Bewerbung erfolgreich eingereicht": "Application successfully submitted",
    "Ihre Bewerbung bei %s": "Your application at %s",
    "Betreff": "Subject",
    "Nachricht": "Message",
    "Empfänger": "Recipient",
    "Absender": "Sender",
    "Gesendet": "Sent",
    "Fehlgeschlagen": "Failed",
    "Ausstehend": "Pending",

    // Validation & Errors
    "Pflichtfeld": "Required field",
    "Dieses Feld ist erforderlich.": "This field is required.",
    "Bitte geben Sie eine gültige E-Mail-Adresse ein.": "Please enter a valid email address.",
    "Bitte geben Sie eine gültige Telefonnummer ein.": "Please enter a valid phone number.",
    "Die Datei ist zu groß.": "The file is too large.",
    "Ungültiges Dateiformat.": "Invalid file format.",
    "Maximale Dateigröße: %s": "Maximum file size: %s",
    "Erlaubte Dateitypen: %s": "Allowed file types: %s",

    // Schema Validation
    "Stellentitel fehlt": "Job title is missing",
    "Stellenbeschreibung fehlt": "Job description is missing",
    "Stellenbeschreibung ist sehr kurz (min. 100 Zeichen empfohlen)": "Job description is very short (min. 100 characters recommended)",
    "Standort fehlt (empfohlen für besseres Ranking)": "Location is missing (recommended for better ranking)",
    "Beschäftigungsart fehlt (Vollzeit, Teilzeit, etc.)": "Employment type is missing (Full-time, Part-time, etc.)",
    "Gehalt fehlt (wichtig für Google for Jobs Ranking)": "Salary is missing (important for Google for Jobs ranking)",
    "Bewerbungsfrist fehlt": "Application deadline is missing",
    "Bewerbungsfrist ist abgelaufen": "Application deadline has expired",
    "Unternehmensname fehlt (in Plugin-Einstellungen oder WordPress-Einstellungen)": "Company name is missing (in plugin settings or WordPress settings)",

    // Application Service
    "Bewerbung konnte nicht gespeichert werden.": "Application could not be saved.",
    "Ungültiger Status.": "Invalid status.",
    "Bewerbung nicht gefunden.": "Application not found.",
    "Status konnte nicht aktualisiert werden.": "Status could not be updated.",
    "Kandidat konnte nicht erstellt werden.": "Candidate could not be created.",

    // Spam Protection
    "Ihre Anfrage wurde als potentieller Spam erkannt.": "Your request was detected as potential spam.",
    "Bitte nehmen Sie sich etwas mehr Zeit zum Ausfüllen des Formulars.": "Please take a little more time to fill out the form.",
    "Sie haben die maximale Anzahl an Bewerbungen erreicht. Bitte versuchen Sie es später erneut.": "You have reached the maximum number of applications. Please try again later.",

    // Time & Date
    "Heute": "Today",
    "Gestern": "Yesterday",
    "Diese Woche": "This Week",
    "Dieser Monat": "This Month",
    "Dieses Jahr": "This Year",
    "Datum": "Date",
    "Uhrzeit": "Time",
    "vor %s": "%s ago",
    "in %s": "in %s",
    "Minuten": "minutes",
    "Stunden": "hours",
    "Tagen": "days",
    "Wochen": "weeks",
    "Monaten": "months",

    // Statistics & Reports
    "Statistiken": "Statistics",
    "Gesamt": "Total",
    "Heute": "Today",
    "Diese Woche": "This Week",
    "Dieser Monat": "This Month",
    "Bewerbungen gesamt": "Total Applications",
    "Offene Bewerbungen": "Open Applications",
    "Einstellungen": "Hires",
    "Conversion Rate": "Conversion Rate",
    "Durchschnittliche Zeit bis Einstellung": "Average Time to Hire",
    "Top Berufsfelder": "Top Job Categories",
    "Bewerbungen nach Status": "Applications by Status",
    "Bewerbungen nach Quelle": "Applications by Source",

    // Settings
    "Allgemein": "General",
    "Firmenname": "Company Name",
    "Firmen-E-Mail": "Company Email",
    "HR-E-Mail": "HR Email",
    "Logo": "Logo",
    "Primärfarbe": "Primary Color",
    "Sekundärfarbe": "Secondary Color",
    "Textfarbe": "Text Color",
    "Hintergrundfarbe": "Background Color",
    "Schriftart": "Font",
    "Standardwerte": "Default Values",
    "Zurücksetzen": "Reset",
    "Standard wiederherstellen": "Restore Default",

    // Pro Features
    "Pro-Funktion": "Pro Feature",
    "Diese Funktion erfordert Pro.": "This feature requires Pro.",
    "Diese Funktion erfordert das KI-Addon.": "This feature requires the AI Addon.",
    "Jetzt upgraden": "Upgrade Now",
    "Mehr über Pro erfahren": "Learn more about Pro",

    // AI Features
    "KI-Analyse": "AI Analysis",
    "KI-Matching": "AI Matching",
    "Passe ich zu diesem Job?": "Am I a good fit for this job?",
    "Lebenslauf hochladen": "Upload Resume",
    "Analysieren": "Analyze",
    "Matching-Score": "Matching Score",
    "Stärken": "Strengths",
    "Verbesserungspotenzial": "Areas for Improvement",
    "Empfehlungen": "Recommendations",

    // Error & System Messages
    "Recruiting Playbook benötigt PHP %1$s oder höher. Sie nutzen PHP %2$s.": "Recruiting Playbook requires PHP %1$s or higher. You are using PHP %2$s.",
    "Recruiting Playbook benötigt WordPress %1$s oder höher. Sie nutzen WordPress %2$s.": "Recruiting Playbook requires WordPress %1$s or higher. You are using WordPress %2$s.",
    "Plugin-Aktivierung fehlgeschlagen. Anforderungen nicht erfüllt.": "Plugin activation failed. Requirements not met.",
    "Recruiting Playbook: Bitte führen Sie \"composer install\" aus.": "Recruiting Playbook: Please run \"composer install\".",
    "Keine Bewerbungen vorhanden.": "No applications yet.",
    "Keine Bewerbungen ausgewählt. Bitte wählen Sie Bewerbungen aus der Liste aus.": "No applications selected. Please select applications from the list.",
    "Bitte wählen Sie eine Vorlage aus.": "Please select a template.",
    "%1$d E-Mails erfolgreich gesendet, %2$d fehlgeschlagen.": "%1$d emails sent successfully, %2$d failed.",
    "%d Empfänger ausgewählt": "%d recipients selected",
    "Keine E-Mail-Vorlagen verfügbar.": "No email templates available.",
    "— Vorlage auswählen —": "— Select template —",
    "Die E-Mails werden sofort an alle ausgewählten Empfänger gesendet.": "The emails will be sent immediately to all selected recipients.",
    "%d E-Mails senden": "Send %d emails",
    "Keine benutzerdefinierten Felder konfiguriert.": "No custom fields configured.",
    "Aktivierte Felder werden im Bewerbungsformular für diese Stelle angezeigt.": "Enabled fields will be displayed in the application form for this job.",
    "Standardkonfiguration: %d Felder aktiv": "Default configuration: %d fields active",
    "Bewerbung wird geladen...": "Loading application...",
    "Bewerbung nicht gefunden.": "Application not found.",
    "Keine Bewerbung angegeben.": "No application specified.",
    "Bewerbung von %s": "Application from %s",
    "Keine Dokumente hochgeladen.": "No documents uploaded.",
    "Keine Aktivitäten aufgezeichnet.": "No activities recorded.",
    "Massen-E-Mail erfordert Pro.": "Bulk email requires Pro.",

    // Form Labels
    "Sehr geehrte Bewerberin, sehr geehrter Bewerber": "Dear Applicant",
    "vielen Dank für Ihre Bewerbung als %1$s bei %2$s.": "Thank you for your application as %1$s at %2$s.",
    "eine neue Bewerbung für die Position %s ist eingegangen.": "a new application for the position %s has been received.",
    "Bewerbungs-ID: %s": "Application ID: %s",
    "wir freuen uns, Ihnen mitteilen zu können, dass Ihre Bewerbung als %s uns überzeugt hat.": "we are pleased to inform you that your application as %s has convinced us.",
    "wir freuen uns sehr, Ihnen nach den positiven Gesprächen ein Angebot für die Position %s unterbreiten zu können!": "we are very pleased to offer you a position as %s following our positive discussions!",
    "Bitte teilen Sie uns Ihre Entscheidung bis zum %s mit.": "Please let us know your decision by %s.",
    "Versand über %s": "Sent via %s",
    "vielen Dank für Ihr Interesse an der Position %1$s bei %2$s und die Zeit, die Sie in Ihre Bewerbung investiert haben.": "Thank you for your interest in the position %1$s at %2$s and the time you invested in your application.",
    "%1$s (max. %2$d MB)": "%1$s (max. %2$d MB)",
    "Maximal %d Dateien": "Maximum %d files",
    "Jobs: %s": "Jobs: %s",
    "Jobs in %s": "Jobs in %s",
    "Karriere bei %s": "Careers at %s",
    "Passe ich zu diesem Job?": "Am I a good fit for this job?",
    "Deinen Traumjob finden": "Find your dream job",
    "Lade deinen Lebenslauf hoch und entdecke passende Jobs.": "Upload your resume and discover matching jobs.",
    "Keine Kategorien verfügbar.": "No categories available.",
    "{count} offene Stellen": "{count} open positions",
    "{count} offene Stelle": "{count} open position",
    "Keine offenen Stellen": "No open positions",

    // Status changes
    "Status geändert von \"%1$s\" zu \"%2$s\"": "Status changed from \"%1$s\" to \"%2$s\"",
    "Notiz hinzugefügt": "Note added",
    "Notiz bearbeitet": "Note edited",
    "Notiz gelöscht": "Note deleted",
    "Dokument hochgeladen": "Document uploaded",
    "Dokument gelöscht": "Document deleted",
    "Bewertung hinzugefügt": "Rating added",
    "Bewertung aktualisiert": "Rating updated",
    "E-Mail gesendet": "Email sent",
    "Bewerbung erstellt": "Application created",
    "Bewerbung aktualisiert": "Application updated",
    "Bewerbung anonymisiert": "Application anonymized",
    "Zum Talent-Pool hinzugefügt": "Added to Talent Pool",
    "Aus Talent-Pool entfernt": "Removed from Talent Pool",

    // Admin UI specific
    "Schnellbearbeitung": "Quick Edit",
    "Massenaktionen": "Bulk Actions",
    "Anwenden": "Apply",
    "Nach Status filtern": "Filter by Status",
    "Nach Stelle filtern": "Filter by Job",
    "Nach Datum filtern": "Filter by Date",
    "Suche nach Name oder E-Mail": "Search by name or email",
    "Zeige %d Einträge": "Show %d entries",
    "Keine Einträge gefunden": "No entries found",
    "Lade...": "Loading...",
    "Mehr laden": "Load more",
    "Alle auswählen": "Select all",
    "Auswahl aufheben": "Deselect all",
    "Ausgewählt: %d": "Selected: %d",

    // Additional strings
    "Ein Template mit diesem Namen existiert bereits.": "A template with this name already exists.",
    "Entdecken Sie unsere aktuellen Stellenangebote in diesem Bereich.": "Discover our current job openings in this area.",
    "&laquo; Zurück": "&laquo; Back",
    "Weiter &raquo;": "Next &raquo;",
    "Ihre Bewerbung im Überblick:": "Your application at a glance:",
    "Bei Fragen stehen wir Ihnen gerne zur Verfügung.": "If you have any questions, please feel free to contact us.",
    "Bitte bringen Sie mit:": "Please bring:",
    "Gültigen Personalausweis oder Reisepass": "Valid ID card or passport",
    "Aktuelle Zeugnisse (falls noch nicht eingereicht)": "Current certificates (if not yet submitted)",
    "Wir freuen uns auf das Gespräch mit Ihnen!": "We look forward to meeting you!",
    "Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.": "If you have any questions, please do not hesitate to contact us.",
    "Wir freuen uns darauf, Sie bald in unserem Team willkommen zu heißen!": "We look forward to welcoming you to our team soon!",
    "Weitere Datei hinzufügen": "Add another file",
    "Bitte angeben...": "Please specify...",
    "Bitte wählen...": "Please select...",
    "oder klicken zum Auswählen": "or click to select",
    "Leider haben wir keine passenden Stellen gefunden.": "Unfortunately, we did not find any matching positions.",
    "PDF, JPG, PNG oder DOCX (max. 10 MB)": "PDF, JPG, PNG or DOCX (max. 10 MB)",
    "Dokument wird hochgeladen...": "Uploading document...",
    "Analyse läuft...": "Analyzing...",
    "%s hat ein ungültiges Format.": "%s has an invalid format.",
    "%s enthält einen ungültigen Wert.": "%s contains an invalid value.",
    "Schritt %d: Felder müssen ein Array sein.": "Step %d: Fields must be an array.",
    "Entdecken Sie unsere aktuellen Stellenangebote und bewerben Sie sich direkt online.": "Discover our current job openings and apply directly online.",
    "Schauen Sie später wieder vorbei oder kontaktieren Sie uns direkt.": "Please check back later or contact us directly.",
    "Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen.": "We have received your documents and will review them carefully.",
    "Keine SMTP-Konfiguration erkannt. Wir empfehlen die Installation eines SMTP-Plugins.": "No SMTP configuration detected. We recommend installing an SMTP plugin.",
    "Konfigurieren Sie, welche E-Mails automatisch gesendet werden, wenn sich der Bewerbungsstatus ändert.": "Configure which emails are sent automatically when the application status changes.",
    "Stelle nicht gefunden.": "Job not found.",
    "Straße und Hausnummer": "Street and house number",
    "Status geändert: %1$s → %2$s": "Status changed: %1$s → %2$s",
    "Finde Positionen in deinem Bereich.": "Find positions in your field.",
    "Action Scheduler Bibliothek fehlt. Bitte führen Sie %s im Plugin-Verzeichnis aus.": "Action Scheduler library is missing. Please run %s in the plugin directory.",
    "So wird Ihre Signatur in E-Mails angezeigt:": "This is how your signature will appear in emails:",
    "Diese Daten werden in E-Mail-Signaturen und im Google for Jobs Schema verwendet.": "This data is used in email signatures and the Google for Jobs schema.",
    "Diese Informationen werden in E-Mails und im Google for Jobs Schema verwendet.": "This information will be used in emails and in the Google for Jobs schema.",
    "Strukturierte Daten für bessere Sichtbarkeit in Google.": "Structured data for better visibility in Google.",
    "Angezeigt im Schema, in E-Mails und auf der Karriereseite.": "Displayed in schema, emails, and on the careers page.",
    "Name, der als Absender in E-Mails angezeigt wird.": "Name displayed as sender in emails.",
    "JSON-LD Schema für bessere Sichtbarkeit in Google": "JSON-LD schema for better visibility in Google",
    "\"Versendet via Recruiting Playbook\" Hinweis in E-Mails ausblenden": "Hide \"Sent via Recruiting Playbook\" notice in emails",
    "— Seite auswählen —": "— Select Page —",
    "— Keine Signatur —": "— No signature —",
    "— Keine Vorlage —": "— No Template —",
    "Läuft ab in %d Tagen": "Expires in %d days",
    "Suche in Name, E-Mail": "Search in name, email",
    "Suche in Betreff, Empfänger": "Search in subject, recipient",
    "Volltext-Suche in Titel/Beschreibung": "Full-text search in title/description",
    "Bewerbungs-IDs müssen als Array übergeben werden.": "Application IDs must be provided as an array.",
    "IDs der Felder in dieser Vorlage": "IDs of fields in this template",

    // Design Settings
    "Farben": "Colors",
    "Typografie": "Typography",
    "Abstände": "Spacing",
    "Rahmen": "Borders",
    "Schatten": "Shadows",
    "Live-Vorschau": "Live Preview",
    "CSS-Variablen": "CSS Variables",
    "Benutzerdefiniertes CSS": "Custom CSS",
    "Design exportieren": "Export Design",
    "Design importieren": "Import Design",
    "Design zurücksetzen": "Reset Design",

    // Misc
    "Ja": "Yes",
    "Nein": "No",
    "Aktiv": "Active",
    "Inaktiv": "Inactive",
    "Aktiviert": "Enabled",
    "Deaktiviert": "Disabled",
    "Alle": "All",
    "Keine": "None",
    "Auswählen": "Select",
    "Optional": "Optional",
    "Erforderlich": "Required",
    "Standard": "Default",
    "Benutzerdefiniert": "Custom",
    "Unbekannt": "Unknown",
    "Nicht verfügbar": "Not available",
    "Laden...": "Loading...",
    "Bitte warten...": "Please wait...",
    "Keine Daten verfügbar": "No data available",
    "Keine Ergebnisse gefunden": "No results found",

    // Form Builder
    "Formularfelder": "Form Fields",
    "Feld hinzufügen": "Add Field",
    "Feld bearbeiten": "Edit Field",
    "Feld löschen": "Delete Field",
    "Feldtyp": "Field Type",
    "Feldname": "Field Name",
    "Beschriftung": "Label",
    "Platzhalter": "Placeholder",
    "Hilfetext": "Help Text",
    "Standardwert": "Default Value",
    "Optionen": "Options",
    "Validierung": "Validation",
    "Mindestlänge": "Minimum Length",
    "Maximallänge": "Maximum Length",
    "Muster (Regex)": "Pattern (Regex)",
    "Bedingte Logik": "Conditional Logic",

    // Field Types
    "Textfeld": "Text Field",
    "Textbereich": "Text Area",
    "E-Mail-Feld": "Email Field",
    "Telefon-Feld": "Phone Field",
    "Zahlenfeld": "Number Field",
    "Datumsfeld": "Date Field",
    "Auswahlfeld": "Select Field",
    "Checkbox": "Checkbox",
    "Radio-Buttons": "Radio Buttons",
    "Datei-Upload": "File Upload",
    "Überschrift": "Heading",
    "Absatz": "Paragraph",
    "Trennlinie": "Separator",

    // Kanban Board
    "Spalte": "Column",
    "Karte verschieben": "Move Card",
    "Neue Spalte": "New Column",
    "Spalte bearbeiten": "Edit Column",
    "Spalte löschen": "Delete Column",

    // Notes & Activity
    "Notizen": "Notes",
    "Notiz hinzufügen": "Add Note",
    "Notiz bearbeiten": "Edit Note",
    "Notiz löschen": "Delete Note",
    "Aktivität": "Activity",
    "Aktivitätsprotokoll": "Activity Log",
    "Verlauf": "History",

    // Documents
    "Dokumente": "Documents",
    "Dokument hochladen": "Upload Document",
    "Dokument herunterladen": "Download Document",
    "Dokument löschen": "Delete Document",
    "Dokumenttyp": "Document Type",
    "Dateigröße": "File Size",
    "Hochgeladen am": "Uploaded on",

    // Rating
    "Bewertung": "Rating",
    "Bewertungen": "Ratings",
    "Bewerten": "Rate",
    "Durchschnittliche Bewertung": "Average Rating",
    "Ihre Bewertung": "Your Rating",

    // Templates
    "Vorlage": "Template",
    "Vorlagen": "Templates",
    "Neue Vorlage": "New Template",
    "Vorlage bearbeiten": "Edit Template",
    "Vorlage löschen": "Delete Template",
    "Vorlage anwenden": "Apply Template",
    "Vorlagenname": "Template Name",

    // Webhooks & API
    "Webhook-URL": "Webhook URL",
    "Ereignis": "Event",
    "Ereignisse": "Events",
    "Geheimschlüssel": "Secret Key",
    "API-Schlüssel": "API Key",
    "Neuer API-Schlüssel": "New API Key",
    "Schlüssel kopieren": "Copy Key",
    "Berechtigungen": "Permissions",
    "Lesen": "Read",
    "Schreiben": "Write",
    "Löschen": "Delete",
    "Alle Berechtigungen": "All Permissions",

    // GDPR
    "Datenschutz": "Privacy",
    "DSGVO": "GDPR",
    "Einwilligung": "Consent",
    "Daten exportieren": "Export Data",
    "Daten löschen": "Delete Data",
    "Anonymisieren": "Anonymize",
    "Aufbewahrungsfrist": "Retention Period",
    "Tage": "days",

    // Import/Export
    "Backup erstellen": "Create Backup",
    "Backup wiederherstellen": "Restore Backup",
    "CSV exportieren": "Export CSV",
    "CSV importieren": "Import CSV",
    "JSON exportieren": "Export JSON",
    "Daten exportieren": "Export Data",

    // User Roles
    "Administrator": "Administrator",
    "HR Manager": "HR Manager",
    "Recruiter": "Recruiter",
    "Hiring Manager": "Hiring Manager",
    "Betrachter": "Viewer",
    "Benutzer": "User",
    "Rolle": "Role",
    "Rollen": "Roles",
    "Berechtigungen verwalten": "Manage Permissions",

    // Integrations
    "Google for Jobs": "Google for Jobs",
    "Schema.org": "Schema.org",
    "XML-Feed": "XML Feed",
    "Elementor": "Elementor",
    "Gutenberg": "Gutenberg",
    "Avada": "Avada",
    "Integration aktivieren": "Enable Integration",
    "Integration deaktivieren": "Disable Integration",

    // Shortcodes
    "Shortcode kopiert": "Shortcode copied",
    "Shortcode": "Shortcode",
    "Verfügbare Shortcodes": "Available Shortcodes",
    "Parameter": "Parameters",
    "Beispiel": "Example",

    // Blocks
    "Block": "Block",
    "Blöcke": "Blocks",
    "Stellen-Block": "Jobs Block",
    "Bewerbungsformular-Block": "Application Form Block",
    "Stellen-Suche-Block": "Job Search Block",
    "Kategorien-Block": "Categories Block",

    // Plural forms
    "%d Stelle gefunden": "%d job found",
    "%d Stellen gefunden": "%d jobs found",
    "%d Bewerbung": "%d application",
    "%d Bewerbungen": "%d applications",
    "%d Tag": "%d day",
    "%d Tage": "%d days",

    // Email Templates
    "Eingangsbestätigung": "Application Confirmation",
    "HR-Benachrichtigung": "HR Notification",
    "Intervieweinladung": "Interview Invitation",
    "Absage": "Rejection",
    "Zusage": "Offer Letter",

    // More specific strings
    "Aktuell keine offenen Stellen verfügbar.": "No open positions currently available.",
    "Veröffentlicht am": "Published on",
    "Letzte Aktualisierung": "Last updated",
    "Details": "Details",
    "Firma": "Company",
    "Branche": "Industry",
    "Unternehmensgröße": "Company Size",
    "Website": "Website",
    "LinkedIn": "LinkedIn",
    "XING": "XING",
    "Verfügbarkeit": "Availability",
    "Gehaltsvorstellung": "Salary Expectation",
    "Frühester Eintrittstermin": "Earliest Start Date",
    "Aktuelle Position": "Current Position",
    "Berufserfahrung": "Work Experience",
    "Ausbildung": "Education",
    "Sprachkenntnisse": "Language Skills",
    "Fähigkeiten": "Skills",
    "Führerschein": "Driver's License",
    "Reisebereitschaft": "Willingness to Travel",
};

// PO-Datei verarbeiten
function processPoFile(filePath, locale) {
    console.log(`\n📝 Verarbeite ${path.basename(filePath)}...`);

    const content = fs.readFileSync(filePath, 'utf8');
    const po = gettextParser.po.parse(content);

    let translated = 0;
    let skipped = 0;
    let notFound = [];

    const poTranslations = po.translations[''] || {};

    for (const [msgid, entry] of Object.entries(poTranslations)) {
        if (msgid === '') continue; // Skip header

        // Wenn bereits übersetzt, überspringen
        if (entry.msgstr && entry.msgstr[0] && entry.msgstr[0].trim() !== '') {
            skipped++;
            continue;
        }

        // Übersetzung suchen
        if (translations[msgid]) {
            entry.msgstr = [translations[msgid]];
            translated++;
        } else if (isEnglishString(msgid)) {
            // Bereits englische Strings beibehalten
            entry.msgstr = [msgid];
            translated++;
        } else {
            notFound.push(msgid);
        }
    }

    // Header aktualisieren
    if (poTranslations['']) {
        const header = poTranslations[''];
        if (header.msgstr && header.msgstr[0]) {
            header.msgstr[0] = header.msgstr[0]
                .replace(/Language: [^\n]+/, `Language: ${locale}`)
                .replace(/PO-Revision-Date: [^\n]+/, `PO-Revision-Date: ${new Date().toISOString().split('.')[0]}+00:00`);
        }
    }

    // Speichern
    const output = gettextParser.po.compile(po);
    fs.writeFileSync(filePath, output);

    // MO generieren
    const moPath = filePath.replace('.po', '.mo');
    const mo = gettextParser.mo.compile(po);
    fs.writeFileSync(moPath, mo);

    console.log(`   ✅ ${translated} übersetzt`);
    console.log(`   ⏭️  ${skipped} bereits vorhanden`);
    if (notFound.length > 0 && notFound.length <= 20) {
        console.log(`   ⚠️  ${notFound.length} nicht gefunden:`);
        notFound.slice(0, 10).forEach(s => console.log(`      - "${s.substring(0, 50)}${s.length > 50 ? '...' : ''}"`));
        if (notFound.length > 10) {
            console.log(`      ... und ${notFound.length - 10} weitere`);
        }
    } else if (notFound.length > 20) {
        console.log(`   ⚠️  ${notFound.length} nicht gefunden (zu viele zum Anzeigen)`);
    }

    return { translated, skipped, notFound: notFound.length };
}

// en_GB erstellen basierend auf en_US
function createEnGB() {
    const enUSPath = path.join(LANGUAGES_DIR, 'recruiting-playbook-en_US.po');
    const enGBPath = path.join(LANGUAGES_DIR, 'recruiting-playbook-en_GB.po');

    // Kopieren falls en_GB nicht existiert
    if (!fs.existsSync(enGBPath)) {
        const content = fs.readFileSync(enUSPath, 'utf8');
        const gbContent = content
            .replace(/Language: en_US/g, 'Language: en_GB')
            .replace(/en_US/g, 'en_GB')
            .replace(/English \(US\)/g, 'English (UK)');
        fs.writeFileSync(enGBPath, gbContent);
        console.log('\n📋 en_GB.po aus en_US.po erstellt');
    }

    return enGBPath;
}

// Hauptfunktion
function main() {
    console.log('🌐 Generiere englische Übersetzungen...');
    console.log(`📚 ${Object.keys(translations).length} Übersetzungen im Wörterbuch`);

    // en_US verarbeiten
    const enUSPath = path.join(LANGUAGES_DIR, 'recruiting-playbook-en_US.po');
    const statsUS = processPoFile(enUSPath, 'en_US');

    // en_GB erstellen und verarbeiten
    const enGBPath = createEnGB();
    const statsGB = processPoFile(enGBPath, 'en_GB');

    console.log('\n✨ Fertig!');
    console.log(`\n📊 Zusammenfassung:`);
    console.log(`   en_US: ${statsUS.translated} übersetzt, ${statsUS.notFound} offen`);
    console.log(`   en_GB: ${statsGB.translated} übersetzt, ${statsGB.notFound} offen`);

    if (statsUS.notFound > 0) {
        console.log(`\n💡 Tipp: Fehlende Strings können in tools/generate-en-translations.mjs ergänzt werden.`);
    }
}

main();
