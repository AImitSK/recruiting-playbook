# Freemius Premium Code Handling - Erkenntnisse aus offizieller Dokumentation

## Quellen
- [Deploy New Product Version with Freemius](https://freemius.com/help/documentation/release-management/deployment/)
- [Handling Licensing](https://freemius.com/help/documentation/wordpress-sdk/software-licensing/)
- [Deployment Process](https://freemius.com/help/documentation/wordpress/deployment-process/)
- [Integrating Freemius SDK](https://freemius.com/help/documentation/wordpress-sdk/integrating-freemius-sdk/)

---

## 1. Zwei Methoden für Premium-Code-Ausschluss

Freemius bietet **zwei parallele Ansätze** um Premium-Code aus der Free-Version zu entfernen:

### Methode A: `@fs_premium_only` Meta-Tag (Datei-Ebene)

**Im Plugin-Header (recruiting-playbook.php):**
```php
<?php
/**
 * Plugin Name: Recruiting Playbook
 * @fs_premium_only /lib/functions.php, /premium-files/
 */
```

**Im Theme-Header (style.css):**
```css
/**
 * Theme Name: My Theme
 * @fs_premium_only /premium-functions.php, /premium-files/
 */
```

**Was passiert:**
- Die angegebenen Dateien/Verzeichnisse werden **physisch aus der Free-Version entfernt**
- Funktioniert für **alle Dateitypen**: PHP, CSS, JS, Bilder, etc.
- Kann einzelne Dateien ODER ganze Verzeichnisse ausschließen

**Alternative Naming-Convention:**
```
functions__premium_only.php  → wird automatisch ausgeschlossen
```

---

### Methode B: `is__premium_only()` Runtime-Checks (Code-Ebene)

**PHP-Code Wrapping:**
```php
class My_Plugin {
    function init() {
        // Dieser IF-Block wird automatisch aus der Free-Version entfernt
        if ( my_fs()->is__premium_only() ) {
            // Premium-only Logic hier
            $this->init_premium_features();
        }
    }
}
```

**Was passiert:**
- Der gesamte IF-Block wird **aus der Free-Version-PHP-Datei entfernt** (Preprocessor)
- Die Datei bleibt, aber der Code innerhalb `if ( is__premium_only() )` ist weg

**Weitere verfügbare Methoden:**
```php
// Check ob User auf einem bestimmten Plan ist
if ( my_fs()->is_plan__premium_only( 'pro' ) ) {
    // Nur für Pro-Plan
}

// Check ob User auf Plan ODER Trial ist
if ( my_fs()->is_plan_or_trial__premium_only( 'pro' ) ) {
    // Für Pro-Plan und Pro-Trial
}

// Check ob Premium-Code verwendet werden kann
if ( my_fs()->can_use_premium_code__premium_only() ) {
    // User hat gültige Lizenz oder Trial
}
```

---

## 2. Freemius Deployment-Prozess

### Schritt 1: ZIP hochladen
```
├── recruiting-playbook/
│   ├── recruiting-playbook.php  (mit @fs_premium_only Tags)
│   ├── src/
│   │   ├── Admin/Pages/KanbanBoard.php  (@fs_premium_only)
│   │   ├── Services/EmailService.php     (@fs_premium_only)
│   └── vendor/
```

### Schritt 2: Freemius PHP Preprocessor
- **Parst** alle PHP-Dateien
- **Entfernt** `@fs_premium_only` Dateien/Verzeichnisse
- **Entfernt** Code innerhalb `if ( is__premium_only() )`
- **Generiert** zwei Versionen:
  - **Premium ZIP**: Alles enthalten
  - **Free ZIP**: Premium-Code entfernt

### Schritt 3: Download & WordPress.org
```bash
# 1. Free-Version manuell von Freemius Dashboard downloaden
# 2. ZIP entpacken und prüfen
unzip recruiting-playbook-free.zip
# 3. Via SVN zu WordPress.org hochladen
svn commit -m "Update to v1.2.42"
```

---

## 3. WordPress.org Compliance Strategie

**WordPress.org Regel:**
> "All code hosted on WordPress.org must be free and fully-functional. No locked features allowed, even if they're in the code."

**Freemius Lösung:**
1. **Einen Codebase** mit Premium-Features entwickeln
2. **@fs_premium_only** Tags verwenden für Premium-Dateien
3. **is__premium_only()** Wrapper für Premium-Logic
4. **Freemius generiert** Free-Version automatisch
5. **Free-Version enthält KEINEN Premium-Code** mehr

---

## 4. Zwei Config-Settings im SDK

```php
// Freemius SDK Init
my_plugin_fs() = fs_dynamic_init( array(
    'id'                  => '12345',
    'slug'                => 'recruiting-playbook',

    // WICHTIG: Gibt an ob DIESER Code die Free oder Premium-Version ist
    'is_premium'          => false,  // false = Free Codebase, true = Premium

    // Gibt an ob eine Premium-Version existiert
    'has_premium_version' => true,

    // Premium-Slug (für Parallel Activation)
    'premium_slug'        => 'recruiting-playbook-premium',

    // WordPress.org Compliance Flag
    'is_org_compliant'    => true,  // MUSS true sein für WordPress.org
));
```

---

## 5. Parallel Activation (Optional)

Für Freemium-Modell wo Free + Premium parallel installiert werden können:

```php
'parallel_activation' => array(
    'enabled' => true,
    'premium_version_basename' => 'recruiting-playbook-premium/recruiting-playbook.php',
)
```

**Hinweis:** Freemius rät davon ab (Conversion-Rate nur 2%, UX-Verwirrung)

---

## 6. Was WIR falsch gemacht haben

### ❌ Aktueller Stand:
```php
// recruiting-playbook.php Zeile 24-50
/**
 * @fs_premium_only
 *   /src/Integrations/Elementor/,
 *   /src/Blocks/,
 *   /src/Admin/Pages/KanbanBoard.php,
 *   /src/Admin/Pages/TalentPoolPage.php,
 *   ...
 */
```

**Problem:** Diese Dateien sind **trotzdem im Free-ZIP enthalten!**

**Grund:** Unbekannt - entweder:
1. Freemius Preprocessor funktioniert nicht wie dokumentiert
2. Unsere Syntax ist falsch
3. Die Tags werden ignoriert weil wir `is__premium_only()` Runtime-Checks verwenden

---

## 7. Richtige Lösung laut Freemius

### Option A: Nur `@fs_premium_only` Meta-Tag nutzen

**recruiting-playbook.php:**
```php
/**
 * Plugin Name: Recruiting Playbook
 * @fs_premium_only /src/Admin/Pages/KanbanBoard.php, /src/Admin/Pages/TalentPoolPage.php, /src/Services/EmailService.php
 */
```

→ Diese Dateien sollten **physisch entfernt** werden aus Free-ZIP

### Option B: Nur `is__premium_only()` Runtime-Checks

**ALLE Premium-Dateien bleiben**, aber:

```php
// src/Admin/Pages/KanbanBoard.php
class KanbanBoard {
    public function render() {
        // GESAMTER Code in is__premium_only() wrapper
        if ( rp_fs()->is__premium_only() ) {
            // Kanban rendering...
        }
    }
}
```

→ Der Code innerhalb IF wird **aus Free-Version PHP entfernt**

### Option C: Hybrid (Empfohlen von Freemius)

- **Große Premium-Features:** Via `@fs_premium_only` komplett ausschließen
- **Kleine Premium-Snippets:** Via `is__premium_only()` im Code wrappen

---

## 8. Nächste Schritte

### Test 1: Prüfen ob `@fs_premium_only` funktioniert
1. Freemius Deploy triggern
2. Free-Version downloaden
3. Checken: Sind `KanbanBoard.php`, etc. wirklich weg?

### Test 2: Falls NICHT → is__premium_only() nutzen
```php
// Menu.php
public function renderKanban(): void {
    if ( rp_fs()->is__premium_only() ) {
        $kanban_page = new KanbanBoard();
        $kanban_page->render();
    }
}
```

### Test 3: Hybrid-Ansatz
- `@fs_premium_only` für große Features (Kanban, Talent Pool, Email Templates)
- `is__premium_only()` für kleine Snippets

---

## 9. Debugging

**Freemius Dashboard checken:**
- Versions → Gewählte Version → "Download Free Version"
- Entpacken und manuell prüfen welche Dateien enthalten sind

**Falls @fs_premium_only nicht funktioniert:**
- Syntax checken (Komma-separiert, keine Leerzeichen vor Pfaden)
- SDK Version checken (min. 2.13.0)
- Freemius Support kontaktieren

---

## Zusammenfassung

**Freemius bietet 2 Methoden:**
1. **@fs_premium_only**: Entfernt Dateien physisch
2. **is__premium_only()**: Entfernt Code-Blöcke via Preprocessor

**Beide sollten funktionieren für WordPress.org Compliance.**

**Unser Problem:** @fs_premium_only entfernt die Dateien NICHT → Debugging nötig!
