# KI-Job-Finder: Phase 2 - Frontend

> **Voraussetzung:** [Phase 1 abgeschlossen](./ki-job-finder-phase-1-api.md)

## Ziel dieser Phase

Frontend-Komponenten für den Job-Finder:
- Alpine.js Komponente
- HTML Template mit Upload-Zone & Ergebnis-Liste
- CSS Styles für Match-Cards

---

## 1. Alpine.js Komponente

### 1.1 Neue Datei erstellen

**Datei:** `plugin/assets/src/js/components/job-finder.js`

```javascript
/**
 * KI-Job-Finder Alpine.js Component
 *
 * Ermöglicht Multi-Job-Matching: CV gegen alle aktiven Jobs analysieren.
 */

const jobFinderComponent = () => ({
    // ===== STATE =====
    file: null,
    fileName: '',
    fileSize: '',
    isDragging: false,
    status: 'idle', // idle | uploading | processing | completed | error
    jobRequestId: null,
    pollInterval: null,
    progress: 0,

    // Ergebnisse
    result: null,
    error: null,

    // Config (von wp_localize_script)
    config: window.rpJobFinderConfig || {},

    // ===== INIT =====
    init() {
        console.log('[RP] Job-Finder Component initialized');
    },

    // ===== FILE HANDLING =====
    handleDragOver(e) {
        e.preventDefault();
        this.isDragging = true;
    },

    handleDragLeave(e) {
        e.preventDefault();
        this.isDragging = false;
    },

    handleDrop(e) {
        e.preventDefault();
        this.isDragging = false;

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    },

    handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    },

    processFile(file) {
        // Validierung
        const allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!allowedTypes.includes(file.type)) {
            this.error = this.config.i18n?.invalidFileType ||
                'Bitte laden Sie eine PDF, JPG, PNG oder DOCX Datei hoch.';
            this.status = 'error';
            return;
        }

        // Max 10 MB
        if (file.size > 10 * 1024 * 1024) {
            this.error = this.config.i18n?.fileTooLarge ||
                'Die Datei ist zu groß. Maximum: 10 MB.';
            this.status = 'error';
            return;
        }

        this.file = file;
        this.fileName = file.name;
        this.fileSize = this.formatFileSize(file.size);
        this.error = null;
    },

    removeFile() {
        this.file = null;
        this.fileName = '';
        this.fileSize = '';
    },

    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    },

    // ===== ANALYSIS =====
    async startAnalysis() {
        if (!this.file) return;

        this.status = 'uploading';
        this.progress = 10;
        this.error = null;

        try {
            const formData = new FormData();
            formData.append('file', this.file);
            formData.append('limit', this.config.maxMatches || 5);

            const response = await fetch(this.config.endpoints.analyze, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-WP-Nonce': this.config.nonce
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Analyse fehlgeschlagen');
            }

            this.jobRequestId = data.job_id;
            this.status = 'processing';
            this.progress = 30;

            this.startPolling();

        } catch (e) {
            console.error('[RP] Job-Finder Error:', e);
            this.status = 'error';
            this.error = e.message;
        }
    },

    // ===== POLLING =====
    startPolling() {
        this.pollInterval = setInterval(() => this.checkStatus(), 2000);

        // Simulierter Progress
        this.simulateProgress();
    },

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },

    async checkStatus() {
        if (!this.jobRequestId) return;

        try {
            const response = await fetch(
                `${this.config.endpoints.status}/${this.jobRequestId}`,
                {
                    headers: {
                        'X-WP-Nonce': this.config.nonce
                    }
                }
            );

            const data = await response.json();

            if (data.status === 'completed') {
                this.stopPolling();
                // Parse result if it's a string
                if (typeof data.result_message === 'string') {
                    this.result = JSON.parse(data.result_message);
                } else {
                    this.result = data.result || data;
                }
                this.status = 'completed';
                this.progress = 100;
            } else if (data.status === 'failed') {
                this.stopPolling();
                this.status = 'error';
                this.error = data.error_message || 'Analyse fehlgeschlagen';
            }

        } catch (e) {
            console.error('[RP] Status Check Error:', e);
        }
    },

    simulateProgress() {
        const steps = [40, 55, 70, 85];
        let i = 0;

        const interval = setInterval(() => {
            if (this.status !== 'processing' || i >= steps.length) {
                clearInterval(interval);
                return;
            }
            this.progress = steps[i];
            i++;
        }, 3000);
    },

    // ===== HELPERS =====
    getCategoryClass(category) {
        const classes = {
            high: 'rp-job-finder-match--high',
            medium: 'rp-job-finder-match--medium',
            low: 'rp-job-finder-match--low'
        };
        return classes[category] || '';
    },

    getCategoryLabel(category) {
        const labels = {
            high: this.config.i18n?.resultHigh || 'Gute Übereinstimmung',
            medium: this.config.i18n?.resultMedium || 'Teilweise passend',
            low: this.config.i18n?.resultLow || 'Weniger passend'
        };
        return labels[category] || '';
    },

    getScoreClass(category) {
        const classes = {
            high: 'rp-text-success',
            medium: 'rp-text-warning',
            low: 'rp-text-error'
        };
        return classes[category] || '';
    },

    // ===== COMPUTED =====
    get hasResults() {
        return this.status === 'completed' &&
               this.result?.matches?.length > 0;
    },

    get noMatches() {
        return this.status === 'completed' &&
               (!this.result?.matches || this.result.matches.length === 0);
    },

    get statusMessage() {
        const messages = {
            uploading: this.config.i18n?.uploading || 'Dokument wird hochgeladen...',
            processing: this.config.i18n?.analyzing || 'Analysiere Lebenslauf gegen alle Stellen...'
        };
        return messages[this.status] || '';
    },

    // ===== ACTIONS =====
    reset() {
        this.file = null;
        this.fileName = '';
        this.fileSize = '';
        this.status = 'idle';
        this.result = null;
        this.error = null;
        this.progress = 0;
        this.jobRequestId = null;
        this.stopPolling();
    }
});

// Registrierung
function registerJobFinderComponent() {
    if (typeof Alpine !== 'undefined' && Alpine.data) {
        console.log('[RP] Registering jobFinder component');
        Alpine.data('jobFinder', jobFinderComponent);
    }
}

if (typeof Alpine !== 'undefined') {
    registerJobFinderComponent();
} else {
    document.addEventListener('alpine:init', registerJobFinderComponent);
}
```

---

## 2. CSS Styles

### 2.1 Neue Datei erstellen

**Datei:** `plugin/assets/src/css/job-finder.css`

```css
/**
 * KI-Job-Finder Styles
 *
 * Basiert auf match-modal.css, erweitert um Ergebnis-Liste
 */

/* ===== CONTAINER ===== */
.rp-job-finder {
    @apply rp-bg-white rp-rounded-xl rp-shadow-lg rp-border rp-border-gray-200;
    @apply rp-p-6 sm:rp-p-8 md:rp-p-10;
    @apply rp-max-w-3xl rp-mx-auto;
}

/* ===== HEADER ===== */
.rp-job-finder__header {
    @apply rp-text-center rp-mb-8;
}

.rp-job-finder__title {
    @apply rp-text-2xl sm:rp-text-3xl rp-font-bold rp-text-gray-900 rp-mb-2;
}

.rp-job-finder__subtitle {
    @apply rp-text-gray-600 rp-text-base;
}

.rp-job-finder__job-count {
    @apply rp-text-sm rp-text-gray-500 rp-mt-2;
}

/* ===== UPLOAD ZONE (wiederverwendet von match-modal) ===== */
.rp-job-finder .rp-match-upload-zone {
    @apply rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-xl;
    @apply rp-p-8 rp-text-center rp-cursor-pointer;
    @apply rp-transition-all rp-duration-200;
}

.rp-job-finder .rp-match-upload-zone:hover,
.rp-job-finder .rp-match-upload-zone--dragging {
    @apply rp-border-primary rp-bg-primary/5;
}

/* ===== INFO BOX ===== */
.rp-job-finder .rp-match-info-box {
    @apply rp-bg-blue-50 rp-border rp-border-blue-200 rp-rounded-lg;
    @apply rp-p-4 rp-flex rp-gap-3;
}

.rp-job-finder .rp-match-info-box svg {
    @apply rp-w-5 rp-h-5 rp-text-blue-500 rp-flex-shrink-0 rp-mt-0.5;
}

/* ===== SUBMIT BUTTON ===== */
.rp-job-finder__submit {
    @apply rp-w-full rp-mt-6 rp-py-3 rp-px-6;
    @apply rp-bg-primary rp-text-white rp-font-semibold rp-rounded-lg;
    @apply rp-transition-all rp-duration-200;
    @apply hover:rp-bg-primary-dark;
    @apply disabled:rp-opacity-50 disabled:rp-cursor-not-allowed;
}

/* ===== PROCESSING ===== */
.rp-job-finder__processing {
    @apply rp-text-center rp-py-12;
}

.rp-job-finder__spinner {
    @apply rp-w-12 rp-h-12 rp-mx-auto rp-mb-4;
    @apply rp-border-4 rp-border-gray-200 rp-border-t-primary rp-rounded-full;
    animation: rp-spin 1s linear infinite;
}

@keyframes rp-spin {
    to { transform: rotate(360deg); }
}

.rp-job-finder__progress {
    @apply rp-w-full rp-max-w-xs rp-mx-auto rp-mt-4;
    @apply rp-bg-gray-200 rp-rounded-full rp-h-2 rp-overflow-hidden;
}

.rp-job-finder__progress-bar {
    @apply rp-bg-primary rp-h-full rp-transition-all rp-duration-500;
}

/* ===== RESULTS ===== */
.rp-job-finder-results {
    @apply rp-mt-6;
}

.rp-job-finder-results__header {
    @apply rp-flex rp-items-center rp-justify-between rp-mb-6;
}

.rp-job-finder-results__title {
    @apply rp-text-xl rp-font-semibold rp-text-gray-900;
}

.rp-job-finder-results__count {
    @apply rp-text-sm rp-text-gray-500;
}

/* ===== PROFILE CARD ===== */
.rp-job-finder-profile {
    @apply rp-bg-gray-50 rp-rounded-lg rp-p-4 rp-mb-6;
}

.rp-job-finder-profile__title {
    @apply rp-font-semibold rp-text-gray-900 rp-mb-3;
}

.rp-job-finder-profile__skills {
    @apply rp-flex rp-flex-wrap rp-gap-2;
}

.rp-job-finder-profile__skill {
    @apply rp-px-3 rp-py-1 rp-bg-white rp-border rp-border-gray-200;
    @apply rp-rounded-full rp-text-sm rp-text-gray-700;
}

/* ===== MATCH CARDS ===== */
.rp-job-finder-matches {
    @apply rp-space-y-4;
}

.rp-job-finder-match {
    @apply rp-border rp-border-gray-200 rp-rounded-xl rp-p-5;
    @apply rp-transition-all rp-duration-200;
    @apply hover:rp-shadow-md;
}

.rp-job-finder-match--high {
    @apply rp-border-l-4 rp-border-l-green-500;
}

.rp-job-finder-match--medium {
    @apply rp-border-l-4 rp-border-l-yellow-500;
}

.rp-job-finder-match--low {
    @apply rp-border-l-4 rp-border-l-gray-400;
}

.rp-job-finder-match__header {
    @apply rp-flex rp-items-start rp-justify-between rp-gap-4;
}

.rp-job-finder-match__title {
    @apply rp-font-semibold rp-text-lg rp-text-gray-900;
}

.rp-job-finder-match__category {
    @apply rp-text-sm rp-text-gray-500;
}

.rp-job-finder-match__score {
    @apply rp-text-2xl rp-font-bold;
}

.rp-job-finder-match__message {
    @apply rp-text-gray-600 rp-text-sm rp-mt-2;
}

/* ===== SKILLS BADGES ===== */
.rp-job-finder-match__skills {
    @apply rp-mt-4 rp-flex rp-flex-wrap rp-gap-2;
}

.rp-job-finder-match__skill {
    @apply rp-px-2.5 rp-py-1 rp-text-xs rp-rounded-full;
}

.rp-job-finder-match__skill--matched {
    @apply rp-bg-green-50 rp-text-green-700;
}

.rp-job-finder-match__skill--missing {
    @apply rp-bg-gray-100 rp-text-gray-500 rp-line-through;
}

/* ===== ACTION BUTTONS ===== */
.rp-job-finder-match__actions {
    @apply rp-mt-4 rp-flex rp-flex-wrap rp-gap-3;
}

.rp-job-finder-match__btn {
    @apply rp-px-4 rp-py-2 rp-text-sm rp-font-medium rp-rounded-lg;
    @apply rp-transition-colors rp-duration-200;
}

.rp-job-finder-match__btn--primary {
    @apply rp-bg-primary rp-text-white hover:rp-bg-primary-dark;
}

.rp-job-finder-match__btn--secondary {
    @apply rp-bg-white rp-border rp-border-gray-300 rp-text-gray-700;
    @apply hover:rp-bg-gray-50;
}

/* ===== NO MATCHES ===== */
.rp-job-finder__no-matches {
    @apply rp-text-center rp-py-12;
}

.rp-job-finder__no-matches-icon {
    @apply rp-w-16 rp-h-16 rp-mx-auto rp-text-gray-300 rp-mb-4;
}

.rp-job-finder__no-matches-text {
    @apply rp-text-gray-600 rp-mb-4;
}

/* ===== ERROR ===== */
.rp-job-finder__error {
    @apply rp-bg-red-50 rp-border rp-border-red-200 rp-rounded-lg;
    @apply rp-p-4 rp-text-center;
}

.rp-job-finder__error-text {
    @apply rp-text-red-600 rp-font-medium;
}

/* ===== RESET BUTTON ===== */
.rp-job-finder__reset {
    @apply rp-text-center rp-mt-8;
}

/* ===== COLOR UTILITIES ===== */
.rp-text-success { @apply rp-text-green-600; }
.rp-text-warning { @apply rp-text-yellow-600; }
.rp-text-error { @apply rp-text-red-600; }
```

---

## 3. HTML Template

### 3.1 Neue Datei erstellen

**Datei:** `plugin/templates/partials/job-finder.php`

```php
<?php
/**
 * Job-Finder Template
 *
 * @var array  $atts         Shortcode Attribute
 * @var int    $job_count    Anzahl aktiver Jobs
 * @var bool   $show_profile Profil anzeigen
 * @var bool   $show_skills  Skills anzeigen
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="rp-plugin">
    <div class="rp-job-finder" x-data="jobFinder" x-cloak>

        <!-- Header -->
        <div class="rp-job-finder__header">
            <h2 class="rp-job-finder__title">
                <?php echo esc_html( $atts['title'] ); ?>
            </h2>
            <p class="rp-job-finder__subtitle">
                <?php echo esc_html( $atts['subtitle'] ); ?>
            </p>
            <p class="rp-job-finder__job-count">
                <?php
                printf(
                    esc_html( _n(
                        '%d offene Stelle wird analysiert',
                        '%d offene Stellen werden analysiert',
                        $job_count,
                        'recruiting-playbook'
                    ) ),
                    $job_count
                );
                ?>
            </p>
        </div>

        <!-- ===== STATUS: IDLE (Upload) ===== -->
        <template x-if="status === 'idle'">
            <div>
                <!-- Datenschutz-Hinweis -->
                <div class="rp-match-info-box rp-mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <div>
                        <strong><?php esc_html_e( 'Datenschutz', 'recruiting-playbook' ); ?></strong>
                        <p class="rp-text-sm rp-text-gray-600 rp-mt-1">
                            <?php esc_html_e( 'Ihre persönlichen Daten werden automatisch entfernt. Nur Ihre beruflichen Qualifikationen werden analysiert.', 'recruiting-playbook' ); ?>
                        </p>
                    </div>
                </div>

                <!-- Upload Zone -->
                <div
                    class="rp-match-upload-zone"
                    :class="{ 'rp-match-upload-zone--dragging': isDragging }"
                    @dragover.prevent="handleDragOver"
                    @dragleave.prevent="handleDragLeave"
                    @drop.prevent="handleDrop"
                    @click="$refs.fileInput.click()"
                >
                    <template x-if="!file">
                        <div>
                            <svg class="rp-w-12 rp-h-12 rp-mx-auto rp-text-gray-400 rp-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="rp-text-gray-600 rp-font-medium">
                                <?php esc_html_e( 'Lebenslauf hier ablegen', 'recruiting-playbook' ); ?>
                            </p>
                            <p class="rp-text-sm rp-text-gray-500 rp-mt-1">
                                <?php esc_html_e( 'oder klicken zum Auswählen', 'recruiting-playbook' ); ?>
                            </p>
                            <p class="rp-text-xs rp-text-gray-400 rp-mt-2">
                                PDF, JPG, PNG, DOCX (max. 10 MB)
                            </p>
                        </div>
                    </template>

                    <template x-if="file">
                        <div class="rp-flex rp-items-center rp-justify-center rp-gap-3">
                            <svg class="rp-w-8 rp-h-8 rp-text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="rp-text-left">
                                <p class="rp-font-medium rp-text-gray-900" x-text="fileName"></p>
                                <p class="rp-text-sm rp-text-gray-500" x-text="fileSize"></p>
                            </div>
                            <button
                                type="button"
                                class="rp-ml-2 rp-text-gray-400 hover:rp-text-red-500"
                                @click.stop="removeFile()"
                            >
                                <svg class="rp-w-5 rp-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>

                    <input
                        type="file"
                        x-ref="fileInput"
                        class="rp-hidden"
                        accept=".pdf,.jpg,.jpeg,.png,.docx"
                        @change="handleFileSelect"
                    >
                </div>

                <!-- Submit Button -->
                <button
                    type="button"
                    class="rp-job-finder__submit"
                    :disabled="!file"
                    @click="startAnalysis()"
                >
                    <?php esc_html_e( 'Passende Jobs finden', 'recruiting-playbook' ); ?>
                </button>
            </div>
        </template>

        <!-- ===== STATUS: PROCESSING ===== -->
        <template x-if="status === 'uploading' || status === 'processing'">
            <div class="rp-job-finder__processing">
                <div class="rp-job-finder__spinner"></div>
                <p class="rp-text-gray-600 rp-font-medium" x-text="statusMessage"></p>
                <div class="rp-job-finder__progress">
                    <div class="rp-job-finder__progress-bar" :style="'width: ' + progress + '%'"></div>
                </div>
                <p class="rp-text-sm rp-text-gray-500 rp-mt-2">
                    <?php
                    printf(
                        esc_html__( 'Analysiere %d Stellen...', 'recruiting-playbook' ),
                        $job_count
                    );
                    ?>
                </p>
            </div>
        </template>

        <!-- ===== STATUS: COMPLETED (mit Ergebnissen) ===== -->
        <template x-if="hasResults">
            <div class="rp-job-finder-results">

                <!-- Profil-Zusammenfassung -->
                <?php if ( $show_profile ) : ?>
                <template x-if="result.profile">
                    <div class="rp-job-finder-profile">
                        <h3 class="rp-job-finder-profile__title">
                            <?php esc_html_e( 'Erkanntes Profil', 'recruiting-playbook' ); ?>
                        </h3>
                        <div class="rp-job-finder-profile__skills">
                            <template x-for="skill in result.profile.extractedSkills" :key="skill">
                                <span class="rp-job-finder-profile__skill" x-text="skill"></span>
                            </template>
                        </div>
                        <template x-if="result.profile.experienceYears">
                            <p class="rp-text-sm rp-text-gray-600 rp-mt-2">
                                <span x-text="result.profile.experienceYears"></span>
                                <?php esc_html_e( 'Jahre Berufserfahrung', 'recruiting-playbook' ); ?>
                            </p>
                        </template>
                    </div>
                </template>
                <?php endif; ?>

                <!-- Results Header -->
                <div class="rp-job-finder-results__header">
                    <h3 class="rp-job-finder-results__title">
                        <?php esc_html_e( 'Deine Top-Matches', 'recruiting-playbook' ); ?>
                    </h3>
                    <span class="rp-job-finder-results__count">
                        <span x-text="result.matches.length"></span> von
                        <span x-text="result.totalJobsAnalyzed"></span> Stellen
                    </span>
                </div>

                <!-- Match Cards -->
                <div class="rp-job-finder-matches">
                    <template x-for="(match, index) in result.matches" :key="match.jobId">
                        <div class="rp-job-finder-match" :class="getCategoryClass(match.category)">

                            <!-- Header -->
                            <div class="rp-job-finder-match__header">
                                <div>
                                    <h4 class="rp-job-finder-match__title" x-text="match.jobTitle"></h4>
                                    <span class="rp-job-finder-match__category" x-text="getCategoryLabel(match.category)"></span>
                                </div>
                                <div class="rp-job-finder-match__score" :class="getScoreClass(match.category)">
                                    <span x-text="match.score + '%'"></span>
                                </div>
                            </div>

                            <!-- Message -->
                            <p class="rp-job-finder-match__message" x-text="match.message"></p>

                            <!-- Skills -->
                            <?php if ( $show_skills ) : ?>
                            <div class="rp-job-finder-match__skills">
                                <template x-for="skill in match.matchedSkills.slice(0, 5)" :key="'matched-' + skill">
                                    <span class="rp-job-finder-match__skill rp-job-finder-match__skill--matched" x-text="skill"></span>
                                </template>
                                <template x-for="skill in match.missingSkills.slice(0, 3)" :key="'missing-' + skill">
                                    <span class="rp-job-finder-match__skill rp-job-finder-match__skill--missing" x-text="skill"></span>
                                </template>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="rp-job-finder-match__actions">
                                <a
                                    :href="match.jobUrl"
                                    class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary"
                                >
                                    <?php esc_html_e( 'Stelle ansehen', 'recruiting-playbook' ); ?>
                                </a>
                                <a
                                    :href="match.applyUrl"
                                    class="rp-job-finder-match__btn rp-job-finder-match__btn--primary"
                                >
                                    <?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Reset -->
                <div class="rp-job-finder__reset">
                    <button
                        type="button"
                        class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary"
                        @click="reset()"
                    >
                        <?php esc_html_e( 'Neue Analyse starten', 'recruiting-playbook' ); ?>
                    </button>
                </div>
            </div>
        </template>

        <!-- ===== STATUS: NO MATCHES ===== -->
        <template x-if="noMatches">
            <div class="rp-job-finder__no-matches">
                <svg class="rp-job-finder__no-matches-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <p class="rp-job-finder__no-matches-text">
                    <?php esc_html_e( 'Leider haben wir keine passenden Stellen gefunden.', 'recruiting-playbook' ); ?>
                </p>
                <a
                    href="<?php echo esc_url( get_post_type_archive_link( 'job_listing' ) ); ?>"
                    class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary"
                >
                    <?php esc_html_e( 'Alle Stellen ansehen', 'recruiting-playbook' ); ?>
                </a>
            </div>
        </template>

        <!-- ===== STATUS: ERROR ===== -->
        <template x-if="status === 'error'">
            <div class="rp-job-finder__error">
                <p class="rp-job-finder__error-text" x-text="error"></p>
                <button
                    type="button"
                    class="rp-job-finder-match__btn rp-job-finder-match__btn--secondary rp-mt-4"
                    @click="reset()"
                >
                    <?php esc_html_e( 'Erneut versuchen', 'recruiting-playbook' ); ?>
                </button>
            </div>
        </template>

    </div>
</div>
```

---

## Checkliste Phase 2

- [ ] `job-finder.js`: Alpine.js Komponente erstellen
- [ ] `job-finder.css`: Styles erstellen
- [ ] `job-finder.php`: Template erstellen
- [ ] Tailwind Build ausführen (`npm run build`)
- [ ] Visueller Test im Browser

---

## Nächste Phase

→ [Phase 3: Shortcode & Integration](./ki-job-finder-phase-3-shortcode.md)

---

*Erstellt: Januar 2026*
