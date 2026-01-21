# UI Notifications & Messaging

## Übersicht

Das Plugin verwendet ein einheitliches System für User-Feedback:

| Bereich | Technologie | Library |
|---------|-------------|---------|
| **Admin (React)** | React | react-hot-toast |
| **Frontend (Alpine)** | Alpine.js | Eigenbau |
| **Messages** | JSON | Zentrale Registry |

```
┌─────────────────────────────────────────────────────────────────┐
│                    NOTIFICATION-SYSTEM                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              ZENTRALE MESSAGE-REGISTRY                   │   │
│  │                   (JSON, übersetzbar)                    │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│              ┌──────────────┴──────────────┐                   │
│              │                             │                    │
│              ▼                             ▼                    │
│  ┌─────────────────────┐       ┌─────────────────────┐        │
│  │   ADMIN (React)     │       │  FRONTEND (Alpine)  │        │
│  │   react-hot-toast   │       │   Alpine.js Store   │        │
│  └─────────────────────┘       └─────────────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Notification-Typen

| Typ | Verwendung | Farbe | Icon |
|-----|------------|-------|------|
| `success` | Aktion erfolgreich | Grün | ✓ Checkmark |
| `error` | Fehler aufgetreten | Rot | ✕ X |
| `warning` | Warnung/Hinweis | Gelb/Orange | ⚠ Dreieck |
| `info` | Information | Blau | ℹ Info |
| `loading` | Ladevorgang | Grau | Spinner |

---

## 1. Zentrale Message-Registry

### Struktur

```
src/
└── messages/
    ├── index.php           # Loader
    ├── de_DE.json          # Deutsch
    ├── en_US.json          # Englisch
    └── ...
```

### Message-Format

```json
// src/messages/de_DE.json
{
    "application": {
        "submitted": "Ihre Bewerbung wurde erfolgreich gesendet!",
        "submitted_description": "Wir melden uns zeitnah bei Ihnen.",
        "error": "Bewerbung konnte nicht gesendet werden",
        "error_description": "Bitte versuchen Sie es erneut oder kontaktieren Sie uns.",
        "deleted": "Bewerbung wurde gelöscht",
        "status_changed": "Status wurde auf \"{status}\" geändert"
    },
    "job": {
        "created": "Stelle wurde erstellt",
        "updated": "Änderungen wurden gespeichert",
        "published": "Stelle wurde veröffentlicht",
        "archived": "Stelle wurde archiviert",
        "deleted": "Stelle wurde gelöscht"
    },
    "candidate": {
        "added_to_pool": "Kandidat wurde zum Talent-Pool hinzugefügt",
        "removed_from_pool": "Kandidat wurde aus dem Talent-Pool entfernt",
        "deleted_gdpr": "Kandidatendaten wurden gelöscht (DSGVO)"
    },
    "file": {
        "uploaded": "Datei wurde hochgeladen",
        "deleted": "Datei wurde gelöscht",
        "too_large": "Datei ist zu groß (max. {max} MB)",
        "invalid_type": "Dateityp nicht erlaubt. Erlaubt: {types}",
        "upload_error": "Fehler beim Hochladen der Datei"
    },
    "validation": {
        "required": "Dieses Feld ist erforderlich",
        "email": "Bitte geben Sie eine gültige E-Mail-Adresse ein",
        "phone": "Bitte geben Sie eine gültige Telefonnummer ein",
        "min_length": "Mindestens {min} Zeichen erforderlich",
        "max_length": "Maximal {max} Zeichen erlaubt",
        "date_past": "Datum muss in der Vergangenheit liegen",
        "date_future": "Datum muss in der Zukunft liegen"
    },
    "settings": {
        "saved": "Einstellungen wurden gespeichert",
        "error": "Fehler beim Speichern der Einstellungen",
        "reset": "Einstellungen wurden zurückgesetzt"
    },
    "api": {
        "key_created": "API-Schlüssel wurde erstellt",
        "key_deleted": "API-Schlüssel wurde gelöscht",
        "key_copied": "API-Schlüssel wurde in die Zwischenablage kopiert",
        "unauthorized": "Nicht autorisiert",
        "rate_limited": "Zu viele Anfragen. Bitte warten Sie einen Moment."
    },
    "webhook": {
        "created": "Webhook wurde erstellt",
        "updated": "Webhook wurde aktualisiert",
        "deleted": "Webhook wurde gelöscht",
        "test_sent": "Test-Webhook wurde gesendet",
        "test_success": "Webhook-Test erfolgreich",
        "test_failed": "Webhook-Test fehlgeschlagen: {error}"
    },
    "email": {
        "sent": "E-Mail wurde gesendet",
        "error": "E-Mail konnte nicht gesendet werden",
        "template_saved": "E-Mail-Vorlage wurde gespeichert"
    },
    "export": {
        "started": "Export wird vorbereitet...",
        "ready": "Export ist bereit zum Download",
        "error": "Fehler beim Erstellen des Exports"
    },
    "general": {
        "saved": "Gespeichert",
        "deleted": "Gelöscht",
        "copied": "In Zwischenablage kopiert",
        "error": "Ein Fehler ist aufgetreten",
        "network_error": "Netzwerkfehler. Bitte prüfen Sie Ihre Verbindung.",
        "loading": "Wird geladen...",
        "please_wait": "Bitte warten...",
        "confirm_delete": "Sind Sie sicher, dass Sie dies löschen möchten?",
        "action_irreversible": "Diese Aktion kann nicht rückgängig gemacht werden."
    }
}
```

### PHP-Loader

```php
<?php
// src/messages/index.php

namespace RecruitingPlaybook\Messages;

class MessageRegistry {
    
    private static ?array $messages = null;
    private static string $locale = 'de_DE';
    
    /**
     * Messages laden
     */
    public static function load( ?string $locale = null ): array {
        if ( $locale ) {
            self::$locale = $locale;
        } elseif ( self::$locale === 'de_DE' ) {
            self::$locale = determine_locale();
        }
        
        if ( self::$messages !== null ) {
            return self::$messages;
        }
        
        $file = __DIR__ . '/' . self::$locale . '.json';
        
        // Fallback zu Englisch
        if ( ! file_exists( $file ) ) {
            $file = __DIR__ . '/en_US.json';
        }
        
        // Fallback zu Deutsch
        if ( ! file_exists( $file ) ) {
            $file = __DIR__ . '/de_DE.json';
        }
        
        self::$messages = json_decode( file_get_contents( $file ), true ) ?? [];
        
        return self::$messages;
    }
    
    /**
     * Einzelne Message abrufen
     * 
     * @param string $key z.B. "application.submitted"
     * @param array $replacements z.B. ['status' => 'Interview']
     */
    public static function get( string $key, array $replacements = [] ): string {
        $messages = self::load();
        
        // Key auflösen (z.B. "application.submitted" → $messages['application']['submitted'])
        $parts = explode( '.', $key );
        $message = $messages;
        
        foreach ( $parts as $part ) {
            if ( ! isset( $message[ $part ] ) ) {
                return $key; // Fallback: Key selbst zurückgeben
            }
            $message = $message[ $part ];
        }
        
        if ( ! is_string( $message ) ) {
            return $key;
        }
        
        // Platzhalter ersetzen
        foreach ( $replacements as $placeholder => $value ) {
            $message = str_replace( '{' . $placeholder . '}', $value, $message );
        }
        
        return $message;
    }
    
    /**
     * Alle Messages für JS bereitstellen
     */
    public static function get_all(): array {
        return self::load();
    }
    
    /**
     * Messages für bestimmte Kategorie
     */
    public static function get_category( string $category ): array {
        $messages = self::load();
        return $messages[ $category ] ?? [];
    }
}

// Hilfsfunktion
function rp_message( string $key, array $replacements = [] ): string {
    return MessageRegistry::get( $key, $replacements );
}
```

### Messages an JavaScript übergeben

```php
<?php
// In Assets.php

public function localize_scripts(): void {
    wp_localize_script( 'rp-admin', 'rpAdmin', [
        'restUrl' => rest_url( 'recruiting/v1/' ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
        'messages' => MessageRegistry::get_all(),
    ] );
    
    wp_localize_script( 'rp-frontend', 'rpData', [
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'restUrl'  => rest_url( 'recruiting/v1/' ),
        'nonce'    => wp_create_nonce( 'rp_frontend' ),
        'messages' => MessageRegistry::get_all(),
    ] );
}
```

---

## 2. Admin: react-hot-toast

### Installation

```bash
cd admin-ui
npm install react-hot-toast
```

### Setup

```jsx
// admin-ui/src/App.jsx

import { Toaster } from 'react-hot-toast';

function App() {
    return (
        <div className="rp-admin-app">
            {/* Toast Container */}
            <Toaster 
                position="top-right"
                toastOptions={{
                    // Standard-Optionen
                    duration: 4000,
                    
                    // Styling passend zum Plugin
                    style: {
                        background: 'var(--rp-surface)',
                        color: 'var(--rp-text)',
                        border: '1px solid var(--rp-border)',
                        borderRadius: 'var(--rp-border-radius)',
                        boxShadow: 'var(--rp-shadow-lg)',
                        padding: '12px 16px',
                        fontSize: '14px',
                    },
                    
                    // Typ-spezifische Styles
                    success: {
                        iconTheme: {
                            primary: 'var(--rp-success)',
                            secondary: 'white',
                        },
                    },
                    error: {
                        duration: 6000,
                        iconTheme: {
                            primary: 'var(--rp-error)',
                            secondary: 'white',
                        },
                    },
                }}
            />
            
            {/* App Content */}
            <Router>
                {/* ... */}
            </Router>
        </div>
    );
}

export default App;
```

### Toast-Service

```javascript
// admin-ui/src/services/toast.js

import toast from 'react-hot-toast';

/**
 * Message aus Registry holen
 */
function getMessage(key, replacements = {}) {
    const messages = window.rpAdmin?.messages || {};
    const parts = key.split('.');
    
    let message = messages;
    for (const part of parts) {
        if (!message[part]) return key;
        message = message[part];
    }
    
    if (typeof message !== 'string') return key;
    
    // Platzhalter ersetzen
    Object.entries(replacements).forEach(([placeholder, value]) => {
        message = message.replace(`{${placeholder}}`, value);
    });
    
    return message;
}

/**
 * Toast-Service mit Message-Registry Integration
 */
export const toastService = {
    /**
     * Erfolgs-Toast
     */
    success(messageKey, replacements = {}, options = {}) {
        const message = getMessage(messageKey, replacements);
        return toast.success(message, options);
    },
    
    /**
     * Fehler-Toast
     */
    error(messageKey, replacements = {}, options = {}) {
        const message = getMessage(messageKey, replacements);
        return toast.error(message, {
            duration: 6000,
            ...options,
        });
    },
    
    /**
     * Info-Toast
     */
    info(messageKey, replacements = {}, options = {}) {
        const message = getMessage(messageKey, replacements);
        return toast(message, {
            icon: 'ℹ️',
            ...options,
        });
    },
    
    /**
     * Warning-Toast
     */
    warning(messageKey, replacements = {}, options = {}) {
        const message = getMessage(messageKey, replacements);
        return toast(message, {
            icon: '⚠️',
            style: {
                borderLeft: '4px solid var(--rp-warning)',
            },
            ...options,
        });
    },
    
    /**
     * Loading-Toast (gibt ID zurück zum späteren Update)
     */
    loading(messageKey, replacements = {}, options = {}) {
        const message = getMessage(messageKey, replacements);
        return toast.loading(message, options);
    },
    
    /**
     * Promise-Toast (zeigt Loading → Success/Error)
     */
    promise(promise, messages, options = {}) {
        return toast.promise(
            promise,
            {
                loading: getMessage(messages.loading || 'general.loading'),
                success: getMessage(messages.success || 'general.saved'),
                error: (err) => getMessage(messages.error || 'general.error') + 
                    (err?.message ? `: ${err.message}` : ''),
            },
            options
        );
    },
    
    /**
     * Toast aktualisieren
     */
    update(toastId, options) {
        toast.dismiss(toastId);
        if (options.type === 'success') {
            return this.success(options.message);
        } else if (options.type === 'error') {
            return this.error(options.message);
        }
    },
    
    /**
     * Toast schließen
     */
    dismiss(toastId) {
        toast.dismiss(toastId);
    },
    
    /**
     * Alle Toasts schließen
     */
    dismissAll() {
        toast.dismiss();
    },
    
    /**
     * Custom Toast mit JSX
     */
    custom(render, options = {}) {
        return toast.custom(render, options);
    },
};

export default toastService;
```

### Verwendung in Komponenten

```jsx
// admin-ui/src/components/Applications/ApplicationActions.jsx

import { toastService } from '../../services/toast';
import { useApplications } from '../../hooks/useApplications';

function ApplicationActions({ application }) {
    const { updateStatus, deleteApplication } = useApplications();
    
    const handleStatusChange = async (newStatus) => {
        try {
            await updateStatus(application.id, newStatus);
            toastService.success('application.status_changed', { 
                status: newStatus 
            });
        } catch (error) {
            toastService.error('application.error');
        }
    };
    
    const handleDelete = async () => {
        // Mit Promise-Toast
        toastService.promise(
            deleteApplication(application.id),
            {
                loading: 'general.please_wait',
                success: 'application.deleted',
                error: 'general.error',
            }
        );
    };
    
    return (
        <div className="application-actions">
            {/* ... */}
        </div>
    );
}
```

```jsx
// admin-ui/src/components/Settings/SettingsForm.jsx

import { toastService } from '../../services/toast';

function SettingsForm() {
    const [saving, setSaving] = useState(false);
    
    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        
        const loadingToast = toastService.loading('general.please_wait');
        
        try {
            await saveSettings(formData);
            toastService.dismiss(loadingToast);
            toastService.success('settings.saved');
        } catch (error) {
            toastService.dismiss(loadingToast);
            toastService.error('settings.error');
        } finally {
            setSaving(false);
        }
    };
    
    return (
        <form onSubmit={handleSubmit}>
            {/* ... */}
        </form>
    );
}
```

### Custom Toast für komplexe Fälle

```jsx
// Bestätigungs-Toast mit Aktion

import toast from 'react-hot-toast';

function confirmDelete(onConfirm) {
    toast.custom(
        (t) => (
            <div className={`
                rp-bg-surface rp-border rp-border-border rp-rounded-lg rp-shadow-lg rp-p-4
                ${t.visible ? 'rp-animate-enter' : 'rp-animate-leave'}
            `}>
                <p className="rp-text-text rp-font-medium rp-mb-2">
                    {getMessage('general.confirm_delete')}
                </p>
                <p className="rp-text-text-muted rp-text-sm rp-mb-4">
                    {getMessage('general.action_irreversible')}
                </p>
                <div className="rp-flex rp-gap-2 rp-justify-end">
                    <button
                        onClick={() => toast.dismiss(t.id)}
                        className="rp-px-3 rp-py-1.5 rp-text-sm rp-text-text-muted hover:rp-text-text rp-transition"
                    >
                        Abbrechen
                    </button>
                    <button
                        onClick={() => {
                            toast.dismiss(t.id);
                            onConfirm();
                        }}
                        className="rp-px-3 rp-py-1.5 rp-text-sm rp-bg-error rp-text-white rp-rounded hover:rp-bg-error/90 rp-transition"
                    >
                        Löschen
                    </button>
                </div>
            </div>
        ),
        {
            duration: Infinity,
            position: 'top-center',
        }
    );
}
```

---

## 3. Frontend: Alpine.js Toast Store

### Toast Store

```javascript
// assets/js/components/toast.js

document.addEventListener('alpine:init', () => {
    
    /**
     * Message aus Registry holen
     */
    function getMessage(key, replacements = {}) {
        const messages = window.rpData?.messages || {};
        const parts = key.split('.');
        
        let message = messages;
        for (const part of parts) {
            if (!message[part]) return key;
            message = message[part];
        }
        
        if (typeof message !== 'string') return key;
        
        Object.entries(replacements).forEach(([placeholder, value]) => {
            message = message.replace(`{${placeholder}}`, value);
        });
        
        return message;
    }
    
    /**
     * Toast Store
     */
    Alpine.store('toast', {
        items: [],
        maxItems: 5,
        
        /**
         * Toast hinzufügen
         */
        add(messageKey, type = 'info', options = {}) {
            const id = Date.now() + Math.random();
            const message = getMessage(messageKey, options.replacements || {});
            const duration = options.duration ?? this.getDefaultDuration(type);
            
            const toast = {
                id,
                message,
                type,
                title: options.title ? getMessage(options.title) : null,
                dismissible: options.dismissible ?? true,
                progress: duration > 0,
                createdAt: Date.now(),
            };
            
            // Max items begrenzen
            if (this.items.length >= this.maxItems) {
                this.items.shift();
            }
            
            this.items.push(toast);
            
            // Auto-dismiss
            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
            
            return id;
        },
        
        /**
         * Standard-Duration nach Typ
         */
        getDefaultDuration(type) {
            const durations = {
                success: 4000,
                error: 6000,
                warning: 5000,
                info: 4000,
                loading: 0, // Kein Auto-dismiss
            };
            return durations[type] ?? 4000;
        },
        
        /**
         * Toast entfernen
         */
        remove(id) {
            const index = this.items.findIndex(item => item.id === id);
            if (index > -1) {
                this.items.splice(index, 1);
            }
        },
        
        /**
         * Alle entfernen
         */
        clear() {
            this.items = [];
        },
        
        // Convenience-Methoden
        success(messageKey, options = {}) {
            return this.add(messageKey, 'success', options);
        },
        
        error(messageKey, options = {}) {
            return this.add(messageKey, 'error', options);
        },
        
        warning(messageKey, options = {}) {
            return this.add(messageKey, 'warning', options);
        },
        
        info(messageKey, options = {}) {
            return this.add(messageKey, 'info', options);
        },
        
        loading(messageKey, options = {}) {
            return this.add(messageKey, 'loading', { ...options, duration: 0 });
        },
        
        /**
         * Loading in Success/Error umwandeln
         */
        update(id, type, messageKey, options = {}) {
            const index = this.items.findIndex(item => item.id === id);
            if (index > -1) {
                this.items[index] = {
                    ...this.items[index],
                    type,
                    message: getMessage(messageKey, options.replacements || {}),
                    progress: true,
                };
                
                // Auto-dismiss nach Update
                const duration = options.duration ?? this.getDefaultDuration(type);
                if (duration > 0) {
                    setTimeout(() => this.remove(id), duration);
                }
            }
        },
    });
});
```

### Toast Container Komponente

```html
<!-- templates/partials/toast-container.php -->

<div 
    x-data
    x-show="$store.toast.items.length > 0"
    class="rp-fixed rp-bottom-4 rp-right-4 rp-z-50 rp-flex rp-flex-col rp-gap-2 rp-max-w-sm rp-w-full"
    aria-live="polite"
>
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="rp-transition rp-ease-out rp-duration-300"
            x-transition:enter-start="rp-opacity-0 rp-translate-x-4"
            x-transition:enter-end="rp-opacity-100 rp-translate-x-0"
            x-transition:leave="rp-transition rp-ease-in rp-duration-200"
            x-transition:leave-start="rp-opacity-100 rp-translate-x-0"
            x-transition:leave-end="rp-opacity-0 rp-translate-x-4"
            :class="{
                'rp-bg-success-light rp-border-success': toast.type === 'success',
                'rp-bg-error-light rp-border-error': toast.type === 'error',
                'rp-bg-warning-light rp-border-warning': toast.type === 'warning',
                'rp-bg-info-light rp-border-info': toast.type === 'info',
                'rp-bg-surface rp-border-border': toast.type === 'loading',
            }"
            class="rp-border rp-rounded-lg rp-shadow-lg rp-overflow-hidden"
            role="alert"
        >
            <div class="rp-p-4 rp-flex rp-items-start rp-gap-3">
                <!-- Icon -->
                <div class="rp-flex-shrink-0">
                    <!-- Success Icon -->
                    <template x-if="toast.type === 'success'">
                        <svg class="rp-w-5 rp-h-5 rp-text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    
                    <!-- Error Icon -->
                    <template x-if="toast.type === 'error'">
                        <svg class="rp-w-5 rp-h-5 rp-text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    
                    <!-- Warning Icon -->
                    <template x-if="toast.type === 'warning'">
                        <svg class="rp-w-5 rp-h-5 rp-text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    
                    <!-- Info Icon -->
                    <template x-if="toast.type === 'info'">
                        <svg class="rp-w-5 rp-h-5 rp-text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    
                    <!-- Loading Spinner -->
                    <template x-if="toast.type === 'loading'">
                        <svg class="rp-w-5 rp-h-5 rp-text-primary rp-animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="rp-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="rp-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </template>
                </div>
                
                <!-- Content -->
                <div class="rp-flex-1 rp-min-w-0">
                    <p 
                        x-show="toast.title" 
                        x-text="toast.title"
                        class="rp-text-sm rp-font-medium rp-text-text"
                    ></p>
                    <p 
                        x-text="toast.message"
                        :class="toast.title ? 'rp-text-text-muted' : 'rp-text-text'"
                        class="rp-text-sm"
                    ></p>
                </div>
                
                <!-- Close Button -->
                <button
                    x-show="toast.dismissible"
                    @click="$store.toast.remove(toast.id)"
                    class="rp-flex-shrink-0 rp-p-1 rp-rounded rp-text-text-muted hover:rp-text-text hover:rp-bg-black/5 rp-transition"
                >
                    <svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Progress Bar -->
            <div 
                x-show="toast.progress && toast.type !== 'loading'"
                class="rp-h-1 rp-bg-black/10"
            >
                <div 
                    :class="{
                        'rp-bg-success': toast.type === 'success',
                        'rp-bg-error': toast.type === 'error',
                        'rp-bg-warning': toast.type === 'warning',
                        'rp-bg-info': toast.type === 'info',
                    }"
                    class="rp-h-full rp-transition-all rp-duration-100"
                    :style="`width: ${100 - ((Date.now() - toast.createdAt) / (toast.type === 'error' ? 60 : 40))}%`"
                    x-init="$nextTick(() => { 
                        const interval = setInterval(() => {
                            $el.style.width = Math.max(0, 100 - ((Date.now() - toast.createdAt) / (toast.type === 'error' ? 60 : 40))) + '%';
                        }, 100);
                        setTimeout(() => clearInterval(interval), 6000);
                    })"
                ></div>
            </div>
        </div>
    </template>
</div>
```

### Verwendung im Frontend

```html
<!-- In application-form.php -->

<form 
    x-data="applicationForm()" 
    @submit.prevent="submit()"
>
    <!-- ... Formular-Felder ... -->
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('applicationForm', () => ({
        loading: false,
        
        async submit() {
            this.loading = true;
            const loadingId = Alpine.store('toast').loading('general.please_wait');
            
            try {
                const response = await fetch(/* ... */);
                
                if (response.ok) {
                    Alpine.store('toast').update(loadingId, 'success', 'application.submitted');
                    this.success = true;
                } else {
                    const error = await response.json();
                    Alpine.store('toast').update(loadingId, 'error', 'application.error');
                }
            } catch (error) {
                Alpine.store('toast').update(loadingId, 'error', 'general.network_error');
            } finally {
                this.loading = false;
            }
        },
    }));
});
</script>
```

```javascript
// Validierungsfehler anzeigen
if (!this.validate()) {
    Alpine.store('toast').error('validation.required');
    return;
}

// Datei zu groß
if (file.size > maxSize) {
    Alpine.store('toast').error('file.too_large', {
        replacements: { max: '10' }
    });
    return;
}

// Erfolgreicher Upload
Alpine.store('toast').success('file.uploaded');
```

---

## 4. Styling (Tailwind)

### Toast-Animationen

```css
/* In src/css/components/toast.css */

/* Enter Animation */
@keyframes rp-toast-enter {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Leave Animation */
@keyframes rp-toast-leave {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

.rp-animate-toast-enter {
    animation: rp-toast-enter 0.3s ease-out;
}

.rp-animate-toast-leave {
    animation: rp-toast-leave 0.2s ease-in forwards;
}

/* Progress Bar Animation */
@keyframes rp-progress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

.rp-toast-progress {
    animation: rp-progress var(--duration, 4s) linear forwards;
}
```

---

## 5. API-Error-Handling

### Standardisierte Error-Responses

```php
<?php
// In REST API Controllern

class BaseController extends WP_REST_Controller {
    
    /**
     * Error Response mit Message-Key
     */
    protected function error_response( 
        string $message_key, 
        int $status = 400, 
        array $replacements = [],
        array $details = [] 
    ): WP_Error {
        return new WP_Error(
            $message_key,
            rp_message( $message_key, $replacements ),
            [
                'status'  => $status,
                'details' => $details,
            ]
        );
    }
    
    /**
     * Validation Error
     */
    protected function validation_error( array $errors ): WP_Error {
        $messages = [];
        
        foreach ( $errors as $field => $message_key ) {
            $messages[ $field ] = rp_message( $message_key );
        }
        
        return new WP_Error(
            'validation_error',
            rp_message( 'general.error' ),
            [
                'status' => 422,
                'errors' => $messages,
            ]
        );
    }
}
```

### Frontend Error-Handling

```javascript
// API-Response verarbeiten
async function handleApiResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
        // Validation Errors
        if (response.status === 422 && data.data?.errors) {
            Object.values(data.data.errors).forEach(error => {
                Alpine.store('toast').error(error);
            });
            return { success: false, errors: data.data.errors };
        }
        
        // Andere Fehler
        const messageKey = data.code || 'general.error';
        Alpine.store('toast').error(messageKey);
        return { success: false, error: data.message };
    }
    
    return { success: true, data };
}
```

---

## 6. Best Practices

### Do's ✅

```javascript
// Spezifische Messages verwenden
toastService.success('application.submitted');

// Mit Platzhaltern für dynamische Werte
toastService.success('application.status_changed', { status: 'Interview' });

// Loading → Success/Error Flow
const id = toastService.loading('general.please_wait');
try {
    await saveData();
    toastService.update(id, 'success', 'settings.saved');
} catch (e) {
    toastService.update(id, 'error', 'settings.error');
}
```

### Don'ts ❌

```javascript
// Keine hardcoded Strings
toastService.success('Gespeichert!'); // ❌

// Keine technischen Fehlermeldungen
toastService.error(error.stack); // ❌

// Nicht zu viele Toasts auf einmal
items.forEach(item => toastService.success('saved')); // ❌
// Besser: toastService.success('items.batch_saved', { count: items.length });
```

---

## 7. Feature-Matrix

| Feature | FREE | PRO | AI |
|---------|:----:|:---:|:--:|
| Toast Notifications | ✅ | ✅ | ✅ |
| Mehrsprachige Messages | ✅ | ✅ | ✅ |
| Custom Toast Styling | ❌ | ✅ | ✅ |
| Toast Position wählbar | ❌ | ✅ | ✅ |

---

*Letzte Aktualisierung: Januar 2025*
