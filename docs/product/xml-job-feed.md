# XML Job Feed

> Ihre Stellenanzeigen automatisch auf Jobbörsen -- mit einem einzigen Feed.

---

## Was ist der XML Job Feed?

Der XML Job Feed stellt alle Ihre veröffentlichten Stellenanzeigen als standardisierte XML-Datei bereit. Jobbörsen können diesen Feed automatisch einlesen und Ihre Stellen auf ihren Plattformen listen -- ohne dass Sie jede Stelle einzeln einstellen müssen.

**Unterstützte Jobbörsen:**
- Jooble
- Talent.com (ehemals Neuvoo)
- Adzuna
- Regionale Jobbörsen
- Jede Plattform, die Standard-XML-Feeds akzeptiert

---

## So funktioniert es

Nach der Plugin-Installation ist der Feed sofort unter dieser URL verfügbar:

```
https://ihre-domain.de/feed/jobs/
```

Diese URL geben Sie bei der jeweiligen Jobbörse als Feed-URL an. Die Jobbörse ruft den Feed regelmäßig ab und aktualisiert die Stellenanzeigen automatisch.

### Was im Feed enthalten ist

Jede Stelle enthält folgende Informationen:

- **Titel und Beschreibung** der Stelle
- **Standort** (Stadt)
- **Beschäftigungsart** (Vollzeit, Teilzeit, Ausbildung, etc.)
- **Gehaltsspanne** (wenn angegeben und aktiviert)
- **Bewerbungsfrist**
- **Direktlink** zur Stellenanzeige auf Ihrer Website
- **Firma** und **Kategorie**
- **Remote-Option** (wenn gesetzt)
- **Kontakt-E-Mail**

### Automatische Aktualisierung

- Neue Stellen erscheinen sofort im Feed
- Geänderte Stellen werden automatisch aktualisiert
- Abgelaufene oder deaktivierte Stellen verschwinden automatisch
- Der Feed wird für 1 Stunde gecacht (Performance)

---

## Einrichtung bei Jobbörsen

### Jooble

1. Registrieren Sie sich als Arbeitgeber auf [jooble.org](https://jooble.org)
2. Gehen Sie zu "Feed hinzufügen"
3. Fügen Sie Ihre Feed-URL ein: `https://ihre-domain.de/feed/jobs/`
4. Jooble prüft den Feed und beginnt mit der Indexierung

### Talent.com

1. Registrieren Sie sich auf [talent.com](https://www.talent.com)
2. Kontaktieren Sie den Support mit Ihrer Feed-URL
3. Talent.com richtet den automatischen Import ein

### Adzuna

1. Registrieren Sie sich als Arbeitgeber auf [adzuna.de](https://www.adzuna.de)
2. Reichen Sie Ihre Feed-URL ein
3. Adzuna beginnt mit dem regelmäßigen Abruf

### Andere Jobbörsen

Die meisten Jobbörsen akzeptieren Standard-XML-Feeds. Geben Sie einfach Ihre Feed-URL an:
```
https://ihre-domain.de/feed/jobs/
```

---

## Einstellungen

Die Feed-Einstellungen finden Sie unter **Einstellungen > Integrationen > XML Job Feed**.

| Einstellung | Standard | Beschreibung |
|-------------|----------|--------------|
| **Feed aktiviert** | An | Feed ein-/ausschalten |
| **Gehalt anzeigen** | An | Gehaltsinformationen im Feed |
| **HTML-Beschreibung** | An | Formatierte Beschreibung (empfohlen) |
| **Max. Stellen** | 50 | Maximale Anzahl Stellen im Feed (1-500) |

### Feed-URL kopieren

Im Integrationen-Tab sehen Sie Ihre Feed-URL mit einem Kopieren-Button. Diese URL geben Sie bei den Jobbörsen an.

### Gehalt im Feed

Wenn aktiviert, enthält jede Stelle die Gehaltsspanne in verschiedenen Formaten:
- Formatiert: "3.400-4.200 EUR/Monat"
- Einzelwerte: salary_min, salary_max, salary_currency

Stellen, bei denen "Gehalt verbergen" aktiviert ist, zeigen auch im Feed kein Gehalt.

### HTML vs. Plain Text

- **HTML (Standard):** Die Stellenbeschreibung enthält Formatierungen (Überschriften, Listen, etc.) -- empfohlen für die meisten Jobbörsen
- **Plain Text:** Nur der reine Text ohne Formatierung -- für Plattformen, die kein HTML unterstützen

---

## Beschäftigungsarten im Feed

Das Plugin übersetzt deutsche Beschäftigungsarten automatisch ins englische Standard-Format:

| Ihre Angabe | Im Feed |
|-------------|---------|
| Vollzeit | full-time |
| Teilzeit | part-time |
| Minijob | part-time |
| Ausbildung | internship |
| Praktikum | internship |
| Werkstudent | part-time |
| Freiberuflich | freelance |

---

## Häufige Fragen

### Muss ich den Feed manuell aktualisieren?

Nein. Der Feed wird bei jedem Abruf automatisch aus Ihren aktuellen Stellenanzeigen generiert. Neue Stellen erscheinen sofort, gelöschte verschwinden automatisch.

### Kostet der XML Feed etwas?

Der XML Job Feed ist kostenlos und in der Free-Version von Recruiting Playbook enthalten. Ob die Jobbörse selbst Gebühren erhebt, hängt von der jeweiligen Plattform ab.

### Wie viele Stellen kann der Feed enthalten?

Standardmäßig die letzten 50 Stellen. Sie können den Wert in den Einstellungen auf bis zu 500 erhöhen.

### Wie oft rufen Jobbörsen den Feed ab?

Das hängt von der Jobbörse ab -- in der Regel alle 1-24 Stunden. Der Feed wird auf Ihrer Seite für 1 Stunde gecacht, sodass häufige Abrufe die Performance nicht beeinträchtigen.

### Kann ich den Feed deaktivieren?

Ja, unter **Einstellungen > Integrationen > XML Job Feed** können Sie den Feed komplett deaktivieren. Die URL gibt dann einen 404-Fehler zurück.

### Funktioniert der Feed auch mit Indeed?

Indeed stellt XML-Feeds zum 1. April 2026 ein und migriert auf eine neue API. Der Feed ist daher nicht für Indeed konzipiert, funktioniert aber mit allen anderen genannten Plattformen.

---

## Technische Details

- **URL:** `/feed/jobs/`
- **Format:** XML (UTF-8)
- **Content-Type:** `application/xml`
- **Caching:** 1 Stunde (Transient)
- **Robots:** `noindex, follow` (Feed soll nicht direkt von Google indexiert werden)
