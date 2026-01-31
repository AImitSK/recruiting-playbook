# KI-Matching: Phase 4 - WordPress Frontend

> **Voraussetzung:** [Phase 3 abgeschlossen](./ki-matching-phase-3-analysis.md)

## Ziel dieser Phase

Integration ins WordPress Plugin:
- Match-Button in der Stellen-Card
- Upload-Modal mit Datei-Upload
- Ergebnis-Anzeige mit Score und Kategorie
- Polling für Async-Verarbeitung

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                     WORDPRESS FRONTEND                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    STELLEN-CARD                              │   │
│  │                                                              │   │
│  │   Pflegefachkraft (m/w/d)                                   │   │
│  │   📍 Bielefeld | ⏰ Vollzeit                                │   │
│  │                                                              │   │
│  │   [Mehr erfahren]  [🤖 Bin ich ein Match?]  ← Button        │   │
│  │                                                              │   │
│  └──────────────────────────────┬──────────────────────────────┘   │
│                                 │ click                             │
│                                 ▼                                   │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    UPLOAD MODAL                              │   │
│  │                   (Alpine.js Component)                      │   │
│  │                                                              │   │
│  │   States: idle → uploading → processing → result/error      │   │
│  │                                                              │   │
│  └──────────────────────────────┬──────────────────────────────┘   │
│                                 │ fetch                             │
│                                 ▼                                   │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    REST PROXY                                │   │
│  │                   (WordPress → API)                          │   │
│  │                                                              │   │
│  │   POST /wp-json/recruiting/v1/match/analyze                 │   │
│  │   GET  /wp-json/recruiting/v1/match/status/{id}             │   │
│  │                                                              │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 1. Feature Flag

Das Matching-Feature ist nur für AI_ADDON und BUNDLE verfügbar.

### src/Licensing/FeatureFlags.php

✅ **Bereits implementiert** - Feature Flag `ai_cv_matching` wurde hinzugefügt:

```php
'AI_ADDON' => [
    // ... bestehende Features ...
    'ai_cv_matching' => true,    // KI-Matching für Lebensläufe
],

'BUNDLE' => [
    // ... bestehende Features ...
    'ai_cv_matching' => true,    // KI-Matching für Lebensläufe
],
```

### src/Licensing/helpers.php

✅ **Bereits implementiert** - Helper-Funktion wurde hinzugefügt:

```php
/**
 * Prüft ob CV-Matching verfügbar ist
 *
 * @return bool True wenn ai_cv_matching Feature aktiv (AI_ADDON oder BUNDLE).
 */
function rp_has_cv_matching(): bool {
    return rp_can( 'ai_cv_matching' ) === true;
}
```

---

## 2. Shortcode: [rp_ai_job_match]

Der KI-Match Button wird über einen Shortcode eingebunden. Dies ermöglicht:
- **Theme-Unabhängigkeit**: Funktioniert mit Avada, Elementor, etc.
- **Flexibilität**: Kann überall platziert werden
- **Konsistenz**: Einheitliche Implementierung

### 2.1 Shortcode-Attribute

| Attribut | Default | Beschreibung |
|----------|---------|--------------|
| `job_id` | Auto | Job-ID (automatisch auf Single-Job-Seiten) |
| `title` | "Passe ich zu diesem Job?" | Button-Text |
| `style` | primary | `primary`, `outline`, `secondary` |
| `size` | normal | `small`, `large` |
| `class` | - | Zusätzliche CSS-Klassen |

### 2.2 Verwendung

**Automatisch auf Single-Job-Seiten:**
```
[rp_ai_job_match]
```

**Mit spezifischer Job-ID:**
```
[rp_ai_job_match job_id="123"]
```

**Mit Outline-Style (volle Breite):**
```
[rp_ai_job_match style="outline" class="rp-w-full"]
```

### 2.3 Integration in Templates

**templates/archive-job_listing.php** (Job-Liste):
```php
<?php if ( function_exists( 'rp_has_cv_matching' ) && rp_has_cv_matching() ) : ?>
    <div class="rp-relative rp-z-20" @click.stop>
        <?php
        echo do_shortcode(
            sprintf(
                '[rp_ai_job_match job_id="%d" title="%s" style="outline"]',
                get_the_ID(),
                esc_attr__( 'Passe ich zu diesem Job?', 'recruiting-playbook' )
            )
        );
        ?>
    </div>
<?php endif; ?>
```

**templates/single-job_listing.php** (Einzelstelle):
```php
<?php if ( function_exists( 'rp_has_cv_matching' ) && rp_has_cv_matching() ) : ?>
    <div class="rp-mt-4">
        <?php echo do_shortcode( '[rp_ai_job_match style="outline" class="rp-w-full"]' ); ?>
    </div>
<?php endif; ?>
```

### 2.4 Shortcode-Implementierung

**src/Frontend/Shortcodes.php:**

```php
/**
 * KI-Job-Match Button rendern
 *
 * Shortcode: [rp_ai_job_match]
 */
public function renderAiJobMatch( $atts ): string {
    // Feature-Check
    if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
        return $this->renderUpgradePrompt(
            'ai_cv_matching',
            __( 'KI-Job-Match', 'recruiting-playbook' ),
            'AI_ADDON'
        );
    }

    $atts = shortcode_atts(
        [
            'job_id'  => 0,
            'title'   => __( 'Passe ich zu diesem Job?', 'recruiting-playbook' ),
            'display' => 'button',
            'style'   => '',        // primary (default), secondary, outline
            'size'    => '',        // small, large
            'class'   => '',
        ],
        $atts,
        'rp_ai_job_match'
    );

    $job_id = absint( $atts['job_id'] );

    // Wenn keine job_id, versuche aktuelle Stelle zu verwenden
    if ( ! $job_id && is_singular( 'job_listing' ) ) {
        $job_id = get_the_ID();
    }

    // Validieren
    if ( ! $job_id ) {
        return '<!-- KI-Match: Keine Job-ID -->';
    }

    $job = get_post( $job_id );
    if ( ! $job || 'job_listing' !== $job->post_type ) {
        return '<!-- KI-Match: Job nicht gefunden -->';
    }

    // Assets laden
    $this->enqueueMatchModalAssets();

    // Modal im Footer registrieren (nur einmal)
    $this->registerMatchModal();

    // Button-Klassen: wp-element-button für Theme-Farben
    $btn_classes = [ 'wp-element-button' ];

    if ( 'outline' === $atts['style'] ) {
        $btn_classes[] = 'is-style-outline';
    }

    if ( ! empty( $atts['class'] ) ) {
        $btn_classes[] = esc_attr( $atts['class'] );
    }

    $btn_class_string = implode( ' ', $btn_classes );
    $button_text = esc_html( $atts['title'] );

    ob_start();
    ?>
    <div class="rp-plugin rp-ai-job-match" x-data>
        <button
            type="button"
            class="<?php echo esc_attr( $btn_class_string ); ?>"
            @click="$dispatch('open-match-modal', { jobId: <?php echo esc_attr( $job_id ); ?>, jobTitle: '<?php echo esc_js( $job->post_title ); ?>' })"
        >
            <span style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?php echo $button_text; ?></span>
            </span>
        </button>
    </div>
    <?php

    return ob_get_clean();
}
```

### 2.5 Modal-Registrierung

Das Modal-Template wird einmalig im Footer eingefügt:

```php
private function registerMatchModal(): void {
    if ( $this->match_modal_registered ) {
        return;
    }

    $this->match_modal_registered = true;

    add_action(
        'wp_footer',
        function () {
            $template = RP_PLUGIN_DIR . 'templates/partials/match-modal.php';
            if ( file_exists( $template ) ) {
                include $template;
            }
        },
        20
    );
}
```

---

## 3. Alpine.js Match-Modal Komponente

### assets/js/components/match-modal.js

```javascript
/**
 * CV Matching Modal Component
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('matchModal', () => ({
        // State
        isOpen: false,
        jobId: null,
        jobTitle: '',

        // Upload State
        file: null,
        isDragging: false,

        // Process State
        status: 'idle', // idle, uploading, processing, completed, error
        jobRequestId: null,
        pollInterval: null,
        progress: 0,

        // Result
        result: null,
        error: null,

        // Init
        init() {
            // Modal öffnen Event
            window.addEventListener('open-match-modal', (e) => {
                this.open(e.detail.jobId, e.detail.jobTitle);
            });

            // ESC zum Schließen
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },

        // Modal öffnen
        open(jobId, jobTitle) {
            this.reset();
            this.jobId = jobId;
            this.jobTitle = jobTitle;
            this.isOpen = true;
            document.body.classList.add('rp-modal-open');
        },

        // Modal schließen
        close() {
            this.isOpen = false;
            document.body.classList.remove('rp-modal-open');
            this.stopPolling();
        },

        // State zurücksetzen
        reset() {
            this.file = null;
            this.status = 'idle';
            this.jobRequestId = null;
            this.progress = 0;
            this.result = null;
            this.error = null;
            this.stopPolling();
        },

        // Drag & Drop Handler
        handleDragOver(e) {
            e.preventDefault();
            this.isDragging = true;
        },

        handleDragLeave() {
            this.isDragging = false;
        },

        handleDrop(e) {
            e.preventDefault();
            this.isDragging = false;

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        },

        // File Input Handler
        handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        },

        // File validieren
        handleFile(file) {
            // Erlaubte Typen
            const allowedTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!allowedTypes.includes(file.type)) {
                this.error = 'Bitte laden Sie eine PDF, JPG, PNG oder DOCX Datei hoch.';
                return;
            }

            // Max 10MB
            if (file.size > 10 * 1024 * 1024) {
                this.error = 'Die Datei ist zu groß. Maximum: 10 MB.';
                return;
            }

            this.file = file;
            this.error = null;
        },

        // Datei entfernen
        removeFile() {
            this.file = null;
        },

        // Analyse starten
        async startAnalysis() {
            if (!this.file || !this.jobId) return;

            this.status = 'uploading';
            this.error = null;
            this.progress = 10;

            try {
                const formData = new FormData();
                formData.append('file', this.file);
                formData.append('job_id', this.jobId);

                const response = await fetch(rpMatchConfig.endpoints.analyze, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-WP-Nonce': rpMatchConfig.nonce,
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Analyse fehlgeschlagen');
                }

                this.jobRequestId = data.jobId;
                this.status = 'processing';
                this.progress = 30;

                // Polling starten
                this.startPolling();

            } catch (e) {
                this.status = 'error';
                this.error = e.message || 'Ein Fehler ist aufgetreten';
            }
        },

        // Polling für Ergebnis
        startPolling() {
            this.pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(
                        `${rpMatchConfig.endpoints.status}/${this.jobRequestId}`,
                        {
                            headers: {
                                'X-WP-Nonce': rpMatchConfig.nonce,
                            },
                        }
                    );

                    const data = await response.json();

                    // Progress simulieren
                    if (this.progress < 90) {
                        this.progress += 10;
                    }

                    if (data.status === 'completed') {
                        this.result = data.result;
                        this.status = 'completed';
                        this.progress = 100;
                        this.stopPolling();
                    } else if (data.status === 'failed') {
                        this.error = data.error || 'Analyse fehlgeschlagen';
                        this.status = 'error';
                        this.stopPolling();
                    }

                } catch (e) {
                    // Bei Netzwerkfehler weiter versuchen
                    console.error('Polling error:', e);
                }
            }, 2000); // Alle 2 Sekunden

            // Timeout nach 2 Minuten
            setTimeout(() => {
                if (this.status === 'processing') {
                    this.error = 'Die Analyse dauert zu lange. Bitte versuchen Sie es später erneut.';
                    this.status = 'error';
                    this.stopPolling();
                }
            }, 120000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        // Ergebnis-Farbe
        get resultColor() {
            if (!this.result) return '';
            const colors = {
                low: 'rp-result--low',
                medium: 'rp-result--medium',
                high: 'rp-result--high',
            };
            return colors[this.result.category] || '';
        },

        // Ergebnis-Label
        get resultLabel() {
            if (!this.result) return '';
            const labels = {
                low: 'Eher nicht passend',
                medium: 'Teilweise passend',
                high: 'Gute Übereinstimmung',
            };
            return labels[this.result.category] || '';
        },

        // Formatierter Dateiname
        get fileName() {
            if (!this.file) return '';
            const name = this.file.name;
            if (name.length > 30) {
                return name.substring(0, 27) + '...';
            }
            return name;
        },

        // Formatierte Dateigröße
        get fileSize() {
            if (!this.file) return '';
            const kb = this.file.size / 1024;
            if (kb < 1024) {
                return Math.round(kb) + ' KB';
            }
            return (kb / 1024).toFixed(1) + ' MB';
        },
    }));
});
```

---

## 4. Modal Template

### templates/partials/match-modal.php

```php
<?php
/**
 * Match Modal Template
 * Wird einmal am Ende der Seite eingefügt
 */
?>

<div
    x-data="matchModal"
    x-show="isOpen"
    x-cloak
    class="rp-modal-overlay"
    @click.self="close()"
>
    <div class="rp-modal" role="dialog" aria-modal="true">
        <!-- Header -->
        <div class="rp-modal__header">
            <h2 class="rp-modal__title">
                <?php esc_html_e('Bin ich ein Match?', 'recruiting-playbook'); ?>
            </h2>
            <button type="button" class="rp-modal__close" @click="close()">
                <span class="sr-only"><?php esc_html_e('Schließen', 'recruiting-playbook'); ?></span>
                <svg><!-- X Icon --></svg>
            </button>
        </div>

        <!-- Body -->
        <div class="rp-modal__body">

            <!-- Job-Titel -->
            <p class="rp-modal__job-title" x-text="jobTitle"></p>

            <!-- Status: Idle (Upload) -->
            <template x-if="status === 'idle'">
                <div>
                    <!-- Datenschutz-Hinweis -->
                    <div class="rp-info-box">
                        <svg><!-- Info Icon --></svg>
                        <div>
                            <strong><?php esc_html_e('Datenschutz', 'recruiting-playbook'); ?></strong>
                            <p><?php esc_html_e('Ihre persönlichen Daten (Name, Adresse, etc.) werden automatisch entfernt. Nur Ihre beruflichen Qualifikationen werden analysiert. Nach der Analyse werden alle Daten sofort gelöscht.', 'recruiting-playbook'); ?></p>
                        </div>
                    </div>

                    <!-- Upload Zone -->
                    <div
                        class="rp-upload-zone"
                        :class="{ 'rp-upload-zone--dragging': isDragging, 'rp-upload-zone--has-file': file }"
                        @dragover="handleDragOver($event)"
                        @dragleave="handleDragLeave()"
                        @drop="handleDrop($event)"
                    >
                        <template x-if="!file">
                            <div class="rp-upload-zone__empty">
                                <svg class="rp-upload-zone__icon"><!-- Upload Icon --></svg>
                                <p class="rp-upload-zone__text">
                                    <?php esc_html_e('Lebenslauf hier ablegen', 'recruiting-playbook'); ?>
                                </p>
                                <p class="rp-upload-zone__hint">
                                    <?php esc_html_e('oder', 'recruiting-playbook'); ?>
                                </p>
                                <label class="rp-btn rp-btn--secondary rp-btn--sm">
                                    <?php esc_html_e('Datei auswählen', 'recruiting-playbook'); ?>
                                    <input
                                        type="file"
                                        class="sr-only"
                                        accept=".pdf,.jpg,.jpeg,.png,.docx"
                                        @change="handleFileSelect($event)"
                                    >
                                </label>
                                <p class="rp-upload-zone__formats">
                                    <?php esc_html_e('PDF, JPG, PNG oder DOCX (max. 10 MB)', 'recruiting-playbook'); ?>
                                </p>
                            </div>
                        </template>

                        <template x-if="file">
                            <div class="rp-upload-zone__file">
                                <svg class="rp-upload-zone__file-icon"><!-- Document Icon --></svg>
                                <div class="rp-upload-zone__file-info">
                                    <span class="rp-upload-zone__file-name" x-text="fileName"></span>
                                    <span class="rp-upload-zone__file-size" x-text="fileSize"></span>
                                </div>
                                <button type="button" class="rp-upload-zone__remove" @click="removeFile()">
                                    <svg><!-- X Icon --></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Error -->
                    <template x-if="error">
                        <div class="rp-error-box" x-text="error"></div>
                    </template>

                    <!-- Submit Button -->
                    <button
                        type="button"
                        class="rp-btn rp-btn--primary rp-btn--block"
                        :disabled="!file"
                        @click="startAnalysis()"
                    >
                        <?php esc_html_e('Analyse starten', 'recruiting-playbook'); ?>
                    </button>
                </div>
            </template>

            <!-- Status: Uploading / Processing -->
            <template x-if="status === 'uploading' || status === 'processing'">
                <div class="rp-processing">
                    <div class="rp-processing__spinner"></div>
                    <p class="rp-processing__text">
                        <span x-show="status === 'uploading'"><?php esc_html_e('Dokument wird hochgeladen...', 'recruiting-playbook'); ?></span>
                        <span x-show="status === 'processing'"><?php esc_html_e('Analyse läuft...', 'recruiting-playbook'); ?></span>
                    </p>
                    <div class="rp-progress">
                        <div class="rp-progress__bar" :style="{ width: progress + '%' }"></div>
                    </div>
                    <p class="rp-processing__hint">
                        <?php esc_html_e('Dies kann einige Sekunden dauern.', 'recruiting-playbook'); ?>
                    </p>
                </div>
            </template>

            <!-- Status: Completed -->
            <template x-if="status === 'completed' && result">
                <div class="rp-result" :class="resultColor">
                    <div class="rp-result__score">
                        <span class="rp-result__score-value" x-text="result.score + '%'"></span>
                        <div class="rp-result__score-bar">
                            <div class="rp-result__score-fill" :style="{ width: result.score + '%' }"></div>
                        </div>
                    </div>

                    <p class="rp-result__category" x-text="resultLabel"></p>
                    <p class="rp-result__message" x-text="result.message"></p>

                    <div class="rp-result__actions">
                        <a :href="'<?php echo esc_url(home_url('/bewerbung/')); ?>?job=' + jobId" class="rp-btn rp-btn--primary">
                            <?php esc_html_e('Jetzt bewerben', 'recruiting-playbook'); ?>
                        </a>
                        <button type="button" class="rp-btn rp-btn--secondary" @click="reset()">
                            <?php esc_html_e('Neue Analyse', 'recruiting-playbook'); ?>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Status: Error -->
            <template x-if="status === 'error'">
                <div class="rp-error">
                    <svg class="rp-error__icon"><!-- Error Icon --></svg>
                    <p class="rp-error__text" x-text="error"></p>
                    <button type="button" class="rp-btn rp-btn--secondary" @click="reset()">
                        <?php esc_html_e('Erneut versuchen', 'recruiting-playbook'); ?>
                    </button>
                </div>
            </template>

        </div>
    </div>
</div>
```

---

## 5. WordPress REST API Proxy

### src/Api/MatchController.php

```php
<?php

namespace RecruitingPlaybook\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class MatchController {

    private const API_BASE_URL = 'https://api.recruiting-playbook.de/v1';

    /**
     * Routes registrieren
     */
    public function register_routes(): void {
        register_rest_route('recruiting/v1', '/match/analyze', [
            'methods'  => 'POST',
            'callback' => [$this, 'analyze'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('recruiting/v1', '/match/status/(?P<id>[a-f0-9-]+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * POST /match/analyze
     */
    public function analyze(WP_REST_Request $request): WP_REST_Response|WP_Error {
        // Feature-Check
        if (!rp_has_cv_matching()) {
            return new WP_Error(
                'feature_not_available',
                __('CV-Matching erfordert das AI-Addon.', 'recruiting-playbook'),
                ['status' => 403]
            );
        }

        // File prüfen
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error(
                'missing_file',
                __('Bitte laden Sie eine Datei hoch.', 'recruiting-playbook'),
                ['status' => 400]
            );
        }

        $file = $files['file'];
        $job_id = $request->get_param('job_id');

        if (!$job_id) {
            return new WP_Error(
                'missing_job_id',
                __('Job-ID erforderlich.', 'recruiting-playbook'),
                ['status' => 400]
            );
        }

        // Job-Daten laden
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'job_listing') {
            return new WP_Error(
                'invalid_job',
                __('Stelle nicht gefunden.', 'recruiting-playbook'),
                ['status' => 404]
            );
        }

        $job_data = $this->get_job_data($job);

        // Freemius Auth Headers erstellen
        $auth_headers = $this->get_freemius_auth_headers();

        if (is_wp_error($auth_headers)) {
            return $auth_headers;
        }

        // Request an externe API
        $boundary = wp_generate_password(24, false);
        $body = $this->build_multipart_body($boundary, $file, $job_data);

        $response = wp_remote_post(self::API_BASE_URL . '/analysis/upload', [
            'timeout' => 30,
            'headers' => array_merge($auth_headers, [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ]),
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error(
                'api_error',
                __('Analyse-Service nicht erreichbar.', 'recruiting-playbook'),
                ['status' => 503]
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 400) {
            return new WP_Error(
                $body['error'] ?? 'api_error',
                $body['message'] ?? __('Analyse fehlgeschlagen.', 'recruiting-playbook'),
                ['status' => $status_code]
            );
        }

        return new WP_REST_Response($body, 202);
    }

    /**
     * GET /match/status/{id}
     */
    public function get_status(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $analysis_id = $request->get_param('id');

        $auth_headers = $this->get_freemius_auth_headers();

        if (is_wp_error($auth_headers)) {
            return $auth_headers;
        }

        $response = wp_remote_get(self::API_BASE_URL . '/analysis/' . $analysis_id, [
            'timeout' => 10,
            'headers' => $auth_headers,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Service nicht erreichbar.', ['status' => 503]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return new WP_REST_Response($body);
    }

    /**
     * Freemius Auth Headers erstellen
     *
     * Die API verwendet Freemius-basierte Authentifizierung:
     * - X-Freemius-Install-Id: Installation ID
     * - X-Freemius-Timestamp: ISO Timestamp
     * - X-Freemius-Signature: HMAC-SHA256(secret_key + '|' + timestamp)
     * - X-Site-Url: WordPress Site URL
     *
     * @return array|WP_Error Auth-Headers oder Fehler
     */
    private function get_freemius_auth_headers(): array|WP_Error {
        // Freemius SDK prüfen
        if (!function_exists('rp_fs')) {
            return new WP_Error(
                'freemius_not_available',
                __('Freemius SDK nicht verfügbar.', 'recruiting-playbook'),
                ['status' => 500]
            );
        }

        $fs = rp_fs();

        // Installation ID holen
        $install_id = $fs->get_site()->id ?? null;
        $secret_key = $fs->get_site()->secret_key ?? null;

        if (!$install_id || !$secret_key) {
            return new WP_Error(
                'no_freemius_install',
                __('Keine gültige Freemius-Installation gefunden.', 'recruiting-playbook'),
                ['status' => 403]
            );
        }

        // Timestamp und Signatur erstellen
        $timestamp = gmdate('c'); // ISO 8601 Format
        $signature = hash('sha256', $secret_key . '|' . $timestamp);

        return [
            'X-Freemius-Install-Id' => (string) $install_id,
            'X-Freemius-Timestamp'  => $timestamp,
            'X-Freemius-Signature'  => $signature,
            'X-Site-Url'            => site_url(),
        ];
    }

    /**
     * Job-Daten für API aufbereiten
     */
    private function get_job_data(\WP_Post $job): array {
        $requirements = get_post_meta($job->ID, '_rp_requirements', true) ?: [];
        $nice_to_have = get_post_meta($job->ID, '_rp_nice_to_have', true) ?: [];

        return [
            'title' => $job->post_title,
            'description' => wp_strip_all_tags($job->post_content),
            'requirements' => is_array($requirements) ? $requirements : [$requirements],
            'niceToHave' => is_array($nice_to_have) ? $nice_to_have : [],
        ];
    }

    /**
     * Multipart Body bauen
     */
    private function build_multipart_body(string $boundary, array $file, array $job_data): string {
        $body = '';

        // File
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file['name']}\"\r\n";
        $body .= "Content-Type: {$file['type']}\r\n\r\n";
        $body .= file_get_contents($file['tmp_name']) . "\r\n";

        // Job Data
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"jobData\"\r\n";
        $body .= "Content-Type: application/json\r\n\r\n";
        $body .= json_encode($job_data) . "\r\n";

        $body .= "--{$boundary}--\r\n";

        return $body;
    }
}
```

---

## 6. Scripts & Styles einbinden

### src/Frontend/Assets.php

```php
<?php

namespace RecruitingPlaybook\Frontend;

class Assets {

    public function enqueue_match_modal(): void {
        // Nur wenn Feature aktiv
        if (!rp_has_cv_matching()) {
            return;
        }

        // Script
        wp_enqueue_script(
            'rp-match-modal',
            RP_PLUGIN_URL . 'assets/js/components/match-modal.js',
            ['alpine'],
            RP_VERSION,
            true
        );

        // Konfiguration
        wp_localize_script('rp-match-modal', 'rpMatchConfig', [
            'endpoints' => [
                'analyze' => rest_url('recruiting/v1/match/analyze'),
                'status' => rest_url('recruiting/v1/match/status'),
            ],
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'uploading' => __('Wird hochgeladen...', 'recruiting-playbook'),
                'processing' => __('Analyse läuft...', 'recruiting-playbook'),
                'error' => __('Ein Fehler ist aufgetreten', 'recruiting-playbook'),
            ],
        ]);

        // Styles
        wp_enqueue_style(
            'rp-match-modal',
            RP_PLUGIN_URL . 'assets/css/match-modal.css',
            [],
            RP_VERSION
        );
    }
}
```

---

## 7. CSS Styles

### assets/css/match-modal.css (Auszug)

```css
/* Result Colors */
.rp-result--low {
    --rp-result-color: var(--rp-error);
    --rp-result-bg: var(--rp-error-light);
}

.rp-result--medium {
    --rp-result-color: var(--rp-warning);
    --rp-result-bg: var(--rp-warning-light);
}

.rp-result--high {
    --rp-result-color: var(--rp-success);
    --rp-result-bg: var(--rp-success-light);
}

/* Score Display */
.rp-result__score {
    text-align: center;
    margin-bottom: var(--rp-spacing-lg);
}

.rp-result__score-value {
    font-size: var(--rp-font-size-3xl);
    font-weight: 700;
    color: var(--rp-result-color);
}

.rp-result__score-bar {
    height: 8px;
    background: var(--rp-border);
    border-radius: var(--rp-border-radius-full);
    overflow: hidden;
    margin-top: var(--rp-spacing-sm);
}

.rp-result__score-fill {
    height: 100%;
    background: var(--rp-result-color);
    border-radius: var(--rp-border-radius-full);
    transition: width 0.5s ease;
}

.rp-result__category {
    font-size: var(--rp-font-size-lg);
    font-weight: 600;
    color: var(--rp-result-color);
    text-align: center;
}

.rp-result__message {
    text-align: center;
    color: var(--rp-text-muted);
    margin: var(--rp-spacing-md) 0 var(--rp-spacing-xl);
}
```

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ **Shortcode `[rp_ai_job_match]`** für theme-unabhängige Integration
- ✅ Match-Button in Job-Liste und Einzelstellen-Seite
- ✅ Upload-Modal mit Drag & Drop
- ✅ Async Verarbeitung mit Progress-Anzeige
- ✅ Ergebnis-Darstellung mit Score und Kategorie
- ✅ WordPress REST API Proxy
- ✅ Feature-Gating (nur AI_ADDON/BUNDLE)

---

## Gesamtergebnis

Nach Abschluss aller 4 Phasen habt ihr ein vollständiges KI-Matching Feature:

1. **Infrastruktur** (Cloudflare Worker, Datenbank)
2. **Anonymisierung** (Presidio Service)
3. **Analyse** (Claude API Integration)
4. **Frontend** (WordPress UI)

```
Bewerber → Upload → Anonymisierung → Claude → Score → Bewerber
                         ↓
                   PII bleibt lokal
                   (DSGVO-konform)
```

---

*Erstellt: Januar 2025*
*Aktualisiert: Januar 2026* (Freemius Auth, Feature Flags, Shortcode [rp_ai_job_match])
