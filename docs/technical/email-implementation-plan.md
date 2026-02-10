# E-Mail-System: Implementierungsplan

> **Basiert auf:** [email-signature-specification.md](email-signature-specification.md)
> **Branch:** `feature/email-system`
> **Erstellt:** Januar 2025

---

## Übersicht

Dieser Plan beschreibt die Implementierung des überarbeiteten E-Mail-Systems mit:
- Trennung von Templates und Signaturen
- Bereinigung der Pseudo-Variablen
- Neue Firmendaten-Verwaltung
- Aktualisierte API-Dokumentation

### Priorisierung

| Priorität | Bedeutung |
|-----------|-----------|
| **P0** | Blocker - Muss zuerst fertig sein |
| **P1** | Kritisch - Kernfunktionalität |
| **P2** | Wichtig - Vollständigkeit |
| **P3** | Nice-to-have - Kann später |

### Komplexität

| Symbol | Aufwand |
|--------|---------|
| `[S]` | Small - < 1 Stunde |
| `[M]` | Medium - 1-3 Stunden |
| `[L]` | Large - 3-8 Stunden |
| `[XL]` | Extra Large - > 1 Tag |

---

## Phase 1: Datenbank & Backend-Grundlagen (P0) ✅

### 1.1 Datenbank-Migration
- [x] `[M]` Neue Tabelle `rp_signatures` erstellen
  - Datei: `plugin/src/Database/Schema.php`
  - Migration in `plugin/src/Database/Migrator.php`
- [x] `[S]` Migrator um Signatur-Tabelle erweitern
- [x] `[S]` Default Firmen-Signatur bei Aktivierung erstellen

### 1.2 Firmendaten-Einstellungen erweitern
- [x] `[M]` `rp_settings['company']` Schema definieren
  - `name`, `street`, `zip`, `city`, `phone`, `email`, `website`
  - `default_sender_name`, `default_sender_email`
  - Datei: `plugin/src/Admin/Settings.php` (oder neu erstellen)
- [x] `[S]` Validierung für Firmendaten

### 1.3 Repository Layer
- [x] `[M]` `SignatureRepository.php` erstellen
  - `find(int $id)`
  - `findByUser(int $user_id)`
  - `findDefaultForUser(int $user_id)`
  - `findCompanyDefault()`
  - `create(array $data)`
  - `update(int $id, array $data)`
  - `delete(int $id)`
  - `setDefault(int $id, int $user_id)`

---

## Phase 2: Services (P0/P1) ✅

### 2.1 SignatureService erstellen
- [x] `[L]` `plugin/src/Services/SignatureService.php`
  - `render(int $signature_id): string`
  - `renderCompanyBlock(): string`
  - `renderMinimalSignature(): string`
  - `getDefaultForUser(int $user_id): ?array`
  - `getOptionsForUser(int $user_id): array`

### 2.2 PlaceholderService bereinigen (P0)
- [x] `[M]` Pseudo-Variablen entfernen aus `PlaceholderService.php`
  - Entfernen: `termin_*` (5 Stück)
  - Entfernen: `absender_*` (4 Stück)
  - Entfernen: `kontakt_*` (3 Stück)
  - Entfernen: `start_datum`, `vertragsart`, `arbeitszeit`, `antwort_frist`
  - **17 Variablen total entfernen**
- [x] `[S]` `getAvailablePlaceholders()` aktualisieren (nur 17 echte)
- [x] `[S]` Preview-Werte für Editor anpassen

### 2.3 EmailService anpassen (P1)
- [x] `[M]` `composeEmail()` erweitern
  - Signatur-Parameter hinzufügen
  - Signatur automatisch anhängen
  - Fallback-Kette implementieren
- [x] `[S]` Automatische E-Mails: Firmen-Signatur verwenden

---

## Phase 3: REST API (P1) ✅

### 3.1 Signaturen-Endpoints
- [x] `[L]` `plugin/src/Api/SignatureController.php` erstellen
  ```
  GET    /recruiting/v1/signatures
  POST   /recruiting/v1/signatures
  GET    /recruiting/v1/signatures/{id}
  PUT    /recruiting/v1/signatures/{id}
  DELETE /recruiting/v1/signatures/{id}
  POST   /recruiting/v1/signatures/{id}/default
  GET    /recruiting/v1/signatures/company
  PUT    /recruiting/v1/signatures/company
  ```
- [x] `[S]` Permission Callbacks (User kann nur eigene Signaturen)
- [x] `[S]` Firmen-Signatur nur für Admins editierbar

### 3.2 Firmendaten-Endpoints
- [x] `[M]` Endpoints in `SettingsController.php` hinzufügen
  ```
  GET    /recruiting/v1/settings/company
  POST   /recruiting/v1/settings/company
  ```

### 3.3 E-Mail-Versand anpassen
- [x] `[S]` `/emails/send` um `signature_id` Parameter erweitern
- [x] `[S]` `/emails/preview` um Signatur-Vorschau erweitern

---

## Phase 4: Admin UI - React (P1) ✅

### 4.1 Tab-Struktur für E-Mail-Templates
- [x] `[M]` Tabs-Komponente für Template-Seite
  - Tab: Vorlagen (bestehend)
  - Tab: Signaturen (neu)
  - Tab: Automatisierung (neu/umgebaut)
  - Datei: `plugin/assets/src/js/admin/email/index.jsx`

### 4.2 Signaturen-Tab
- [x] `[L]` `SignatureList.jsx` - Liste der User-Signaturen (Tabellen-Ansicht)
- [x] `[L]` `SignatureEditor.jsx` - Signatur bearbeiten/erstellen
  - Name-Feld
  - Rich-Text-Editor für Signatur-Inhalt
  - Checkbox: Als Standard
  - Tabs: Bearbeiten / Vorschau
- [x] `[S]` API-Hooks: `useSignatures.js`

> **Hinweis:** Es gibt keine separate Firmen-Signatur-Komponente mehr. Wenn ein User keine Signatur hat, wird automatisch eine Signatur aus den Firmendaten generiert.

### 4.3 Automatisierungs-Tab
- [x] `[L]` `AutomationSettings.jsx` umbauen
  - Nur 3 automatisierbare: Eingangsbestätigung, Absage, Zurückgezogen
  - Toggle pro Automatisierung
  - Template-Auswahl pro Automatisierung
  - Verzögerungsoptionen (Sofort bis 24 Stunden)
- [x] `[S]` Validierung: Nur passende Template-Kategorien anzeigen

### 4.4 E-Mail-Composer anpassen
- [x] `[M]` Signatur-Dropdown hinzufügen
  - User-Signaturen anzeigen
  - Firmen-Signatur Option
  - "Keine Signatur" Option
- [x] `[M]` Signatur-Vorschau im Composer
- [x] `[S]` Signatur bei Vorschau mit rendern

### 4.5 Variablen-Picker bereinigen
- [x] `[M]` `PlaceholderPicker.jsx` aktualisiert
  - Nur 17 echte Variablen anzeigen (7 Kandidat, 3 Bewerbung, 4 Stelle, 3 Firma)
  - Gruppen: Kandidat, Bewerbung, Stelle, Firma
  - Entfernte Gruppen: Absender, Interview, Angebot, Kontakt
  - Daten werden dynamisch von `PlaceholderService::getPlaceholdersByGroup()` geladen

---

## Phase 5: Firmendaten-Tab in Einstellungen (P1) ✅

### 5.1 Settings-Seite erweitern
- [x] `[M]` Tab "Firmendaten" hinzufügen
  - Datei: `plugin/src/Admin/Settings.php`
- [x] `[L]` Firmendaten-Formular erstellen
  - Firmenname (Pflicht)
  - Adresse (Straße, PLZ, Stadt)
  - Telefon, Website
  - Kontakt-E-Mail (Pflicht)
  - Standard-Absender (Name + E-Mail)
- [x] `[S]` Validierung (E-Mail-Format, Pflichtfelder)
- [x] `[S]` Speichern via REST API

---

## Phase 6: Templates migrieren (P2) ✅

### 6.1 Bestehende Templates aktualisieren
- [x] `[M]` Eingangsbestätigung - Signatur entfernen
- [x] `[M]` Absage - Signatur entfernen
- [x] `[M]` Zurückgezogen - neu erstellen (automatisierbar)

### 6.2 Manuelle Templates mit Lücken erstellen
- [x] `[M]` Interview-Einladung - mit `___` Lücken
- [x] `[M]` Interview-Erinnerung - mit `___` Lücken
- [x] `[M]` Angebot - mit `___` Lücken
- [x] `[M]` Zusage/Vertrag - mit `___` Lücken

### 6.3 Talent-Pool Templates
- [x] `[M]` Aufnahme in Talent-Pool (automatisierbar)
- [x] `[M]` Passende Stelle verfügbar (optional automatisierbar)

### 6.4 Seed-Daten aktualisieren
- [x] `[M]` `Migrator.php` - Default-Templates anpassen
- [x] `[S]` Template-Kategorien prüfen/erweitern

---

## Phase 7: API-Dokumentation (P2) ✅

### 7.1 api-specification.md aktualisieren
- [x] `[L]` E-Mail-Templates Endpoints dokumentieren
  ```
  GET/POST/PATCH/DELETE /email-templates
  GET /email-templates/placeholders
  GET /email-templates/categories
  ```
- [x] `[L]` Signaturen Endpoints dokumentieren
  ```
  GET/POST/PUT/DELETE /signatures
  GET/PUT /signatures/company
  ```
- [x] `[M]` E-Mail-Versand Endpoints dokumentieren
  ```
  POST /emails/send
  POST /emails/preview
  GET /emails/log
  ```
- [x] `[M]` Firmendaten Endpoints dokumentieren
  ```
  GET/POST /settings/company
  ```
- [x] `[S]` Request/Response Beispiele für alle neuen Endpoints
- [x] `[S]` Hinweis auf bereinigte Platzhalter-Liste

### 7.2 Hinweis auf Änderungen
- [x] `[S]` Breaking Changes dokumentieren (entfernte Variablen)
- [x] `[S]` Migration Guide für bestehende Templates

---

## Phase 8: Cleanup & Testing (P2)

### 8.1 Code Cleanup
- [ ] `[M]` Alte Pseudo-Variablen-Referenzen finden und entfernen
- [ ] `[S]` Ungenutzte Imports/Funktionen entfernen
- [ ] `[S]` Code-Kommentare aktualisieren

### 8.2 Testing
- [ ] `[M]` Unit Tests für SignatureService
- [ ] `[M]` Unit Tests für SignatureRepository
- [ ] `[M]` API Tests für Signatur-Endpoints
- [ ] `[L]` E2E Tests für Signatur-Workflow
- [ ] `[S]` Manuelle Tests: Template-Editor ohne Pseudo-Variablen

---

## Phase 9: Nice-to-have (P3)

### 9.1 Erweiterte Features
- [ ] `[M]` Signatur-Import aus Outlook/Gmail Format
- [ ] `[M]` HTML-Signatur mit Rich-Text-Editor
- [ ] `[S]` Signatur-Vorlagen (vordefinierte Layouts)

### 9.2 UX-Verbesserungen
- [ ] `[S]` Onboarding: Signatur-Setup beim ersten E-Mail-Versand
- [ ] `[S]` Warnung wenn Template Pseudo-Variablen enthält (Migration)

---

## Abhängigkeiten

```
Phase 1 (DB)
    │
    ▼
Phase 2 (Services)
    │
    ├──────────────────┐
    ▼                  ▼
Phase 3 (API)    Phase 4 (UI Tabs)
    │                  │
    ▼                  ▼
Phase 5 (Settings)   Phase 6 (Templates)
    │                  │
    └────────┬─────────┘
             ▼
        Phase 7 (Docs)
             │
             ▼
        Phase 8 (Testing)
             │
             ▼
        Phase 9 (Nice-to-have)
```

---

## Geschätzter Gesamtaufwand

| Phase | Aufwand | Priorität |
|-------|---------|-----------|
| Phase 1: Datenbank | ~4h | P0 |
| Phase 2: Services | ~6h | P0/P1 |
| Phase 3: REST API | ~6h | P1 |
| Phase 4: Admin UI | ~12h | P1 |
| Phase 5: Settings UI | ~4h | P1 |
| Phase 6: Templates | ~6h | P2 |
| Phase 7: API Docs | ~4h | P2 |
| Phase 8: Testing | ~6h | P2 |
| Phase 9: Nice-to-have | ~4h | P3 |
| **Gesamt** | **~52h** | |

---

## Checkliste vor Go-Live

- [ ] Alle P0/P1 Tasks abgeschlossen
- [ ] Keine PHP Errors/Warnings
- [ ] Keine JavaScript Console Errors
- [ ] API-Dokumentation vollständig
- [ ] Bestehende Templates migriert (ohne Signatur)
- [ ] Default Firmen-Signatur existiert
- [ ] E2E Test: E-Mail mit Signatur versenden
- [ ] Code Review durchgeführt

---

*Letzte Aktualisierung: 28. Januar 2025*
