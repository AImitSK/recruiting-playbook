# E-Mail Signaturen & Firmendaten

## Übersicht

Dieses Konzept trennt **Template-Inhalt** von **Signatur**. Templates enthalten nur den eigentlichen E-Mail-Text. Die Signatur wird separat verwaltet und vor dem Versand angehängt.

### Prinzipien

1. **Templates = reiner Inhalt** – keine Grußformel, keine Signatur
2. **Signaturen = persönlich** – jeder User verwaltet seine eigenen Signaturen
3. **Auswahl vor Versand** – bei manuellen E-Mails wählt der User seine Signatur
4. **Fallback-Kette** – User-Signatur → Auto-generierte Signatur aus Firmendaten

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────┐
│                         E-Mail-Aufbau                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  TEMPLATE-INHALT                                        │   │
│   │  (aus rp_email_templates.body)                          │   │
│   │                                                         │   │
│   │  Sehr geehrte(r) {anrede_formal},                       │   │
│   │                                                         │   │
│   │  vielen Dank für Ihre Bewerbung als {stelle}.           │   │
│   │  Wir haben Ihre Unterlagen erhalten und melden uns      │   │
│   │  in Kürze bei Ihnen.                                    │   │
│   │                                                         │   │
│   └─────────────────────────────────────────────────────────┘   │
│                              +                                  │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  SIGNATUR                                               │   │
│   │  (aus rp_signatures oder Firmen-Default)                │   │
│   │                                                         │   │
│   │  Mit freundlichen Grüßen                                │   │
│   │                                                         │   │
│   │  Maria Schmidt                                          │   │
│   │  HR Manager                                             │   │
│   │  Tel: +49 30 12345-67                                   │   │
│   │  E-Mail: m.schmidt@firma.de                             │   │
│   │                                                         │   │
│   │  ─────────────────────────────────                      │   │
│   │  Muster GmbH | Musterstr. 1, 12345 Berlin               │   │
│   │  www.muster.de                                          │   │
│   │                                                         │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Datenmodell

### 1. Firmendaten (Plugin-Einstellungen)

**Option:** `rp_settings` (flache Struktur)

```php
// Firmendaten (Pflichtfelder)
'company_name'          => 'Muster GmbH',           // Firmenname
'company_email'         => 'jobs@muster.de',        // Allgemeine Kontakt-E-Mail

// Firmendaten (Optionale Felder)
'company_street'        => 'Musterstraße 1',        // Straße + Hausnummer
'company_zip'           => '12345',                 // PLZ
'company_city'          => 'Berlin',                // Stadt
'company_phone'         => '+49 30 12345-0',        // Telefon Zentrale
'company_website'       => 'https://muster.de',     // Website

// Standard-Absender für E-Mails
'sender_name'           => 'HR Team',               // Absendername
'sender_email'          => 'jobs@muster.de',        // Absender E-Mail (From:)

// Pro-Feature: E-Mail-Branding
'hide_email_branding'   => false,                   // Copyright-Zeile in E-Mails verstecken
```

> **Hinweis:** Die Firmendaten werden direkt auf Root-Level in `rp_settings` gespeichert (flache Struktur), nicht verschachtelt unter `company`.

### 2. Signaturen-Tabelle

**Tabelle:** `{prefix}rp_signatures`

```sql
CREATE TABLE {prefix}rp_signatures (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id         bigint(20) unsigned NULL,          -- NULL = Firmen-Signatur, sonst User-ID
    name            varchar(100) NOT NULL,             -- z.B. "Meine Signatur", "Formal"
    greeting        varchar(255) DEFAULT NULL,         -- Grußformel (optional)
    content         text NOT NULL,                     -- Signatur-Inhalt (HTML)
    include_company tinyint(1) DEFAULT 1,              -- Firmen-Kontaktblock anhängen?
    is_default      tinyint(1) DEFAULT 0,              -- Default für diesen User/Firma?
    created_at      datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at      datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY is_default (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

> **Signatur-Typen und Fallback-Kette:**
>
> | Typ | `user_id` | Beschreibung | Verwaltung |
> |-----|-----------|--------------|------------|
> | **User-Signatur** | `= User-ID` | Persönliche Signatur | Jeder User für sich |
> | **Firmen-Signatur** | `= NULL` | Optionale Firmen-Signatur in DB | Nur Admins |
> | **Auto-generiert** | - | Fallback aus Plugin-Einstellungen | Automatisch |
>
> **Fallback-Kette beim E-Mail-Versand:**
> 1. Explizit gewählte Signatur (per ID)
> 2. User-Default-Signatur (falls vorhanden)
> 3. Firmen-Signatur aus DB (falls vorhanden, `user_id = NULL`)
> 4. Auto-generierte Minimal-Signatur aus Firmendaten (`rp_settings`)

### 3. User-Einstellung: Standard-Signatur

**User Meta:** `rp_default_signature_id`

```php
// Speichern
update_user_meta( $user_id, 'rp_default_signature_id', $signature_id );

// Abrufen
$default_sig_id = get_user_meta( $user_id, 'rp_default_signature_id', true );
```

---

## Signatur-Typen

### Typ 1: Persönliche Signatur (User-spezifisch)

Jeder User kann eigene Signaturen erstellen und verwalten.

```
Mit freundlichen Grüßen

Maria Schmidt
HR Manager
Tel: +49 30 12345-67
E-Mail: m.schmidt@firma.de
```

**Datenbank-Eintrag:**
```php
[
    'user_id'         => 5,                    // User ID (Pflichtfeld)
    'name'            => 'Meine Standard-Signatur',
    'content'         => "Mit freundlichen Grüßen\n\nMaria Schmidt\nHR Manager\nTel: +49 30 12345-67\nE-Mail: m.schmidt@firma.de",
    'is_default'      => 1,
]
```

### Typ 2: Auto-generierte Signatur (Fallback)

Wenn keine Signatur existiert oder für automatische E-Mails, wird automatisch eine professionelle Signatur aus den Firmendaten generiert:

```
Mit freundlichen Grüßen

Ihr Muster GmbH Team

──────────────────────────
Muster GmbH
Musterstr. 1, 12345 Berlin
+49 30 12345-0 · jobs@muster.de · www.muster.de
```

Diese Signatur wird **nicht** in der Datenbank gespeichert, sondern dynamisch aus den Einstellungen (`rp_settings['company']`) generiert. Dies vereinfacht die Verwaltung und stellt sicher, dass die Firmendaten immer aktuell sind.

---

## Menüstruktur

Die neuen Funktionen werden in bestehende Seiten als Tabs integriert:

```
Recruiting
├── E-Mail-Templates
│   ├── [Tab] Vorlagen        ← bestehend
│   ├── [Tab] Signaturen      ← NEU: persönliche Signaturen
│   └── [Tab] Automatisierung ← NEU: automatische E-Mails
│
└── Einstellungen
    ├── [Tab] Allgemein       ← bestehend
    ├── [Tab] Firmendaten     ← NEU: Adresse, Kontakt, Standard-Absender
    └── [Tab] Design          ← bestehend (Branding)
```

> **Hinweis:** Firmendaten werden unter Einstellungen gepflegt und dienen als Fallback für die automatisch generierte Signatur.

---

## UI: Firmendaten (Tab unter Einstellungen)

**Menüpfad:** Recruiting → Einstellungen → Tab: Firmendaten

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Einstellungen                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│  [Allgemein]  [Firmendaten]  [Design]                                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                          [Speichern]    │
│                                                                          │
│  ┌─ FIRMENINFORMATIONEN ────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Firmenname *                                                        ││
│  │  ┌────────────────────────────────────────────────────────────────┐ ││
│  │  │ Muster GmbH                                                    │ ││
│  │  └────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  │  ┌─ Adresse ──────────────────────────────────────────────────────┐ ││
│  │  │  Straße + Nr.        PLZ          Stadt                        │ ││
│  │  │  ┌─────────────────┐ ┌─────────┐ ┌───────────────────────────┐│ ││
│  │  │  │ Musterstraße 1  │ │ 12345   │ │ Berlin                    ││ ││
│  │  │  └─────────────────┘ └─────────┘ └───────────────────────────┘│ ││
│  │  └────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  │  ┌─────────────────────────────┐  ┌────────────────────────────────┐││
│  │  │  Telefon (Zentrale)         │  │  Website                       │││
│  │  │  ┌───────────────────────┐  │  │  ┌────────────────────────┐   │││
│  │  │  │ +49 30 12345-0        │  │  │  │ https://muster.de      │   │││
│  │  │  └───────────────────────┘  │  │  └────────────────────────┘   │││
│  │  └─────────────────────────────┘  └────────────────────────────────┘││
│  │                                                                      ││
│  │  Kontakt E-Mail (für Bewerber) *                                     ││
│  │  ┌────────────────────────────────────────────────────────────────┐ ││
│  │  │ jobs@muster.de                                                 │ ││
│  │  └────────────────────────────────────────────────────────────────┘ ││
│  │  ℹ️ Diese E-Mail wird als Antwort-Adresse verwendet                   ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ STANDARD-ABSENDER ──────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Wird verwendet für:                                                 ││
│  │  • Automatische E-Mails (Eingangsbestätigung, Absagen)               ││
│  │  • Fallback wenn User keine Signatur hat                             ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ ABSENDER-DETAILS ───────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Absendername                        Absender E-Mail                 ││
│  │  ┌────────────────────────────┐     ┌────────────────────────────┐  ││
│  │  │ HR Team Muster GmbH        │     │ jobs@muster.de             │  ││
│  │  └────────────────────────────┘     └────────────────────────────┘  ││
│  │  ℹ️ Wird für automatische E-Mails und als Fallback verwendet          ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ℹ️ Die Firmen-Signatur wird unter E-Mail-Templates → Signaturen         │
│    verwaltet.                                                            │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## UI: Signaturen (Tab unter E-Mail-Templates)

**Menüpfad:** Recruiting → E-Mail-Templates → Tab: Signaturen

```
┌─────────────────────────────────────────────────────────────────────────┐
│  E-Mail-Templates                                                        │
├─────────────────────────────────────────────────────────────────────────┤
│  [Vorlagen]  [Signaturen]  [Automatisierung]                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Meine Signaturen                                   [+ Neue Signatur]   │
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────────┐
│  │  Name         │ Vorschau                      │ Status    │ Aktionen ││
│  ├───────────────┼───────────────────────────────┼───────────┼──────────┤│
│  │  Standard     │ Mit freundlichen Grüßen,      │ ★ Standard│ ✏️ 🗑️   ││
│  │               │ Maria Schmidt, HR Manager...  │           │          ││
│  ├───────────────┼───────────────────────────────┼───────────┼──────────┤│
│  │  Kurz & knapp │ Beste Grüße, Maria Schmidt    │           │ ☆ ✏️ 🗑️ ││
│  ├───────────────┼───────────────────────────────┼───────────┼──────────┤│
│  │  Englisch     │ Best regards, Maria Schmidt...│           │ ☆ ✏️ 🗑️ ││
│  └───────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ Hinweis ────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  ℹ️ Wenn keine Signatur ausgewählt ist, wird automatisch eine        ││
│  │    Signatur aus den Firmendaten generiert.                           ││
│  │                                                                      ││
│  │    Firmendaten können unter Einstellungen → Firmendaten gepflegt     ││
│  │    werden.                                                           ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Signatur bearbeiten (Modal/Drawer)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Signatur bearbeiten                               [Abbrechen] [Speichern]│
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Name                                                                    │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ Standard-Signatur                                                  │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ☑ Als Standard-Signatur verwenden                                       │
│                                                                          │
│  Signatur-Inhalt                                                         │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ Mit freundlichen Grüßen                                            │ │
│  │                                                                    │ │
│  │ Maria Schmidt                                                      │ │
│  │ HR Manager                                                         │ │
│  │ Tel: +49 30 12345-67                                               │ │
│  │ E-Mail: m.schmidt@firma.de                                         │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│  ℹ️ Gestalten Sie Ihre E-Mail-Signatur mit Ihren Kontaktdaten.          │
│                                                                          │
│  [Bearbeiten]  [Vorschau]                                               │
│                                                                          │
│  Vorschau                                                                │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ So wird Ihre Signatur in E-Mails aussehen:                         │ │
│  │ ──────────────────────────────────────────────                     │ │
│  │ Mit freundlichen Grüßen                                            │ │
│  │                                                                    │ │
│  │ Maria Schmidt                                                      │ │
│  │ HR Manager                                                         │ │
│  │ Tel: +49 30 12345-67                                               │ │
│  │ E-Mail: m.schmidt@firma.de                                         │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## UI: Signatur-Auswahl beim E-Mail-Versand

Im E-Mail-Composer erscheint eine Signatur-Auswahl:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  E-Mail verfassen                                                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  An: max.mustermann@beispiel.de                                          │
│                                                                          │
│  Vorlage: [Eingangsbestätigung           ▼]                              │
│                                                                          │
│  Betreff                                                                 │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ Ihre Bewerbung als Senior PHP Developer                            │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ Sehr geehrter Herr Mustermann,                                     │ │
│  │                                                                    │ │
│  │ vielen Dank für Ihre Bewerbung als Senior PHP Developer.           │ │
│  │ Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig     │ │
│  │ prüfen.                                                            │ │
│  │                                                                    │ │
│  │ Sie erhalten in Kürze eine Rückmeldung von uns.                    │ │
│  │                                                                    │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌─ Signatur ───────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  [● Meine Standard-Signatur     ▼]                                   ││
│  │                                                                      ││
│  │    ○ Meine Standard-Signatur  ← aktuelle Auswahl                     ││
│  │    ○ Kurz & knapp                                                    ││
│  │    ○ Englisch                                                        ││
│  │    ─────────────────────                                             ││
│  │    ○ Keine Signatur                                                  ││
│  │                                                                      ││
│  │  Vorschau:                                                           ││
│  │  ┌────────────────────────────────────────────────────────────────┐ ││
│  │  │ Mit freundlichen Grüßen                                        │ ││
│  │  │                                                                │ ││
│  │  │ Maria Schmidt                                                  │ ││
│  │  │ HR Manager                                                     │ ││
│  │  │ Tel: +49 30 12345-67                                           │ ││
│  │  └────────────────────────────────────────────────────────────────┘ ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│                                              [Abbrechen] [📧 Senden]    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Signatur-Rendering

### SignatureService

```php
<?php
namespace RecruitingPlaybook\Services;

class SignatureService {

    /**
     * Rendert eine vollständige Signatur
     */
    public function render( int $signature_id ): string {
        $signature = $this->repository->find( $signature_id );

        if ( ! $signature ) {
            return $this->renderMinimalSignature();
        }

        $html = '<div class="rp-signature">';

        // Grußformel
        if ( ! empty( $signature['greeting'] ) ) {
            $html .= '<p class="rp-signature__greeting">'
                   . esc_html( $signature['greeting'] )
                   . '</p>';
        }

        // Signatur-Inhalt
        $html .= '<div class="rp-signature__content">'
               . nl2br( esc_html( $signature['content'] ) )
               . '</div>';

        // Firmendaten anhängen?
        if ( $signature['include_company'] ) {
            $html .= $this->renderCompanyBlock();
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Rendert den Firmen-Block
     */
    public function renderCompanyBlock(): string {
        $company = $this->getCompanyData();

        $parts = [];

        // Firmenname
        $parts[] = esc_html( $company['name'] );

        // Adresse
        $address = [];
        if ( ! empty( $company['street'] ) ) {
            $address[] = $company['street'];
        }
        if ( ! empty( $company['zip'] ) && ! empty( $company['city'] ) ) {
            $address[] = $company['zip'] . ' ' . $company['city'];
        }
        if ( ! empty( $address ) ) {
            $parts[] = implode( ', ', $address );
        }

        $html = '<div class="rp-signature__company">';
        $html .= '<hr class="rp-signature__divider">';
        $html .= '<p>' . implode( ' | ', $parts ) . '</p>';

        // Kontaktdaten
        $contact = [];
        if ( ! empty( $company['phone'] ) ) {
            $contact[] = 'Tel: ' . esc_html( $company['phone'] );
        }
        if ( ! empty( $company['email'] ) ) {
            $contact[] = esc_html( $company['email'] );
        }
        if ( ! empty( $contact ) ) {
            $html .= '<p>' . implode( ' | ', $contact ) . '</p>';
        }

        // Website
        if ( ! empty( $company['website'] ) ) {
            $html .= '<p><a href="' . esc_url( $company['website'] ) . '">'
                   . esc_html( preg_replace( '#^https?://#', '', $company['website'] ) )
                   . '</a></p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Minimale Signatur als Fallback
     */
    public function renderMinimalSignature(): string {
        $company = $this->getCompanyData();

        $html = '<div class="rp-signature rp-signature--minimal">';
        $html .= '<p>Mit freundlichen Grüßen</p>';
        $html .= '<p><strong>' . esc_html( $company['name'] ) . '</strong></p>';

        if ( ! empty( $company['email'] ) ) {
            $html .= '<p>' . esc_html( $company['email'] ) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Holt die Standard-Signatur für einen User
     */
    public function getDefaultForUser( int $user_id ): ?array {
        // 1. User-spezifische Default-Signatur aus User-Meta
        $signature_id = get_user_meta( $user_id, 'rp_default_signature_id', true );
        if ( $signature_id ) {
            $signature = $this->repository->find( $signature_id );
            if ( $signature && $signature['user_id'] === $user_id ) {
                return $signature;
            }
        }

        // 2. Erste Signatur des Users mit is_default = 1
        return $this->repository->findDefaultForUser( $user_id );

        // Wenn keine Signatur gefunden: renderMinimalSignature() wird verwendet
    }

    /**
     * Alle Signaturen für Dropdown
     */
    public function getOptionsForUser( int $user_id ): array {
        $options = [];

        // User-Signaturen
        $user_signatures = $this->repository->findByUser( $user_id );
        foreach ( $user_signatures as $sig ) {
            $options[] = [
                'id'         => $sig['id'],
                'name'       => $sig['name'],
                'type'       => 'personal',
                'is_default' => (bool) $sig['is_default'],
            ];
        }

        // Option: Keine Signatur
        $options[] = [
            'id'         => 0,
            'name'       => __( 'Keine Signatur', 'recruiting-playbook' ),
            'type'       => 'none',
            'is_default' => false,
        ];

        return $options;
    }
}
```

---

## E-Mail-Zusammenbau

### Geänderter EmailService

```php
<?php
namespace RecruitingPlaybook\Services;

class EmailService {

    public function composeEmail( array $params ): array {
        // Template-Inhalt holen und Platzhalter ersetzen
        $body = $this->placeholderService->replace(
            $params['template_body'],
            $params['context']
        );

        // Signatur anhängen (wenn nicht "keine Signatur" gewählt)
        if ( ! empty( $params['signature_id'] ) ) {
            $signature_html = $this->signatureService->render( $params['signature_id'] );
            $body .= "\n\n" . $signature_html;
        } elseif ( $params['signature_id'] !== 0 ) {
            // Keine explizite Auswahl → Default verwenden
            $default = $this->signatureService->getDefaultForUser( $params['user_id'] );
            if ( $default ) {
                $body .= "\n\n" . $this->signatureService->render( $default['id'] );
            } else {
                // Kein Default → Minimale Signatur
                $body .= "\n\n" . $this->signatureService->renderMinimalSignature();
            }
        }
        // signature_id === 0 → Keine Signatur anhängen

        return [
            'subject'   => $params['subject'],
            'body_html' => $body,
            'body_text' => wp_strip_all_tags( $body ),
        ];
    }
}
```

---

## REST API Endpoints

### Signaturen

```
GET    /recruiting/v1/signatures              # Alle Signaturen des aktuellen Users
POST   /recruiting/v1/signatures              # Neue Signatur erstellen
GET    /recruiting/v1/signatures/{id}         # Einzelne Signatur
PUT    /recruiting/v1/signatures/{id}         # Signatur aktualisieren
DELETE /recruiting/v1/signatures/{id}         # Signatur löschen
POST   /recruiting/v1/signatures/{id}/default # Als Standard setzen
GET    /recruiting/v1/signatures/options      # Signatur-Optionen für Dropdown
POST   /recruiting/v1/signatures/preview      # Signatur-Vorschau rendern
```

### Firmendaten

```
GET    /recruiting/v1/settings/company        # Firmendaten abrufen
POST   /recruiting/v1/settings/company        # Firmendaten speichern
```

> **Hinweis:** Die Firmen-Signatur wird nicht über einen separaten API-Endpoint verwaltet. Stattdessen wird sie automatisch aus den Firmendaten generiert. Die Firmendaten können über `/settings/company` gepflegt werden.

---

## Fallback-Kette

```
┌─────────────────────────────────────────────────────────────────┐
│                     Signatur-Auflösung                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │ User wählt      │
                    │ Signatur aus?   │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
              ▼              ▼              ▼
        ┌─────────┐   ┌───────────┐  ┌──────────┐
        │ Gewählte│   │ "Keine    │  │ Keine    │
        │ Signatur│   │ Signatur" │  │ Auswahl  │
        └────┬────┘   └─────┬─────┘  └────┬─────┘
             │              │              │
             ▼              ▼              ▼
        [Signatur     [Keine         ┌─────────────────┐
         anhängen]    Signatur]      │ User hat        │
                                     │ Default-Sig?    │
                                     └────────┬────────┘
                                              │
                                    ┌─────────┴─────────┐
                                    │                   │
                                    ▼                   ▼
                              ┌──────────┐       ┌────────────────┐
                              │ Ja:      │       │ Nein:          │
                              │ User-Sig │       │ Auto-generiert │
                              │ nutzen   │       │ aus Firmendaten│
                              └──────────┘       └────────────────┘
```

**Vereinfachte Fallback-Kette:**

1. **Explizit gewählte Signatur** → wird verwendet
2. **Keine Auswahl** → User-Default-Signatur
3. **Keine User-Signatur** → Automatisch generiert aus Firmendaten

Die automatisch generierte Signatur enthält:
- "Mit freundlichen Grüßen"
- "Ihr {Firmenname} Team"
- Firmendaten (Adresse, Telefon, E-Mail, Website)

---

## Automatische vs. Manuelle E-Mails

| E-Mail-Typ | Signatur-Quelle |
|------------|-----------------|
| **Automatisch** (Eingangsbestätigung, Absage) | Auto-generiert aus Firmendaten |
| **Manuell** (User schreibt/sendet) | User wählt aus Dropdown |

Bei automatischen E-Mails gibt es keinen "User" im klassischen Sinne → automatisch generierte Signatur aus den Firmendaten-Einstellungen wird verwendet.

---

## Migration bestehender Templates

Bestehende Templates müssen angepasst werden:

### Vorher (mit Signatur im Template)

```html
Sehr geehrte(r) {anrede_formal},

vielen Dank für Ihre Bewerbung...

Mit freundlichen Grüßen
{absender_name}
{firma}
```

### Nachher (nur Inhalt)

```html
Sehr geehrte(r) {anrede_formal},

vielen Dank für Ihre Bewerbung...
```

Die Signatur wird automatisch vom System angehängt.

---

## Bereinigte Platzhalter-Liste

Nach dieser Änderung werden folgende Platzhalter **entfernt**:

| Entfernt | Grund |
|----------|-------|
| `{absender_name}` | Kommt aus Signatur |
| `{absender_email}` | Kommt aus Signatur |
| `{absender_telefon}` | Kommt aus Signatur |
| `{absender_position}` | Kommt aus Signatur |
| `{kontakt_name}` | Kommt aus Firmendaten |
| `{kontakt_telefon}` | Kommt aus Firmendaten |
| `{termin_datum}` | Pseudo-Variable |
| `{termin_uhrzeit}` | Pseudo-Variable |
| `{termin_ort}` | Pseudo-Variable |
| `{termin_teilnehmer}` | Pseudo-Variable |
| `{termin_dauer}` | Pseudo-Variable |
| `{start_datum}` | Pseudo-Variable |
| `{vertragsart}` | Pseudo-Variable |
| `{arbeitszeit}` | Pseudo-Variable |
| `{antwort_frist}` | Pseudo-Variable |

### Verbleibende echte Platzhalter

| Gruppe | Platzhalter |
|--------|-------------|
| **Bewerber** | `{anrede}`, `{anrede_formal}`, `{vorname}`, `{nachname}`, `{name}`, `{email}`, `{telefon}` |
| **Bewerbung** | `{bewerbung_id}`, `{bewerbung_datum}`, `{bewerbung_status}` |
| **Stelle** | `{stelle}`, `{stelle_ort}`, `{stelle_typ}`, `{stelle_url}` |
| **Firma** | `{firma}`, `{firma_website}` |

**Ergebnis: 17 echte Platzhalter statt 33 (7 Kandidat, 3 Bewerbung, 4 Stelle, 3 Firma)**

---

## Neue System-Templates

Die bestehenden Templates müssen neu erstellt werden – **ohne Pseudo-Variablen** und **ohne Signatur im Template**.

### Template-Übersicht

| Template | Kategorie | Automatisierbar | Hinweise |
|----------|-----------|:---------------:|----------|
| Eingangsbestätigung | `confirmation` | ✅ | Trigger: Bewerbung eingegangen |
| Absage | `rejection` | ✅ | Trigger: Status → rejected |
| Zurückgezogen | `withdrawn` | ✅ | Trigger: Status → withdrawn |
| Interview-Einladung | `interview` | ❌ | Manuelle Vorlage mit Lücken |
| Interview-Erinnerung | `interview` | ❌ | Manuelle Vorlage mit Lücken |
| Angebot | `offer` | ❌ | Manuelle Vorlage mit Lücken |
| Zusage/Vertrag | `hired` | ❌ | Manuelle Vorlage mit Lücken |
| Aufnahme in Talent-Pool | `talent-pool` | ✅ | Trigger: In Talent-Pool verschoben |
| Passende Stelle verfügbar | `talent-pool` | ⚠️ | Optional automatisierbar |

### Template-Inhalte (Beispiele)

#### 1. Eingangsbestätigung (automatisierbar)

```html
Sehr geehrte(r) {anrede_formal},

vielen Dank für Ihre Bewerbung als {stelle} bei {firma}.

Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen.
Sie erhalten in Kürze eine Rückmeldung von uns.
```

#### 2. Absage (automatisierbar)

```html
Sehr geehrte(r) {anrede_formal},

vielen Dank für Ihr Interesse an der Position {stelle} bei {firma}
und die Zeit, die Sie in Ihre Bewerbung investiert haben.

Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns
für andere Kandidaten entschieden haben.

Wir wünschen Ihnen für Ihren weiteren beruflichen Weg alles Gute.
```

#### 3. Zurückgezogen (automatisierbar)

```html
Sehr geehrte(r) {anrede_formal},

wir bestätigen, dass Sie Ihre Bewerbung als {stelle} zurückgezogen haben.

Wir bedauern Ihre Entscheidung und wünschen Ihnen für die Zukunft alles Gute.
Sollten Sie zu einem späteren Zeitpunkt Interesse an einer Position bei uns
haben, freuen wir uns über Ihre erneute Bewerbung.
```

#### 4. Interview-Einladung (manuell – mit Lücken)

```html
Sehr geehrte(r) {anrede_formal},

wir freuen uns, Sie zu einem persönlichen Gespräch für die Position
{stelle} einzuladen.

Termin-Details:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Datum:              _______________
Uhrzeit:            _______________
Ort:                _______________
Gesprächspartner:   _______________
Voraussichtliche Dauer: ca. _____ Minuten
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Bitte bringen Sie folgende Unterlagen mit:
• Gültigen Personalausweis
• Aktuelle Zeugnisse (falls noch nicht eingereicht)

Bei Rückfragen oder falls Sie den Termin nicht wahrnehmen können,
melden Sie sich bitte umgehend bei uns.

Wir freuen uns auf das Gespräch mit Ihnen!
```

#### 5. Interview-Erinnerung (manuell – mit Lücken)

```html
Sehr geehrte(r) {anrede_formal},

wir möchten Sie an Ihr bevorstehendes Vorstellungsgespräch erinnern:

Position: {stelle}
Datum:    _______________
Uhrzeit:  _______________
Ort:      _______________

Wir freuen uns auf Sie!
```

#### 6. Angebot (manuell – mit Lücken)

```html
Sehr geehrte(r) {anrede_formal},

wir freuen uns, Ihnen nach den positiven Gesprächen ein Angebot für die
Position {stelle} zu unterbreiten.

Eckdaten des Angebots:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Position:           {stelle}
Eintrittsdatum:     _______________
Vertragsart:        _______________
Arbeitszeit:        _______________
Vergütung:          _______________
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Bitte geben Sie uns bis zum _______________ Bescheid, ob Sie unser
Angebot annehmen möchten.

Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.
```

#### 7. Zusage/Vertrag (manuell – mit Lücken)

```html
Sehr geehrte(r) {anrede_formal},

wir freuen uns sehr, Sie in unserem Team begrüßen zu dürfen!

Anbei erhalten Sie Ihren Arbeitsvertrag für die Position {stelle}.

Wichtige Informationen:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Startdatum:         _______________
Ihr Ansprechpartner am ersten Tag: _______________
Treffpunkt:         _______________
Uhrzeit:            _______________
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Bitte senden Sie uns den unterschriebenen Vertrag bis zum _______________
zurück.

Wir freuen uns auf die Zusammenarbeit!
```

#### 8. Aufnahme in Talent-Pool (automatisierbar)

```html
Sehr geehrte(r) {anrede_formal},

vielen Dank für Ihr Interesse an {firma}.

Auch wenn wir aktuell keine passende Position für Sie haben, hat uns Ihr
Profil überzeugt. Wir haben Sie daher in unseren Talent-Pool aufgenommen.

Sobald eine passende Stelle frei wird, kommen wir gerne auf Sie zu.

Falls Sie dies nicht wünschen oder Ihre Daten aktualisieren möchten,
kontaktieren Sie uns bitte unter {firma_website}.
```

#### 9. Passende Stelle verfügbar (optional automatisierbar)

```html
Sehr geehrte(r) {anrede_formal},

Sie befinden sich in unserem Talent-Pool und wir haben eine
Stelle, die zu Ihrem Profil passen könnte:

{stelle} in {stelle_ort}

Weitere Details finden Sie unter:
{stelle_url}

Falls Sie Interesse haben, freuen wir uns über Ihre Rückmeldung.
```

---

## Automatisierungs-Tab (Umbau)

Der bestehende Automatisierungs-Tab wird vereinfacht und zeigt nur die **tatsächlich automatisierbaren** E-Mails.

**Menüpfad:** Recruiting → E-Mail-Templates → Tab: Automatisierung
*(oder als Bereich innerhalb von Einstellungen)*

### UI-Mockup

```
┌─────────────────────────────────────────────────────────────────────────┐
│  E-Mail-Templates                                                        │
├─────────────────────────────────────────────────────────────────────────┤
│  [Vorlagen]  [Signaturen]  [Automatisierung]                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Automatische E-Mails bei Status-Änderungen                              │
│  ─────────────────────────────────────────────                           │
│  Diese E-Mails werden automatisch versendet, wenn sich der Status        │
│  einer Bewerbung ändert.                                                 │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  ┌──────────────────────────────────────────────────────────────┐   ││
│  │  │  ☑  Eingangsbestätigung                                      │   ││
│  │  │      Wird gesendet wenn: Neue Bewerbung eingeht              │   ││
│  │  │      Template: [Eingangsbestätigung        ▼] [Vorschau]     │   ││
│  │  └──────────────────────────────────────────────────────────────┘   ││
│  │                                                                      ││
│  │  ┌──────────────────────────────────────────────────────────────┐   ││
│  │  │  ☑  Absage                                                   │   ││
│  │  │      Wird gesendet wenn: Status → Abgelehnt                  │   ││
│  │  │      Template: [Absage                     ▼] [Vorschau]     │   ││
│  │  └──────────────────────────────────────────────────────────────┘   ││
│  │                                                                      ││
│  │  ┌──────────────────────────────────────────────────────────────┐   ││
│  │  │  ☐  Zurückgezogen (Bestätigung)                              │   ││
│  │  │      Wird gesendet wenn: Status → Zurückgezogen              │   ││
│  │  │      Template: [Zurückgezogen              ▼] [Vorschau]     │   ││
│  │  └──────────────────────────────────────────────────────────────┘   ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  Automatische E-Mails für Talent-Pool                                    │
│  ─────────────────────────────────────────────                           │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  ┌──────────────────────────────────────────────────────────────┐   ││
│  │  │  ☑  Aufnahme in Talent-Pool                                  │   ││
│  │  │      Wird gesendet wenn: Kandidat in Talent-Pool verschoben  │   ││
│  │  │      Template: [Aufnahme Talent-Pool       ▼] [Vorschau]     │   ││
│  │  └──────────────────────────────────────────────────────────────┘   ││
│  │                                                                      ││
│  │  ┌──────────────────────────────────────────────────────────────┐   ││
│  │  │  ☐  Passende Stelle verfügbar                    [PRO]       │   ││
│  │  │      Wird gesendet wenn: Neue Stelle matcht Talent-Profil    │   ││
│  │  │      Template: [Passende Stelle            ▼] [Vorschau]     │   ││
│  │  │      ⚠️ Erfordert manuelle Prüfung vor Versand                │   ││
│  │  └──────────────────────────────────────────────────────────────┘   ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ℹ️ Alle automatischen E-Mails verwenden die auto-generierte Signatur    │
│    aus den Firmendaten-Einstellungen.                                   │
│                                                                          │
│                                                          [Speichern]    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Datenstruktur für Automatisierung

**Option:** `rp_settings['email_automation']`

```php
'email_automation' => [
    'confirmation' => [
        'enabled'     => true,
        'template_id' => 1,
    ],
    'rejection' => [
        'enabled'     => true,
        'template_id' => 2,
    ],
    'withdrawn' => [
        'enabled'     => false,  // standardmäßig aus
        'template_id' => 3,
    ],
    'talent_pool_added' => [
        'enabled'     => true,
        'template_id' => 8,
    ],
    'talent_pool_match' => [
        'enabled'     => false,
        'template_id' => 9,
        'require_review' => true,  // Manuelle Prüfung vor Versand
    ],
]
```

### Trigger-Logik

| Trigger | Hook | Bedingung |
|---------|------|-----------|
| Eingangsbestätigung | `rp_application_created` | Immer bei neuer Bewerbung |
| Absage | `rp_application_status_changed` | `new_status === 'rejected'` |
| Zurückgezogen | `rp_application_status_changed` | `new_status === 'withdrawn'` |
| Talent-Pool Aufnahme | `rp_candidate_added_to_pool` | Kandidat wird in Pool verschoben |
| Passende Stelle | `rp_job_published` | Matching mit Pool-Profilen |

### Wichtige Hinweise

1. **Keine Interview-/Angebots-Automatisierung** – Diese Templates sind nur Vorlagen für manuelle E-Mails
2. **Auto-generierte Signatur** – Alle automatischen E-Mails verwenden die automatisch generierte Signatur aus den Firmendaten
3. **Template-Auswahl** – Nur Templates der passenden Kategorie werden im Dropdown angezeigt
4. **Deaktivierbar** – Jede Automatisierung kann einzeln an/aus geschaltet werden

---

## Zusammenfassung: Was muss umgebaut werden

### Templates

| Aktion | Details |
|--------|---------|
| Alte Templates entfernen | Alle mit Pseudo-Variablen |
| Neue Templates erstellen | 9 Stück (siehe oben) |
| Signaturen entfernen | Aus allen Template-Inhalten |
| Lücken einfügen | Bei manuellen Templates (`___`) |

### PlaceholderService

| Aktion | Details |
|--------|---------|
| Variablen entfernen | Alle 17 Pseudo- und fraglichen Variablen |
| Gruppen bereinigen | `sender`, `interview`, `contract` Gruppen entfernen |

### UI-Komponenten

| Komponente | Änderung |
|------------|----------|
| E-Mail-Templates Seite | Tab "Signaturen" hinzufügen (nur persönliche Signaturen) |
| E-Mail-Templates Seite | Tab "Automatisierung" hinzufügen/umbauen |
| Einstellungen Seite | Tab "Firmendaten" hinzufügen (für auto-generierte Signatur) |
| E-Mail-Composer | Signatur-Dropdown hinzufügen (persönliche Signaturen) |
| Variablen-Picker | Bereinigte Liste (16 statt 33) |

---

*Letzte Aktualisierung: Januar 2025*
