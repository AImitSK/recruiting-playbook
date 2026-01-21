# Feature-Übersicht

## Feature-Matrix

| Feature | FREE | PRO | AI-ADDON |
|---------|:----:|:---:|:--------:|
| **Stellenanzeigen** | | | |
| Custom Post Type für Stellen | ✅ | ✅ | ✅ |
| Aktive Stellen | 3 | ∞ | ∞ |
| Stellen-Templates | 2 | 5+ | 5+ |
| Custom Fields für Stellen | ❌ | ✅ | ✅ |
| Stellen archivieren/duplizieren | ❌ | ✅ | ✅ |
| SEO-Meta-Felder | Basic | Erweitert | Erweitert |
| | | | |
| **Bewerbungsformular** | | | |
| Standard-Formular | ✅ | ✅ | ✅ |
| Lebenslauf-Upload | ✅ | ✅ | ✅ |
| Mehrere Dokumente | ❌ | ✅ | ✅ |
| Custom Fields | ❌ | ✅ | ✅ |
| DSGVO-Checkboxen | ✅ | ✅ | ✅ |
| Conditional Logic | ❌ | ✅ | ✅ |
| | | | |
| **Bewerbermanagement** | | | |
| Bewerber-Liste | Basic | Erweitert | Erweitert |
| Bewerber-Detailansicht | ❌ | ✅ | ✅ |
| Status-Tracking | ❌ | ✅ | ✅ |
| Kanban-Board | ❌ | ✅ | ✅ |
| Notizen pro Bewerber | ❌ | ✅ | ✅ |
| Bewertungssystem | ❌ | ✅ | ✅ |
| Bewerber-Suche & Filter | ❌ | ✅ | ✅ |
| | | | |
| **E-Mails** | | | |
| Benachrichtigung bei Bewerbung | ✅ | ✅ | ✅ |
| E-Mail an Bewerber (manuell) | ❌ | ✅ | ✅ |
| E-Mail-Templates | ❌ | ✅ | ✅ |
| Automatische Eingangsbestätigung | ❌ | ✅ | ✅ |
| | | | |
| **Benutzer & Rechte** | | | |
| Mehrere Benutzer | ❌ | ✅ | ✅ |
| Rollen (Admin/Recruiter/Viewer) | ❌ | ✅ | ✅ |
| Stellen-Zuweisung pro User | ❌ | ✅ | ✅ |
| | | | |
| **Reporting** | | | |
| Bewerbungen pro Stelle | ❌ | ✅ | ✅ |
| Time-to-Hire | ❌ | ✅ | ✅ |
| Conversion-Rates | ❌ | ✅ | ✅ |
| Export (CSV) | ❌ | ✅ | ✅ |
| | | | |
| **Integrationen** | | | |
| Webhook (Zapier/Make) | ❌ | ✅ | ✅ |
| Google Jobs Schema | ✅ | ✅ | ✅ |
| REST API | ❌ | ✅ | ✅ |
| | | | |
| **KI-Features** | | | |
| Stellentexte generieren | ❌ | ❌ | ✅ |
| Texte optimieren/umschreiben | ❌ | ❌ | ✅ |
| SEO-Vorschläge | ❌ | ❌ | ✅ |
| Textbaustein-Bibliothek | ❌ | ❌ | ✅ |
| | | | |
| **Sonstiges** | | | |
| Branding entfernen | ❌ | ✅ | ✅ |
| Premium-Support | ❌ | ✅ | ✅ |
| Updates | ✅ | 1 Jahr | ✅ |

---

## Feature-Details nach Bereich

### Stellenanzeigen

#### Custom Post Type `job_listing`

```
Felder:
- Titel (Standard)
- Beschreibung (Editor)
- Standort (Text / Taxonomy)
- Beschäftigungsart (Vollzeit, Teilzeit, Minijob, etc.)
- Gehalt/Stundenlohn (Optional, Range möglich)
- Bewerbungsfrist (Datum)
- Ansprechpartner (User-Referenz oder Freitext)
- Status (Entwurf, Aktiv, Pausiert, Archiviert)
```

#### Templates

**FREE:**
- Klassisch (Liste)
- Kachel-Ansicht

**PRO:**
- + Modernes Card-Design
- + Kompakt-Liste
- + Branding-Anpassung (Farben, Logo)

---

### Bewerbermanagement (nur PRO)

#### Kanban-Board

```
Spalten (konfigurierbar):
┌─────────┬───────────┬───────────┬─────────┬────────────┐
│   NEU   │ SCREENING │ INTERVIEW │ ANGEBOT │ ENTSCHIEDEN│
│         │           │           │         │            │
│ ┌─────┐ │ ┌─────┐   │           │         │ ┌─────────┐│
│ │Max M│ │ │Lisa │   │           │         │ │✓ Stefan ││
│ └─────┘ │ └─────┘   │           │         │ │✗ Anna   ││
│ ┌─────┐ │           │           │         │ └─────────┘│
│ │Sara │ │           │           │         │            │
│ └─────┘ │           │           │         │            │
└─────────┴───────────┴───────────┴─────────┴────────────┘
```

#### Bewerber-Detailansicht

- Stammdaten (Name, Kontakt, etc.)
- Hochgeladene Dokumente
- Timeline (alle Aktivitäten)
- Notizen (mit Autor & Timestamp)
- Bewertung (Sterne oder Punkte)
- Quick-Actions (E-Mail senden, Status ändern, Absagen)

---

### KI-Features (nur AI-ADDON)

#### Stellentext generieren

**Input-Formular:**
```
Jobtitel: [Pflegefachkraft (m/w/d)          ]
Branche:  [Pflege / Gesundheit        ▼]
Stichpunkte zu Aufgaben:
[ ] Grundpflege
[ ] Behandlungspflege  
[ ] Dokumentation
[✓] Eigene eingeben: _______________

Stichpunkte zu Anforderungen:
[ ] Examen Pflege
[ ] Führerschein
[✓] Eigene eingeben: _______________

Tonalität: ○ Formell  ● Freundlich  ○ Locker
```

**Output:**
- Einleitung (Unternehmen vorstellen)
- Aufgaben (Bullet Points)
- Anforderungen (Muss/Kann)
- Benefits (generisch + eigene)
- Call-to-Action

**Nachbearbeitung:**
- Direkt im Editor bearbeiten
- "Kürzer/Länger" Button
- "Formeller/Lockerer" Button
- In Stelle übernehmen

---

## Priorisierung für MVP

### Must-Have (Phase 1)

1. Custom Post Type für Stellen
2. Frontend-Anzeige (Shortcode + Block)
3. Bewerbungsformular mit Upload
4. Bewerber-Liste im Backend
5. E-Mail-Benachrichtigung
6. Basis-Styling

### Should-Have (Phase 2 / Pro)

7. Kanban-Board
8. Status-Tracking
9. E-Mail-Templates
10. Benutzerrollen
11. Custom Fields

### Nice-to-Have (Phase 3 / AI)

12. KI-Textgenerierung
13. Reporting
14. Webhooks/API
15. CV-Parsing

---

*Letzte Aktualisierung: Januar 2025*
