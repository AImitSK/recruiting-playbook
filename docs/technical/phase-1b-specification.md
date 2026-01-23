# Phase 1B: Technische Spezifikation

> **Woche 3-4: Bewerbungs-Flow**
> Bewerber können sich erfolgreich bewerben, HR wird benachrichtigt

---

## Inhaltsverzeichnis

1. [Frontend-Templates](#1-frontend-templates)
2. [Bewerbungsformular](#2-bewerbungsformular)
3. [REST API Endpoint](#3-rest-api-endpoint)
4. [Datei-Upload & Dokumentenverwaltung](#4-datei-upload--dokumentenverwaltung)
5. [Spam-Schutz](#5-spam-schutz)
6. [DSGVO-Compliance](#6-dsgvo-compliance)
7. [E-Mail-Benachrichtigungen](#7-e-mail-benachrichtigungen)
8. [SMTP-Konfigurationsprüfung](#8-smtp-konfigurationsprüfung)
9. [Einstellungen-Seite](#9-einstellungen-seite)
10. [CSS Custom Properties](#10-css-custom-properties)

---

## 1. Frontend-Templates

### Template-Hierarchie

```
Theme kann überschreiben:
└── theme/recruiting-playbook/
    ├── archive-job_listing.php
    └── single-job_listing.php

Plugin-Fallback:
└── plugin/templates/
    ├── archive-job_listing.php
    └── single-job_listing.php
```

### archive-job_listing.php

```php
<?php
/**
 * Template für Job-Archiv
 */

get_header();
?>

<main class="rp-jobs-archive">
    <div class="rp-container rp-mx-auto rp-px-4 sm:rp-px-6 lg:rp-px-8 rp-py-8 sm:rp-py-12">

        <!-- Header -->
        <header class="rp-text-center rp-mb-8 sm:rp-mt-16">
            <h1 class="rp-text-4xl sm:rp-text-5xl rp-font-bold rp-tracking-tight rp-text-gray-900 rp-leading-8">
                <?php post_type_archive_title(); ?>
            </h1>
            <p class="rp-mt-5 rp-text-xl rp-text-gray-600 rp-leading-relaxed">
                <?php echo esc_html( get_theme_mod( 'rp_archive_description', __( 'Entdecken Sie unsere offenen Stellen', 'recruiting-playbook' ) ) ); ?>
            </p>
        </header>

        <!-- Job Grid -->
        <?php if ( have_posts() ) : ?>
            <div class="rp-grid rp-grid-cols-1 md:rp-grid-cols-2 lg:rp-grid-cols-3 rp-gap-6">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php get_template_part( 'templates/content', 'job-card' ); ?>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="rp-mt-12">
                <?php the_posts_pagination(); ?>
            </div>
        <?php else : ?>
            <p class="rp-text-center rp-text-gray-500">
                <?php esc_html_e( 'Aktuell keine offenen Stellen.', 'recruiting-playbook' ); ?>
            </p>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
```

### single-job_listing.php

```php
<?php
/**
 * Template für einzelne Stelle
 */

get_header();

while ( have_posts() ) :
    the_post();

    // Meta-Daten laden
    $salary_min  = get_post_meta( get_the_ID(), '_rp_salary_min', true );
    $salary_max  = get_post_meta( get_the_ID(), '_rp_salary_max', true );
    $hide_salary = get_post_meta( get_the_ID(), '_rp_hide_salary', true );
    $deadline    = get_post_meta( get_the_ID(), '_rp_application_deadline', true );
    $contact     = get_post_meta( get_the_ID(), '_rp_contact_person', true );
    $remote      = get_post_meta( get_the_ID(), '_rp_remote_option', true );

    // Taxonomien
    $locations        = get_the_terms( get_the_ID(), 'job_location' );
    $employment_types = get_the_terms( get_the_ID(), 'employment_type' );
    $categories       = get_the_terms( get_the_ID(), 'job_category' );
?>

<main class="rp-single-job">
    <article class="rp-container rp-mx-auto rp-px-4 sm:rp-px-6 lg:rp-px-8 rp-py-8">

        <!-- Header -->
        <header class="rp-mb-8">
            <h1 class="rp-text-4xl rp-font-bold rp-text-gray-900">
                <?php the_title(); ?>
            </h1>

            <!-- Meta Badges -->
            <div class="rp-flex rp-flex-wrap rp-gap-3 rp-mt-4">
                <?php if ( $locations ) : ?>
                    <span class="rp-badge">
                        <svg><!-- Location Icon --></svg>
                        <?php echo esc_html( $locations[0]->name ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( $employment_types ) : ?>
                    <span class="rp-badge">
                        <?php echo esc_html( $employment_types[0]->name ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( ! $hide_salary && ( $salary_min || $salary_max ) ) : ?>
                    <span class="rp-badge">
                        <?php echo esc_html( rp_format_salary( $salary_min, $salary_max ) ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Content Grid -->
        <div class="rp-grid lg:rp-grid-cols-3 rp-gap-8">

            <!-- Main Content -->
            <div class="lg:rp-col-span-2">
                <div class="rp-prose rp-max-w-none">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Sidebar mit Bewerbungsformular -->
            <aside class="lg:rp-sticky lg:rp-top-8">
                <div class="rp-bg-white rp-rounded-xl rp-p-6 rp-space-y-6">
                    <h2 class="rp-text-2xl rp-font-semibold">
                        <?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
                    </h2>

                    <!-- Alpine.js Bewerbungsformular -->
                    <div x-data="applicationForm(<?php echo get_the_ID(); ?>)">
                        <!-- Formular hier -->
                    </div>
                </div>
            </aside>

        </div>
    </article>
</main>

<?php
endwhile;
get_footer();
?>
```

---

## 2. Bewerbungsformular

### Alpine.js Komponente: applicationForm

**Datei:** `assets/src/js/application-form.js`

```javascript
/**
 * Alpine.js Application Form Component
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('applicationForm', (jobId) => ({
        // Form State
        jobId: jobId,
        step: 1,
        loading: false,
        success: false,
        error: null,

        // Form Data
        formData: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            cover_letter: '',
        },

        // File Upload
        files: [],
        uploadProgress: 0,
        dragover: false,

        // Validation
        errors: {},
        touched: {},

        // Spam Protection
        formLoadTime: Date.now(),
        honeypot: '',

        // Methods
        init() {
            this.formLoadTime = Date.now();
        },

        validateField(field) {
            this.errors[field] = null;
            const value = this.formData[field];

            switch (field) {
                case 'first_name':
                case 'last_name':
                    if (!value?.trim()) {
                        this.errors[field] = rpForm.i18n.required;
                    }
                    break;

                case 'email':
                    if (!value?.trim()) {
                        this.errors[field] = rpForm.i18n.required;
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        this.errors[field] = rpForm.i18n.invalidEmail;
                    }
                    break;
            }

            return !this.errors[field];
        },

        validateAll() {
            let valid = true;
            ['first_name', 'last_name', 'email'].forEach(field => {
                if (!this.validateField(field)) valid = false;
            });

            if (!this.privacyAccepted) {
                this.errors.privacy = rpForm.i18n.privacyRequired;
                valid = false;
            }

            return valid;
        },

        handleFileSelect(event) {
            const newFiles = Array.from(event.target.files || event.dataTransfer.files);

            newFiles.forEach(file => {
                // Validierung
                if (file.size > 10 * 1024 * 1024) {
                    this.error = rpForm.i18n.fileTooLarge;
                    return;
                }

                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png'
                ];

                if (!allowedTypes.includes(file.type)) {
                    this.error = rpForm.i18n.invalidFileType;
                    return;
                }

                this.files.push({
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: this.detectDocumentType(file.name),
                    preview: file.type.startsWith('image/')
                        ? URL.createObjectURL(file)
                        : null
                });
            });
        },

        detectDocumentType(filename) {
            const lower = filename.toLowerCase();
            if (lower.includes('lebenslauf') || lower.includes('cv') || lower.includes('resume')) {
                return 'resume';
            }
            if (lower.includes('anschreiben') || lower.includes('cover') || lower.includes('motivation')) {
                return 'cover_letter';
            }
            if (lower.includes('zeugnis') || lower.includes('zertifikat') || lower.includes('certificate')) {
                return 'certificate';
            }
            return 'other';
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        async submit() {
            if (!this.validateAll()) return;

            // Spam Check: Mindestens 3 Sekunden
            const elapsed = Date.now() - this.formLoadTime;
            if (elapsed < 3000) {
                this.error = 'Bitte warten Sie einen Moment.';
                return;
            }

            // Honeypot Check
            if (this.honeypot) {
                console.warn('Honeypot triggered');
                this.success = true; // Fake success
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();

                // Basic Data
                formData.append('job_id', this.jobId);
                formData.append('first_name', this.formData.first_name);
                formData.append('last_name', this.formData.last_name);
                formData.append('email', this.formData.email);
                formData.append('phone', this.formData.phone || '');
                formData.append('cover_letter', this.formData.cover_letter || '');
                formData.append('privacy_accepted', '1');

                // Spam Protection
                formData.append('_hp', this.honeypot);
                formData.append('_time', this.formLoadTime.toString());

                // Files
                this.files.forEach((fileObj, index) => {
                    formData.append(`documents[${index}]`, fileObj.file);
                    formData.append(`document_types[${index}]`, fileObj.type);
                });

                const response = await fetch(rpForm.apiUrl + 'applications', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': rpForm.nonce
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Ein Fehler ist aufgetreten');
                }

                this.success = true;

            } catch (err) {
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }));
});
```

### Formular-Template (HTML)

```html
<form @submit.prevent="submit" class="rp-space-y-4">
    <!-- Honeypot (versteckt) -->
    <input type="text" name="_hp" x-model="honeypot"
           style="position: absolute; left: -9999px;"
           tabindex="-1" autocomplete="off">

    <!-- Name -->
    <div class="rp-grid rp-grid-cols-2 rp-gap-4">
        <div>
            <label for="first_name" class="rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1">
                <?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> *
            </label>
            <input type="text" id="first_name"
                   x-model="formData.first_name"
                   @blur="touched.first_name = true; validateField('first_name')"
                   :class="{'rp-border-error': errors.first_name && touched.first_name}"
                   class="rp-w-full rp-px-3 rp-py-2 rp-border rp-border-gray-300 rp-rounded-md"
                   required>
            <p x-show="errors.first_name && touched.first_name"
               x-text="errors.first_name"
               class="rp-text-error rp-text-sm rp-mt-1"></p>
        </div>

        <div>
            <label for="last_name" class="rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1">
                <?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?> *
            </label>
            <input type="text" id="last_name"
                   x-model="formData.last_name"
                   @blur="touched.last_name = true; validateField('last_name')"
                   :class="{'rp-border-error': errors.last_name && touched.last_name}"
                   class="rp-w-full rp-px-3 rp-py-2 rp-border rp-border-gray-300 rp-rounded-md"
                   required>
        </div>
    </div>

    <!-- E-Mail -->
    <div>
        <label for="email" class="rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1">
            <?php esc_html_e( 'E-Mail', 'recruiting-playbook' ); ?> *
        </label>
        <input type="email" id="email"
               x-model="formData.email"
               @blur="touched.email = true; validateField('email')"
               :class="{'rp-border-error': errors.email && touched.email}"
               class="rp-w-full rp-px-3 rp-py-2 rp-border rp-border-gray-300 rp-rounded-md"
               required>
        <p x-show="errors.email && touched.email"
           x-text="errors.email"
           class="rp-text-error rp-text-sm rp-mt-1"></p>
    </div>

    <!-- Telefon (optional) -->
    <div>
        <label for="phone" class="rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1">
            <?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?>
        </label>
        <input type="tel" id="phone"
               x-model="formData.phone"
               class="rp-w-full rp-px-3 rp-py-2 rp-border rp-border-gray-300 rp-rounded-md">
    </div>

    <!-- Datei-Upload -->
    <div>
        <label class="rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1">
            <?php esc_html_e( 'Dokumente hochladen', 'recruiting-playbook' ); ?>
        </label>

        <!-- Drag & Drop Zone -->
        <div @drop.prevent="handleFileSelect($event); dragover = false"
             @dragover.prevent="dragover = true"
             @dragleave="dragover = false"
             :class="{'rp-border-primary rp-bg-primary-light': dragover}"
             class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center rp-cursor-pointer rp-transition-colors"
             @click="$refs.fileInput.click()">

            <svg class="rp-mx-auto rp-h-12 rp-w-12 rp-text-gray-400">
                <!-- Upload Icon -->
            </svg>

            <p class="rp-mt-2 rp-text-sm rp-text-gray-600">
                <?php esc_html_e( 'Dateien hier ablegen oder klicken zum Auswählen', 'recruiting-playbook' ); ?>
            </p>
            <p class="rp-text-xs rp-text-gray-500 rp-mt-1">
                PDF, DOC, DOCX, JPG, PNG (max. 10 MB)
            </p>

            <input type="file" x-ref="fileInput" @change="handleFileSelect($event)"
                   multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                   class="rp-hidden">
        </div>

        <!-- File List -->
        <template x-if="files.length > 0">
            <ul class="rp-mt-3 rp-space-y-2">
                <template x-for="(file, index) in files" :key="index">
                    <li class="rp-flex rp-items-center rp-justify-between rp-p-2 rp-bg-gray-50 rp-rounded">
                        <div class="rp-flex rp-items-center rp-gap-2">
                            <svg class="rp-w-5 rp-h-5 rp-text-gray-400"><!-- File Icon --></svg>
                            <span x-text="file.name" class="rp-text-sm rp-truncate"></span>
                            <span x-text="formatFileSize(file.size)" class="rp-text-xs rp-text-gray-500"></span>
                        </div>
                        <button type="button" @click="removeFile(index)"
                                class="rp-text-gray-400 hover:rp-text-error">
                            <svg class="rp-w-4 rp-h-4"><!-- X Icon --></svg>
                        </button>
                    </li>
                </template>
            </ul>
        </template>
    </div>

    <!-- Anschreiben -->
    <div>
        <label for="cover_letter" class="rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1">
            <?php esc_html_e( 'Nachricht (optional)', 'recruiting-playbook' ); ?>
        </label>
        <textarea id="cover_letter" x-model="formData.cover_letter" rows="4"
                  class="rp-w-full rp-px-3 rp-py-2 rp-border rp-border-gray-300 rp-rounded-md"></textarea>
    </div>

    <!-- DSGVO Checkbox -->
    <div class="rp-flex rp-items-start rp-gap-2">
        <input type="checkbox" id="privacy" x-model="privacyAccepted"
               class="rp-mt-1" required>
        <label for="privacy" class="rp-text-sm rp-text-gray-600">
            <?php
            printf(
                esc_html__( 'Ich habe die %s gelesen und akzeptiere diese.', 'recruiting-playbook' ),
                '<a href="' . esc_url( rp_get_privacy_url() ) . '" target="_blank" class="rp-text-primary rp-underline">' .
                esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' ) . '</a>'
            );
            ?>
            *
        </label>
    </div>
    <p x-show="errors.privacy" class="rp-text-error rp-text-sm">
        <?php esc_html_e( 'Bitte stimmen Sie der Datenschutzerklärung zu.', 'recruiting-playbook' ); ?>
    </p>

    <!-- Error Message -->
    <div x-show="error" x-cloak
         class="rp-p-3 rp-bg-error-light rp-border rp-border-error rp-rounded-md">
        <p x-text="error" class="rp-text-error rp-text-sm"></p>
    </div>

    <!-- Submit Button -->
    <button type="submit"
            :disabled="loading"
            class="rp-w-full rp-btn rp-btn-primary disabled:rp-opacity-50 disabled:rp-cursor-not-allowed">
        <span x-show="!loading"><?php esc_html_e( 'Bewerbung absenden', 'recruiting-playbook' ); ?></span>
        <span x-show="loading" class="rp-flex rp-items-center rp-justify-center rp-gap-2">
            <svg class="rp-animate-spin rp-h-5 rp-w-5"><!-- Spinner --></svg>
            <?php esc_html_e( 'Wird gesendet...', 'recruiting-playbook' ); ?>
        </span>
    </button>
</form>

<!-- Success Message -->
<div x-show="success" x-cloak class="rp-text-center rp-p-6">
    <div class="rp-w-16 rp-h-16 rp-mx-auto rp-bg-success-light rp-rounded-full rp-flex rp-items-center rp-justify-center">
        <svg class="rp-w-8 rp-h-8 rp-text-success"><!-- Check Icon --></svg>
    </div>
    <h3 class="rp-mt-4 rp-text-xl rp-font-semibold rp-text-gray-900">
        <?php esc_html_e( 'Vielen Dank!', 'recruiting-playbook' ); ?>
    </h3>
    <p class="rp-mt-2 rp-text-gray-600">
        <?php esc_html_e( 'Ihre Bewerbung wurde erfolgreich übermittelt.', 'recruiting-playbook' ); ?>
    </p>
</div>
```

---

## 3. REST API Endpoint

### ApplicationController.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller für Bewerbungen
 */
class ApplicationController extends WP_REST_Controller {

    protected $namespace = 'recruiting/v1';
    protected $rest_base = 'applications';

    /**
     * Routen registrieren
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => '__return_true', // Öffentlich
                    'args'                => $this->get_create_args(),
                ],
            ]
        );
    }

    /**
     * Parameter-Schema für POST
     */
    private function get_create_args(): array {
        return [
            'job_id' => [
                'required'          => true,
                'type'              => 'integer',
                'validate_callback' => function( $value ) {
                    return get_post_type( $value ) === 'job_listing';
                },
            ],
            'first_name' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'last_name' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'email' => [
                'required'          => true,
                'type'              => 'string',
                'validate_callback' => 'is_email',
                'sanitize_callback' => 'sanitize_email',
            ],
            'phone' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'cover_letter' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'privacy_accepted' => [
                'required' => true,
                'type'     => 'boolean',
            ],
        ];
    }

    /**
     * Bewerbung erstellen
     */
    public function create_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        // Spam-Schutz prüfen
        $spam_check = $this->check_spam( $request );
        if ( is_wp_error( $spam_check ) ) {
            return $spam_check;
        }

        // Rate Limiting
        $rate_check = $this->check_rate_limit();
        if ( is_wp_error( $rate_check ) ) {
            return $rate_check;
        }

        global $wpdb;

        try {
            // 1. Kandidat erstellen oder aktualisieren
            $candidate_id = $this->create_or_update_candidate( $request );

            // 2. Bewerbung erstellen
            $application_id = $this->create_application( $candidate_id, $request );

            // 3. Dokumente verarbeiten
            $this->process_documents( $application_id, $candidate_id, $request );

            // 4. E-Mails versenden
            $this->send_notifications( $application_id, $candidate_id, $request );

            // 5. Activity Log
            $this->log_activity( $application_id, 'created', 'Bewerbung eingegangen' );

            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => __( 'Bewerbung erfolgreich eingereicht.', 'recruiting-playbook' ),
                    'id'      => $application_id,
                ],
                201
            );

        } catch ( \Exception $e ) {
            return new WP_Error(
                'application_error',
                $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Kandidat erstellen oder aktualisieren
     */
    private function create_or_update_candidate( WP_REST_Request $request ): int {
        global $wpdb;

        $email = $request->get_param( 'email' );
        $table = $wpdb->prefix . 'rp_candidates';

        // Existierenden Kandidaten suchen
        $existing = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s", $email )
        );

        $data = [
            'email'                => $email,
            'first_name'           => $request->get_param( 'first_name' ),
            'last_name'            => $request->get_param( 'last_name' ),
            'phone'                => $request->get_param( 'phone' ) ?? '',
            'gdpr_consent'         => 1,
            'gdpr_consent_date'    => current_time( 'mysql' ),
            'gdpr_consent_version' => RP_VERSION,
            'updated_at'           => current_time( 'mysql' ),
        ];

        if ( $existing ) {
            $wpdb->update( $table, $data, [ 'id' => $existing ] );
            return (int) $existing;
        }

        $data['source']     = 'form';
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $table, $data );

        return (int) $wpdb->insert_id;
    }

    /**
     * Bewerbung erstellen
     */
    private function create_application( int $candidate_id, WP_REST_Request $request ): int {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_applications';

        $wpdb->insert(
            $table,
            [
                'candidate_id'  => $candidate_id,
                'job_id'        => $request->get_param( 'job_id' ),
                'status'        => 'new',
                'cover_letter'  => $request->get_param( 'cover_letter' ) ?? '',
                'source_url'    => wp_get_referer() ?: '',
                'ip_address'    => $this->get_client_ip(),
                'user_agent'    => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            ]
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Client-IP ermitteln
     */
    private function get_client_ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Spam-Prüfung
     */
    private function check_spam( WP_REST_Request $request ): bool|WP_Error {
        // Honeypot
        $honeypot = $request->get_param( '_hp' );
        if ( ! empty( $honeypot ) ) {
            return new WP_Error(
                'spam_detected',
                __( 'Spam erkannt.', 'recruiting-playbook' ),
                [ 'status' => 403 ]
            );
        }

        // Time Check (min. 3 Sekunden)
        $form_time = $request->get_param( '_time' );
        if ( $form_time ) {
            $elapsed = ( time() * 1000 ) - (int) $form_time;
            if ( $elapsed < 3000 ) {
                return new WP_Error(
                    'too_fast',
                    __( 'Formular zu schnell abgesendet.', 'recruiting-playbook' ),
                    [ 'status' => 403 ]
                );
            }
        }

        return true;
    }

    /**
     * Rate Limiting (5 Bewerbungen pro Stunde pro IP)
     */
    private function check_rate_limit(): bool|WP_Error {
        $ip = $this->get_client_ip();
        $transient_key = 'rp_rate_' . md5( $ip );
        $count = get_transient( $transient_key ) ?: 0;

        if ( $count >= 5 ) {
            return new WP_Error(
                'rate_limit',
                __( 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.', 'recruiting-playbook' ),
                [ 'status' => 429 ]
            );
        }

        set_transient( $transient_key, $count + 1, HOUR_IN_SECONDS );
        return true;
    }
}
```

---

## 4. Datei-Upload & Dokumentenverwaltung

### DocumentService.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

/**
 * Service für Dokumenten-Verwaltung
 */
class DocumentService {

    /**
     * Erlaubte Dateitypen
     */
    private const ALLOWED_TYPES = [
        'application/pdf'                                                         => 'pdf',
        'application/msword'                                                      => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/jpeg'                                                              => 'jpg',
        'image/png'                                                               => 'png',
    ];

    /**
     * Max. Dateigröße (10 MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Upload-Verzeichnis
     */
    public static function getUploadDir(): array {
        $upload_dir = wp_upload_dir();
        $rp_dir     = $upload_dir['basedir'] . '/recruiting-playbook';
        $rp_url     = $upload_dir['baseurl'] . '/recruiting-playbook';

        // Verzeichnis erstellen falls nicht vorhanden
        if ( ! file_exists( $rp_dir ) ) {
            wp_mkdir_p( $rp_dir );

            // .htaccess für Schutz (Apache)
            file_put_contents(
                $rp_dir . '/.htaccess',
                "Order Deny,Allow\nDeny from all"
            );

            // index.php als Fallback
            file_put_contents(
                $rp_dir . '/index.php',
                '<?php // Silence is golden'
            );
        }

        return [
            'path' => $rp_dir,
            'url'  => $rp_url,
        ];
    }

    /**
     * Datei validieren
     */
    public static function validateFile( array $file ): bool|WP_Error {
        // Größe prüfen
        if ( $file['size'] > self::MAX_FILE_SIZE ) {
            return new WP_Error(
                'file_too_large',
                __( 'Die Datei ist zu groß (max. 10 MB).', 'recruiting-playbook' )
            );
        }

        // MIME-Type prüfen (echte Prüfung, nicht nur Extension)
        $finfo    = finfo_open( FILEINFO_MIME_TYPE );
        $mime     = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        if ( ! array_key_exists( $mime, self::ALLOWED_TYPES ) ) {
            return new WP_Error(
                'invalid_type',
                __( 'Dateityp nicht erlaubt.', 'recruiting-playbook' )
            );
        }

        return true;
    }

    /**
     * Datei speichern
     */
    public static function saveFile( array $file, int $application_id, int $candidate_id, string $document_type = 'other' ): int|WP_Error {
        global $wpdb;

        // Validierung
        $valid = self::validateFile( $file );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $upload_dir = self::getUploadDir();

        // Eindeutiger Dateiname (UUID)
        $ext           = pathinfo( $file['name'], PATHINFO_EXTENSION );
        $uuid          = wp_generate_uuid4();
        $new_filename  = $uuid . '.' . strtolower( $ext );
        $year_month    = date( 'Y/m' );
        $target_dir    = $upload_dir['path'] . '/' . $year_month;

        // Unterverzeichnis erstellen
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        $target_path = $target_dir . '/' . $new_filename;

        // Datei verschieben
        if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
            return new WP_Error(
                'upload_failed',
                __( 'Datei konnte nicht gespeichert werden.', 'recruiting-playbook' )
            );
        }

        // Hash für Integrität
        $file_hash = hash_file( 'sha256', $target_path );

        // In Datenbank speichern
        $table = $wpdb->prefix . 'rp_documents';

        $wpdb->insert(
            $table,
            [
                'application_id' => $application_id,
                'candidate_id'   => $candidate_id,
                'file_name'      => $new_filename,
                'original_name'  => sanitize_file_name( $file['name'] ),
                'file_path'      => $target_path,
                'file_type'      => $file['type'],
                'file_size'      => $file['size'],
                'file_hash'      => $file_hash,
                'document_type'  => $document_type,
                'created_at'     => current_time( 'mysql' ),
            ]
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Schutz-Status prüfen
     */
    public static function checkProtection(): array {
        $upload_dir = self::getUploadDir();

        // Server-Typ ermitteln
        $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $is_nginx        = stripos( $server_software, 'nginx' ) !== false;
        $is_apache       = stripos( $server_software, 'apache' ) !== false;

        if ( $is_nginx ) {
            return [
                'protected'   => false,
                'server_type' => 'nginx',
                'message'     => __( 'Nginx erfordert manuelle Konfiguration für Dokumentenschutz.', 'recruiting-playbook' ),
            ];
        }

        // Apache: .htaccess prüfen
        $htaccess_path = $upload_dir['path'] . '/.htaccess';
        if ( file_exists( $htaccess_path ) ) {
            return [
                'protected'   => true,
                'server_type' => 'apache',
                'message'     => __( 'Dokumentenschutz aktiv (.htaccess).', 'recruiting-playbook' ),
            ];
        }

        return [
            'protected'   => false,
            'server_type' => $is_apache ? 'apache' : 'unknown',
            'message'     => __( 'Dokumentenschutz nicht konfiguriert.', 'recruiting-playbook' ),
        ];
    }
}
```

### DocumentDownloadService.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

/**
 * Sichere Dokument-Downloads mit Token-Validierung
 */
class DocumentDownloadService {

    private const TOKEN_EXPIRY = 3600; // 1 Stunde

    /**
     * Download-URL generieren
     */
    public static function generateDownloadUrl( int $document_id ): string {
        $token = self::generateToken( $document_id );

        return admin_url(
            sprintf(
                'admin-ajax.php?action=rp_download_document&id=%d&token=%s',
                $document_id,
                $token
            )
        );
    }

    /**
     * Token generieren
     */
    private static function generateToken( int $document_id ): string {
        $user_id = get_current_user_id();
        $expiry  = time() + self::TOKEN_EXPIRY;

        $data = sprintf( '%d:%d:%d', $document_id, $user_id, $expiry );
        $hash = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );

        return base64_encode( $data . ':' . $hash );
    }

    /**
     * Token validieren
     */
    public static function validateToken( int $document_id, string $token ): bool {
        $decoded = base64_decode( $token );

        if ( ! $decoded || substr_count( $decoded, ':' ) !== 3 ) {
            return false;
        }

        list( $token_doc_id, $token_user_id, $expiry, $hash ) = explode( ':', $decoded );

        // Validierungen
        if ( (int) $token_doc_id !== $document_id ) {
            return false;
        }

        if ( (int) $expiry < time() ) {
            return false;
        }

        if ( (int) $token_user_id !== get_current_user_id() ) {
            return false;
        }

        // Hash prüfen
        $data          = sprintf( '%d:%d:%d', $token_doc_id, $token_user_id, $expiry );
        $expected_hash = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );

        return hash_equals( $expected_hash, $hash );
    }

    /**
     * Download ausführen
     */
    public static function serveDownload( int $document_id ): void {
        global $wpdb;

        $table    = $wpdb->prefix . 'rp_documents';
        $document = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $document_id ),
            ARRAY_A
        );

        if ( ! $document || ! file_exists( $document['file_path'] ) ) {
            wp_die( __( 'Dokument nicht gefunden.', 'recruiting-playbook' ), '', [ 'response' => 404 ] );
        }

        // Download-Zähler erhöhen
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET download_count = download_count + 1 WHERE id = %d",
                $document_id
            )
        );

        // Headers setzen
        header( 'Content-Type: ' . $document['file_type'] );
        header( 'Content-Disposition: attachment; filename="' . $document['original_name'] . '"' );
        header( 'Content-Length: ' . $document['file_size'] );
        header( 'Cache-Control: no-cache, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Datei ausgeben
        readfile( $document['file_path'] );
        exit;
    }

    /**
     * AJAX-Handler registrieren
     */
    public static function registerAjaxHandler(): void {
        add_action( 'wp_ajax_rp_download_document', [ self::class, 'handleAjaxDownload' ] );
    }

    /**
     * AJAX-Download verarbeiten
     */
    public static function handleAjaxDownload(): void {
        // Berechtigung prüfen
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Keine Berechtigung.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
        }

        $document_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $token       = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

        if ( ! $document_id || ! $token ) {
            wp_die( __( 'Ungültige Anfrage.', 'recruiting-playbook' ), '', [ 'response' => 400 ] );
        }

        // Token validieren
        if ( ! self::validateToken( $document_id, $token ) ) {
            wp_die( __( 'Download-Link abgelaufen oder ungültig.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
        }

        // Download ausführen
        self::serveDownload( $document_id );
    }
}
```

---

## 5. Spam-Schutz

### Implementierte Mechanismen

| Mechanismus | Beschreibung | Implementierung |
|-------------|--------------|-----------------|
| **Honeypot** | Verstecktes Feld, das Bots ausfüllen | `<input name="_hp" style="position:absolute;left:-9999px">` |
| **Time Check** | Mindestzeit 3 Sekunden | `formLoadTime` vs. Submit-Zeit |
| **Rate Limiting** | Max. 5 Bewerbungen/Stunde/IP | WordPress Transients |
| **Turnstile** | Cloudflare Widget (optional) | Phase 2 |

### Rate Limiting Implementierung

```php
/**
 * Rate Limit prüfen
 */
private function check_rate_limit(): bool|WP_Error {
    $ip            = $this->get_client_ip();
    $transient_key = 'rp_rate_' . md5( $ip );
    $count         = get_transient( $transient_key ) ?: 0;

    if ( $count >= 5 ) {
        return new WP_Error(
            'rate_limit',
            __( 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.', 'recruiting-playbook' ),
            [ 'status' => 429 ]
        );
    }

    set_transient( $transient_key, $count + 1, HOUR_IN_SECONDS );
    return true;
}
```

---

## 6. DSGVO-Compliance

### Gespeicherte Consent-Daten

```sql
-- In rp_candidates
gdpr_consent          TINYINT(1)  -- 0/1
gdpr_consent_date     DATETIME    -- Zeitstempel
gdpr_consent_version  VARCHAR(20) -- Plugin-Version bei Einwilligung
```

### Pflicht-Checkbox im Formular

```html
<div class="rp-flex rp-items-start rp-gap-2">
    <input type="checkbox" id="privacy" required>
    <label for="privacy">
        Ich habe die <a href="/datenschutz" target="_blank">Datenschutzerklärung</a>
        gelesen und akzeptiere diese. *
    </label>
</div>
```

### Daten-Export & Löschung

- Implementiert in `GdprService.php` (Phase 1C)
- Export als JSON mit allen personenbezogenen Daten
- Soft-Delete mit Anonymisierung

---

## 7. E-Mail-Benachrichtigungen

### EmailService.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

/**
 * E-Mail-Versand Service
 */
class EmailService {

    /**
     * Benachrichtigung an HR
     */
    public static function sendNewApplicationNotification(
        int $application_id,
        array $candidate,
        array $job
    ): bool {
        $settings = get_option( 'rp_settings', [] );
        $to       = $settings['notification_email'] ?? get_option( 'admin_email' );

        $subject = sprintf(
            __( '[%s] Neue Bewerbung: %s', 'recruiting-playbook' ),
            get_bloginfo( 'name' ),
            $job['title']
        );

        $message = self::renderTemplate(
            'email/new-application-hr.php',
            [
                'candidate'   => $candidate,
                'job'         => $job,
                'admin_url'   => admin_url( 'admin.php?page=rp-application-detail&id=' . $application_id ),
                'company'     => $settings['company_name'] ?? get_bloginfo( 'name' ),
            ]
        );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
        ];

        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Eingangsbestätigung an Bewerber
     */
    public static function sendConfirmationToApplicant(
        array $candidate,
        array $job
    ): bool {
        $settings = get_option( 'rp_settings', [] );

        $subject = sprintf(
            __( 'Ihre Bewerbung bei %s', 'recruiting-playbook' ),
            $settings['company_name'] ?? get_bloginfo( 'name' )
        );

        $message = self::renderTemplate(
            'email/confirmation-applicant.php',
            [
                'candidate' => $candidate,
                'job'       => $job,
                'company'   => $settings['company_name'] ?? get_bloginfo( 'name' ),
            ]
        );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ( $settings['company_name'] ?? get_bloginfo( 'name' ) ) . ' <' . get_option( 'admin_email' ) . '>',
        ];

        return wp_mail( $candidate['email'], $subject, $message, $headers );
    }

    /**
     * Template rendern
     */
    private static function renderTemplate( string $template, array $data ): string {
        extract( $data );

        ob_start();
        include RP_PLUGIN_DIR . 'templates/' . $template;
        return ob_get_clean();
    }

    /**
     * SMTP-Konfiguration prüfen
     */
    public static function checkSmtpConfig(): array {
        // Bekannte SMTP-Plugins prüfen
        $smtp_plugins = [
            'wp-mail-smtp/wp_mail_smtp.php'     => 'WP Mail SMTP',
            'post-smtp/postman-smtp.php'        => 'Post SMTP',
            'smtp-mailer/main.php'              => 'SMTP Mailer',
            'easy-wp-smtp/easy-wp-smtp.php'     => 'Easy WP SMTP',
            'fluent-smtp/fluent-smtp.php'       => 'Fluent SMTP',
        ];

        $active_plugins = get_option( 'active_plugins', [] );

        foreach ( $smtp_plugins as $plugin_file => $plugin_name ) {
            if ( in_array( $plugin_file, $active_plugins, true ) ) {
                return [
                    'configured' => true,
                    'plugin'     => $plugin_name,
                    'message'    => sprintf(
                        __( 'SMTP ist über %s konfiguriert.', 'recruiting-playbook' ),
                        $plugin_name
                    ),
                ];
            }
        }

        return [
            'configured' => false,
            'plugin'     => null,
            'message'    => __( 'Kein SMTP-Plugin erkannt. E-Mails werden über PHP mail() gesendet, was zu Zustellproblemen führen kann.', 'recruiting-playbook' ),
        ];
    }
}
```

### E-Mail-Templates

**templates/email/new-application-hr.php:**

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .btn { display: inline-block; padding: 12px 24px; background: #2271b1; color: white;
               text-decoration: none; border-radius: 4px; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php esc_html_e( 'Neue Bewerbung eingegangen', 'recruiting-playbook' ); ?></h1>
        </div>

        <div class="content">
            <p><?php esc_html_e( 'Eine neue Bewerbung wurde eingereicht:', 'recruiting-playbook' ); ?></p>

            <table style="width: 100%; margin: 20px 0;">
                <tr>
                    <td><strong><?php esc_html_e( 'Name:', 'recruiting-playbook' ); ?></strong></td>
                    <td><?php echo esc_html( $candidate['first_name'] . ' ' . $candidate['last_name'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'E-Mail:', 'recruiting-playbook' ); ?></strong></td>
                    <td><a href="mailto:<?php echo esc_attr( $candidate['email'] ); ?>"><?php echo esc_html( $candidate['email'] ); ?></a></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Stelle:', 'recruiting-playbook' ); ?></strong></td>
                    <td><?php echo esc_html( $job['title'] ); ?></td>
                </tr>
            </table>

            <p style="text-align: center; margin-top: 30px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="btn">
                    <?php esc_html_e( 'Bewerbung ansehen', 'recruiting-playbook' ); ?>
                </a>
            </p>
        </div>

        <div class="footer">
            <p><?php echo esc_html( $company ); ?> - Recruiting Playbook</p>
        </div>
    </div>
</body>
</html>
```

---

## 8. SMTP-Konfigurationsprüfung

### Dashboard-Warnung

```php
/**
 * SMTP-Warnung im Dashboard anzeigen
 */
public function renderSmtpNotice(): void {
    $smtp_status = EmailService::checkSmtpConfig();

    if ( ! $smtp_status['configured'] ) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'E-Mail-Konfiguration:', 'recruiting-playbook' ); ?></strong>
                <?php echo esc_html( $smtp_status['message'] ); ?>
            </p>
            <p>
                <?php esc_html_e( 'Empfohlene SMTP-Plugins:', 'recruiting-playbook' ); ?>
                <a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP Mail SMTP</a>,
                <a href="https://wordpress.org/plugins/post-smtp/" target="_blank">Post SMTP</a>
            </p>
        </div>
        <?php
    }
}
```

---

## 9. Einstellungen-Seite

### Settings.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Admin;

/**
 * Settings-Seite im Admin
 */
class Settings {

    private const OPTION_NAME = 'rp_settings';

    /**
     * Settings registrieren
     */
    public function registerSettings(): void {
        register_setting(
            'rp_settings_group',
            self::OPTION_NAME,
            [
                'sanitize_callback' => [ $this, 'sanitizeSettings' ],
                'default'           => $this->getDefaults(),
            ]
        );

        // Sektion: Allgemein
        add_settings_section(
            'rp_general_section',
            __( 'Allgemeine Einstellungen', 'recruiting-playbook' ),
            [ $this, 'renderGeneralSection' ],
            'rp-settings'
        );

        // Felder
        $this->addField( 'company_name', __( 'Firmenname', 'recruiting-playbook' ), 'text' );
        $this->addField( 'notification_email', __( 'Benachrichtigungs-E-Mail', 'recruiting-playbook' ), 'email' );
        $this->addField( 'privacy_url', __( 'Datenschutz-Seite', 'recruiting-playbook' ), 'page_select' );
        $this->addField( 'jobs_per_page', __( 'Stellen pro Seite', 'recruiting-playbook' ), 'number' );
        $this->addField( 'jobs_slug', __( 'URL-Slug', 'recruiting-playbook' ), 'slug' );
        $this->addField( 'enable_schema', __( 'Google for Jobs Schema', 'recruiting-playbook' ), 'checkbox' );
    }

    /**
     * Standard-Werte
     */
    private function getDefaults(): array {
        return [
            'company_name'       => get_bloginfo( 'name' ),
            'notification_email' => get_option( 'admin_email' ),
            'privacy_url'        => get_privacy_policy_url(),
            'jobs_per_page'      => 10,
            'jobs_slug'          => 'jobs',
            'enable_schema'      => true,
        ];
    }

    /**
     * Settings sanitizen
     */
    public function sanitizeSettings( array $input ): array {
        $output = [];

        $output['company_name']       = sanitize_text_field( $input['company_name'] ?? '' );
        $output['notification_email'] = sanitize_email( $input['notification_email'] ?? '' );

        // privacy_url kommt als Page-ID von wp_dropdown_pages
        $privacy_page_id = absint( $input['privacy_url'] ?? 0 );
        $output['privacy_url'] = $privacy_page_id ? get_permalink( $privacy_page_id ) : '';

        $output['jobs_per_page']      = absint( $input['jobs_per_page'] ?? 10 );
        $output['jobs_slug']          = sanitize_title( $input['jobs_slug'] ?? 'jobs' );
        $output['enable_schema']      = ! empty( $input['enable_schema'] );

        // Slug-Änderung erfordert Rewrite-Flush
        $old_settings = get_option( self::OPTION_NAME, [] );
        if ( ( $old_settings['jobs_slug'] ?? 'jobs' ) !== $output['jobs_slug'] ) {
            set_transient( 'rp_flush_rewrite_rules', true, 60 );
        }

        return $output;
    }
}
```

---

## 10. CSS Custom Properties

### Theme-Integration

```css
:root {
    /* Primärfarbe - überschreibbar via Theme oder Admin */
    --rp-color-primary: #2271b1;
    --rp-color-primary-hover: #135e96;
    --rp-color-primary-light: #f0f6fc;

    /* Status-Farben */
    --rp-color-success: #00a32a;
    --rp-color-success-light: #edfaef;
    --rp-color-error: #d63638;
    --rp-color-error-light: #fcf0f1;
    --rp-color-warning: #dba617;

    /* Typography */
    --rp-font-family: inherit;
    --rp-text-xs: 0.75rem;
    --rp-text-sm: 0.875rem;
    --rp-text-base: 1rem;
    --rp-text-lg: 1.125rem;
    --rp-text-xl: 1.25rem;
    --rp-text-2xl: 1.5rem;

    /* Spacing */
    --rp-space-1: 0.25rem;
    --rp-space-2: 0.5rem;
    --rp-space-3: 0.75rem;
    --rp-space-4: 1rem;
    --rp-space-6: 1.5rem;
    --rp-space-8: 2rem;

    /* Border Radius */
    --rp-radius: 0.375rem;
    --rp-radius-md: 0.5rem;
    --rp-radius-lg: 0.75rem;
    --rp-radius-xl: 1rem;
    --rp-radius-full: 9999px;

    /* Shadows */
    --rp-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --rp-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    --rp-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --rp-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
```

---

## Deliverables Phase 1B

| Item | Beschreibung | Kriterium |
|------|--------------|-----------|
| Job-Templates | Archive + Single funktionieren | ✅ Frontend sichtbar |
| Bewerbungsformular | Alpine.js Komponente | ✅ Validierung + Submit |
| Datei-Upload | Drag & Drop, Multi-File | ✅ Dateien gespeichert |
| REST API | POST /applications | ✅ 201 Response |
| Spam-Schutz | Honeypot + Time + Rate Limit | ✅ Bots blockiert |
| DSGVO | Consent-Checkbox + Speicherung | ✅ Daten erfasst |
| E-Mail HR | Benachrichtigung | ✅ E-Mail empfangen |
| E-Mail Bewerber | Eingangsbestätigung | ✅ E-Mail empfangen |
| SMTP-Check | Warnung wenn nicht konfiguriert | ✅ Notice sichtbar |
| Einstellungen | Basis-Seite | ✅ Werte speicherbar |

---

## Nächste Phase: Phase 1C

Nach erfolgreichem Abschluss von Phase 1B:

→ **Phase 1C: Admin-Basics** (Woche 5-6)
- Bewerber-Listenansicht (WP_List_Table)
- Bewerber-Detailseite
- Dokument-Download (Token-basiert)
- Status-Management
- Backup-Export (JSON)
- DSGVO-Funktionen

---

*Technische Spezifikation erstellt: Januar 2025*
