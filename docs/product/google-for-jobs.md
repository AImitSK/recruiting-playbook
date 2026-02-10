# Google for Jobs Integration

> Ihre Stellenanzeigen automatisch in der Google-Jobsuche -- ohne Zusatzkosten.

---

## Was ist Google for Jobs?

Google for Jobs ist ein spezieller Bereich in den Google-Suchergebnissen, der Stellenanzeigen prominent anzeigt. Wenn jemand nach "Pflegefachkraft Berlin" sucht, erscheinen passende Stellen direkt in einer übersichtlichen Karte -- noch vor den normalen Suchergebnissen.

**Das Beste:** Die Einbindung ist komplett kostenlos. Keine Klickgebühren, keine monatlichen Kosten.

---

## So funktioniert es

Recruiting Playbook fügt auf jeder veröffentlichten Stellenanzeige automatisch ein spezielles Datenformat (JSON-LD Schema) in den Quellcode ein. Google erkennt dieses Format beim Crawlen Ihrer Website und nimmt die Stelle in die Jobsuche auf.

**Sie müssen nichts tun** -- die Integration ist standardmäßig aktiviert und funktioniert sofort nach der Plugin-Installation.

### Was Google anzeigt

- Jobtitel und Unternehmen
- Standort oder "Remote"
- Beschäftigungsart (Vollzeit, Teilzeit, etc.)
- Gehaltsspanne (wenn angegeben)
- Bewerbungsfrist
- Direktlink zur Bewerbung auf Ihrer Website

---

## Einstellungen

Die Google for Jobs Integration finden Sie unter **Einstellungen > Integrationen**.

### Optionale Felder steuern

| Einstellung | Standard | Beschreibung |
|-------------|----------|--------------|
| **Gehalt anzeigen** | An | Gehaltsspanne im Schema ausgeben (empfohlen für besseres Ranking) |
| **Remote kennzeichnen** | An | Remote-Option als TELECOMMUTE kennzeichnen |
| **Bewerbungsfrist** | An | Ablaufdatum der Stelle übermitteln |

**Tipp:** Google bevorzugt Stellenanzeigen mit Gehaltsangaben. Stellen mit Gehalt werden deutlich häufiger angeklickt.

---

## Optimale Stellenanzeigen erstellen

Damit Ihre Stellen in Google for Jobs optimal angezeigt werden, achten Sie auf folgende Punkte:

### Pflichtfelder

Diese Felder müssen ausgefüllt sein, damit Google die Stelle indexiert:

- **Jobtitel** -- klar und präzise, z.B. "Pflegefachkraft (m/w/d)"
- **Stellenbeschreibung** -- mindestens 100 Zeichen, am besten ausführlich
- **Firmendaten** -- in den Plugin-Einstellungen unter "Firmendaten" hinterlegen

### Empfohlene Felder

Diese Felder verbessern das Ranking und die Klickrate:

- **Standort** -- Weisen Sie der Stelle einen Standort zu
- **Beschäftigungsart** -- Vollzeit, Teilzeit, Minijob, etc.
- **Gehalt** -- Gehaltsspanne angeben (min/max)
- **Bewerbungsfrist** -- Bis wann kann man sich bewerben?
- **Remote-Option** -- Ist die Stelle remote oder hybrid?

---

## Schema validieren

So prüfen Sie, ob das Schema korrekt ausgegeben wird:

1. Öffnen Sie den [Google Rich Results Test](https://search.google.com/test/rich-results)
2. Geben Sie die URL einer veröffentlichten Stelle ein
3. Klicken Sie auf "URL testen"
4. Unter "Erkannte strukturierte Daten" sollte **JobPosting** erscheinen

### Häufige Warnungen

| Warnung | Lösung |
|---------|--------|
| "Empfohlenes Feld fehlt: baseSalary" | Gehaltsspanne in der Stelle hinterlegen |
| "Empfohlenes Feld fehlt: employmentType" | Beschäftigungsart zuweisen |
| "validThrough liegt in der Vergangenheit" | Bewerbungsfrist aktualisieren oder entfernen |

---

## Indexierung in der Google Search Console

Nach der Aktivierung dauert es in der Regel **2-5 Tage**, bis Google Ihre Stellen indexiert hat. In der [Google Search Console](https://search.google.com/search-console) können Sie den Status unter **Verbesserungen > Stellenangebote** verfolgen.

### Tipps für schnellere Indexierung

- Reichen Sie Ihre Sitemap in der Search Console ein
- Nutzen Sie die URL-Prüfung für einzelne Stellen
- Halten Sie Ihre Stellen aktuell (abgelaufene Stellen entfernen)

---

## Beschäftigungsarten

Das Plugin übersetzt deutsche Beschäftigungsarten automatisch in das von Google erwartete Format:

| Ihre Angabe | Google-Format |
|-------------|---------------|
| Vollzeit | FULL_TIME |
| Teilzeit | PART_TIME |
| Minijob | PART_TIME |
| Ausbildung | INTERN |
| Praktikum | INTERN |
| Werkstudent | PART_TIME |
| Freiberuflich | CONTRACTOR |

---

## Häufige Fragen

### Muss ich etwas bei Google anmelden?

Nein. Google crawlt Ihre Website automatisch und erkennt das Schema-Markup. Eine Anmeldung bei der Google Search Console ist empfohlen (zur Kontrolle), aber nicht erforderlich.

### Kostet Google for Jobs etwas?

Nein, Google for Jobs ist komplett kostenlos. Die Integration ist in der kostenlosen Version von Recruiting Playbook enthalten.

### Wie schnell erscheinen meine Stellen bei Google?

In der Regel 2-5 Tage nach Veröffentlichung. Bei bereits indexierten Websites kann es schneller gehen.

### Werden abgelaufene Stellen automatisch entfernt?

Ja. Wenn die Bewerbungsfrist abgelaufen ist oder Sie die Stelle auf "Entwurf" setzen, wird das Schema nicht mehr ausgegeben. Google entfernt die Stelle dann automatisch.

### Funktioniert das auch mit WPML/Polylang?

Ja. Das Schema wird für jede Sprachversion der Stelle separat generiert.

### Kann ich die Integration deaktivieren?

Ja, unter **Einstellungen > Integrationen > Google for Jobs** können Sie den Schalter auf "Aus" setzen.

---

## Weiterführende Links

- [Google for Jobs -- Offizielle Dokumentation](https://developers.google.com/search/docs/appearance/structured-data/job-posting)
- [Rich Results Test](https://search.google.com/test/rich-results)
- [Google Search Console](https://search.google.com/search-console)
