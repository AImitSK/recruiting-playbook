# Freemius Checkout Integration Setup

Diese Anleitung erklärt, wie du die Freemius Checkout Integration für die Pricing-Seite konfigurierst.

## 1. Plan IDs aus Freemius Dashboard holen

### Schritt 1: In Freemius Dashboard einloggen
https://dashboard.freemius.com/

### Schritt 2: Product auswählen
- Products → **Recruiting Playbook** (ID: 23533)
- Tab **Plans** öffnen

### Schritt 3: Plan IDs kopieren

Du siehst eine Liste mit deinen Plänen:
- **Pro (1 Website)** → Klick auf "Edit" → URL anschauen: `/products/23533/plans/XXXXX`
- **Agentur (3 Websites)** → Klick auf "Edit" → URL anschauen: `/products/23533/plans/YYYYY`

Die Zahlen `XXXXX` und `YYYYY` sind deine **Plan IDs**.

**Alternative:** Get Checkout Code Button → Plan ID steht im Code

## 2. Environment Variables setzen

### Schritt 1: .env.local erstellen
```bash
cd website
cp .env.local.example .env.local
```

### Schritt 2: Plan IDs eintragen
```env
NEXT_PUBLIC_FREEMIUS_PRO_PLAN_ID=12345
NEXT_PUBLIC_FREEMIUS_AGENCY_PLAN_ID=12346
```

**Wichtig:** Ersetze `12345` und `12346` mit deinen echten Plan IDs!

### Schritt 3: Development Server neu starten
```bash
npm run dev
```

## 3. Testen

### Pricing-Seite öffnen:
http://localhost:3000/pricing

### "Pro kaufen" Button klicken:
- Sollte Freemius Checkout Overlay öffnen
- Zeigt Pro-Plan (149€) mit 1 Website-Lizenz
- EUR als Währung

### "Agentur kaufen" Button klicken:
- Sollte Freemius Checkout Overlay öffnen
- Zeigt Agentur-Plan (249€) mit 3 Website-Lizenzen

### Was passiert wenn Plan IDs fehlen?
- Fallback: Redirect zu Freemius Hosted Checkout URL
- Konsole zeigt Error: "Plan ID not configured"

## 4. Deployment (Vercel)

### Environment Variables in Vercel setzen:

1. Vercel Dashboard öffnen
2. Project **recruiting-playbook** auswählen
3. Settings → Environment Variables
4. Hinzufügen:
   - Key: `NEXT_PUBLIC_FREEMIUS_PRO_PLAN_ID`
   - Value: `12345` (deine echte Pro Plan ID)
   - Environments: Production, Preview, Development
5. Wiederholen für `NEXT_PUBLIC_FREEMIUS_AGENCY_PLAN_ID`
6. Deploy neu starten

## 5. Wie funktioniert es?

### Components:

**`FreemiusCheckout.jsx`**
- Lädt Freemius Checkout SDK (`checkout.min.js`)
- Export: `useFreemiusCheckout()` Hook
- Export: `openFreemiusCheckout()` Funktion

**`PricingCards.jsx`**
- Nutzt `useFreemiusCheckout()` zum SDK-Laden
- Button Click → `openFreemiusCheckout({ planType: 'pro' })`
- Öffnet Overlay-Checkout mit korrektem Plan

### Checkout Flow:

1. User klickt "Pro kaufen"
2. `openFreemiusCheckout()` wird aufgerufen
3. Freemius Overlay öffnet sich
4. User gibt Daten ein & zahlt
5. Success Callback → Redirect zu `/thank-you?purchase=success`
6. Cancel → Overlay schließt sich

## 6. Anpassungen

### Custom Success Page:
```js
openFreemiusCheckout({
  planType: 'pro',
  onSuccess: (data) => {
    window.location.href = '/custom-success-page'
  }
})
```

### Google Analytics Tracking:
```js
// In FreemiusCheckout.jsx, track() Callback:
track: function (event, data) {
  if (window.gtag) {
    gtag('event', event, {
      event_category: 'Freemius Checkout',
      event_label: data.plan_id,
    })
  }
}
```

### Währung ändern:
```js
openFreemiusCheckout({
  planType: 'pro',
  currency: 'usd', // statt 'eur'
})
```

## 7. Troubleshooting

### Checkout öffnet nicht:
- Browser Console öffnen (F12)
- Fehler anschauen
- Prüfen ob `window.FS` vorhanden ist
- SDK-Load Error? → Firewall/Adblocker deaktivieren

### "Plan ID not configured" Error:
- `.env.local` existiert?
- Plan IDs korrekt eingetragen?
- `NEXT_PUBLIC_` Prefix vorhanden?
- Development Server neu gestartet nach .env Änderung?

### Redirect statt Overlay:
- SDK noch nicht geladen → Fallback zu Hosted Checkout
- Normal bei erstem Seitenaufruf wenn SDK langsam lädt

### Wrong Plan shown:
- Plan ID falsch kopiert
- Prüfe in Freemius Dashboard ob Plan ID stimmt

## 8. Links

- [Freemius Checkout Documentation](https://freemius.com/help/documentation/checkout/integration/freemius-checkout-buy-button/)
- [Hosted Checkout Fallback](https://freemius.com/help/documentation/checkout/integration/hosted-checkout/)
- [Freemius API](https://docs.freemius.com/api)
