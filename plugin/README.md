# Recruiting Playbook

Professionelles Bewerbermanagement für WordPress - Stellenanzeigen erstellen, Bewerbungen verwalten, DSGVO-konform.

## Installation

1. Plugin-Ordner nach `wp-content/plugins/recruiting-playbook` kopieren
2. Plugin im WordPress-Admin unter "Plugins" aktivieren
3. Setup-Wizard folgen für Erstkonfiguration

## Systemanforderungen

- WordPress 6.0 oder höher
- PHP 8.0 oder höher
- MySQL 5.7 oder höher

## Features

### Stellenverwaltung
- Custom Post Type für Stellenanzeigen
- Kategorien (Berufsfelder, Standorte, Beschäftigungsarten)
- Gehaltsinformationen mit Verstecken-Option
- Remote/Hybrid-Optionen
- Google for Jobs Schema (JSON-LD)

### Bewerbungen
- Mehrstufiges Bewerbungsformular
- Drag & Drop Datei-Upload
- Spam-Schutz (Honeypot, Rate-Limiting)
- E-Mail-Benachrichtigungen
- DSGVO-konforme Datenspeicherung

### Administration
- Übersichtliches Dashboard
- Bewerber-Listenverwaltung
- Status-Management (Neu → Interview → Angebot → Eingestellt)
- Backup-Export als JSON

## Shortcodes

### [rp_jobs]

Zeigt eine Liste der Stellenanzeigen.

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `limit` | Anzahl Stellen | 10 |
| `category` | Filter nach Kategorie-Slug | - |
| `location` | Filter nach Standort-Slug | - |
| `type` | Filter nach Beschäftigungsart | - |
| `orderby` | Sortierung (date, title, rand) | date |
| `order` | ASC oder DESC | DESC |
| `columns` | Spalten im Grid (1-4) | 1 |
| `show_excerpt` | Auszug anzeigen | true |

**Beispiel:**
```
[rp_jobs limit="5" category="it" columns="2"]
```

### [rp_job_search]

Zeigt ein Suchformular mit Filtern und Ergebnissen.

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `show_search` | Suchfeld anzeigen | true |
| `show_category` | Kategorie-Filter | true |
| `show_location` | Standort-Filter | true |
| `show_type` | Beschäftigungsart-Filter | true |
| `limit` | Stellen pro Seite | 10 |
| `columns` | Spalten im Grid | 1 |

**Beispiel:**
```
[rp_job_search show_type="false" limit="20"]
```

### [rp_application_form]

Zeigt das Bewerbungsformular.

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `job_id` | ID der Stelle | (automatisch) |
| `title` | Überschrift | "Jetzt bewerben" |
| `show_job_title` | Stellentitel anzeigen | true |

**Beispiel:**
```
[rp_application_form job_id="123" title="Ihre Bewerbung"]
```

## E-Mail-Konfiguration

Für zuverlässigen E-Mail-Versand empfehlen wir die Installation eines SMTP-Plugins:

- [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/)
- [Post SMTP](https://wordpress.org/plugins/post-smtp/)
- [FluentSMTP](https://wordpress.org/plugins/fluent-smtp/)

Das Plugin zeigt eine Warnung im Dashboard, wenn kein SMTP konfiguriert ist.

## Template-Überschreibung

Templates können im Theme überschrieben werden:

1. Ordner `recruiting-playbook/` im Theme erstellen
2. Template kopieren und anpassen:
   - `recruiting-playbook/archive-job_listing.php`
   - `recruiting-playbook/single-job_listing.php`

## Hooks & Filter

### Actions

```php
// Nach Erstellung einer Bewerbung
do_action( 'rp_application_created', $application_id, $data );

// Nach Status-Änderung
do_action( 'rp_application_status_changed', $id, $new_status, $old_status );

// Spam blockiert
do_action( 'rp_spam_blocked', $type, $ip, $request );
```

### Filter

```php
// E-Mail-Empfänger für Bewerbungsbenachrichtigung
$recipients = apply_filters( 'rp_notification_recipients', $recipients, $application_id );

// Erlaubte Dateitypen
$types = apply_filters( 'rp_allowed_file_types', $default_types );
```

## DSGVO

Das Plugin speichert personenbezogene Daten gemäß DSGVO:

- Bewerberdaten werden nur mit Einwilligung gespeichert
- Einwilligungs-Timestamp wird protokolliert
- Soft-Delete ermöglicht Wiederherstellung
- Export- und Lösch-Funktionen vorhanden

## Support

- GitHub Issues: https://github.com/AImitSK/recruiting-playbook/issues
- Dokumentation: https://github.com/AImitSK/recruiting-playbook/wiki

## Lizenz

GPL-2.0-or-later
