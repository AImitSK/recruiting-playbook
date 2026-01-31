# KI-Job-Finder: Phase 3 - Shortcode & Integration

> **Voraussetzung:** [Phase 2 abgeschlossen](./ki-job-finder-phase-2-frontend.md)

## Ziel dieser Phase

Integration in WordPress:
- Shortcode `[rp_ai_job_finder]` registrieren
- Asset-Loading (CSS + JS)
- Feature-Flag Check

---

## 1. Shortcode in Shortcodes.php

### 1.1 Methode hinzufügen

**Datei:** `plugin/src/Frontend/Shortcodes.php`

```php
/**
 * KI-Job-Finder rendern
 *
 * Shortcode: [rp_ai_job_finder]
 *
 * Attribute:
 * - title: Überschrift (default: "Finde deinen passenden Job")
 * - subtitle: Untertitel
 * - max_matches: Anzahl Top-Matches (1-10, default: 5)
 * - show_profile: Extrahiertes Profil anzeigen (default: true)
 * - show_skills: Skills in Match-Cards anzeigen (default: true)
 *
 * @param array|string $atts Shortcode-Attribute.
 * @return string HTML-Ausgabe.
 */
public function renderAiJobFinder( $atts ): string {
    // Feature-Check
    if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
        return $this->renderUpgradePrompt(
            'ai_cv_matching',
            __( 'KI-Job-Finder', 'recruiting-playbook' ),
            'AI_ADDON'
        );
    }

    $atts = shortcode_atts(
        [
            'title'        => __( 'Finde deinen passenden Job', 'recruiting-playbook' ),
            'subtitle'     => __( 'Lade deinen Lebenslauf hoch und finde heraus, welche Stellen am besten zu dir passen.', 'recruiting-playbook' ),
            'max_matches'  => 5,
            'show_profile' => 'true',
            'show_skills'  => 'true',
        ],
        $atts,
        'rp_ai_job_finder'
    );

    // Limit validieren
    $max_matches = max( 1, min( 10, absint( $atts['max_matches'] ) ) );

    // Aktive Jobs zählen
    $job_count = (int) wp_count_posts( 'job_listing' )->publish;

    if ( $job_count === 0 ) {
        return '<div class="rp-plugin">
            <div class="rp-bg-gray-50 rp-rounded-lg rp-p-6 rp-text-center">
                <p class="rp-text-gray-500">' .
                esc_html__( 'Aktuell keine offenen Stellen verfügbar.', 'recruiting-playbook' ) .
                '</p>
            </div>
        </div>';
    }

    // Assets laden
    $this->enqueueJobFinderAssets( $max_matches );

    // Booleans konvertieren
    $show_profile = filter_var( $atts['show_profile'], FILTER_VALIDATE_BOOLEAN );
    $show_skills  = filter_var( $atts['show_skills'], FILTER_VALIDATE_BOOLEAN );

    // Template rendern
    ob_start();
    include RP_PLUGIN_DIR . 'templates/partials/job-finder.php';
    return ob_get_clean();
}
```

### 1.2 Asset-Loading Methode

```php
/**
 * Job-Finder Assets laden
 *
 * @param int $maxMatches Maximale Anzahl Matches.
 */
private function enqueueJobFinderAssets( int $maxMatches = 5 ): void {
    // Basis-Assets
    $this->enqueueAssets();

    // Job-Finder CSS
    $css_file = RP_PLUGIN_DIR . 'assets/dist/css/job-finder.css';
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'rp-job-finder',
            RP_PLUGIN_URL . 'assets/dist/css/job-finder.css',
            [ 'rp-frontend' ],
            RP_VERSION . '-' . filemtime( $css_file )
        );
    }

    // Job-Finder JS
    $js_file = RP_PLUGIN_DIR . 'assets/src/js/components/job-finder.js';
    if ( file_exists( $js_file ) ) {
        wp_enqueue_script(
            'rp-job-finder',
            RP_PLUGIN_URL . 'assets/src/js/components/job-finder.js',
            [],
            RP_VERSION,
            true
        );

        wp_localize_script(
            'rp-job-finder',
            'rpJobFinderConfig',
            [
                'endpoints' => [
                    'analyze' => rest_url( 'recruiting/v1/match/job-finder' ),
                    'status'  => rest_url( 'recruiting/v1/match/status' ),
                ],
                'nonce'      => wp_create_nonce( 'wp_rest' ),
                'maxMatches' => $maxMatches,
                'i18n'       => [
                    'uploading'       => __( 'Dokument wird hochgeladen...', 'recruiting-playbook' ),
                    'analyzing'       => __( 'Analysiere Lebenslauf gegen alle Stellen...', 'recruiting-playbook' ),
                    'resultHigh'      => __( 'Gute Übereinstimmung', 'recruiting-playbook' ),
                    'resultMedium'    => __( 'Teilweise passend', 'recruiting-playbook' ),
                    'resultLow'       => __( 'Weniger passend', 'recruiting-playbook' ),
                    'invalidFileType' => __( 'Bitte laden Sie eine PDF, JPG, PNG oder DOCX Datei hoch.', 'recruiting-playbook' ),
                    'fileTooLarge'    => __( 'Die Datei ist zu groß. Maximum: 10 MB.', 'recruiting-playbook' ),
                    'error'           => __( 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'recruiting-playbook' ),
                ],
            ]
        );
    }

    // Alpine.js (nach Job-Finder JS)
    $this->enqueueAlpine( [ 'rp-job-finder' ] );
}
```

### 1.3 Upgrade-Prompt Methode

```php
/**
 * Upgrade-Prompt für nicht verfügbare Features
 *
 * @param string $feature      Feature-Name.
 * @param string $feature_name Anzeige-Name.
 * @param string $required_tier Benötigter Tier.
 * @return string HTML.
 */
private function renderUpgradePrompt( string $feature, string $feature_name, string $required_tier ): string {
    $tier_labels = [
        'PRO'      => 'Pro',
        'AI_ADDON' => 'AI Addon',
        'BUNDLE'   => 'Pro + AI Bundle',
    ];

    $label = $tier_labels[ $required_tier ] ?? $required_tier;

    return '<div class="rp-plugin">
        <div class="rp-bg-gray-50 rp-rounded-lg rp-p-6 rp-text-center">
            <svg class="rp-w-12 rp-h-12 rp-mx-auto rp-text-gray-400 rp-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h3 class="rp-font-semibold rp-text-gray-900 rp-mb-2">' .
            esc_html( $feature_name ) . '</h3>
            <p class="rp-text-gray-600 rp-mb-4">' .
            sprintf(
                esc_html__( 'Dieses Feature ist Teil von %s.', 'recruiting-playbook' ),
                '<strong>' . esc_html( $label ) . '</strong>'
            ) . '</p>
            <a href="' . esc_url( admin_url( 'admin.php?page=recruiting-playbook-license' ) ) . '" class="rp-inline-block rp-px-4 rp-py-2 rp-bg-primary rp-text-white rp-rounded-lg hover:rp-bg-primary-dark">' .
            esc_html__( 'Upgrade Info', 'recruiting-playbook' ) . '</a>
        </div>
    </div>';
}
```

### 1.4 Shortcode registrieren

In der `register()` Methode:

```php
public function register(): void {
    add_shortcode( 'rp_jobs', [ $this, 'renderJobList' ] );
    add_shortcode( 'rp_job_search', [ $this, 'renderJobSearch' ] );
    add_shortcode( 'rp_application_form', [ $this, 'renderApplicationForm' ] );
    add_shortcode( 'rp_custom_application_form', [ $this, 'renderCustomApplicationForm' ] );

    // NEU: Job-Finder
    add_shortcode( 'rp_ai_job_finder', [ $this, 'renderAiJobFinder' ] );
}
```

---

## 2. Build-Prozess

### 2.1 CSS in Tailwind aufnehmen

**Datei:** `plugin/tailwind.config.js`

Sicherstellen, dass `job-finder.css` im Content enthalten ist:

```javascript
module.exports = {
  content: [
    './src/**/*.php',
    './templates/**/*.php',
    './assets/src/**/*.js',
    './assets/src/**/*.css',
  ],
  // ...
}
```

### 2.2 Build ausführen

```bash
cd plugin
npm run build
```

Dies kompiliert:
- `assets/src/css/job-finder.css` → `assets/dist/css/job-finder.css`

---

## 3. Verwendung

### 3.1 Shortcode-Beispiele

**Standard:**
```
[rp_ai_job_finder]
```

**Mit angepasstem Titel:**
```
[rp_ai_job_finder title="Welcher Job passt zu dir?"]
```

**Mehr Matches anzeigen:**
```
[rp_ai_job_finder max_matches="10"]
```

**Ohne Profil-Anzeige:**
```
[rp_ai_job_finder show_profile="false"]
```

**Komplettes Beispiel:**
```
[rp_ai_job_finder
    title="Dein Karriere-Match"
    subtitle="Finde in Sekunden heraus, welche Stelle zu dir passt"
    max_matches="5"
    show_profile="true"
    show_skills="true"
]
```

### 3.2 Seite erstellen

1. Neue WordPress-Seite erstellen
2. Titel: "Job-Finder" oder "Karriere-Match"
3. Shortcode einfügen
4. Veröffentlichen

---

## 4. Test-Checkliste

### 4.1 Funktionale Tests

- [ ] Shortcode wird gerendert (mit AI_ADDON/BUNDLE Lizenz)
- [ ] Upgrade-Prompt bei FREE/PRO Lizenz
- [ ] "Keine Stellen" Meldung bei 0 Jobs
- [ ] Datei-Upload funktioniert (Drag & Drop + Click)
- [ ] Datei-Validierung (Typ + Größe)
- [ ] API-Aufruf wird gesendet
- [ ] Polling zeigt Status
- [ ] Ergebnisse werden angezeigt
- [ ] Match-Cards mit korrekten Farben
- [ ] "Stelle ansehen" Link funktioniert
- [ ] "Jetzt bewerben" Link funktioniert
- [ ] "Neue Analyse" Reset funktioniert
- [ ] Fehler-Handling funktioniert

### 4.2 Visuelle Tests

- [ ] Responsive auf Mobile
- [ ] Responsive auf Tablet
- [ ] Responsive auf Desktop
- [ ] Dark Mode (falls Theme unterstützt)
- [ ] Korrekte Abstände und Schriften

---

## Checkliste Phase 3

- [ ] `Shortcodes.php`: Methode `renderAiJobFinder()` hinzufügen
- [ ] `Shortcodes.php`: Methode `enqueueJobFinderAssets()` hinzufügen
- [ ] `Shortcodes.php`: Methode `renderUpgradePrompt()` hinzufügen (falls nicht vorhanden)
- [ ] `Shortcodes.php`: Shortcode in `register()` registrieren
- [ ] Build: `npm run build` ausführen
- [ ] Test-Seite mit Shortcode erstellen
- [ ] Alle Tests durchführen

---

## Zusammenfassung

Nach Abschluss aller 3 Phasen haben Sie:

1. **API** (Phase 1)
   - WordPress Endpoint `/recruiting/v1/match/job-finder`
   - Worker Route `/v1/analysis/job-finder`
   - Multi-Job Claude Prompt

2. **Frontend** (Phase 2)
   - Alpine.js Komponente `jobFinder`
   - CSS Styles
   - HTML Template

3. **Integration** (Phase 3)
   - Shortcode `[rp_ai_job_finder]`
   - Asset-Loading
   - Feature-Gate

---

*Erstellt: Januar 2026*
