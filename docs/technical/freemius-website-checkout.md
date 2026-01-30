# Freemius Website Checkout Integration

> **Status:** Separates Projekt (unabhängig von KI-Matching)
> **Webseite:** https://recruiting-playbook.com (Vercel)

## Übersicht

Diese Dokumentation beschreibt die Integration von Freemius Checkout auf der Marketing-Webseite, damit Kunden direkt auf der Webseite kaufen können.

---

## Aktueller Stand

| Element | Status |
|---------|--------|
| Free Download | ✅ `/recruiting-playbook.zip` |
| "Pro kaufen" Button | ❌ Platzhalter (`#`) |
| "KI-Addon aktivieren" Button | ❌ Platzhalter (`#`) |

---

## Checkout-Optionen

### Option A: Freemius Checkout Links (Empfohlen)

Direkte Links zum Freemius Checkout-Dialog:

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{PLAN_ID}/
```

**Vorteile:**
- Einfache Integration (nur Links ändern)
- Freemius übernimmt kompletten Checkout
- Automatische Lizenz-Zustellung per E-Mail
- Unterstützt alle Zahlungsmethoden (Kreditkarte, PayPal, SEPA)

### Option B: Freemius Buy Button (JavaScript)

Eingebetteter Checkout-Dialog direkt auf der Seite:

```html
<script src="https://checkout.freemius.com/checkout.min.js"></script>
<script>
const handler = FS.Checkout.configure({
    plugin_id: '12345',
    plan_id: '9999',
    public_key: 'pk_xxx',
    image: 'https://recruiting-playbook.com/logo.png'
});

document.getElementById('pro-kaufen').addEventListener('click', (e) => {
    handler.open({
        name: 'Recruiting Playbook Pro',
        licenses: 1,
        billing_cycle: 'lifetime',
        success: (data) => {
            // Weiterleitung nach erfolgreichem Kauf
            window.location.href = '/danke?license=' + data.license_key;
        }
    });
    e.preventDefault();
});
</script>
```

**Vorteile:**
- Checkout öffnet als Modal (Kunde bleibt auf Seite)
- Bessere UX
- Callback nach erfolgreichem Kauf

---

## Checkout-URLs für Recruiting Playbook

Nach Freemius-Setup die Platzhalter mit echten IDs ersetzen:

### Pro Lizenz (Lifetime)

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{PRO_PLAN_ID}/licenses/1/
```

### Pro Agentur-Lizenz (5 Websites)

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{PRO_PLAN_ID}/licenses/5/
```

### AI-Addon (Monatlich)

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{AI_ADDON_PLAN_ID}/billing_cycle/monthly/
```

### AI-Addon (Jährlich)

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{AI_ADDON_PLAN_ID}/billing_cycle/annual/
```

### Bundle (Pro + AI-Addon)

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{BUNDLE_PLAN_ID}/
```

---

## URL-Parameter

| Parameter | Beschreibung | Beispiel |
|-----------|--------------|----------|
| `plugin_id` | Freemius Product ID | `12345` |
| `plan_id` | Plan ID | `9999` |
| `licenses` | Anzahl Lizenzen | `1`, `5`, `unlimited` |
| `billing_cycle` | Abrechnungszyklus | `monthly`, `annual`, `lifetime` |
| `currency` | Währung | `eur`, `usd` |
| `coupon` | Gutscheincode | `LAUNCH20` |
| `trial` | Trial aktivieren | `free`, `paid` |
| `success_url` | Redirect nach Kauf | `https://recruiting-playbook.com/danke` |
| `cancel_url` | Redirect bei Abbruch | `https://recruiting-playbook.com/pricing` |

---

## Integration auf der Webseite

### 1. Einfache Link-Integration

In der Vercel/Next.js Webseite die Buttons aktualisieren:

```tsx
// components/PricingCard.tsx

const FREEMIUS_PRODUCT_ID = '12345';
const PLANS = {
  pro: '1111',
  pro_agency: '2222',
  ai_addon: '3333',
  bundle: '4444',
};

export function PricingCard({ plan }: { plan: 'pro' | 'ai_addon' | 'bundle' }) {
  const getCheckoutUrl = () => {
    const baseUrl = 'https://checkout.freemius.com/mode/dialog/plugin';

    switch (plan) {
      case 'pro':
        return `${baseUrl}/${FREEMIUS_PRODUCT_ID}/plan/${PLANS.pro}/licenses/1/`;
      case 'ai_addon':
        return `${baseUrl}/${FREEMIUS_PRODUCT_ID}/plan/${PLANS.ai_addon}/billing_cycle/annual/`;
      case 'bundle':
        return `${baseUrl}/${FREEMIUS_PRODUCT_ID}/plan/${PLANS.bundle}/`;
    }
  };

  return (
    <a
      href={getCheckoutUrl()}
      target="_blank"
      rel="noopener noreferrer"
      className="btn btn-primary"
    >
      {plan === 'pro' ? 'Pro kaufen' : 'Addon aktivieren'}
    </a>
  );
}
```

### 2. JavaScript SDK Integration (Modal)

```tsx
// components/FreemiusCheckout.tsx
'use client';

import { useEffect, useRef } from 'react';
import Script from 'next/script';

interface CheckoutProps {
  planId: string;
  productName: string;
  billingCycle?: 'monthly' | 'annual' | 'lifetime';
  licenses?: number;
  children: React.ReactNode;
}

export function FreemiusCheckout({
  planId,
  productName,
  billingCycle = 'lifetime',
  licenses = 1,
  children
}: CheckoutProps) {
  const handlerRef = useRef<any>(null);

  useEffect(() => {
    if (typeof window !== 'undefined' && (window as any).FS) {
      handlerRef.current = (window as any).FS.Checkout.configure({
        plugin_id: process.env.NEXT_PUBLIC_FREEMIUS_PRODUCT_ID,
        plan_id: planId,
        public_key: process.env.NEXT_PUBLIC_FREEMIUS_PUBLIC_KEY,
        image: 'https://recruiting-playbook.com/logo.png',
      });
    }
  }, [planId]);

  const handleClick = (e: React.MouseEvent) => {
    e.preventDefault();

    if (handlerRef.current) {
      handlerRef.current.open({
        name: productName,
        licenses,
        billing_cycle: billingCycle,
        success: (data: any) => {
          // Optional: Analytics Event
          window.location.href = `/danke?license=${data.license_key}`;
        },
      });
    }
  };

  return (
    <>
      <Script
        src="https://checkout.freemius.com/checkout.min.js"
        strategy="lazyOnload"
      />
      <button onClick={handleClick}>
        {children}
      </button>
    </>
  );
}
```

### 3. Verwendung in Pricing-Seite

```tsx
// app/pricing/page.tsx

import { FreemiusCheckout } from '@/components/FreemiusCheckout';

export default function PricingPage() {
  return (
    <div className="pricing-grid">
      {/* Free */}
      <div className="pricing-card">
        <h3>Free</h3>
        <p className="price">0 €</p>
        <a href="/recruiting-playbook.zip" className="btn">
          Herunterladen
        </a>
      </div>

      {/* Pro */}
      <div className="pricing-card">
        <h3>Pro</h3>
        <p className="price">149 €</p>
        <FreemiusCheckout
          planId={process.env.NEXT_PUBLIC_FREEMIUS_PRO_PLAN_ID!}
          productName="Recruiting Playbook Pro"
          billingCycle="lifetime"
          licenses={1}
        >
          Pro kaufen
        </FreemiusCheckout>
      </div>

      {/* AI-Addon */}
      <div className="pricing-card">
        <h3>KI-Addon</h3>
        <p className="price">19 €/Monat</p>
        <FreemiusCheckout
          planId={process.env.NEXT_PUBLIC_FREEMIUS_AI_PLAN_ID!}
          productName="KI-Addon"
          billingCycle="monthly"
        >
          Addon aktivieren
        </FreemiusCheckout>
      </div>
    </div>
  );
}
```

---

## Environment Variables (Vercel)

In Vercel Dashboard → Settings → Environment Variables:

```env
NEXT_PUBLIC_FREEMIUS_PRODUCT_ID=12345
NEXT_PUBLIC_FREEMIUS_PUBLIC_KEY=pk_xxx
NEXT_PUBLIC_FREEMIUS_PRO_PLAN_ID=1111
NEXT_PUBLIC_FREEMIUS_AI_PLAN_ID=3333
NEXT_PUBLIC_FREEMIUS_BUNDLE_PLAN_ID=4444
```

---

## Danke-Seite nach Kauf

### app/danke/page.tsx

```tsx
import { Suspense } from 'react';

function DankeContent() {
  const searchParams = useSearchParams();
  const licenseKey = searchParams.get('license');

  return (
    <div className="container">
      <h1>Vielen Dank für Ihren Kauf!</h1>

      {licenseKey && (
        <div className="license-box">
          <p>Ihr Lizenzschlüssel:</p>
          <code>{licenseKey}</code>
        </div>
      )}

      <h2>Nächste Schritte</h2>
      <ol>
        <li>
          <a href="/recruiting-playbook.zip">Plugin herunterladen</a>
          (falls noch nicht geschehen)
        </li>
        <li>Plugin in WordPress installieren</li>
        <li>Im Plugin-Menü "Lizenz aktivieren" klicken</li>
        <li>Lizenzschlüssel eingeben</li>
      </ol>

      <p>
        Sie erhalten außerdem eine E-Mail mit Ihrem Lizenzschlüssel
        und weiteren Informationen.
      </p>
    </div>
  );
}

export default function DankePage() {
  return (
    <Suspense fallback={<div>Laden...</div>}>
      <DankeContent />
    </Suspense>
  );
}
```

---

## Post-Purchase Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      KAUF-FLOW                                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Kunde auf Webseite                                              │
│         │                                                            │
│         ▼                                                            │
│  2. Klickt "Pro kaufen" / "Addon aktivieren"                        │
│         │                                                            │
│         ▼                                                            │
│  3. Freemius Checkout (Modal oder neue Seite)                       │
│         │                                                            │
│         ▼                                                            │
│  4. Zahlung (Kreditkarte / PayPal / SEPA)                           │
│         │                                                            │
│         ▼                                                            │
│  5. Erfolg → Redirect zu /danke?license=xxx                         │
│         │                                                            │
│         ├──────────────────────────────────────┐                    │
│         ▼                                      ▼                    │
│  6a. E-Mail mit Lizenzschlüssel         6b. Danke-Seite zeigt       │
│      (automatisch von Freemius)             Lizenz + Anleitung      │
│         │                                      │                    │
│         └──────────────┬───────────────────────┘                    │
│                        ▼                                            │
│  7. Kunde installiert Plugin in WordPress                           │
│         │                                                            │
│         ▼                                                            │
│  8. Aktiviert Lizenz im WordPress Dashboard                         │
│         │                                                            │
│         ▼                                                            │
│  9. Freemius SDK validiert → Features freigeschaltet                │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Freemius Dashboard Konfiguration

### 1. Checkout-Einstellungen

Im Freemius Dashboard → Checkout:
- **Success URL:** `https://recruiting-playbook.com/danke`
- **Cancel URL:** `https://recruiting-playbook.com/pricing`
- **Logo hochladen** für Checkout-Dialog

### 2. E-Mail Templates

Unter Settings → Emails:
- Purchase Confirmation anpassen
- Lizenzschlüssel prominent anzeigen
- Link zur Dokumentation einfügen

### 3. Webhook (Optional)

Für erweiterte Integrationen (z.B. CRM, Newsletter):
- URL: `https://recruiting-playbook.com/api/webhooks/freemius`
- Events: `license.created`, `subscription.created`

---

## Testing

### Sandbox-Modus

Freemius bietet einen Sandbox-Modus zum Testen:

1. Im Dashboard → Settings → "Enable Sandbox"
2. Test-Kreditkarte: `4242 4242 4242 4242`
3. Ablaufdatum: beliebig in der Zukunft
4. CVC: beliebige 3 Ziffern

### Test-Checkout-URL

```
https://checkout.freemius.com/mode/dialog/plugin/{PRODUCT_ID}/plan/{PLAN_ID}/?sandbox=true
```

---

## Checkliste

- [ ] Freemius Account erstellen
- [ ] Produkt "Recruiting Playbook" anlegen
- [ ] Plans erstellen (Free, Pro, AI-Addon, Bundle)
- [ ] Plan IDs notieren
- [ ] Environment Variables in Vercel setzen
- [ ] Checkout-Komponente implementieren
- [ ] Buttons auf /pricing aktualisieren
- [ ] Buttons auf /ai aktualisieren
- [ ] Danke-Seite erstellen
- [ ] Im Sandbox-Modus testen
- [ ] Live schalten

---

## Referenzen

- [Freemius Checkout Docs](https://freemius.com/help/documentation/selling-with-freemius/freemius-checkout-buy-button/)
- [Freemius JavaScript SDK](https://freemius.com/help/documentation/selling-with-freemius/freemius-checkout-buy-button/#javascript-sdk)
- [Checkout URL Parameters](https://freemius.com/help/documentation/selling-with-freemius/freemius-checkout-buy-button/#checkout-url-parameters)
