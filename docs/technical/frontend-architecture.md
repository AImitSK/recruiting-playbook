# Frontend-Architektur

## Übersicht

Das öffentliche Frontend (Stellenanzeigen, Bewerbungsformular) verwendet:

| Technologie | Zweck | Größe |
|-------------|-------|-------|
| **Alpine.js** | Interaktivität (Filter, Formulare, Modals) | ~15kb |
| **Tailwind CSS** | Utility-first Styling | ~10-30kb (purged) |
| **CSS Custom Properties** | Theme-Integration, Anpassbarkeit | - |

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND-ARCHITEKTUR                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    PHP TEMPLATES                         │   │
│  │         (Server-Side Rendering für SEO)                  │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                     ALPINE.JS                            │   │
│  │              (Progressive Enhancement)                   │   │
│  │                                                          │   │
│  │  • Filter & Suche        • Formular-Validierung         │   │
│  │  • Akkordeons            • Modals                        │   │
│  │  • Tabs                  • Toast-Nachrichten            │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    TAILWIND CSS                          │   │
│  │            + CSS Custom Properties                       │   │
│  │                                                          │   │
│  │  • Utility Classes       • Theme-Farben                 │   │
│  │  • Responsive Design     • Dark Mode (optional)         │   │
│  │  • Komponenten           • Überschreibbar               │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Design-Prinzipien

### 1. Progressive Enhancement

Das Frontend funktioniert **auch ohne JavaScript**:

```html
<!-- Ohne JS: Normales Formular-Submit -->
<!-- Mit JS: AJAX-Submit mit Validierung -->
<form 
    action="<?php echo esc_url( $form_action ); ?>" 
    method="post"
    x-data="applicationForm()"
    @submit.prevent="submit()"
>
    <!-- Felder -->
    <button type="submit">
        <span x-show="!loading">Jetzt bewerben</span>
        <span x-show="loading" x-cloak>Wird gesendet...</span>
    </button>
</form>
```

### 2. Server-Side Rendering (SEO)

Alle Inhalte werden von PHP gerendert – Alpine.js fügt nur Interaktivität hinzu:

```php
// ✅ SEO-freundlich: Inhalte sind im HTML
<div class="rp-job-list">
    <?php foreach ( $jobs as $job ) : ?>
        <article class="rp-job-card">
            <h2><?php echo esc_html( $job->post_title ); ?></h2>
            <!-- ... -->
        </article>
    <?php endforeach; ?>
</div>

// ❌ Nicht SEO-freundlich: Inhalte werden per JS geladen
<div x-data="{ jobs: [] }" x-init="jobs = await fetchJobs()">
    <template x-for="job in jobs">
        <!-- ... -->
    </template>
</div>
```

### 3. Theme-Integration

Das Plugin übernimmt automatisch Theme-Eigenschaften wo möglich:

```css
.rp-job-card {
    font-family: var(--rp-font-family, inherit);
    color: var(--rp-text, inherit);
    background: var(--rp-background, inherit);
}
```

---

## CSS Custom Properties (Design Tokens)

### Standard-Variablen

```css
/* ==========================================================================
   RECRUITING PLAYBOOK - CSS CUSTOM PROPERTIES
   ========================================================================== */

:root {
    /* ─────────────────────────────────────────────────────────────────────
       FARBEN - Primär
       ───────────────────────────────────────────────────────────────────── */
    --rp-primary: #2563eb;
    --rp-primary-hover: #1d4ed8;
    --rp-primary-light: #dbeafe;
    --rp-primary-contrast: #ffffff;

    /* ─────────────────────────────────────────────────────────────────────
       FARBEN - Sekundär
       ───────────────────────────────────────────────────────────────────── */
    --rp-secondary: #64748b;
    --rp-secondary-hover: #475569;
    --rp-secondary-light: #f1f5f9;

    /* ─────────────────────────────────────────────────────────────────────
       FARBEN - Status
       ───────────────────────────────────────────────────────────────────── */
    --rp-success: #22c55e;
    --rp-success-light: #dcfce7;
    --rp-warning: #f59e0b;
    --rp-warning-light: #fef3c7;
    --rp-error: #ef4444;
    --rp-error-light: #fee2e2;
    --rp-info: #3b82f6;
    --rp-info-light: #dbeafe;

    /* ─────────────────────────────────────────────────────────────────────
       FARBEN - Neutral
       ───────────────────────────────────────────────────────────────────── */
    --rp-background: #ffffff;
    --rp-background-alt: #f8fafc;
    --rp-surface: #ffffff;
    --rp-text: #1e293b;
    --rp-text-muted: #64748b;
    --rp-text-light: #94a3b8;
    --rp-border: #e2e8f0;
    --rp-border-dark: #cbd5e1;

    /* ─────────────────────────────────────────────────────────────────────
       TYPOGRAFIE
       ───────────────────────────────────────────────────────────────────── */
    --rp-font-family: inherit;
    --rp-font-size-xs: 0.75rem;
    --rp-font-size-sm: 0.875rem;
    --rp-font-size-base: 1rem;
    --rp-font-size-lg: 1.125rem;
    --rp-font-size-xl: 1.25rem;
    --rp-font-size-2xl: 1.5rem;
    --rp-font-size-3xl: 1.875rem;
    --rp-line-height: 1.5;

    /* ─────────────────────────────────────────────────────────────────────
       SPACING
       ───────────────────────────────────────────────────────────────────── */
    --rp-spacing-xs: 0.25rem;
    --rp-spacing-sm: 0.5rem;
    --rp-spacing-md: 1rem;
    --rp-spacing-lg: 1.5rem;
    --rp-spacing-xl: 2rem;
    --rp-spacing-2xl: 3rem;

    /* ─────────────────────────────────────────────────────────────────────
       BORDER & SHADOW
       ───────────────────────────────────────────────────────────────────── */
    --rp-border-radius-sm: 0.25rem;
    --rp-border-radius: 0.5rem;
    --rp-border-radius-lg: 0.75rem;
    --rp-border-radius-xl: 1rem;
    --rp-border-radius-full: 9999px;
    
    --rp-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --rp-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --rp-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --rp-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);

    /* ─────────────────────────────────────────────────────────────────────
       TRANSITIONS
       ───────────────────────────────────────────────────────────────────── */
    --rp-transition-fast: 150ms ease;
    --rp-transition: 200ms ease;
    --rp-transition-slow: 300ms ease;

    /* ─────────────────────────────────────────────────────────────────────
       LAYOUT
       ───────────────────────────────────────────────────────────────────── */
    --rp-container-max: 1200px;
    --rp-card-padding: var(--rp-spacing-lg);
}

/* ==========================================================================
   DARK MODE (optional, wenn Theme es unterstützt)
   ========================================================================== */

@media (prefers-color-scheme: dark) {
    :root.rp-dark-mode {
        --rp-background: #0f172a;
        --rp-background-alt: #1e293b;
        --rp-surface: #1e293b;
        --rp-text: #f1f5f9;
        --rp-text-muted: #94a3b8;
        --rp-border: #334155;
        --rp-border-dark: #475569;
    }
}
```

### Theme-Überschreibung durch User

Im Admin können Farben angepasst werden. Das Plugin generiert dann dynamisches CSS:

```php
// In wp_head ausgeben
function rp_output_custom_styles() {
    $settings = get_option( 'rp_settings' );
    $design = $settings['design'] ?? [];
    
    if ( empty( $design ) ) {
        return;
    }
    
    echo '<style id="rp-custom-styles">';
    echo ':root {';
    
    if ( ! empty( $design['primary_color'] ) ) {
        echo '--rp-primary: ' . esc_attr( $design['primary_color'] ) . ';';
    }
    
    if ( ! empty( $design['border_radius'] ) ) {
        echo '--rp-border-radius: ' . esc_attr( $design['border_radius'] ) . ';';
    }
    
    // ... weitere Eigenschaften
    
    echo '}';
    echo '</style>';
}
add_action( 'wp_head', 'rp_output_custom_styles', 100 );
```

### Theme-Überschreibung durch Developer

Themes können Plugin-Styles überschreiben:

```css
/* Im Theme: style.css oder eigene CSS-Datei */

/* Option 1: Variablen überschreiben */
:root {
    --rp-primary: #e11d48;  /* Theme-Primärfarbe */
    --rp-border-radius: 0;   /* Eckige Ecken */
    --rp-font-family: 'Inter', sans-serif;
}

/* Option 2: Spezifische Komponenten überschreiben */
.rp-job-card {
    border: 2px solid var(--theme-border-color);
}

.rp-btn-primary {
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
```

---

## Tailwind CSS Konfiguration

### tailwind.config.js

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
    // Nur Plugin-Dateien scannen
    content: [
        './templates/**/*.php',
        './src/Frontend/**/*.php',
        './assets/js/**/*.js',
    ],
    
    // Prefix um Konflikte mit Themes zu vermeiden
    prefix: 'rp-',
    
    // Wichtig: Keine Tailwind-Resets (würde Theme-Styles zerstören)
    corePlugins: {
        preflight: false,
    },
    
    theme: {
        extend: {
            // CSS Custom Properties als Tailwind-Farben
            colors: {
                primary: {
                    DEFAULT: 'var(--rp-primary)',
                    hover: 'var(--rp-primary-hover)',
                    light: 'var(--rp-primary-light)',
                    contrast: 'var(--rp-primary-contrast)',
                },
                secondary: {
                    DEFAULT: 'var(--rp-secondary)',
                    hover: 'var(--rp-secondary-hover)',
                    light: 'var(--rp-secondary-light)',
                },
                success: {
                    DEFAULT: 'var(--rp-success)',
                    light: 'var(--rp-success-light)',
                },
                warning: {
                    DEFAULT: 'var(--rp-warning)',
                    light: 'var(--rp-warning-light)',
                },
                error: {
                    DEFAULT: 'var(--rp-error)',
                    light: 'var(--rp-error-light)',
                },
                surface: 'var(--rp-surface)',
                background: {
                    DEFAULT: 'var(--rp-background)',
                    alt: 'var(--rp-background-alt)',
                },
                text: {
                    DEFAULT: 'var(--rp-text)',
                    muted: 'var(--rp-text-muted)',
                    light: 'var(--rp-text-light)',
                },
                border: {
                    DEFAULT: 'var(--rp-border)',
                    dark: 'var(--rp-border-dark)',
                },
            },
            
            fontFamily: {
                sans: ['var(--rp-font-family)', 'system-ui', 'sans-serif'],
            },
            
            fontSize: {
                xs: 'var(--rp-font-size-xs)',
                sm: 'var(--rp-font-size-sm)',
                base: 'var(--rp-font-size-base)',
                lg: 'var(--rp-font-size-lg)',
                xl: 'var(--rp-font-size-xl)',
                '2xl': 'var(--rp-font-size-2xl)',
                '3xl': 'var(--rp-font-size-3xl)',
            },
            
            borderRadius: {
                sm: 'var(--rp-border-radius-sm)',
                DEFAULT: 'var(--rp-border-radius)',
                lg: 'var(--rp-border-radius-lg)',
                xl: 'var(--rp-border-radius-xl)',
                full: 'var(--rp-border-radius-full)',
            },
            
            boxShadow: {
                sm: 'var(--rp-shadow-sm)',
                DEFAULT: 'var(--rp-shadow)',
                md: 'var(--rp-shadow-md)',
                lg: 'var(--rp-shadow-lg)',
            },
            
            spacing: {
                xs: 'var(--rp-spacing-xs)',
                sm: 'var(--rp-spacing-sm)',
                md: 'var(--rp-spacing-md)',
                lg: 'var(--rp-spacing-lg)',
                xl: 'var(--rp-spacing-xl)',
                '2xl': 'var(--rp-spacing-2xl)',
            },
            
            transitionDuration: {
                fast: '150ms',
                DEFAULT: '200ms',
                slow: '300ms',
            },
        },
    },
    
    plugins: [
        require('@tailwindcss/forms')({
            strategy: 'class', // Nur mit .rp-form-* Klassen
        }),
    ],
};
```

### Verwendung in Templates

```html
<!-- Alle Tailwind-Klassen haben rp- Prefix -->
<div class="rp-bg-surface rp-rounded-lg rp-shadow rp-p-lg">
    <h2 class="rp-text-2xl rp-font-bold rp-text-text rp-mb-md">
        <?php echo esc_html( $job->post_title ); ?>
    </h2>
    
    <p class="rp-text-text-muted rp-text-sm rp-mb-lg">
        <?php echo esc_html( $job->location ); ?>
    </p>
    
    <a href="<?php echo esc_url( $apply_url ); ?>" 
       class="rp-inline-flex rp-items-center rp-px-lg rp-py-sm rp-bg-primary rp-text-primary-contrast rp-rounded rp-font-medium hover:rp-bg-primary-hover rp-transition">
        Jetzt bewerben
    </a>
</div>
```

---

## Alpine.js Komponenten

### Setup

```php
// assets/js/frontend.js wird geladen
wp_enqueue_script(
    'rp-frontend',
    RP_PLUGIN_URL . 'assets/js/frontend.js',
    [],
    RP_VERSION,
    true
);

// Alpine.js von CDN (oder lokal)
wp_enqueue_script(
    'alpinejs',
    'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
    [],
    '3.13.0',
    true
);
wp_script_add_data( 'alpinejs', 'defer', true );

// Daten für JS bereitstellen
wp_localize_script( 'rp-frontend', 'rpData', [
    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
    'restUrl'   => rest_url( 'recruiting/v1/' ),
    'nonce'     => wp_create_nonce( 'rp_frontend' ),
    'i18n'      => [
        'loading'        => __( 'Wird geladen...', 'recruiting-playbook' ),
        'error'          => __( 'Ein Fehler ist aufgetreten', 'recruiting-playbook' ),
        'success'        => __( 'Erfolgreich gesendet', 'recruiting-playbook' ),
        'required'       => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
        'invalidEmail'   => __( 'Bitte gültige E-Mail eingeben', 'recruiting-playbook' ),
        'fileTooLarge'   => __( 'Datei ist zu groß', 'recruiting-playbook' ),
        'invalidFileType'=> __( 'Dateityp nicht erlaubt', 'recruiting-playbook' ),
    ],
    'settings'  => [
        'maxFileSize'    => 10 * 1024 * 1024, // 10 MB
        'allowedTypes'   => [ 'pdf', 'doc', 'docx' ],
    ],
] );
```

### Job-Filter Komponente

```javascript
// assets/js/components/job-filter.js

document.addEventListener('alpine:init', () => {
    Alpine.data('jobFilter', () => ({
        // State
        search: '',
        location: '',
        employmentType: '',
        loading: false,
        
        // Computed
        get hasFilters() {
            return this.search || this.location || this.employmentType;
        },
        
        // Methods
        init() {
            // URL-Parameter beim Laden übernehmen
            const params = new URLSearchParams(window.location.search);
            this.search = params.get('search') || '';
            this.location = params.get('location') || '';
            this.employmentType = params.get('type') || '';
            
            // Filter-Änderungen beobachten
            this.$watch('search', () => this.debouncedFilter());
            this.$watch('location', () => this.filter());
            this.$watch('employmentType', () => this.filter());
        },
        
        debouncedFilter: Alpine.debounce(function() {
            this.filter();
        }, 300),
        
        filter() {
            // URL aktualisieren
            const url = new URL(window.location);
            
            if (this.search) {
                url.searchParams.set('search', this.search);
            } else {
                url.searchParams.delete('search');
            }
            
            if (this.location) {
                url.searchParams.set('location', this.location);
            } else {
                url.searchParams.delete('location');
            }
            
            if (this.employmentType) {
                url.searchParams.set('type', this.employmentType);
            } else {
                url.searchParams.delete('type');
            }
            
            // Browser-History aktualisieren (ohne Reload)
            window.history.pushState({}, '', url);
            
            // Jobs filtern
            this.filterJobs();
        },
        
        filterJobs() {
            const cards = document.querySelectorAll('[data-job-card]');
            
            cards.forEach(card => {
                const title = card.dataset.title?.toLowerCase() || '';
                const location = card.dataset.location?.toLowerCase() || '';
                const type = card.dataset.employmentType || '';
                
                const matchesSearch = !this.search || 
                    title.includes(this.search.toLowerCase());
                const matchesLocation = !this.location || 
                    location === this.location.toLowerCase();
                const matchesType = !this.employmentType || 
                    type === this.employmentType;
                
                if (matchesSearch && matchesLocation && matchesType) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // "Keine Ergebnisse" anzeigen
            this.checkEmptyState();
        },
        
        checkEmptyState() {
            const visibleCards = document.querySelectorAll(
                '[data-job-card]:not([style*="display: none"])'
            );
            const emptyState = document.querySelector('[data-empty-state]');
            
            if (emptyState) {
                emptyState.style.display = visibleCards.length === 0 ? '' : 'none';
            }
        },
        
        reset() {
            this.search = '';
            this.location = '';
            this.employmentType = '';
            
            const url = new URL(window.location);
            url.search = '';
            window.history.pushState({}, '', url);
            
            this.filterJobs();
        },
    }));
});
```

### Bewerbungsformular Komponente

```javascript
// assets/js/components/application-form.js

document.addEventListener('alpine:init', () => {
    Alpine.data('applicationForm', () => ({
        // State
        loading: false,
        success: false,
        errors: {},
        
        // Form Data
        formData: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            cover_letter: '',
            salary_expectation: '',
            earliest_start: '',
            consent_privacy: false,
            consent_talent_pool: false,
        },
        
        // Files
        files: {
            cv: null,
            certificates: [],
        },
        
        // Validation
        validate() {
            this.errors = {};
            
            if (!this.formData.first_name.trim()) {
                this.errors.first_name = rpData.i18n.required;
            }
            
            if (!this.formData.last_name.trim()) {
                this.errors.last_name = rpData.i18n.required;
            }
            
            if (!this.formData.email.trim()) {
                this.errors.email = rpData.i18n.required;
            } else if (!this.isValidEmail(this.formData.email)) {
                this.errors.email = rpData.i18n.invalidEmail;
            }
            
            if (!this.files.cv) {
                this.errors.cv = rpData.i18n.required;
            }
            
            if (!this.formData.consent_privacy) {
                this.errors.consent_privacy = rpData.i18n.required;
            }
            
            return Object.keys(this.errors).length === 0;
        },
        
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        // File Handling
        handleFileSelect(event, type) {
            const file = event.target.files[0];
            
            if (!file) return;
            
            // Größe prüfen
            if (file.size > rpData.settings.maxFileSize) {
                this.errors[type] = rpData.i18n.fileTooLarge;
                event.target.value = '';
                return;
            }
            
            // Typ prüfen
            const ext = file.name.split('.').pop().toLowerCase();
            if (!rpData.settings.allowedTypes.includes(ext)) {
                this.errors[type] = rpData.i18n.invalidFileType;
                event.target.value = '';
                return;
            }
            
            // Fehler löschen und Datei speichern
            delete this.errors[type];
            
            if (type === 'cv') {
                this.files.cv = file;
            } else {
                this.files.certificates.push(file);
            }
        },
        
        removeFile(type, index = null) {
            if (type === 'cv') {
                this.files.cv = null;
            } else if (index !== null) {
                this.files.certificates.splice(index, 1);
            }
        },
        
        // Submit
        async submit() {
            if (!this.validate()) {
                // Zum ersten Fehler scrollen
                const firstError = document.querySelector('.rp-field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            
            this.loading = true;
            this.errors = {};
            
            try {
                const formData = new FormData();
                
                // Text-Felder
                Object.entries(this.formData).forEach(([key, value]) => {
                    formData.append(key, value);
                });
                
                // Job ID
                formData.append('job_id', this.$el.dataset.jobId);
                
                // Dateien
                if (this.files.cv) {
                    formData.append('cv', this.files.cv);
                }
                
                this.files.certificates.forEach((file, index) => {
                    formData.append(`certificate_${index}`, file);
                });
                
                // Nonce
                formData.append('_wpnonce', rpData.nonce);
                
                // Request
                const response = await fetch(rpData.restUrl + 'applications', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': rpData.nonce,
                    },
                    body: formData,
                });
                
                const result = await response.json();
                
                if (!response.ok) {
                    throw new Error(result.message || rpData.i18n.error);
                }
                
                // Erfolg
                this.success = true;
                
                // Event für Tracking
                this.$dispatch('rp-application-submitted', { 
                    jobId: this.$el.dataset.jobId 
                });
                
                // Zum Erfolgs-Message scrollen
                this.$nextTick(() => {
                    const successMsg = document.querySelector('[data-success-message]');
                    if (successMsg) {
                        successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                
            } catch (error) {
                console.error('Application submit error:', error);
                this.errors.form = error.message || rpData.i18n.error;
            } finally {
                this.loading = false;
            }
        },
    }));
});
```

### Modal Komponente

```javascript
// assets/js/components/modal.js

document.addEventListener('alpine:init', () => {
    Alpine.data('modal', (initialOpen = false) => ({
        open: initialOpen,
        
        init() {
            // ESC zum Schließen
            this.$watch('open', (value) => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        },
        
        show() {
            this.open = true;
        },
        
        close() {
            this.open = false;
        },
        
        toggle() {
            this.open = !this.open;
        },
    }));
});
```

### Toast/Notification Komponente

```javascript
// assets/js/components/toast.js

document.addEventListener('alpine:init', () => {
    Alpine.store('toasts', {
        items: [],
        
        add(message, type = 'info', duration = 5000) {
            const id = Date.now();
            
            this.items.push({ id, message, type });
            
            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
        },
        
        remove(id) {
            this.items = this.items.filter(item => item.id !== id);
        },
        
        success(message) {
            this.add(message, 'success');
        },
        
        error(message) {
            this.add(message, 'error');
        },
        
        warning(message) {
            this.add(message, 'warning');
        },
        
        info(message) {
            this.add(message, 'info');
        },
    });
});

// Verwendung in Templates:
// Alpine.store('toasts').success('Bewerbung gesendet!');
```

---

## PHP Templates

### Template-Struktur

```
templates/
├── archive-job.php              # Job-Listing Seite
├── single-job.php               # Job-Einzelansicht
├── partials/
│   ├── job-card.php             # Einzelne Job-Karte
│   ├── job-filters.php          # Filter-Leiste
│   ├── job-meta.php             # Standort, Typ, Gehalt
│   ├── application-form.php     # Bewerbungsformular
│   ├── application-success.php  # Erfolgs-Meldung
│   └── pagination.php           # Seitenzahlen
└── emails/
    ├── application-received.php
    └── ...
```

### Beispiel: job-card.php

```php
<?php
/**
 * Template: Job Card
 * 
 * Überschreibbar im Theme unter:
 * theme/recruiting-playbook/partials/job-card.php
 *
 * @var WP_Post $job
 */

defined( 'ABSPATH' ) || exit;

$location = get_post_meta( $job->ID, '_rp_address_city', true );
$employment_type = get_the_terms( $job->ID, 'employment_type' );
$employment_label = $employment_type ? $employment_type[0]->name : '';
$salary_min = get_post_meta( $job->ID, '_rp_salary_min', true );
$salary_max = get_post_meta( $job->ID, '_rp_salary_max', true );
$salary_public = get_post_meta( $job->ID, '_rp_salary_public', true );
?>

<article 
    class="rp-job-card rp-bg-surface rp-rounded-lg rp-shadow rp-p-lg rp-transition hover:rp-shadow-md"
    data-job-card
    data-title="<?php echo esc_attr( strtolower( $job->post_title ) ); ?>"
    data-location="<?php echo esc_attr( strtolower( $location ) ); ?>"
    data-employment-type="<?php echo esc_attr( $employment_type ? $employment_type[0]->slug : '' ); ?>"
>
    <!-- Header -->
    <div class="rp-flex rp-items-start rp-justify-between rp-gap-md rp-mb-md">
        <div>
            <h3 class="rp-text-xl rp-font-semibold rp-text-text rp-mb-xs">
                <a href="<?php echo esc_url( get_permalink( $job ) ); ?>" 
                   class="hover:rp-text-primary rp-transition">
                    <?php echo esc_html( $job->post_title ); ?>
                </a>
            </h3>
            
            <?php if ( $location ) : ?>
                <p class="rp-text-text-muted rp-text-sm rp-flex rp-items-center rp-gap-xs">
                    <svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?php echo esc_html( $location ); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ( $employment_label ) : ?>
            <span class="rp-inline-flex rp-px-sm rp-py-xs rp-bg-primary-light rp-text-primary rp-text-xs rp-font-medium rp-rounded-full">
                <?php echo esc_html( $employment_label ); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Excerpt -->
    <?php if ( has_excerpt( $job ) ) : ?>
        <p class="rp-text-text-muted rp-text-sm rp-mb-lg rp-line-clamp-2">
            <?php echo esc_html( get_the_excerpt( $job ) ); ?>
        </p>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="rp-flex rp-items-center rp-justify-between rp-pt-md rp-border-t rp-border-border">
        <?php if ( $salary_public && ( $salary_min || $salary_max ) ) : ?>
            <span class="rp-text-sm rp-font-medium rp-text-text">
                <?php echo esc_html( rp_format_salary( $salary_min, $salary_max ) ); ?>
            </span>
        <?php else : ?>
            <span></span>
        <?php endif; ?>
        
        <a href="<?php echo esc_url( get_permalink( $job ) ); ?>" 
           class="rp-inline-flex rp-items-center rp-gap-xs rp-text-primary rp-text-sm rp-font-medium hover:rp-text-primary-hover rp-transition">
            <?php esc_html_e( 'Details ansehen', 'recruiting-playbook' ); ?>
            <svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</article>
```

### Beispiel: application-form.php

```php
<?php
/**
 * Template: Bewerbungsformular
 *
 * @var WP_Post $job
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'rp_settings' );
$required_fields = $settings['forms']['required_fields'] ?? [ 'first_name', 'last_name', 'email', 'cv' ];
?>

<div 
    x-data="applicationForm()" 
    class="rp-application-form"
    data-job-id="<?php echo esc_attr( $job->ID ); ?>"
>
    <!-- Erfolgs-Meldung -->
    <div 
        x-show="success" 
        x-cloak
        data-success-message
        class="rp-bg-success-light rp-border rp-border-success rp-rounded-lg rp-p-lg rp-text-center"
    >
        <svg class="rp-w-12 rp-h-12 rp-mx-auto rp-text-success rp-mb-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="rp-text-xl rp-font-semibold rp-text-text rp-mb-sm">
            <?php esc_html_e( 'Vielen Dank für Ihre Bewerbung!', 'recruiting-playbook' ); ?>
        </h3>
        <p class="rp-text-text-muted">
            <?php esc_html_e( 'Wir haben Ihre Unterlagen erhalten und melden uns zeitnah bei Ihnen.', 'recruiting-playbook' ); ?>
        </p>
    </div>
    
    <!-- Formular -->
    <form 
        x-show="!success"
        @submit.prevent="submit()"
        class="rp-space-y-lg"
        enctype="multipart/form-data"
    >
        <!-- Fehler-Meldung -->
        <div 
            x-show="errors.form" 
            x-cloak
            class="rp-bg-error-light rp-border rp-border-error rp-rounded rp-p-md rp-text-error rp-text-sm"
            x-text="errors.form"
        ></div>
        
        <!-- Name -->
        <div class="rp-grid rp-grid-cols-1 md:rp-grid-cols-2 rp-gap-md">
            <!-- Vorname -->
            <div>
                <label for="rp-first-name" class="rp-block rp-text-sm rp-font-medium rp-text-text rp-mb-xs">
                    <?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?>
                    <?php if ( in_array( 'first_name', $required_fields ) ) : ?>
                        <span class="rp-text-error">*</span>
                    <?php endif; ?>
                </label>
                <input 
                    type="text" 
                    id="rp-first-name"
                    x-model="formData.first_name"
                    :class="{ 'rp-border-error': errors.first_name }"
                    class="rp-form-input rp-w-full rp-rounded rp-border rp-border-border rp-px-md rp-py-sm focus:rp-border-primary focus:rp-ring-1 focus:rp-ring-primary rp-transition"
                    <?php echo in_array( 'first_name', $required_fields ) ? 'required' : ''; ?>
                >
                <p x-show="errors.first_name" x-text="errors.first_name" class="rp-field-error rp-text-error rp-text-xs rp-mt-xs"></p>
            </div>
            
            <!-- Nachname -->
            <div>
                <label for="rp-last-name" class="rp-block rp-text-sm rp-font-medium rp-text-text rp-mb-xs">
                    <?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?>
                    <?php if ( in_array( 'last_name', $required_fields ) ) : ?>
                        <span class="rp-text-error">*</span>
                    <?php endif; ?>
                </label>
                <input 
                    type="text" 
                    id="rp-last-name"
                    x-model="formData.last_name"
                    :class="{ 'rp-border-error': errors.last_name }"
                    class="rp-form-input rp-w-full rp-rounded rp-border rp-border-border rp-px-md rp-py-sm focus:rp-border-primary focus:rp-ring-1 focus:rp-ring-primary rp-transition"
                    <?php echo in_array( 'last_name', $required_fields ) ? 'required' : ''; ?>
                >
                <p x-show="errors.last_name" x-text="errors.last_name" class="rp-field-error rp-text-error rp-text-xs rp-mt-xs"></p>
            </div>
        </div>
        
        <!-- E-Mail -->
        <div>
            <label for="rp-email" class="rp-block rp-text-sm rp-font-medium rp-text-text rp-mb-xs">
                <?php esc_html_e( 'E-Mail', 'recruiting-playbook' ); ?>
                <span class="rp-text-error">*</span>
            </label>
            <input 
                type="email" 
                id="rp-email"
                x-model="formData.email"
                :class="{ 'rp-border-error': errors.email }"
                class="rp-form-input rp-w-full rp-rounded rp-border rp-border-border rp-px-md rp-py-sm focus:rp-border-primary focus:rp-ring-1 focus:rp-ring-primary rp-transition"
                required
            >
            <p x-show="errors.email" x-text="errors.email" class="rp-field-error rp-text-error rp-text-xs rp-mt-xs"></p>
        </div>
        
        <!-- Telefon -->
        <div>
            <label for="rp-phone" class="rp-block rp-text-sm rp-font-medium rp-text-text rp-mb-xs">
                <?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?>
            </label>
            <input 
                type="tel" 
                id="rp-phone"
                x-model="formData.phone"
                class="rp-form-input rp-w-full rp-rounded rp-border rp-border-border rp-px-md rp-py-sm focus:rp-border-primary focus:rp-ring-1 focus:rp-ring-primary rp-transition"
            >
        </div>
        
        <!-- Lebenslauf Upload -->
        <div>
            <label class="rp-block rp-text-sm rp-font-medium rp-text-text rp-mb-xs">
                <?php esc_html_e( 'Lebenslauf', 'recruiting-playbook' ); ?>
                <span class="rp-text-error">*</span>
            </label>
            
            <div 
                :class="{ 'rp-border-error': errors.cv, 'rp-border-primary rp-bg-primary-light': files.cv }"
                class="rp-border-2 rp-border-dashed rp-border-border rp-rounded-lg rp-p-lg rp-text-center rp-transition"
            >
                <template x-if="!files.cv">
                    <div>
                        <svg class="rp-w-10 rp-h-10 rp-mx-auto rp-text-text-muted rp-mb-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <label class="rp-cursor-pointer">
                            <span class="rp-text-primary rp-font-medium hover:rp-text-primary-hover">
                                <?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
                            </span>
                            <span class="rp-text-text-muted">
                                <?php esc_html_e( 'oder per Drag & Drop', 'recruiting-playbook' ); ?>
                            </span>
                            <input 
                                type="file" 
                                class="rp-hidden"
                                accept=".pdf,.doc,.docx"
                                @change="handleFileSelect($event, 'cv')"
                            >
                        </label>
                        <p class="rp-text-xs rp-text-text-muted rp-mt-xs">
                            <?php esc_html_e( 'PDF, DOC, DOCX bis 10 MB', 'recruiting-playbook' ); ?>
                        </p>
                    </div>
                </template>
                
                <template x-if="files.cv">
                    <div class="rp-flex rp-items-center rp-justify-between">
                        <div class="rp-flex rp-items-center rp-gap-sm">
                            <svg class="rp-w-8 rp-h-8 rp-text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div class="rp-text-left">
                                <p class="rp-text-sm rp-font-medium rp-text-text" x-text="files.cv.name"></p>
                                <p class="rp-text-xs rp-text-text-muted" x-text="(files.cv.size / 1024 / 1024).toFixed(2) + ' MB'"></p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="removeFile('cv')"
                            class="rp-text-error hover:rp-text-error rp-p-xs"
                        >
                            <svg class="rp-w-5 rp-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
            
            <p x-show="errors.cv" x-text="errors.cv" class="rp-field-error rp-text-error rp-text-xs rp-mt-xs"></p>
        </div>
        
        <!-- Anschreiben -->
        <div>
            <label for="rp-cover-letter" class="rp-block rp-text-sm rp-font-medium rp-text-text rp-mb-xs">
                <?php esc_html_e( 'Anschreiben', 'recruiting-playbook' ); ?>
            </label>
            <textarea 
                id="rp-cover-letter"
                x-model="formData.cover_letter"
                rows="5"
                class="rp-form-textarea rp-w-full rp-rounded rp-border rp-border-border rp-px-md rp-py-sm focus:rp-border-primary focus:rp-ring-1 focus:rp-ring-primary rp-transition"
                placeholder="<?php esc_attr_e( 'Optional: Erzählen Sie uns, warum Sie zu uns passen...', 'recruiting-playbook' ); ?>"
            ></textarea>
        </div>
        
        <!-- Datenschutz -->
        <div class="rp-space-y-sm">
            <label class="rp-flex rp-items-start rp-gap-sm rp-cursor-pointer">
                <input 
                    type="checkbox" 
                    x-model="formData.consent_privacy"
                    :class="{ 'rp-border-error': errors.consent_privacy }"
                    class="rp-form-checkbox rp-mt-1 rp-rounded rp-border-border rp-text-primary focus:rp-ring-primary"
                    required
                >
                <span class="rp-text-sm rp-text-text-muted">
                    <?php 
                    printf(
                        esc_html__( 'Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
                        '<a href="' . esc_url( get_privacy_policy_url() ) . '" target="_blank" class="rp-text-primary hover:rp-underline">' . esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' ) . '</a>'
                    );
                    ?>
                    <span class="rp-text-error">*</span>
                </span>
            </label>
            <p x-show="errors.consent_privacy" x-text="errors.consent_privacy" class="rp-field-error rp-text-error rp-text-xs"></p>
            
            <label class="rp-flex rp-items-start rp-gap-sm rp-cursor-pointer">
                <input 
                    type="checkbox" 
                    x-model="formData.consent_talent_pool"
                    class="rp-form-checkbox rp-mt-1 rp-rounded rp-border-border rp-text-primary focus:rp-ring-primary"
                >
                <span class="rp-text-sm rp-text-text-muted">
                    <?php esc_html_e( 'Ich möchte in den Talent-Pool aufgenommen werden und auch für zukünftige Stellen kontaktiert werden.', 'recruiting-playbook' ); ?>
                </span>
            </label>
        </div>
        
        <!-- Submit -->
        <div class="rp-pt-md">
            <button 
                type="submit"
                :disabled="loading"
                class="rp-w-full rp-flex rp-items-center rp-justify-center rp-gap-sm rp-px-xl rp-py-md rp-bg-primary rp-text-primary-contrast rp-font-medium rp-rounded-lg hover:rp-bg-primary-hover focus:rp-ring-2 focus:rp-ring-primary focus:rp-ring-offset-2 rp-transition disabled:rp-opacity-50 disabled:rp-cursor-not-allowed"
            >
                <span x-show="!loading">
                    <?php esc_html_e( 'Bewerbung absenden', 'recruiting-playbook' ); ?>
                </span>
                <span x-show="loading" x-cloak class="rp-flex rp-items-center rp-gap-sm">
                    <svg class="rp-animate-spin rp-w-5 rp-h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="rp-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="rp-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <?php esc_html_e( 'Wird gesendet...', 'recruiting-playbook' ); ?>
                </span>
            </button>
        </div>
    </form>
</div>
```

---

## Build-Prozess

### package.json (Frontend)

```json
{
    "name": "recruiting-playbook-frontend",
    "version": "1.0.0",
    "scripts": {
        "dev": "npm run watch",
        "watch": "npm-run-all --parallel watch:*",
        "watch:css": "npx tailwindcss -i ./src/css/main.css -o ./assets/css/public.css --watch",
        "watch:js": "esbuild ./src/js/main.js --bundle --outfile=./assets/js/public.js --watch",
        "build": "npm-run-all build:*",
        "build:css": "npx tailwindcss -i ./src/css/main.css -o ./assets/css/public.css --minify",
        "build:js": "esbuild ./src/js/main.js --bundle --outfile=./assets/js/public.js --minify"
    },
    "devDependencies": {
        "tailwindcss": "^3.4.0",
        "@tailwindcss/forms": "^0.5.0",
        "esbuild": "^0.19.0",
        "npm-run-all": "^4.1.5"
    }
}
```

### src/css/main.css

```css
/* Tailwind Base - NICHT @tailwind base; (würde Theme überschreiben) */
@tailwind components;
@tailwind utilities;

/* CSS Custom Properties */
@import './variables.css';

/* Komponenten */
@import './components/job-card.css';
@import './components/form.css';
@import './components/modal.css';
@import './components/toast.css';
```

### src/js/main.js

```javascript
// Alpine.js Komponenten
import './components/job-filter.js';
import './components/application-form.js';
import './components/modal.js';
import './components/toast.js';

// Alpine.js initialisieren (falls nicht vom CDN)
// import Alpine from 'alpinejs';
// window.Alpine = Alpine;
// Alpine.start();
```

---

## Admin-Einstellungen (Design)

```php
<?php
// src/Admin/Settings/Design.php

namespace RecruitingPlaybook\Admin\Settings;

class Design {
    
    public function register(): void {
        add_settings_section(
            'rp_design_section',
            __( 'Design', 'recruiting-playbook' ),
            [ $this, 'render_section' ],
            'rp-settings-design'
        );
        
        // Primärfarbe
        add_settings_field(
            'rp_primary_color',
            __( 'Primärfarbe', 'recruiting-playbook' ),
            [ $this, 'render_color_field' ],
            'rp-settings-design',
            'rp_design_section',
            [
                'name' => 'design.primary_color',
                'default' => '#2563eb',
                'inherit_option' => true,
            ]
        );
        
        // Border Radius
        add_settings_field(
            'rp_border_radius',
            __( 'Ecken-Radius', 'recruiting-playbook' ),
            [ $this, 'render_select_field' ],
            'rp-settings-design',
            'rp_design_section',
            [
                'name' => 'design.border_radius',
                'options' => [
                    '0'      => __( 'Eckig', 'recruiting-playbook' ),
                    '0.25rem' => __( 'Leicht gerundet', 'recruiting-playbook' ),
                    '0.5rem'  => __( 'Gerundet', 'recruiting-playbook' ),
                    '0.75rem' => __( 'Stark gerundet', 'recruiting-playbook' ),
                    '1rem'    => __( 'Sehr stark gerundet', 'recruiting-playbook' ),
                ],
                'default' => '0.5rem',
            ]
        );
    }
    
    public function render_color_field( array $args ): void {
        $settings = get_option( 'rp_settings' );
        $value = $settings['design'][ str_replace( 'design.', '', $args['name'] ) ] ?? $args['default'];
        ?>
        <div class="rp-color-field">
            <input 
                type="color" 
                name="rp_settings[<?php echo esc_attr( $args['name'] ); ?>]"
                value="<?php echo esc_attr( $value ); ?>"
            >
            <input 
                type="text" 
                value="<?php echo esc_attr( $value ); ?>"
                class="small-text"
                pattern="^#[0-9A-Fa-f]{6}$"
            >
            <?php if ( $args['inherit_option'] ?? false ) : ?>
                <label>
                    <input 
                        type="checkbox" 
                        name="rp_settings[<?php echo esc_attr( $args['name'] ); ?>_inherit]"
                        value="1"
                        <?php checked( $settings['design'][ str_replace( 'design.', '', $args['name'] ) . '_inherit' ] ?? false ); ?>
                    >
                    <?php esc_html_e( 'Vom Theme übernehmen', 'recruiting-playbook' ); ?>
                </label>
            <?php endif; ?>
        </div>
        <?php
    }
}
```

---

## Theme-Kompatibilität

### Template-Überschreibung

Themes können Templates überschreiben:

```
theme/
└── recruiting-playbook/
    ├── archive-job.php
    ├── single-job.php
    └── partials/
        ├── job-card.php
        └── application-form.php
```

```php
// Template-Loader
function rp_locate_template( string $template_name ): string {
    // 1. Im Theme suchen
    $theme_template = locate_template( 'recruiting-playbook/' . $template_name );
    
    if ( $theme_template ) {
        return $theme_template;
    }
    
    // 2. Im Plugin
    return RP_PLUGIN_DIR . 'templates/' . $template_name;
}
```

### CSS-Überschreibung

```css
/* Im Theme können alle Variablen überschrieben werden */

:root {
    /* Theme-Farben für Plugin übernehmen */
    --rp-primary: var(--theme-primary, #2563eb);
    --rp-font-family: var(--theme-font, inherit);
}

/* Oder spezifische Komponenten */
.rp-job-card {
    /* Theme-spezifische Anpassungen */
    border-left: 4px solid var(--rp-primary);
}
```

---

*Letzte Aktualisierung: Januar 2025*
