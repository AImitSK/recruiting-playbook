# Freemius Customer Portal Customization

Dieses Verzeichnis enthält Custom CSS für das Freemius Customer Portal.

## 📁 Dateien

- **`customer-portal.css`** - Custom CSS mit Recruiting Playbook Brand Colors

## 🎨 Brand Colors

Das CSS verwendet folgende Brand Colors:

```css
--rp-primary: #1d71b8;      /* Primary Blue */
--rp-green: #2fac66;        /* Success Green */
--rp-blue: #36a9e1;         /* Light Blue */
```

## 📤 CSS bei Freemius hochladen

### Schritt 1: Freemius Dashboard öffnen

1. Gehe zu: **https://dashboard.freemius.com/**
2. Melde dich mit deinem Freemius-Account an
3. Wähle **"Recruiting Playbook"** aus der Plugin-Liste

### Schritt 2: Customization Settings

1. Linke Sidebar → **"Settings"**
2. Tab → **"Customization"**
3. Scrolle zu **"Custom CSS"**

### Schritt 3: CSS hochladen

**Option A: Direkt einfügen**
1. Öffne `customer-portal.css` in einem Texteditor
2. Kopiere den gesamten CSS-Code
3. Füge ihn in das Textfeld **"Custom CSS"** ein
4. Klicke **"Save Changes"**

**Option B: File Upload (falls verfügbar)**
1. Klicke auf **"Upload CSS File"** oder **"Choose File"**
2. Wähle `customer-portal.css` aus
3. Klicke **"Upload"** oder **"Save"**

### Schritt 4: Testen

1. Öffne dein Customer Portal: `https://checkout.freemius.com/...`
2. Überprüfe, ob die Brand Colors angewendet wurden
3. Teste auf verschiedenen Seiten:
   - Account Dashboard
   - Download-Seite
   - Subscription Management
   - Invoice History

## 🖼️ Optional: Custom Logo hochladen

1. Kopiere dein Logo nach `website/public/freemius/logo.png`
2. Im Freemius Dashboard → **Settings → Customization**
3. **"Logo URL"** → URL zu deinem Logo eintragen
4. Empfohlene Größe: 200x40px (PNG oder SVG)

## 🔄 CSS aktualisieren

Wenn du Änderungen am CSS vornimmst:

1. Bearbeite `customer-portal.css`
2. Kopiere den aktualisierten Code
3. Freemius Dashboard → Settings → Customization
4. Ersetze das alte CSS
5. **"Save Changes"**

## 📝 CSS-Anpassungen

### Farben ändern

Ändere die CSS-Variablen am Anfang der Datei:

```css
:root {
  --rp-primary: #1d71b8;        /* Deine Primärfarbe */
  --rp-green: #2fac66;          /* Success-Farbe */
  --rp-blue: #36a9e1;           /* Info-Farbe */
}
```

### Schriftart ändern

```css
body {
  font-family: 'Deine Schriftart', sans-serif;
}
```

### Border Radius ändern

Suche nach `border-radius` und passe die Werte an (z.B. `8px` → `4px` für eckigere Ecken).

## 🐛 Troubleshooting

**CSS wird nicht angewendet?**
- Cache leeren (Strg+F5)
- Prüfe Browser-Konsole auf CSS-Fehler
- Stelle sicher, dass CSS korrekt gespeichert wurde

**Farben sehen anders aus?**
- Prüfe ob `!important` entfernt wurde
- Freemius könnte eigene Styles mit höherer Spezifität haben

**Mobilansicht sieht komisch aus?**
- Das CSS enthält responsive Breakpoints (`@media`)
- Bei Bedarf anpassen

## 📚 Dokumentation

- [Freemius Customization Docs](https://freemius.com/help/documentation/users-account-management/applying-css-customization/)
- [CSS Variables Guide](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)

## ✅ Checkliste

- [ ] CSS in Freemius Dashboard eingefügt
- [ ] Änderungen gespeichert
- [ ] Customer Portal getestet
- [ ] Mobile-Ansicht geprüft
- [ ] Logo hochgeladen (optional)
- [ ] Team informiert

---

*Erstellt: 15. Februar 2026*
*Letzte Aktualisierung: 15. Februar 2026*
