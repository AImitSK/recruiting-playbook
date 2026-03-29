# WordPress.org Guideline 5 - Manuelle Test-Anleitung

**Zweck:** Sicherstellen dass die Free-Version KEINE Premium-Features zeigt oder erwähnt.
**Basis:** WordPress.org Review Email vom 26. März 2026
**Status:** Alle Tests müssen ✅ sein für Approval!

---

## ⚡ Quick Start

```bash
# 1. Playwright Tests ausführen
cd C:\Users\StefanKühne\Desktop\Projekte\recruiting-playbook
npx playwright test tests/e2e/test-wordpress-org-compliance.spec.js

# 2. Manuell testen (siehe unten)
```

---

## 📋 Manuelle Test-Checkliste

### Test 1: Admin-Menü (KRITISCH!)

**WordPress.org Anforderung:** Keine Premium-Menüpunkte dürfen sichtbar sein (auch nicht mit 🔒 Lock-Icon!)

1. ✅ Öffne: `http://localhost:8082/wp-admin`
2. ✅ Login: admin / admin
3. ✅ Schaue in das linke Admin-Menü unter "Recruiting Playbook"

**VERBOTEN (darf NICHT sichtbar sein):**
- ❌ Kanban Board
- ❌ Talent Pool
- ❌ Reports / Reporting
- ❌ Form Builder
- ❌ Email Templates
- ❌ Bulk Email

**ERLAUBT (MUSS sichtbar sein):**
- ✅ Bewerbungen
- ✅ Einstellungen
- ✅ (Optional: Freemius "Pricing" Link - erlaubt!)

**Screenshot speichern:** `admin-menu-free.png`

---

### Test 2: Bewerbungen-Seite (KRITISCH!)

**WordPress.org Anforderung:** Kein Export-Button, keine Premium-Feature-Hinweise

1. ✅ Öffne: `http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook`
2. ✅ Warte bis Bewerbungen-Tabelle geladen ist

**VERBOTEN:**
- ❌ Export-Button (rechts oben)
- ❌ CSV-Export Erwähnung
- ❌ "Pro" Badges
- ❌ Lock-Icons 🔒

**ERLAUBT:**
- ✅ Bewerbungen-Tabelle
- ✅ Status-Filter
- ✅ Suche
- ✅ "Ansehen" und "Prüfen" Buttons (Free-Features!)

**Screenshot speichern:** `bewerbungen-seite-free.png`

---

### Test 3: Einstellungen > Integrationen (KRITISCH!)

**WordPress.org Anforderung:** Keine Premium-Integrationen dürfen erwähnt werden

1. ✅ Öffne: `http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook-settings`
2. ✅ Klicke auf Tab "Integrationen"

**VERBOTEN (darf NICHT existieren):**
- ❌ Slack
- ❌ Microsoft Teams
- ❌ Google Ads Conversion

**ERLAUBT (MUSS existieren):**
- ✅ Google for Jobs
- ✅ XML Job Feed

**Screenshot speichern:** `integrationen-free.png`

---

### Test 4: Bewerbungs-Detailseite

**WordPress.org Anforderung:** Keine "Upgraden Sie auf Pro" Upgrade-Boxen

1. ✅ Öffne: `http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook`
2. ✅ Klicke auf "Ansehen" bei einer Bewerbung
3. ✅ Schaue die gesamte Detailseite an

**VERBOTEN:**
- ❌ "Pro-Funktion" Box
- ❌ "Upgraden Sie auf Pro" Button
- ❌ Ausgegraut aber sichtbare Premium-Features
- ❌ "Auf Pro upgraden" Links

**ERLAUBT:**
- ✅ Kandidaten-Info
- ✅ Anschreiben
- ✅ Dokumente
- ✅ Status-Änderung
- ✅ Aktivitäts-Log

**Screenshot speichern:** `bewerbungs-detail-free.png`

---

### Test 5: REST API Compliance

**WordPress.org Anforderung:** API darf keine Premium-Keys zurückgeben

1. ✅ Öffne Browser DevTools (F12)
2. ✅ Gehe zu: `http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook-settings`
3. ✅ Klicke auf "Integrationen" Tab
4. ✅ Schaue im Network-Tab nach Request: `GET /wp-json/recruiting/v1/settings/integrations`
5. ✅ Prüfe Response-Body

**VERBOTEN in Response:**
```json
{
  "slack_enabled": false,          // ❌ NICHT erlaubt
  "slack_webhook_url": "",         // ❌ NICHT erlaubt
  "teams_enabled": false,          // ❌ NICHT erlaubt
  "google_ads_enabled": false      // ❌ NICHT erlaubt
}
```

**ERLAUBT in Response:**
```json
{
  "google_jobs_enabled": true,     // ✅ Erlaubt
  "xml_feed_enabled": true         // ✅ Erlaubt
}
```

**Screenshot speichern:** `api-response-free.png`

---

### Test 6: Lock-Icons und Pro-Badges

**WordPress.org Anforderung:** Keine Lock-Icons oder "Pro"-Badges im UI

1. ✅ Durchsuche ALLE Admin-Seiten nach:
   - Bewerbungen
   - Einstellungen (alle Tabs!)
   - Bewerbungs-Detail

**VERBOTEN:**
- ❌ 🔒 Lock-Icon
- ❌ "Pro" Badge neben Features
- ❌ Ausgegraut aber sichtbare Buttons

**ERLAUBT:**
- ✅ Freemius "Upgrade to Pro" Button im Menü (externer Link!)

---

### Test 7: PHP Fatal Errors

**WordPress.org Anforderung:** Plugin muss ohne Errors laden

1. ✅ Aktiviere `WP_DEBUG` in wp-config.php
2. ✅ Öffne alle Admin-Seiten:
   - `/wp-admin/admin.php?page=recruiting-playbook`
   - `/wp-admin/admin.php?page=recruiting-playbook-settings`
   - `/wp-json/recruiting/v1/settings`

**VERBOTEN:**
- ❌ Fatal error: Class "RecruitingPlaybook\Services\EmailService" not found
- ❌ Call to undefined method
- ❌ 500 Internal Server Error

**Debug-Log prüfen:**
```bash
docker exec -it devcontainer-wordpress-1 tail -f /var/www/html/wp-content/debug.log
```

---

### Test 8: Email-Versand (Free-Version)

**WordPress.org Anforderung:** Basic-Features müssen funktionieren

1. ✅ Öffne: `http://localhost:8025` (Mailpit)
2. ✅ Schicke Test-Bewerbung ab
3. ✅ Prüfe Mailpit

**MUSS funktionieren:**
- ✅ Bewerber erhält Bestätigungs-Email (wp_mail)
- ✅ Admin erhält Benachrichtigungs-Email (wp_mail)

**Screenshot speichern:** `mailpit-emails-free.png`

---

## ✅ Finale Checkliste

Vor WordPress.org Upload:

- [ ] Alle 8 manuellen Tests bestanden
- [ ] Playwright Tests bestanden (`npx playwright test`)
- [ ] Keine PHP Errors im Debug-Log
- [ ] Screenshots dokumentiert
- [ ] Version-Nummer erhöht (`plugin/recruiting-playbook.php`)
- [ ] Changelog aktualisiert (`readme.txt`)

---

## 🚨 Was tun bei Test-Fehlern?

### Fehler: Premium-Menüpunkte sichtbar
→ **Fix:** `plugin/src/Admin/Menu.php` - Alle Premium-Items in `rp_can()` Guards einwickeln

### Fehler: Export-Button sichtbar
→ **Fix:** `plugin/assets/src/js/admin/applications/ApplicationsPage.jsx` - Button in `{ canExport && ( ... ) }` einwickeln

### Fehler: Premium-Integrationen in API
→ **Fix:** `plugin/src/Api/IntegrationController.php` - Premium-Keys in `get_settings()` filtern

### Fehler: Upgrade-Box sichtbar
→ **Fix:** `plugin/src/Admin/Pages/ApplicationDetail.php` - `rp_fs()->is__premium_only()` durch `rp_can()` ersetzen

### Fehler: Emails werden nicht verschickt
→ **Fix:** `plugin/src/Services/ApplicationService.php` - `wp_mail()` Fallback hinzufügen

---

## 📧 WordPress.org Antwort-Vorlage

Nach erfolgreichen Tests:

```
Fixed all Guideline 5 (Trialware) issues in version X.X.X:

✅ Premium menu items completely hidden (not just locked)
✅ Export button removed from Applications page
✅ Premium integrations (Slack, Teams, Google Ads) hidden from UI and API
✅ "Upgrade to Pro" boxes removed from detail pages
✅ Basic email notifications work with wp_mail() fallback
✅ All tests passed: E2E (Playwright) + Manual checklist

All premium features are now completely invisible in Free version.
No license checks, no locked features, no upgrade prompts except Freemius menu link.

Free version is fully functional with core features:
- Job listings with application forms
- Application management (view, status changes, delete)
- Basic email notifications (applicant + admin)
- Google for Jobs and XML Feed integrations

Review ID: AUTO recruiting-playbook/aimitsk/30Jan26/T6 26Mar26/3.9A7
```

---

## 🔗 Relevante Dateien

- `plugin/src/Admin/Menu.php` - Admin-Menü
- `plugin/src/Admin/Pages/ApplicationsPage.php` - Bewerbungen-Seite (Server)
- `plugin/assets/src/js/admin/applications/ApplicationsPage.jsx` - Bewerbungen-Seite (React)
- `plugin/assets/src/js/admin/settings/components/IntegrationSettings.jsx` - Integrationen-UI
- `plugin/src/Api/IntegrationController.php` - Integrationen-API
- `plugin/src/Admin/Pages/ApplicationDetail.php` - Bewerbungs-Detail
- `plugin/src/Services/ApplicationService.php` - Email-Versand
