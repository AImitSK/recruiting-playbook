# Admin-UI Architektur

## Übersicht

Das WordPress Admin-Backend verwendet **shadcn/ui** als Komponenten-Bibliothek.

| Aspekt | Entscheidung |
|--------|--------------|
| Framework | React (@wordpress/scripts) |
| Komponenten | shadcn/ui (MIT-Lizenz) |
| Styling | Tailwind CSS |
| State Management | React Hooks + WordPress API |
| Icons | Lucide React |

---

## Warum shadcn/ui?

### Vorteile

1. **MIT-Lizenz** - Darf in kommerziellen WordPress-Plugins verwendet werden
2. **Copy-Paste Komponenten** - Du besitzt den Code, keine externe Abhängigkeit
3. **Tailwind-basiert** - Konsistent mit unserem Frontend
4. **Barrierefreiheit** - Basiert auf Radix UI (ARIA-konform)
5. **Modernes Design** - Professioneller Look
6. **Anpassbar** - Eigene Themes möglich

### Abgelehnte Alternativen

| Alternative | Grund für Ablehnung |
|-------------|---------------------|
| Tailwind UI | Lizenz verbietet Redistribution in Plugins |
| @wordpress/components | Veraltet, inkonsistentes Design |
| Material UI | Zu schwer, React-spezifisch, Google-Look |
| Chakra UI | Zu viele Dependencies |

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────┐
│                    ADMIN-UI ARCHITEKTUR                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                     PHP ENTRY POINTS                        │ │
│  │          (Admin Pages rendern React-Container)              │ │
│  └──────────────────────────┬─────────────────────────────────┘ │
│                             │                                    │
│                             ▼                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                      REACT APP                              │ │
│  │                                                             │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │ │
│  │  │   Pages     │  │ Components  │  │   Hooks     │        │ │
│  │  │             │  │ (shadcn/ui) │  │             │        │ │
│  │  │ - Kanban    │  │ - Button    │  │ - useApi    │        │ │
│  │  │ - Applicant │  │ - Card      │  │ - useToast  │        │ │
│  │  │ - Email     │  │ - Dialog    │  │ - useTable  │        │ │
│  │  │ - Settings  │  │ - Table     │  │             │        │ │
│  │  │ - License   │  │ - Tabs      │  │             │        │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘        │ │
│  │                                                             │ │
│  └──────────────────────────┬─────────────────────────────────┘ │
│                             │                                    │
│                             ▼                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    REST API                                 │ │
│  │              /wp-json/recruiting/v1/*                       │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Ordnerstruktur

```
plugin/
├── assets/
│   └── src/
│       ├── js/
│       │   └── admin/
│       │       ├── index.js              # Entry Point
│       │       ├── components/
│       │       │   ├── ui/               # shadcn/ui Komponenten
│       │       │   │   ├── button.jsx
│       │       │   │   ├── card.jsx
│       │       │   │   ├── dialog.jsx
│       │       │   │   ├── dropdown-menu.jsx
│       │       │   │   ├── input.jsx
│       │       │   │   ├── label.jsx
│       │       │   │   ├── select.jsx
│       │       │   │   ├── separator.jsx
│       │       │   │   ├── table.jsx
│       │       │   │   ├── tabs.jsx
│       │       │   │   ├── textarea.jsx
│       │       │   │   └── toast.jsx
│       │       │   └── app/              # App-spezifische Komponenten
│       │       │       ├── kanban/
│       │       │       ├── applicant/
│       │       │       ├── email/
│       │       │       └── settings/
│       │       ├── hooks/
│       │       │   ├── use-api.js
│       │       │   ├── use-toast.js
│       │       │   └── use-debounce.js
│       │       ├── lib/
│       │       │   └── utils.js          # cn() Helper
│       │       └── pages/
│       │           ├── KanbanPage.jsx
│       │           ├── ApplicantPage.jsx
│       │           ├── EmailPage.jsx
│       │           └── SettingsPage.jsx
│       └── css/
│           └── admin/
│               └── globals.css           # Tailwind + shadcn/ui Base
```

---

## Setup

### 1. Dependencies

```json
// package.json
{
  "dependencies": {
    "@radix-ui/react-dialog": "^1.0.5",
    "@radix-ui/react-dropdown-menu": "^2.0.6",
    "@radix-ui/react-label": "^2.0.2",
    "@radix-ui/react-select": "^2.0.0",
    "@radix-ui/react-separator": "^1.0.3",
    "@radix-ui/react-slot": "^1.0.2",
    "@radix-ui/react-tabs": "^1.0.4",
    "@radix-ui/react-toast": "^1.1.5",
    "class-variance-authority": "^0.7.0",
    "clsx": "^2.1.0",
    "lucide-react": "^0.300.0",
    "tailwind-merge": "^2.2.0"
  }
}
```

### 2. Tailwind Config

```javascript
// tailwind.config.js
module.exports = {
  darkMode: ["class"],
  content: [
    "./assets/src/js/admin/**/*.{js,jsx}",
  ],
  prefix: "rp-",  // Prefix für WordPress-Kompatibilität
  theme: {
    extend: {
      colors: {
        border: "hsl(var(--rp-border))",
        input: "hsl(var(--rp-input))",
        ring: "hsl(var(--rp-ring))",
        background: "hsl(var(--rp-background))",
        foreground: "hsl(var(--rp-foreground))",
        primary: {
          DEFAULT: "hsl(var(--rp-primary))",
          foreground: "hsl(var(--rp-primary-foreground))",
        },
        secondary: {
          DEFAULT: "hsl(var(--rp-secondary))",
          foreground: "hsl(var(--rp-secondary-foreground))",
        },
        destructive: {
          DEFAULT: "hsl(var(--rp-destructive))",
          foreground: "hsl(var(--rp-destructive-foreground))",
        },
        muted: {
          DEFAULT: "hsl(var(--rp-muted))",
          foreground: "hsl(var(--rp-muted-foreground))",
        },
        accent: {
          DEFAULT: "hsl(var(--rp-accent))",
          foreground: "hsl(var(--rp-accent-foreground))",
        },
        popover: {
          DEFAULT: "hsl(var(--rp-popover))",
          foreground: "hsl(var(--rp-popover-foreground))",
        },
        card: {
          DEFAULT: "hsl(var(--rp-card))",
          foreground: "hsl(var(--rp-card-foreground))",
        },
      },
      borderRadius: {
        lg: "var(--rp-radius)",
        md: "calc(var(--rp-radius) - 2px)",
        sm: "calc(var(--rp-radius) - 4px)",
      },
    },
  },
  plugins: [require("tailwindcss-animate")],
}
```

### 3. CSS Variablen (globals.css)

```css
/* assets/src/css/admin/globals.css */

@tailwind base;
@tailwind components;
@tailwind utilities;

/* shadcn/ui Design Tokens - Scoped auf Admin-Container */
.rp-admin {
  --rp-background: 0 0% 100%;
  --rp-foreground: 222.2 84% 4.9%;
  --rp-card: 0 0% 100%;
  --rp-card-foreground: 222.2 84% 4.9%;
  --rp-popover: 0 0% 100%;
  --rp-popover-foreground: 222.2 84% 4.9%;
  --rp-primary: 221.2 83.2% 53.3%;
  --rp-primary-foreground: 210 40% 98%;
  --rp-secondary: 210 40% 96%;
  --rp-secondary-foreground: 222.2 84% 4.9%;
  --rp-muted: 210 40% 96%;
  --rp-muted-foreground: 215.4 16.3% 46.9%;
  --rp-accent: 210 40% 96%;
  --rp-accent-foreground: 222.2 84% 4.9%;
  --rp-destructive: 0 84.2% 60.2%;
  --rp-destructive-foreground: 210 40% 98%;
  --rp-border: 214.3 31.8% 91.4%;
  --rp-input: 214.3 31.8% 91.4%;
  --rp-ring: 221.2 83.2% 53.3%;
  --rp-radius: 0.5rem;
}

/* Base Styles für Admin */
.rp-admin {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color: hsl(var(--rp-foreground));
}

/* Reset WordPress Admin Styles innerhalb unserer Container */
.rp-admin *,
.rp-admin *::before,
.rp-admin *::after {
  box-sizing: border-box;
}

.rp-admin button {
  font-family: inherit;
}
```

### 4. Utils (cn Helper)

```javascript
// assets/src/js/admin/lib/utils.js

import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}
```

---

## Komponenten-Beispiele

### Button

```jsx
// assets/src/js/admin/components/ui/button.jsx

import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva } from "class-variance-authority";
import { cn } from "../../lib/utils";

const buttonVariants = cva(
  "rp-inline-flex rp-items-center rp-justify-center rp-whitespace-nowrap rp-rounded-md rp-text-sm rp-font-medium rp-ring-offset-background rp-transition-colors focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2 disabled:rp-pointer-events-none disabled:rp-opacity-50",
  {
    variants: {
      variant: {
        default: "rp-bg-primary rp-text-primary-foreground hover:rp-bg-primary/90",
        destructive: "rp-bg-destructive rp-text-destructive-foreground hover:rp-bg-destructive/90",
        outline: "rp-border rp-border-input rp-bg-background hover:rp-bg-accent hover:rp-text-accent-foreground",
        secondary: "rp-bg-secondary rp-text-secondary-foreground hover:rp-bg-secondary/80",
        ghost: "hover:rp-bg-accent hover:rp-text-accent-foreground",
        link: "rp-text-primary rp-underline-offset-4 hover:rp-underline",
      },
      size: {
        default: "rp-h-10 rp-px-4 rp-py-2",
        sm: "rp-h-9 rp-rounded-md rp-px-3",
        lg: "rp-h-11 rp-rounded-md rp-px-8",
        icon: "rp-h-10 rp-w-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);

const Button = React.forwardRef(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : "button";
    return (
      <Comp
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    );
  }
);
Button.displayName = "Button";

export { Button, buttonVariants };
```

### Card

```jsx
// assets/src/js/admin/components/ui/card.jsx

import * as React from "react";
import { cn } from "../../lib/utils";

const Card = React.forwardRef(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn(
      "rp-rounded-lg rp-border rp-bg-card rp-text-card-foreground rp-shadow-sm",
      className
    )}
    {...props}
  />
));
Card.displayName = "Card";

const CardHeader = React.forwardRef(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("rp-flex rp-flex-col rp-space-y-1.5 rp-p-6", className)}
    {...props}
  />
));
CardHeader.displayName = "CardHeader";

const CardTitle = React.forwardRef(({ className, ...props }, ref) => (
  <h3
    ref={ref}
    className={cn(
      "rp-text-2xl rp-font-semibold rp-leading-none rp-tracking-tight",
      className
    )}
    {...props}
  />
));
CardTitle.displayName = "CardTitle";

const CardDescription = React.forwardRef(({ className, ...props }, ref) => (
  <p
    ref={ref}
    className={cn("rp-text-sm rp-text-muted-foreground", className)}
    {...props}
  />
));
CardDescription.displayName = "CardDescription";

const CardContent = React.forwardRef(({ className, ...props }, ref) => (
  <div ref={ref} className={cn("rp-p-6 rp-pt-0", className)} {...props} />
));
CardContent.displayName = "CardContent";

const CardFooter = React.forwardRef(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("rp-flex rp-items-center rp-p-6 rp-pt-0", className)}
    {...props}
  />
));
CardFooter.displayName = "CardFooter";

export { Card, CardHeader, CardFooter, CardTitle, CardDescription, CardContent };
```

---

## Layout-System

### Container-Breiten

| Seite | Container | Breite |
|-------|-----------|--------|
| Einstellungen (Lizenz, E-Mail) | `max-w-3xl` | 768px |
| Formulare (Template Editor) | `max-w-4xl` | 896px |
| Datenseiten (Kanban, Listen) | `max-w-7xl` | 1280px |
| Volle Breite | `w-full` | 100% |

### Beispiel: Page Layout

```jsx
// Einstellungs-Seite (schmal)
function SettingsPage() {
  return (
    <div className="rp-admin">
      <div className="rp-max-w-3xl rp-mx-auto rp-py-6">
        <Card>
          <CardHeader>
            <CardTitle>Einstellungen</CardTitle>
          </CardHeader>
          <CardContent>
            {/* ... */}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

// Daten-Seite (breit)
function KanbanPage() {
  return (
    <div className="rp-admin">
      <div className="rp-max-w-7xl rp-mx-auto rp-py-6">
        {/* Kanban Board */}
      </div>
    </div>
  );
}
```

---

## Migration bestehender Seiten

### Reihenfolge

1. **Lizenz-Seite** - Einfachste Seite, guter Start
2. **E-Mail Templates** - Formulare, Tabellen
3. **Bewerber-Detail** - Tabs, Cards, komplexeres Layout
4. **Kanban-Board** - Drag & Drop, komplexeste Seite
5. **Talent-Pool** - Grid, Cards, Filter

### Checkliste pro Seite

- [ ] Alte CSS-Datei entfernen
- [ ] React-Komponente auf shadcn/ui umstellen
- [ ] Layout mit Tailwind (rp-Prefix)
- [ ] Testen (Funktionalität, Responsive)
- [ ] Alte CSS aus Build entfernen

---

## Server-Side Data Injection

PHP übergibt Konfiguration und Daten an React via `wp_localize_script`. Jede Admin-Seite hat ein eigenes globales Objekt.

### `rpSettingsData` (Settings-Seite)

Verfügbar unter `window.rpSettingsData`. Wird von `Settings::enqueueAssets()` injiziert.

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| `logoUrl` | `string` | URL zum Plugin-Logo |
| `homeUrl` | `string` | WordPress `home_url()` |
| `exportUrl` | `string` | URL für Backup-Export |
| `nonce` | `string` | WP-Nonce für Download-Aktionen |
| `pages` | `array` | Alle publizierten Seiten (`{id, title}`) |
| `isPro` | `bool` | Pro-Lizenz aktiv? |
| `i18n` | `object` | Lokalisierte UI-Strings |
| `recruitingUsers` | `array` | **Pro:** Benutzer mit Recruiting-Rollen (`{id, name, role}`) |
| `jobListings` | `array` | **Pro:** Alle Job-Listings (`{id, title, status}`) |

Die Felder `recruitingUsers` und `jobListings` sind nur verfügbar wenn `isPro === true`. Sie werden für die Rollen- und Stellen-Zuweisungs-UI benötigt.

**Zugriff in React:**

```jsx
// Gesamte Konfiguration
const config = window.rpSettingsData || {};

// Einzelne Werte
const i18n = window.rpSettingsData?.i18n || {};
const isPro = window.rpSettingsData?.isPro || false;
```

### Weitere Data-Objekte

| Objekt | Script | Seite |
|--------|--------|-------|
| `rpKanban` | `rp-kanban` | Kanban-Board |
| `rpEmailData` | `rp-admin-email` | E-Mail-System |
| `rpData` | `rp-frontend` | Frontend (öffentlich) |

---

## Best Practices

### DO

```jsx
// ✅ shadcn/ui Komponenten verwenden
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";

// ✅ Tailwind mit rp-Prefix
<div className="rp-flex rp-gap-4 rp-p-6">

// ✅ cn() für bedingte Klassen
<Button className={cn("rp-w-full", isLoading && "rp-opacity-50")}>
```

### DON'T

```jsx
// ❌ Keine @wordpress/components mehr
import { Button } from "@wordpress/components";

// ❌ Keine Inline-Styles
<div style={{ padding: "24px" }}>

// ❌ Keine eigenen CSS-Dateien pro Seite
import "./my-page.css";

// ❌ Keine Tailwind-Klassen ohne Prefix
<div className="flex gap-4 p-6">
```

---

## Referenzen

- [shadcn/ui Dokumentation](https://ui.shadcn.com/)
- [Radix UI Primitives](https://www.radix-ui.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Lucide Icons](https://lucide.dev/)

---

*Letzte Aktualisierung: Januar 2025*
