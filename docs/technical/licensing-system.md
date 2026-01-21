# Lizenz-System

## Übersicht

Das Plugin verwendet ein eigenes Lizenz-System mit folgenden Eigenschaften:

| Aspekt | Entscheidung |
|--------|--------------|
| Lizenzschlüssel | Ein Schlüssel mit Tier |
| Tiers | FREE, PRO, AI_ADDON, BUNDLE |
| Domain-Bindung | Ja |
| Offline-Fallback | 7 Tage Cache |
| Update-Server | GitHub (public Repo) |
| Zahlungsanbieter | Später integrieren |

```
┌─────────────────────────────────────────────────────────────────┐
│                       ARCHITEKTUR                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    WORDPRESS PLUGIN                      │   │
│  │                                                          │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │   │
│  │  │    FREE     │  │     PRO     │  │   AI_ADDON  │     │   │
│  │  │  Features   │  │  Features   │  │  Features   │     │   │
│  │  │  (immer)    │  │ (lizenziert)│  │ (lizenziert)│     │   │
│  │  └─────────────┘  └──────┬──────┘  └──────┬──────┘     │   │
│  │                          │                │             │   │
│  │                          ▼                ▼             │   │
│  │               ┌─────────────────────────────┐           │   │
│  │               │      LICENSE MANAGER        │           │   │
│  │               │                             │           │   │
│  │               │  • Schlüssel validieren     │           │   │
│  │               │  • Feature Flags setzen     │           │   │
│  │               │  • Cache verwalten          │           │   │
│  │               └──────────────┬──────────────┘           │   │
│  │                              │                          │   │
│  └──────────────────────────────┼──────────────────────────┘   │
│                                 │                               │
│                                 │ HTTPS                         │
│                                 ▼                               │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                   LICENSE SERVER                          │  │
│  │                   (später: eigene API)                    │  │
│  │                                                           │  │
│  │  Phase 1: Manuelle Schlüssel-Generierung                 │  │
│  │  Phase 2: Zahlungsanbieter-Integration                   │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                   GITHUB REPOSITORY                       │  │
│  │                   (Update-Server)                         │  │
│  │                                                           │  │
│  │  • Public Repo                                           │  │
│  │  • Releases mit ZIP-Dateien                              │  │
│  │  • Plugin Update Checker Library                         │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Lizenz-Tiers

| Tier | Schlüssel-Prefix | Preis | Features |
|------|------------------|-------|----------|
| FREE | - (kein Schlüssel) | €0 | Basis-Features, 3 Stellen |
| PRO | `RP-PRO-` | €149 einmalig | Unbegrenzt Stellen, Kanban, API, etc. |
| AI_ADDON | `RP-AI-` | €19/Monat | KI-Texte (benötigt PRO) |
| BUNDLE | `RP-BUNDLE-` | €299 + €19/Monat | PRO + AI |

### Schlüssel-Format

```
RP-{TIER}-{RANDOM}-{CHECKSUM}

Beispiele:
RP-PRO-A7K9-M2X4-P8L3-Q5R1-4F2A
RP-AI-B3N8-K4M2-L9P5-R7T1-8C3D
RP-BUNDLE-C5P2-N8K4-M3L7-T9R2-2E4F
```

**Aufbau:**
- `RP-` → Plugin-Prefix
- `{TIER}` → PRO, AI, BUNDLE
- `{RANDOM}` → 16 Zeichen in 4er-Gruppen (Base32)
- `{CHECKSUM}` → 4 Zeichen Prüfsumme

---

## Feature Flags

### Feature-Matrix

| Feature | FREE | PRO | AI_ADDON | BUNDLE |
|---------|:----:|:---:|:--------:|:------:|
| `create_jobs` | ✅ (max 3) | ✅ | ✅ (max 3) | ✅ |
| `unlimited_jobs` | ❌ | ✅ | ❌ | ✅ |
| `application_list` | ✅ | ✅ | ✅ | ✅ |
| `kanban_board` | ❌ | ✅ | ❌ | ✅ |
| `application_status` | Basic | Full | Basic | Full |
| `user_roles` | ❌ | ✅ | ❌ | ✅ |
| `email_templates` | ❌ | ✅ | ❌ | ✅ |
| `api_access` | ❌ | ✅ | ❌ | ✅ |
| `webhooks` | ❌ | ✅ | ❌ | ✅ |
| `reporting` | Basic | Full | Basic | Full |
| `ai_job_generation` | ❌ | ❌ | ✅ | ✅ |
| `ai_text_improvement` | ❌ | ❌ | ✅ | ✅ |
| `ai_templates` | ❌ | ❌ | ✅ | ✅ |
| `custom_branding` | ❌ | ✅ | ❌ | ✅ |
| `priority_support` | ❌ | ✅ | ✅ | ✅ |

### PHP Implementation

```php
<?php
// src/Licensing/FeatureFlags.php

namespace RecruitingPlaybook\Licensing;

class FeatureFlags {
    
    /**
     * Feature-Definitionen pro Tier
     */
    private const FEATURES = [
        'FREE' => [
            'create_jobs'        => true,
            'unlimited_jobs'     => false,
            'max_jobs'           => 3,
            'application_list'   => true,
            'kanban_board'       => false,
            'application_status' => 'basic', // new, hired, rejected
            'user_roles'         => false,
            'email_templates'    => false,
            'api_access'         => false,
            'webhooks'           => false,
            'reporting'          => 'basic',
            'ai_job_generation'  => false,
            'ai_text_improvement'=> false,
            'ai_templates'       => false,
            'custom_branding'    => false,
            'priority_support'   => false,
        ],
        'PRO' => [
            'create_jobs'        => true,
            'unlimited_jobs'     => true,
            'max_jobs'           => -1, // unlimited
            'application_list'   => true,
            'kanban_board'       => true,
            'application_status' => 'full',
            'user_roles'         => true,
            'email_templates'    => true,
            'api_access'         => true,
            'webhooks'           => true,
            'reporting'          => 'full',
            'ai_job_generation'  => false,
            'ai_text_improvement'=> false,
            'ai_templates'       => false,
            'custom_branding'    => true,
            'priority_support'   => true,
        ],
        'AI_ADDON' => [
            // Erbt von FREE, fügt AI hinzu
            'create_jobs'        => true,
            'unlimited_jobs'     => false,
            'max_jobs'           => 3,
            'application_list'   => true,
            'kanban_board'       => false,
            'application_status' => 'basic',
            'user_roles'         => false,
            'email_templates'    => false,
            'api_access'         => false,
            'webhooks'           => false,
            'reporting'          => 'basic',
            'ai_job_generation'  => true,
            'ai_text_improvement'=> true,
            'ai_templates'       => true,
            'custom_branding'    => false,
            'priority_support'   => true,
        ],
        'BUNDLE' => [
            // PRO + AI
            'create_jobs'        => true,
            'unlimited_jobs'     => true,
            'max_jobs'           => -1,
            'application_list'   => true,
            'kanban_board'       => true,
            'application_status' => 'full',
            'user_roles'         => true,
            'email_templates'    => true,
            'api_access'         => true,
            'webhooks'           => true,
            'reporting'          => 'full',
            'ai_job_generation'  => true,
            'ai_text_improvement'=> true,
            'ai_templates'       => true,
            'custom_branding'    => true,
            'priority_support'   => true,
        ],
    ];
    
    /**
     * Aktueller Tier
     */
    private string $tier;
    
    public function __construct( string $tier = 'FREE' ) {
        $this->tier = $tier;
    }
    
    /**
     * Feature-Wert abrufen
     */
    public function get( string $feature ): mixed {
        return self::FEATURES[ $this->tier ][ $feature ] ?? false;
    }
    
    /**
     * Boolean-Check
     */
    public function can( string $feature ): bool {
        $value = $this->get( $feature );
        return (bool) $value;
    }
    
    /**
     * Alle Features für aktuellen Tier
     */
    public function all(): array {
        return self::FEATURES[ $this->tier ] ?? self::FEATURES['FREE'];
    }
    
    /**
     * Tier setzen
     */
    public function setTier( string $tier ): void {
        if ( isset( self::FEATURES[ $tier ] ) ) {
            $this->tier = $tier;
        }
    }
    
    /**
     * Aktuellen Tier abrufen
     */
    public function getTier(): string {
        return $this->tier;
    }
}
```

### Globale Helper-Funktion

```php
<?php
// src/Licensing/helpers.php

/**
 * Prüft ob ein Feature verfügbar ist
 * 
 * @param string $feature Feature-Name
 * @return bool|mixed Feature-Wert
 * 
 * Verwendung:
 *   if ( rp_can( 'kanban_board' ) ) { ... }
 *   $max = rp_can( 'max_jobs' ); // 3 oder -1
 */
function rp_can( string $feature ): mixed {
    static $flags = null;
    
    if ( $flags === null ) {
        $license_manager = \RecruitingPlaybook\Licensing\LicenseManager::get_instance();
        $tier = $license_manager->get_tier();
        $flags = new \RecruitingPlaybook\Licensing\FeatureFlags( $tier );
    }
    
    return $flags->get( $feature );
}

/**
 * Gibt aktuellen Lizenz-Tier zurück
 */
function rp_tier(): string {
    $license_manager = \RecruitingPlaybook\Licensing\LicenseManager::get_instance();
    return $license_manager->get_tier();
}

/**
 * Prüft ob Pro aktiv
 */
function rp_is_pro(): bool {
    return in_array( rp_tier(), [ 'PRO', 'BUNDLE' ], true );
}

/**
 * Prüft ob AI aktiv
 */
function rp_has_ai(): bool {
    return in_array( rp_tier(), [ 'AI_ADDON', 'BUNDLE' ], true );
}
```

### Verwendung im Code

```php
<?php
// In einem Controller oder Service

// Feature-Check vor Ausführung
if ( ! rp_can( 'kanban_board' ) ) {
    return new WP_Error( 
        'feature_not_available',
        __( 'Diese Funktion erfordert Pro.', 'recruiting-playbook' ),
        [ 'status' => 403, 'upgrade_url' => rp_upgrade_url() ]
    );
}

// Stellen-Limit prüfen
$max_jobs = rp_can( 'max_jobs' );
$current_jobs = rp_count_active_jobs();

if ( $max_jobs !== -1 && $current_jobs >= $max_jobs ) {
    return new WP_Error(
        'job_limit_reached',
        sprintf( 
            __( 'Sie haben das Limit von %d Stellen erreicht.', 'recruiting-playbook' ),
            $max_jobs 
        ),
        [ 'status' => 403, 'upgrade_url' => rp_upgrade_url() ]
    );
}

// Bedingte UI-Elemente
if ( rp_can( 'ai_job_generation' ) ) {
    $this->render_ai_button();
}
```

```jsx
// In React (Admin)

// Features werden an JS übergeben
const { tier, features } = window.rpAdmin.license;

// Komponente nur rendern wenn Feature verfügbar
{features.kanban_board && <KanbanBoard />}

// Upgrade-Prompt anzeigen
{!features.kanban_board && (
    <UpgradePrompt 
        feature="Kanban Board" 
        tier="PRO" 
    />
)}
```

---

## License Manager

### Hauptklasse

```php
<?php
// src/Licensing/LicenseManager.php

namespace RecruitingPlaybook\Licensing;

class LicenseManager {
    
    use \RecruitingPlaybook\Traits\Singleton;
    
    private const OPTION_KEY = 'rp_license';
    private const CACHE_KEY = 'rp_license_cache';
    private const CACHE_DURATION = DAY_IN_SECONDS; // 24 Stunden
    private const GRACE_PERIOD = 7 * DAY_IN_SECONDS; // 7 Tage offline
    
    private ?array $license_data = null;
    
    /**
     * Lizenzschlüssel aktivieren
     */
    public function activate( string $license_key ): array {
        // 1. Format validieren
        if ( ! $this->validate_format( $license_key ) ) {
            return [
                'success' => false,
                'error'   => 'invalid_format',
                'message' => __( 'Ungültiges Lizenzschlüssel-Format.', 'recruiting-playbook' ),
            ];
        }
        
        // 2. Bei Server validieren (später)
        $validation = $this->validate_remote( $license_key );
        
        if ( ! $validation['success'] ) {
            return $validation;
        }
        
        // 3. Lokal speichern
        $license_data = [
            'key'          => $license_key,
            'tier'         => $validation['tier'],
            'domain'       => $this->get_domain(),
            'activated_at' => time(),
            'expires_at'   => $validation['expires_at'] ?? null,
            'last_check'   => time(),
        ];
        
        update_option( self::OPTION_KEY, $license_data );
        delete_transient( self::CACHE_KEY );
        
        $this->license_data = $license_data;
        
        return [
            'success' => true,
            'tier'    => $validation['tier'],
            'message' => __( 'Lizenz erfolgreich aktiviert.', 'recruiting-playbook' ),
        ];
    }
    
    /**
     * Lizenz deaktivieren
     */
    public function deactivate(): array {
        $license_data = get_option( self::OPTION_KEY );
        
        if ( empty( $license_data['key'] ) ) {
            return [
                'success' => false,
                'error'   => 'no_license',
                'message' => __( 'Keine aktive Lizenz gefunden.', 'recruiting-playbook' ),
            ];
        }
        
        // Bei Server deaktivieren (später)
        $this->deactivate_remote( $license_data['key'] );
        
        // Lokal löschen
        delete_option( self::OPTION_KEY );
        delete_transient( self::CACHE_KEY );
        
        $this->license_data = null;
        
        return [
            'success' => true,
            'message' => __( 'Lizenz deaktiviert.', 'recruiting-playbook' ),
        ];
    }
    
    /**
     * Aktuellen Tier abrufen
     */
    public function get_tier(): string {
        $license = $this->get_license();
        
        if ( ! $license || ! $this->is_valid() ) {
            return 'FREE';
        }
        
        return $license['tier'] ?? 'FREE';
    }
    
    /**
     * Lizenzdaten abrufen
     */
    public function get_license(): ?array {
        if ( $this->license_data !== null ) {
            return $this->license_data;
        }
        
        $this->license_data = get_option( self::OPTION_KEY, null );
        
        return $this->license_data;
    }
    
    /**
     * Prüft ob Lizenz gültig ist
     */
    public function is_valid(): bool {
        $license = $this->get_license();
        
        if ( ! $license || empty( $license['key'] ) ) {
            return false;
        }
        
        // Domain-Check
        if ( $license['domain'] !== $this->get_domain() ) {
            return false;
        }
        
        // Ablauf-Check (für Subscriptions)
        if ( ! empty( $license['expires_at'] ) && $license['expires_at'] < time() ) {
            return false;
        }
        
        // Cache-Check
        $cache = get_transient( self::CACHE_KEY );
        
        if ( $cache !== false ) {
            return $cache['valid'] ?? false;
        }
        
        // Remote-Check wenn Cache abgelaufen
        return $this->check_and_cache();
    }
    
    /**
     * Remote-Validierung mit Caching
     */
    private function check_and_cache(): bool {
        $license = $this->get_license();
        
        if ( ! $license ) {
            return false;
        }
        
        // Remote-Check versuchen
        $validation = $this->validate_remote( $license['key'] );
        
        if ( $validation['success'] ) {
            // Erfolg: 24h Cache
            set_transient( self::CACHE_KEY, [
                'valid'      => true,
                'checked_at' => time(),
            ], self::CACHE_DURATION );
            
            // Last check aktualisieren
            $license['last_check'] = time();
            update_option( self::OPTION_KEY, $license );
            
            return true;
        }
        
        // Server nicht erreichbar: Grace Period
        if ( $validation['error'] === 'server_unreachable' ) {
            $last_check = $license['last_check'] ?? 0;
            
            if ( ( time() - $last_check ) < self::GRACE_PERIOD ) {
                // Innerhalb Grace Period: Noch gültig
                set_transient( self::CACHE_KEY, [
                    'valid'      => true,
                    'offline'    => true,
                    'checked_at' => time(),
                ], HOUR_IN_SECONDS ); // Kürzerer Cache bei Offline
                
                return true;
            }
            
            // Grace Period abgelaufen
            set_transient( self::CACHE_KEY, [
                'valid'      => false,
                'offline'    => true,
                'checked_at' => time(),
            ], HOUR_IN_SECONDS );
            
            return false;
        }
        
        // Lizenz ungültig
        set_transient( self::CACHE_KEY, [
            'valid'      => false,
            'checked_at' => time(),
        ], self::CACHE_DURATION );
        
        return false;
    }
    
    /**
     * Schlüssel-Format validieren
     */
    private function validate_format( string $key ): bool {
        // Format: RP-{TIER}-{XXXX}-{XXXX}-{XXXX}-{XXXX}-{XXXX}
        $pattern = '/^RP-(PRO|AI|BUNDLE)-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/';
        return (bool) preg_match( $pattern, $key );
    }
    
    /**
     * Tier aus Schlüssel extrahieren
     */
    private function extract_tier( string $key ): string {
        if ( str_starts_with( $key, 'RP-PRO-' ) ) {
            return 'PRO';
        }
        if ( str_starts_with( $key, 'RP-AI-' ) ) {
            return 'AI_ADDON';
        }
        if ( str_starts_with( $key, 'RP-BUNDLE-' ) ) {
            return 'BUNDLE';
        }
        return 'FREE';
    }
    
    /**
     * Domain ermitteln
     */
    private function get_domain(): string {
        $site_url = get_site_url();
        $parsed = parse_url( $site_url );
        return $parsed['host'] ?? '';
    }
    
    /**
     * Remote-Validierung
     * 
     * PHASE 1: Offline-Validierung (nur Format + Checksum)
     * PHASE 2: Server-Validierung
     */
    private function validate_remote( string $key ): array {
        // ──────────────────────────────────────────────────────
        // PHASE 1: Offline-Validierung
        // Später durch echte API ersetzen
        // ──────────────────────────────────────────────────────
        
        if ( ! $this->validate_format( $key ) ) {
            return [
                'success' => false,
                'error'   => 'invalid_key',
                'message' => __( 'Ungültiger Lizenzschlüssel.', 'recruiting-playbook' ),
            ];
        }
        
        // Checksum validieren (letzte 4 Zeichen)
        if ( ! $this->validate_checksum( $key ) ) {
            return [
                'success' => false,
                'error'   => 'invalid_checksum',
                'message' => __( 'Lizenzschlüssel-Prüfsumme ungültig.', 'recruiting-playbook' ),
            ];
        }
        
        // Tier extrahieren
        $tier = $this->extract_tier( $key );
        
        // Für AI: Prüfen ob PRO auch aktiv (oder BUNDLE)
        if ( $tier === 'AI_ADDON' ) {
            $existing = $this->get_license();
            if ( ! $existing || ! in_array( $existing['tier'], [ 'PRO', 'BUNDLE' ], true ) ) {
                // AI alleine erlauben, aber mit Free-Limits
            }
        }
        
        return [
            'success'    => true,
            'tier'       => $tier,
            'expires_at' => $tier === 'AI_ADDON' ? strtotime( '+1 year' ) : null,
        ];
        
        // ──────────────────────────────────────────────────────
        // PHASE 2: Echte API (später implementieren)
        // ──────────────────────────────────────────────────────
        /*
        $response = wp_remote_post( 'https://api.recruiting-playbook.de/v1/license/validate', [
            'timeout' => 15,
            'body'    => [
                'license_key' => $key,
                'domain'      => $this->get_domain(),
                'plugin_version' => RP_VERSION,
            ],
        ] );
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'error'   => 'server_unreachable',
                'message' => __( 'Lizenzserver nicht erreichbar.', 'recruiting-playbook' ),
            ];
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        return $body;
        */
    }
    
    /**
     * Remote-Deaktivierung
     */
    private function deactivate_remote( string $key ): void {
        // PHASE 2: API-Call
        /*
        wp_remote_post( 'https://api.recruiting-playbook.de/v1/license/deactivate', [
            'body' => [
                'license_key' => $key,
                'domain'      => $this->get_domain(),
            ],
        ] );
        */
    }
    
    /**
     * Checksum validieren
     * 
     * Einfacher Algorithmus für Phase 1
     */
    private function validate_checksum( string $key ): bool {
        // Letzte 4 Zeichen = Checksum
        $checksum = substr( $key, -4 );
        $payload = substr( $key, 0, -5 ); // Ohne "-XXXX"
        
        // Einfache Checksum: CRC32 gekürzt
        $calculated = strtoupper( substr( dechex( crc32( $payload ) ), 0, 4 ) );
        
        return $checksum === $calculated;
    }
    
    /**
     * Lizenzstatus für Admin-Anzeige
     */
    public function get_status(): array {
        $license = $this->get_license();
        
        if ( ! $license ) {
            return [
                'tier'        => 'FREE',
                'is_active'   => false,
                'is_valid'    => true, // Free ist immer "gültig"
                'message'     => __( 'Kostenlose Version', 'recruiting-playbook' ),
                'upgrade_url' => $this->get_upgrade_url(),
            ];
        }
        
        $is_valid = $this->is_valid();
        $cache = get_transient( self::CACHE_KEY );
        $is_offline = $cache['offline'] ?? false;
        
        $status = [
            'tier'         => $license['tier'],
            'is_active'    => true,
            'is_valid'     => $is_valid,
            'activated_at' => $license['activated_at'],
            'expires_at'   => $license['expires_at'],
            'domain'       => $license['domain'],
            'is_offline'   => $is_offline,
        ];
        
        if ( ! $is_valid ) {
            $status['message'] = __( 'Lizenz ungültig oder abgelaufen.', 'recruiting-playbook' );
        } elseif ( $is_offline ) {
            $status['message'] = __( 'Offline-Modus (Lizenzserver nicht erreichbar)', 'recruiting-playbook' );
        } else {
            $tier_labels = [
                'PRO'      => 'Pro',
                'AI_ADDON' => 'AI Addon',
                'BUNDLE'   => 'Pro + AI Bundle',
            ];
            $status['message'] = sprintf( 
                __( '%s Lizenz aktiv', 'recruiting-playbook' ),
                $tier_labels[ $license['tier'] ] ?? $license['tier']
            );
        }
        
        return $status;
    }
    
    /**
     * Upgrade-URL
     */
    public function get_upgrade_url(): string {
        // Später: Eigener Shop
        return 'https://recruiting-playbook.de/pricing/';
    }
}

/**
 * Helper-Funktion für Upgrade-URL
 */
function rp_upgrade_url( ?string $tier = null ): string {
    $base = LicenseManager::get_instance()->get_upgrade_url();
    
    if ( $tier ) {
        $base .= '?tier=' . strtolower( $tier );
    }
    
    return $base;
}
```

---

## Lizenzschlüssel generieren (Admin-Tool)

Für Phase 1 ein einfaches Tool zur manuellen Generierung:

```php
<?php
// tools/generate-license.php (CLI)

/**
 * Lizenzschlüssel generieren
 * 
 * Verwendung: php generate-license.php PRO
 */

if ( php_sapi_name() !== 'cli' ) {
    die( 'CLI only' );
}

$tier = $argv[1] ?? 'PRO';
$valid_tiers = [ 'PRO', 'AI', 'BUNDLE' ];

if ( ! in_array( $tier, $valid_tiers, true ) ) {
    die( "Ungültiger Tier. Erlaubt: " . implode( ', ', $valid_tiers ) . "\n" );
}

function generate_random_block(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Keine I, O, 0, 1
    $block = '';
    for ( $i = 0; $i < 4; $i++ ) {
        $block .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
    }
    return $block;
}

function generate_license_key( string $tier ): string {
    $prefix = "RP-{$tier}";
    
    $blocks = [];
    for ( $i = 0; $i < 4; $i++ ) {
        $blocks[] = generate_random_block();
    }
    
    $payload = $prefix . '-' . implode( '-', $blocks );
    
    // Checksum
    $checksum = strtoupper( substr( dechex( crc32( $payload ) ), 0, 4 ) );
    
    return $payload . '-' . $checksum;
}

$key = generate_license_key( $tier );

echo "Generierter Lizenzschlüssel ($tier):\n";
echo $key . "\n";
```

---

## Update-Mechanismus (GitHub)

### Plugin Update Checker einbinden

```php
<?php
// src/Core/Updater.php

namespace RecruitingPlaybook\Core;

class Updater {
    
    private const GITHUB_REPO = 'AImitSK/recruiting-playbook';
    
    /**
     * Updater initialisieren
     */
    public function init(): void {
        // Plugin Update Checker Library
        // https://github.com/YahnisElsts/plugin-update-checker
        
        if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
            require_once RP_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
        }
        
        $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/' . self::GITHUB_REPO,
            RP_PLUGIN_FILE,
            'recruiting-playbook'
        );
        
        // Releases statt Branch
        $updateChecker->setBranch( 'main' );
        $updateChecker->getVcsApi()->enableReleaseAssets();
    }
}
```

### GitHub Release Workflow

```yaml
# .github/workflows/release.yml

name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer
      
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
      
      - name: Build assets
        run: |
          cd admin-ui && npm ci && npm run build
          cd ../frontend && npm ci && npm run build
      
      - name: Create release ZIP
        run: |
          mkdir -p release/recruiting-playbook
          
          # Dateien kopieren
          cp -r src release/recruiting-playbook/
          cp -r assets release/recruiting-playbook/
          cp -r templates release/recruiting-playbook/
          cp -r languages release/recruiting-playbook/
          cp -r vendor release/recruiting-playbook/
          cp recruiting-playbook.php release/recruiting-playbook/
          cp uninstall.php release/recruiting-playbook/
          cp readme.txt release/recruiting-playbook/
          
          # ZIP erstellen
          cd release
          zip -r recruiting-playbook-${{ github.ref_name }}.zip recruiting-playbook
      
      - name: Create GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          files: release/recruiting-playbook-${{ github.ref_name }}.zip
          generate_release_notes: true
```

### Composer: Plugin Update Checker

```json
// composer.json (ergänzen)
{
    "require": {
        "yahnis-elsts/plugin-update-checker": "^5.3"
    }
}
```

---

## Admin-UI für Lizenz

### Lizenz-Einstellungsseite

```php
<?php
// src/Admin/Pages/LicensePage.php

namespace RecruitingPlaybook\Admin\Pages;

use RecruitingPlaybook\Licensing\LicenseManager;

class LicensePage {
    
    public function render(): void {
        $license_manager = LicenseManager::get_instance();
        $status = $license_manager->get_status();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Lizenz', 'recruiting-playbook' ); ?></h1>
            
            <div class="rp-license-card">
                <!-- Aktueller Status -->
                <div class="rp-license-status rp-license-status--<?php echo esc_attr( strtolower( $status['tier'] ) ); ?>">
                    <h2><?php echo esc_html( $status['message'] ); ?></h2>
                    
                    <?php if ( $status['is_active'] ) : ?>
                        <p>
                            <?php printf(
                                esc_html__( 'Aktiviert am: %s', 'recruiting-playbook' ),
                                date_i18n( get_option( 'date_format' ), $status['activated_at'] )
                            ); ?>
                        </p>
                        
                        <?php if ( $status['expires_at'] ) : ?>
                            <p>
                                <?php printf(
                                    esc_html__( 'Gültig bis: %s', 'recruiting-playbook' ),
                                    date_i18n( get_option( 'date_format' ), $status['expires_at'] )
                                ); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ( $status['is_offline'] ) : ?>
                            <p class="rp-notice rp-notice--warning">
                                <?php esc_html_e( 'Offline-Modus: Lizenzserver nicht erreichbar.', 'recruiting-playbook' ); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Lizenz aktivieren/ändern -->
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'rp_license_action' ); ?>
                    <input type="hidden" name="action" value="rp_license_activate">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="license_key">
                                    <?php esc_html_e( 'Lizenzschlüssel', 'recruiting-playbook' ); ?>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    id="license_key" 
                                    name="license_key"
                                    class="regular-text"
                                    placeholder="RP-PRO-XXXX-XXXX-XXXX-XXXX-XXXX"
                                    pattern="RP-(PRO|AI|BUNDLE)-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                                >
                                <p class="description">
                                    <?php esc_html_e( 'Geben Sie Ihren Lizenzschlüssel ein.', 'recruiting-playbook' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Lizenz aktivieren', 'recruiting-playbook' ); ?>
                        </button>
                        
                        <?php if ( $status['is_active'] ) : ?>
                            <button type="submit" name="action" value="rp_license_deactivate" class="button">
                                <?php esc_html_e( 'Lizenz deaktivieren', 'recruiting-playbook' ); ?>
                            </button>
                        <?php endif; ?>
                    </p>
                </form>
                
                <!-- Upgrade-Bereich -->
                <?php if ( $status['tier'] === 'FREE' ) : ?>
                    <div class="rp-upgrade-box">
                        <h3><?php esc_html_e( 'Upgrade auf Pro', 'recruiting-playbook' ); ?></h3>
                        <ul>
                            <li>✓ <?php esc_html_e( 'Unbegrenzt Stellen', 'recruiting-playbook' ); ?></li>
                            <li>✓ <?php esc_html_e( 'Kanban-Board', 'recruiting-playbook' ); ?></li>
                            <li>✓ <?php esc_html_e( 'REST API & Webhooks', 'recruiting-playbook' ); ?></li>
                            <li>✓ <?php esc_html_e( 'E-Mail-Templates', 'recruiting-playbook' ); ?></li>
                        </ul>
                        <a href="<?php echo esc_url( $status['upgrade_url'] ); ?>" class="button button-primary button-hero" target="_blank">
                            <?php esc_html_e( 'Jetzt upgraden', 'recruiting-playbook' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
```

---

## Upgrade-Prompts im Plugin

### Komponente für Feature-Gating

```php
<?php
// src/Admin/Components/UpgradePrompt.php

namespace RecruitingPlaybook\Admin\Components;

class UpgradePrompt {
    
    /**
     * Upgrade-Hinweis rendern
     */
    public static function render( string $feature, string $required_tier = 'PRO' ): void {
        $tier_labels = [
            'PRO'      => 'Pro',
            'AI_ADDON' => 'AI Addon',
            'BUNDLE'   => 'Pro + AI Bundle',
        ];
        
        ?>
        <div class="rp-upgrade-prompt">
            <div class="rp-upgrade-prompt__icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <div class="rp-upgrade-prompt__content">
                <h4>
                    <?php printf(
                        esc_html__( '%s erfordert %s', 'recruiting-playbook' ),
                        esc_html( $feature ),
                        esc_html( $tier_labels[ $required_tier ] ?? $required_tier )
                    ); ?>
                </h4>
                <p>
                    <?php esc_html_e( 'Upgraden Sie, um diese Funktion freizuschalten.', 'recruiting-playbook' ); ?>
                </p>
                <a href="<?php echo esc_url( rp_upgrade_url( $required_tier ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Inline-Hinweis (kleiner)
     */
    public static function inline( string $feature, string $required_tier = 'PRO' ): void {
        ?>
        <span class="rp-pro-badge" title="<?php esc_attr_e( 'Pro-Feature', 'recruiting-playbook' ); ?>">
            <a href="<?php echo esc_url( rp_upgrade_url( $required_tier ) ); ?>">
                <?php echo esc_html( strtoupper( $required_tier ) ); ?>
            </a>
        </span>
        <?php
    }
}
```

### Verwendung

```php
<?php
// In einer Admin-Seite

// Ganzer Bereich gesperrt
if ( ! rp_can( 'kanban_board' ) ) {
    \RecruitingPlaybook\Admin\Components\UpgradePrompt::render( 
        __( 'Kanban-Board', 'recruiting-playbook' ), 
        'PRO' 
    );
    return;
}

// Oder inline neben einem Menüpunkt
?>
<li>
    <a href="#">
        <?php esc_html_e( 'Kanban-Board', 'recruiting-playbook' ); ?>
        <?php if ( ! rp_can( 'kanban_board' ) ) : ?>
            <?php \RecruitingPlaybook\Admin\Components\UpgradePrompt::inline( 'Kanban', 'PRO' ); ?>
        <?php endif; ?>
    </a>
</li>
```

```jsx
// React-Komponente

function UpgradePrompt({ feature, tier = 'PRO' }) {
    const tierLabels = {
        PRO: 'Pro',
        AI_ADDON: 'AI Addon',
        BUNDLE: 'Pro + AI Bundle',
    };
    
    return (
        <div className="rp-upgrade-prompt">
            <div className="rp-upgrade-prompt__icon">
                <LockIcon />
            </div>
            <div className="rp-upgrade-prompt__content">
                <h4>{feature} erfordert {tierLabels[tier]}</h4>
                <p>Upgraden Sie, um diese Funktion freizuschalten.</p>
                <a 
                    href={`${window.rpAdmin.upgradeUrl}?tier=${tier.toLowerCase()}`}
                    className="button button-primary"
                    target="_blank"
                >
                    Mehr erfahren
                </a>
            </div>
        </div>
    );
}
```

---

## Zusammenfassung

### Phase 1 (Jetzt)

- [x] Feature Flags implementieren
- [x] `rp_can()` Helper-Funktion
- [x] License Manager mit Offline-Validierung
- [x] Einfache Checksum-Validierung
- [x] Lizenz-Einstellungsseite im Admin
- [x] Upgrade-Prompts
- [x] GitHub Update Checker

### Phase 2 (Später: Zahlungsintegration)

- [ ] Lizenz-Server API aufsetzen
- [ ] Zahlungsanbieter integrieren (Stripe/Paddle/LemonSqueezy)
- [ ] Automatische Lizenz-Generierung nach Kauf
- [ ] Webhook für Subscription-Events
- [ ] Kunden-Portal für Lizenzverwaltung

---

## Sicherheit gegen lokale Manipulation

### Bekannte Angriffsvektoren

| Angriff | Beschreibung | Risiko |
|---------|--------------|--------|
| **Option Manipulation** | `update_option('rp_license', [...])` im Theme/Plugin | Hoch |
| **Database Edit** | Direktes Ändern von `wp_options` | Mittel |
| **Transient Injection** | Fake-Cache für Lizenzstatus | Mittel |
| **Code Patching** | `rp_can()` Funktion überschreiben | Niedrig |

### Gegenmaßnahmen

#### 1. Regelmäßige Remote-Validierung (Phase 2)

```php
<?php
// src/Licensing/LicenseValidator.php

class LicenseValidator {

    /**
     * Täglicher Cron-Check (zusätzlich zum Cache)
     */
    public static function schedule_daily_check(): void {
        if ( ! wp_next_scheduled( 'rp_license_daily_check' ) ) {
            wp_schedule_event( time(), 'daily', 'rp_license_daily_check' );
        }

        add_action( 'rp_license_daily_check', [ self::class, 'validate_remote' ] );
    }

    /**
     * Remote-Validierung durchführen
     */
    public static function validate_remote(): void {
        $license_manager = LicenseManager::get_instance();
        $license = $license_manager->get_license();

        if ( ! $license || empty( $license['key'] ) ) {
            return;
        }

        // Server-Check erzwingen (Cache ignorieren)
        $result = $license_manager->validate_with_server( $license['key'], force: true );

        if ( ! $result['valid'] ) {
            // Lizenz ungültig: Downgrade auf FREE
            $license_manager->force_downgrade( $result['reason'] );

            // Admin benachrichtigen
            self::notify_admin_invalid_license( $result );
        }
    }
}
```

#### 2. Integritätsprüfung der Lizenzdaten

```php
<?php
// src/Licensing/LicenseIntegrity.php

class LicenseIntegrity {

    private const INTEGRITY_KEY = 'rp_license_integrity';

    /**
     * Signatur für Lizenzdaten erstellen
     */
    public static function sign( array $license_data ): string {
        $payload = json_encode( [
            'key'    => $license_data['key'],
            'tier'   => $license_data['tier'],
            'domain' => $license_data['domain'],
        ] );

        // HMAC mit site-spezifischem Secret
        return hash_hmac( 'sha256', $payload, self::get_secret() );
    }

    /**
     * Signatur verifizieren
     */
    public static function verify( array $license_data, string $signature ): bool {
        $expected = self::sign( $license_data );
        return hash_equals( $expected, $signature );
    }

    /**
     * Site-spezifisches Secret (nicht in DB, schwer zu faken)
     */
    private static function get_secret(): string {
        // Kombination aus mehreren Quellen
        return hash( 'sha256', implode( '|', [
            NONCE_KEY,           // wp-config.php
            SECURE_AUTH_KEY,     // wp-config.php
            DB_NAME,
            site_url(),
        ] ) );
    }
}
```

#### 3. Feature-Checks mit Redundanz

```php
<?php
// src/Licensing/helpers.php (erweitert)

/**
 * Sicherer Feature-Check mit Integritätsprüfung
 */
function rp_can_secure( string $feature ): mixed {
    static $verified = null;

    // Einmalige Integritätsprüfung pro Request
    if ( $verified === null ) {
        $license_manager = LicenseManager::get_instance();
        $license = $license_manager->get_license();

        if ( $license ) {
            $stored_signature = get_option( LicenseIntegrity::INTEGRITY_KEY );
            $verified = LicenseIntegrity::verify( $license, $stored_signature );

            if ( ! $verified ) {
                // Manipulation erkannt!
                do_action( 'rp_license_tampering_detected', $license );

                // Fallback auf FREE
                return ( new FeatureFlags( 'FREE' ) )->get( $feature );
            }
        }

        $verified = true;
    }

    return rp_can( $feature );
}
```

#### 4. Anomalie-Erkennung

```php
<?php
// src/Licensing/AnomalyDetector.php

class AnomalyDetector {

    /**
     * Prüft auf verdächtige Änderungen
     */
    public static function check(): array {
        $anomalies = [];

        // 1. Plötzlicher Tier-Wechsel ohne API-Call
        $last_known_tier = get_transient( 'rp_last_known_tier' );
        $current_tier = rp_tier();

        if ( $last_known_tier && $last_known_tier !== $current_tier ) {
            // War ein legitimer API-Call?
            $last_api_call = get_transient( 'rp_last_license_api_call' );

            if ( ! $last_api_call || ( time() - $last_api_call ) > 60 ) {
                $anomalies[] = [
                    'type'    => 'unexpected_tier_change',
                    'from'    => $last_known_tier,
                    'to'      => $current_tier,
                    'message' => 'Tier changed without API validation',
                ];
            }
        }

        // 2. Lizenz aktiviert, aber nie Server-Check
        $license = LicenseManager::get_instance()->get_license();
        if ( $license && empty( $license['last_server_check'] ) ) {
            $anomalies[] = [
                'type'    => 'never_validated',
                'message' => 'License was never validated with server',
            ];
        }

        // 3. Zu viele Feature-Checks in kurzer Zeit (Brute-Force?)
        // ... weitere Checks

        return $anomalies;
    }
}
```

### Admin-Warnung bei Manipulation

```php
<?php
// Bei erkannter Manipulation
add_action( 'rp_license_tampering_detected', function( $license ) {
    // Admin-Notice
    add_action( 'admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Recruiting Playbook: Lizenzproblem erkannt', 'recruiting-playbook' ); ?></strong><br>
                <?php esc_html_e( 'Die Lizenzdaten scheinen manipuliert worden zu sein. Bitte aktivieren Sie Ihre Lizenz erneut.', 'recruiting-playbook' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-license' ) ); ?>">
                    <?php esc_html_e( 'Zur Lizenzverwaltung', 'recruiting-playbook' ); ?>
                </a>
            </p>
        </div>
        <?php
    } );

    // Log für spätere Analyse
    error_log( sprintf(
        '[Recruiting Playbook] License tampering detected. Domain: %s, Stored tier: %s',
        site_url(),
        $license['tier'] ?? 'unknown'
    ) );
} );
```

### Empfohlene Sicherheitsstufen

| Stufe | Maßnahmen | Für |
|-------|-----------|-----|
| **Basic (Phase 1)** | Checksum, Integritätssignatur | MVP |
| **Standard (Phase 2)** | + Täglicher Remote-Check | Production |
| **Strict (Optional)** | + Anomalie-Erkennung, Echtzeit-Checks | High-Value |

### Hinweis zur Realität

> **Wichtig:** Kein Lizenz-System ist 100% sicher gegen Manipulation.
> Ziel ist es, den Aufwand für Umgehung höher zu machen als den Preis der Lizenz.
> Die meisten Nutzer sind ehrlich – die Maßnahmen schützen vor Gelegenheits-Piraterie,
> nicht vor determinierter Reverse-Engineering.

---

*Letzte Aktualisierung: Januar 2025*
