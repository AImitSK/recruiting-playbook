# Spam-Schutz

## Übersicht

Das Plugin verwendet einen **mehrschichtigen Spam-Schutz** für das Bewerbungsformular:

| Schicht | Methode | Tier | Standard |
|---------|---------|------|----------|
| 1 | Honeypot | FREE | Immer aktiv |
| 2 | Time-Check | FREE | Immer aktiv |
| 3 | Rate Limiting | FREE | Immer aktiv |
| 4 | Turnstile / hCaptcha | FREE | Optional |

```
┌─────────────────────────────────────────────────────────────────┐
│                    SPAM-SCHUTZ PIPELINE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Bewerbung eingereicht                                          │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────┐                                           │
│  │   HONEYPOT      │  Feld ausgefüllt? ──▶ SPAM ❌             │
│  └────────┬────────┘                                           │
│           │ OK                                                  │
│           ▼                                                     │
│  ┌─────────────────┐                                           │
│  │   TIME-CHECK    │  < 3 Sekunden? ──▶ SPAM ❌                │
│  └────────┬────────┘                                           │
│           │ OK                                                  │
│           ▼                                                     │
│  ┌─────────────────┐                                           │
│  │  RATE LIMITING  │  > 5 pro Stunde? ──▶ BLOCKED ❌           │
│  └────────┬────────┘                                           │
│           │ OK                                                  │
│           ▼                                                     │
│  ┌─────────────────┐                                           │
│  │   TURNSTILE     │  Score zu niedrig? ──▶ SPAM ❌            │
│  │   (optional)    │                                           │
│  └────────┬────────┘                                           │
│           │ OK                                                  │
│           ▼                                                     │
│  ┌─────────────────┐                                           │
│  │   VERARBEITUNG  │  Bewerbung speichern ✅                   │
│  └─────────────────┘                                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. Honeypot (Immer aktiv)

Ein unsichtbares Feld, das nur von Bots ausgefüllt wird.

### Implementation

```php
<?php
// src/Frontend/Forms/SpamProtection.php

namespace RecruitingPlaybook\Frontend\Forms;

class SpamProtection {
    
    /**
     * Honeypot-Feld Name (sieht wie echtes Feld aus)
     */
    private const HONEYPOT_FIELD = 'website_url';
    
    /**
     * Honeypot-Feld generieren
     */
    public static function render_honeypot(): string {
        $field_name = self::HONEYPOT_FIELD;
        
        // CSS inline um Verstecken sicherzustellen
        return <<<HTML
        <div class="rp-form-field rp-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;">
            <label for="rp-{$field_name}">Website (nicht ausfüllen)</label>
            <input 
                type="text" 
                name="{$field_name}" 
                id="rp-{$field_name}"
                tabindex="-1"
                autocomplete="off"
                value=""
            >
        </div>
        HTML;
    }
    
    /**
     * Honeypot validieren
     */
    public static function validate_honeypot( array $data ): bool {
        $honeypot_value = $data[ self::HONEYPOT_FIELD ] ?? '';
        
        // Wenn ausgefüllt = Bot
        return empty( $honeypot_value );
    }
}
```

### Template Integration

```php
<!-- templates/partials/application-form.php -->

<form x-data="applicationForm()" @submit.prevent="submit()">
    
    <!-- Honeypot (unsichtbar) -->
    <?php echo \RecruitingPlaybook\Frontend\Forms\SpamProtection::render_honeypot(); ?>
    
    <!-- Echte Felder -->
    <div class="rp-form-field">
        <label for="rp-first-name">Vorname *</label>
        <input type="text" name="first_name" id="rp-first-name" required>
    </div>
    
    <!-- ... weitere Felder ... -->
    
</form>
```

---

## 2. Time-Check (Immer aktiv)

Prüft, ob das Formular zu schnell abgeschickt wurde (Bots sind schnell).

### Implementation

```php
<?php
// src/Frontend/Forms/SpamProtection.php (Fortsetzung)

class SpamProtection {
    
    /**
     * Mindestzeit in Sekunden
     */
    private const MIN_SUBMIT_TIME = 3;
    
    /**
     * Timestamp-Feld Name
     */
    private const TIMESTAMP_FIELD = 'rp_form_token';
    
    /**
     * Timestamp-Feld generieren (verschlüsselt)
     */
    public static function render_timestamp(): string {
        $timestamp = time();
        $token = self::encrypt_timestamp( $timestamp );
        
        return <<<HTML
        <input type="hidden" name="rp_form_token" value="{$token}">
        HTML;
    }
    
    /**
     * Timestamp validieren
     */
    public static function validate_timestamp( array $data ): bool {
        $token = $data[ self::TIMESTAMP_FIELD ] ?? '';
        
        if ( empty( $token ) ) {
            return false;
        }
        
        $timestamp = self::decrypt_timestamp( $token );
        
        if ( ! $timestamp ) {
            return false; // Manipulation
        }
        
        $elapsed = time() - $timestamp;
        
        // Zu schnell = Bot
        if ( $elapsed < self::MIN_SUBMIT_TIME ) {
            return false;
        }
        
        // Zu langsam = Token abgelaufen (24 Stunden)
        if ( $elapsed > DAY_IN_SECONDS ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Timestamp verschlüsseln
     */
    private static function encrypt_timestamp( int $timestamp ): string {
        $data = $timestamp . '|' . wp_generate_password( 8, false );
        return base64_encode( self::simple_encrypt( $data ) );
    }
    
    /**
     * Timestamp entschlüsseln
     */
    private static function decrypt_timestamp( string $token ): ?int {
        try {
            $data = self::simple_decrypt( base64_decode( $token ) );
            $parts = explode( '|', $data );
            return (int) ( $parts[0] ?? 0 );
        } catch ( \Exception $e ) {
            return null;
        }
    }
    
    /**
     * Einfache Verschlüsselung
     */
    private static function simple_encrypt( string $data ): string {
        $key = wp_salt( 'auth' );
        return openssl_encrypt( $data, 'AES-256-CBC', $key, 0, substr( $key, 0, 16 ) );
    }
    
    private static function simple_decrypt( string $data ): string {
        $key = wp_salt( 'auth' );
        return openssl_decrypt( $data, 'AES-256-CBC', $key, 0, substr( $key, 0, 16 ) );
    }
}
```

---

## 3. Rate Limiting (Immer aktiv)

Begrenzt Bewerbungen pro IP-Adresse.

### Implementation

```php
<?php
// src/Frontend/Forms/RateLimiter.php

namespace RecruitingPlaybook\Frontend\Forms;

class RateLimiter {
    
    /**
     * Max Bewerbungen pro IP pro Stunde
     */
    private const MAX_PER_HOUR = 5;
    
    /**
     * Max Bewerbungen pro IP pro Tag
     */
    private const MAX_PER_DAY = 20;
    
    /**
     * Transient Prefix
     */
    private const TRANSIENT_PREFIX = 'rp_rate_';
    
    /**
     * Prüft ob IP blockiert ist
     */
    public static function is_blocked(): bool {
        $ip_hash = self::get_ip_hash();
        
        // Stündliches Limit
        $hourly_key = self::TRANSIENT_PREFIX . 'hourly_' . $ip_hash;
        $hourly_count = (int) get_transient( $hourly_key );
        
        if ( $hourly_count >= self::MAX_PER_HOUR ) {
            return true;
        }
        
        // Tägliches Limit
        $daily_key = self::TRANSIENT_PREFIX . 'daily_' . $ip_hash;
        $daily_count = (int) get_transient( $daily_key );
        
        if ( $daily_count >= self::MAX_PER_DAY ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Zähler erhöhen nach erfolgreicher Bewerbung
     */
    public static function increment(): void {
        $ip_hash = self::get_ip_hash();
        
        // Stündlich
        $hourly_key = self::TRANSIENT_PREFIX . 'hourly_' . $ip_hash;
        $hourly_count = (int) get_transient( $hourly_key );
        set_transient( $hourly_key, $hourly_count + 1, HOUR_IN_SECONDS );
        
        // Täglich
        $daily_key = self::TRANSIENT_PREFIX . 'daily_' . $ip_hash;
        $daily_count = (int) get_transient( $daily_key );
        set_transient( $daily_key, $daily_count + 1, DAY_IN_SECONDS );
    }
    
    /**
     * Verbleibende Versuche
     */
    public static function get_remaining(): array {
        $ip_hash = self::get_ip_hash();
        
        $hourly_key = self::TRANSIENT_PREFIX . 'hourly_' . $ip_hash;
        $hourly_count = (int) get_transient( $hourly_key );
        
        $daily_key = self::TRANSIENT_PREFIX . 'daily_' . $ip_hash;
        $daily_count = (int) get_transient( $daily_key );
        
        return [
            'hourly' => max( 0, self::MAX_PER_HOUR - $hourly_count ),
            'daily'  => max( 0, self::MAX_PER_DAY - $daily_count ),
        ];
    }
    
    /**
     * IP-Adresse hashen (DSGVO: keine rohe IP speichern)
     */
    private static function get_ip_hash(): string {
        $ip = self::get_client_ip();
        return hash( 'sha256', $ip . wp_salt( 'auth' ) );
    }
    
    /**
     * Client-IP ermitteln
     */
    private static function get_client_ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = $_SERVER[ $header ];
                
                // Bei X-Forwarded-For: erste IP nehmen
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
}
```

### Konfigurierbare Limits (Admin)

```php
<?php
// In Einstellungen

$settings = [
    'spam_protection' => [
        'rate_limit_hourly' => 5,  // Konfigurierbar
        'rate_limit_daily'  => 20, // Konfigurierbar
    ],
];
```

---

## 4. Turnstile / hCaptcha (Optional)

### Admin-Einstellungen

```php
<?php
// src/Admin/Settings/SpamSettings.php

namespace RecruitingPlaybook\Admin\Settings;

class SpamSettings {
    
    public function register(): void {
        add_settings_section(
            'rp_spam_section',
            __( 'Spam-Schutz', 'recruiting-playbook' ),
            [ $this, 'render_section' ],
            'rp-settings-spam'
        );
        
        // Captcha-Anbieter
        add_settings_field(
            'rp_captcha_provider',
            __( 'Captcha-Anbieter', 'recruiting-playbook' ),
            [ $this, 'render_captcha_provider' ],
            'rp-settings-spam',
            'rp_spam_section'
        );
        
        // Turnstile Keys
        add_settings_field(
            'rp_turnstile_keys',
            __( 'Cloudflare Turnstile', 'recruiting-playbook' ),
            [ $this, 'render_turnstile_keys' ],
            'rp-settings-spam',
            'rp_spam_section'
        );
        
        // hCaptcha Keys
        add_settings_field(
            'rp_hcaptcha_keys',
            __( 'hCaptcha', 'recruiting-playbook' ),
            [ $this, 'render_hcaptcha_keys' ],
            'rp-settings-spam',
            'rp_spam_section'
        );
    }
    
    public function render_captcha_provider(): void {
        $settings = get_option( 'rp_settings' );
        $provider = $settings['spam']['captcha_provider'] ?? 'none';
        ?>
        <select name="rp_settings[spam][captcha_provider]">
            <option value="none" <?php selected( $provider, 'none' ); ?>>
                <?php esc_html_e( 'Keins (nur Honeypot + Rate Limiting)', 'recruiting-playbook' ); ?>
            </option>
            <option value="turnstile" <?php selected( $provider, 'turnstile' ); ?>>
                <?php esc_html_e( 'Cloudflare Turnstile (empfohlen)', 'recruiting-playbook' ); ?>
            </option>
            <option value="hcaptcha" <?php selected( $provider, 'hcaptcha' ); ?>>
                <?php esc_html_e( 'hCaptcha', 'recruiting-playbook' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Turnstile und hCaptcha sind DSGVO-freundlicher als Google reCAPTCHA.', 'recruiting-playbook' ); ?>
        </p>
        <?php
    }
    
    public function render_turnstile_keys(): void {
        $settings = get_option( 'rp_settings' );
        $site_key = $settings['spam']['turnstile_site_key'] ?? '';
        $secret_key = $settings['spam']['turnstile_secret_key'] ?? '';
        ?>
        <p>
            <label><?php esc_html_e( 'Site Key', 'recruiting-playbook' ); ?></label><br>
            <input type="text" name="rp_settings[spam][turnstile_site_key]" 
                   value="<?php echo esc_attr( $site_key ); ?>" class="regular-text">
        </p>
        <p>
            <label><?php esc_html_e( 'Secret Key', 'recruiting-playbook' ); ?></label><br>
            <input type="password" name="rp_settings[spam][turnstile_secret_key]" 
                   value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text">
        </p>
        <p class="description">
            <?php printf(
                esc_html__( 'Keys erhältst du im %s.', 'recruiting-playbook' ),
                '<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">Cloudflare Dashboard</a>'
            ); ?>
        </p>
        <?php
    }
    
    public function render_hcaptcha_keys(): void {
        $settings = get_option( 'rp_settings' );
        $site_key = $settings['spam']['hcaptcha_site_key'] ?? '';
        $secret_key = $settings['spam']['hcaptcha_secret_key'] ?? '';
        ?>
        <p>
            <label><?php esc_html_e( 'Site Key', 'recruiting-playbook' ); ?></label><br>
            <input type="text" name="rp_settings[spam][hcaptcha_site_key]" 
                   value="<?php echo esc_attr( $site_key ); ?>" class="regular-text">
        </p>
        <p>
            <label><?php esc_html_e( 'Secret Key', 'recruiting-playbook' ); ?></label><br>
            <input type="password" name="rp_settings[spam][hcaptcha_secret_key]" 
                   value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text">
        </p>
        <p class="description">
            <?php printf(
                esc_html__( 'Keys erhältst du auf %s.', 'recruiting-playbook' ),
                '<a href="https://www.hcaptcha.com/" target="_blank">hcaptcha.com</a>'
            ); ?>
        </p>
        <?php
    }
}
```

### Turnstile Integration

```php
<?php
// src/Frontend/Forms/Captcha/Turnstile.php

namespace RecruitingPlaybook\Frontend\Forms\Captcha;

class Turnstile implements CaptchaInterface {
    
    private string $site_key;
    private string $secret_key;
    
    public function __construct( string $site_key, string $secret_key ) {
        $this->site_key = $site_key;
        $this->secret_key = $secret_key;
    }
    
    /**
     * Ist konfiguriert?
     */
    public function is_enabled(): bool {
        return ! empty( $this->site_key ) && ! empty( $this->secret_key );
    }
    
    /**
     * Scripts einbinden
     */
    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            [],
            null,
            true
        );
    }
    
    /**
     * Widget rendern
     */
    public function render(): string {
        if ( ! $this->is_enabled() ) {
            return '';
        }
        
        return sprintf(
            '<div class="cf-turnstile" data-sitekey="%s" data-callback="rpTurnstileCallback" data-theme="auto"></div>
            <input type="hidden" name="cf-turnstile-response" id="rp-turnstile-response">
            <script>
                function rpTurnstileCallback(token) {
                    document.getElementById("rp-turnstile-response").value = token;
                }
            </script>',
            esc_attr( $this->site_key )
        );
    }
    
    /**
     * Response validieren
     */
    public function validate( string $token ): bool {
        if ( empty( $token ) ) {
            return false;
        }
        
        $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret'   => $this->secret_key,
                'response' => $token,
                'remoteip' => $this->get_client_ip(),
            ],
        ] );
        
        if ( is_wp_error( $response ) ) {
            // Bei Fehler: Durchlassen (fail open) oder blockieren (fail closed)?
            // Empfehlung: fail open, damit echte User nicht blockiert werden
            return true;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        return $body['success'] ?? false;
    }
    
    private function get_client_ip(): string {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '';
    }
}
```

### hCaptcha Integration

```php
<?php
// src/Frontend/Forms/Captcha/HCaptcha.php

namespace RecruitingPlaybook\Frontend\Forms\Captcha;

class HCaptcha implements CaptchaInterface {
    
    private string $site_key;
    private string $secret_key;
    
    public function __construct( string $site_key, string $secret_key ) {
        $this->site_key = $site_key;
        $this->secret_key = $secret_key;
    }
    
    public function is_enabled(): bool {
        return ! empty( $this->site_key ) && ! empty( $this->secret_key );
    }
    
    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'hcaptcha',
            'https://js.hcaptcha.com/1/api.js',
            [],
            null,
            true
        );
    }
    
    public function render(): string {
        if ( ! $this->is_enabled() ) {
            return '';
        }
        
        return sprintf(
            '<div class="h-captcha" data-sitekey="%s" data-callback="rpHcaptchaCallback" data-theme="light"></div>
            <input type="hidden" name="h-captcha-response" id="rp-hcaptcha-response">
            <script>
                function rpHcaptchaCallback(token) {
                    document.getElementById("rp-hcaptcha-response").value = token;
                }
            </script>',
            esc_attr( $this->site_key )
        );
    }
    
    public function validate( string $token ): bool {
        if ( empty( $token ) ) {
            return false;
        }
        
        $response = wp_remote_post( 'https://hcaptcha.com/siteverify', [
            'body' => [
                'secret'   => $this->secret_key,
                'response' => $token,
            ],
        ] );
        
        if ( is_wp_error( $response ) ) {
            return true; // Fail open
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        return $body['success'] ?? false;
    }
}
```

### Captcha Interface

```php
<?php
// src/Frontend/Forms/Captcha/CaptchaInterface.php

namespace RecruitingPlaybook\Frontend\Forms\Captcha;

interface CaptchaInterface {
    public function is_enabled(): bool;
    public function enqueue_scripts(): void;
    public function render(): string;
    public function validate( string $token ): bool;
}
```

### Captcha Factory

```php
<?php
// src/Frontend/Forms/Captcha/CaptchaFactory.php

namespace RecruitingPlaybook\Frontend\Forms\Captcha;

class CaptchaFactory {
    
    public static function create(): ?CaptchaInterface {
        $settings = get_option( 'rp_settings' );
        $provider = $settings['spam']['captcha_provider'] ?? 'none';
        
        switch ( $provider ) {
            case 'turnstile':
                return new Turnstile(
                    $settings['spam']['turnstile_site_key'] ?? '',
                    $settings['spam']['turnstile_secret_key'] ?? ''
                );
                
            case 'hcaptcha':
                return new HCaptcha(
                    $settings['spam']['hcaptcha_site_key'] ?? '',
                    $settings['spam']['hcaptcha_secret_key'] ?? ''
                );
                
            default:
                return null;
        }
    }
}
```

---

## 5. Spam-Validator (Alles zusammen)

```php
<?php
// src/Frontend/Forms/SpamValidator.php

namespace RecruitingPlaybook\Frontend\Forms;

use RecruitingPlaybook\Frontend\Forms\Captcha\CaptchaFactory;

class SpamValidator {
    
    /**
     * Bewerbungsdaten validieren
     * 
     * @return true|WP_Error
     */
    public static function validate( array $data ): bool|\WP_Error {
        
        // 1. Rate Limiting
        if ( RateLimiter::is_blocked() ) {
            return new \WP_Error(
                'rate_limited',
                __( 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.', 'recruiting-playbook' ),
                [ 'status' => 429 ]
            );
        }
        
        // 2. Honeypot
        if ( ! SpamProtection::validate_honeypot( $data ) ) {
            // Stille Ablehnung (Bot soll nicht wissen, dass er erkannt wurde)
            return new \WP_Error(
                'spam_detected',
                __( 'Ihre Anfrage konnte nicht verarbeitet werden.', 'recruiting-playbook' ),
                [ 'status' => 400 ]
            );
        }
        
        // 3. Time-Check
        if ( ! SpamProtection::validate_timestamp( $data ) ) {
            return new \WP_Error(
                'invalid_submission',
                __( 'Ihre Sitzung ist abgelaufen. Bitte laden Sie die Seite neu.', 'recruiting-playbook' ),
                [ 'status' => 400 ]
            );
        }
        
        // 4. Captcha (wenn aktiviert)
        $captcha = CaptchaFactory::create();
        
        if ( $captcha && $captcha->is_enabled() ) {
            $token = $data['cf-turnstile-response'] 
                  ?? $data['h-captcha-response'] 
                  ?? '';
            
            if ( ! $captcha->validate( $token ) ) {
                return new \WP_Error(
                    'captcha_failed',
                    __( 'Captcha-Überprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'recruiting-playbook' ),
                    [ 'status' => 400 ]
                );
            }
        }
        
        return true;
    }
    
    /**
     * Nach erfolgreicher Bewerbung aufrufen
     */
    public static function record_success(): void {
        RateLimiter::increment();
    }
}
```

---

## 6. Integration im Bewerbungsformular

### PHP Template

```php
<?php
// templates/partials/application-form.php

$captcha = \RecruitingPlaybook\Frontend\Forms\Captcha\CaptchaFactory::create();

if ( $captcha && $captcha->is_enabled() ) {
    $captcha->enqueue_scripts();
}
?>

<form 
    x-data="applicationForm()" 
    @submit.prevent="submit()"
    class="rp-application-form"
>
    <!-- Spam-Schutz: Honeypot -->
    <?php echo \RecruitingPlaybook\Frontend\Forms\SpamProtection::render_honeypot(); ?>
    
    <!-- Spam-Schutz: Timestamp -->
    <?php echo \RecruitingPlaybook\Frontend\Forms\SpamProtection::render_timestamp(); ?>
    
    <!-- Formular-Felder -->
    <div class="rp-form-field">
        <label for="rp-first-name">
            <?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> *
        </label>
        <input type="text" name="first_name" id="rp-first-name" x-model="formData.first_name" required>
    </div>
    
    <!-- ... weitere Felder ... -->
    
    <!-- Captcha (wenn aktiviert) -->
    <?php if ( $captcha && $captcha->is_enabled() ) : ?>
        <div class="rp-form-field rp-captcha-field">
            <?php echo $captcha->render(); ?>
        </div>
    <?php endif; ?>
    
    <!-- DSGVO -->
    <div class="rp-form-field">
        <label>
            <input type="checkbox" name="consent_privacy" x-model="formData.consent_privacy" required>
            <?php printf(
                esc_html__( 'Ich habe die %s gelesen und stimme zu.', 'recruiting-playbook' ),
                '<a href="' . esc_url( get_privacy_policy_url() ) . '" target="_blank">' . 
                esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' ) . '</a>'
            ); ?>
        </label>
    </div>
    
    <!-- Submit -->
    <button type="submit" :disabled="loading">
        <span x-show="!loading"><?php esc_html_e( 'Bewerbung absenden', 'recruiting-playbook' ); ?></span>
        <span x-show="loading"><?php esc_html_e( 'Wird gesendet...', 'recruiting-playbook' ); ?></span>
    </button>
</form>
```

### Application Service Integration

```php
<?php
// src/Services/ApplicationService.php

use RecruitingPlaybook\Frontend\Forms\SpamValidator;

class ApplicationService {
    
    public function submit( array $data ): Application {
        
        // 1. Spam-Check
        $spam_check = SpamValidator::validate( $data );
        
        if ( is_wp_error( $spam_check ) ) {
            throw new \Exception( $spam_check->get_error_message() );
        }
        
        // 2. Normale Validierung
        $this->validate( $data );
        
        // 3. Bewerbung speichern
        // ... (wie gehabt)
        
        // 4. Rate Limiter aktualisieren
        SpamValidator::record_success();
        
        // 5. E-Mails senden, Webhooks, etc.
        // ...
        
        return $application;
    }
}
```

---

## 7. DSGVO-Hinweise

### Datenschutzerklärung ergänzen

Das Plugin sollte einen Text-Snippet für die Datenschutzerklärung bereitstellen:

```php
<?php
// src/Privacy/PrivacyPolicy.php

class PrivacyPolicy {
    
    public static function get_policy_text(): string {
        $settings = get_option( 'rp_settings' );
        $provider = $settings['spam']['captcha_provider'] ?? 'none';
        
        $text = __( '
## Bewerbungsformular

Wenn Sie sich über unser Bewerbungsformular bewerben, erheben wir folgende Daten:
- Name, E-Mail, Telefon
- Hochgeladene Dokumente (Lebenslauf, Zeugnisse)
- IP-Adresse (anonymisiert für Spam-Schutz)

### Spam-Schutz

Zum Schutz vor automatisierten Anfragen verwenden wir:
- Technische Prüfungen (Honeypot, Zeitstempel)
- Rate Limiting (max. Anfragen pro Zeitraum)
', 'recruiting-playbook' );
        
        if ( $provider === 'turnstile' ) {
            $text .= __( '
- Cloudflare Turnstile zur Bot-Erkennung

Cloudflare Turnstile ist ein Dienst der Cloudflare, Inc. Dabei werden Daten wie IP-Adresse und Browser-Informationen an Cloudflare übermittelt. Weitere Informationen finden Sie in der [Datenschutzerklärung von Cloudflare](https://www.cloudflare.com/privacypolicy/).
', 'recruiting-playbook' );
        }
        
        if ( $provider === 'hcaptcha' ) {
            $text .= __( '
- hCaptcha zur Bot-Erkennung

hCaptcha ist ein Dienst der Intuition Machines, Inc. Dabei werden Daten wie IP-Adresse und Browser-Informationen an hCaptcha übermittelt. Weitere Informationen finden Sie in der [Datenschutzerklärung von hCaptcha](https://www.hcaptcha.com/privacy).
', 'recruiting-playbook' );
        }
        
        return $text;
    }
}

// WordPress Privacy Policy Hook
add_action( 'admin_init', function() {
    wp_add_privacy_policy_content(
        'Recruiting Playbook',
        \RecruitingPlaybook\Privacy\PrivacyPolicy::get_policy_text()
    );
} );
```

---

## 8. Admin-Dashboard: Spam-Statistiken

```php
<?php
// src/Admin/Widgets/SpamStatsWidget.php

class SpamStatsWidget {
    
    public function render(): void {
        $stats = $this->get_stats();
        ?>
        <div class="rp-widget rp-spam-stats">
            <h3><?php esc_html_e( 'Spam-Schutz (letzte 7 Tage)', 'recruiting-playbook' ); ?></h3>
            
            <div class="rp-stats-grid">
                <div class="rp-stat">
                    <span class="rp-stat-value"><?php echo esc_html( $stats['blocked_honeypot'] ); ?></span>
                    <span class="rp-stat-label"><?php esc_html_e( 'Honeypot blockiert', 'recruiting-playbook' ); ?></span>
                </div>
                
                <div class="rp-stat">
                    <span class="rp-stat-value"><?php echo esc_html( $stats['blocked_rate_limit'] ); ?></span>
                    <span class="rp-stat-label"><?php esc_html_e( 'Rate Limit blockiert', 'recruiting-playbook' ); ?></span>
                </div>
                
                <div class="rp-stat">
                    <span class="rp-stat-value"><?php echo esc_html( $stats['blocked_captcha'] ); ?></span>
                    <span class="rp-stat-label"><?php esc_html_e( 'Captcha blockiert', 'recruiting-playbook' ); ?></span>
                </div>
                
                <div class="rp-stat rp-stat--success">
                    <span class="rp-stat-value"><?php echo esc_html( $stats['successful'] ); ?></span>
                    <span class="rp-stat-label"><?php esc_html_e( 'Erfolgreiche Bewerbungen', 'recruiting-playbook' ); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
}
```

---

## Zusammenfassung

| Schicht | Methode | Effektivität | Aufwand |
|---------|---------|--------------|---------|
| 1 | Honeypot | ~70% der Bots | Minimal |
| 2 | Time-Check | ~20% zusätzlich | Minimal |
| 3 | Rate Limiting | Brute-Force-Schutz | Gering |
| 4 | Turnstile/hCaptcha | ~99% der Bots | Konfiguration |

**Empfehlung für Einsteiger:**
- Honeypot + Time-Check + Rate Limiting reicht für kleine Seiten
- Turnstile aktivieren bei Spam-Problemen (kostenlos, schnell eingerichtet)

---

*Letzte Aktualisierung: Januar 2025*
